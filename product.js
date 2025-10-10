// Global variables
let currentProduct = null;
let allItems = [];

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    loadProduct();
});

// Get product ID from URL parameters
function getProductId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

// Load product details
async function loadProduct() {
    const productId = getProductId();
    
    if (!productId) {
        showError('No product ID specified');
        return;
    }

    try {
        showLoading();
        
        // Load product details
        const response = await fetch(`get_items.php?id=${productId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        currentProduct = data;
        displayProduct(currentProduct);
        
        // Update view count
        await updateViews(productId);
        
        hideLoading();
        
    } catch (error) {
        console.error('Error loading product:', error);
        hideLoading();
        showError('Failed to load product details. Please try again later.');
    }
}

// Display product details
function displayProduct(product) {
    const container = document.getElementById('product-container');
    
    container.innerHTML = `
        <div style="grid-column: 1 / -1; margin-bottom: 20px;">
            <a href="index.html" class="back-btn">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                Back to Products
            </a>
        </div>
        
        <div class="product-image-section">
            <img src="${product.image_url}" alt="${escapeHtml(product.title)}" class="product-image" 
                 onerror="this.src='https://via.placeholder.com/600x400?text=Image+Not+Found'">
        </div>
        
        <div class="product-details">
            <h1 class="product-title">${escapeHtml(product.title)}</h1>
            <span class="product-category">${escapeHtml(product.category)}</span>
            <p class="product-description">${escapeHtml(product.description)}</p>
            
            <div class="product-stats">
                <div class="stat-item">
                    <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="stat-text"><span id="like-count">${product.likes}</span> Likes</span>
                </div>
                <div class="stat-item">
                    <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="stat-text"><span id="view-count">${product.views}</span> Views</span>
                </div>
            </div>
            
            <div class="product-actions">
                <button class="action-btn like-btn" onclick="toggleLike(${product.id})" id="like-btn">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg>
                    Like
                </button>
                <button class="action-btn share-btn" onclick="shareProduct()">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"></path>
                    </svg>
                    Share
                </button>
            </div>
        </div>
    `;
    
    // Update page title
    document.title = `${product.title} - E-veikals`;
    
    // Check if item is already liked
    markLikedItem(product);
}

// Check and mark if item is already liked
function markLikedItem(product) {
    if (product.userLiked) {
        const likeBtn = document.getElementById('like-btn');
        if (likeBtn) {
            likeBtn.classList.add('liked');
        }
    }
}

// Toggle like status
async function toggleLike(productId) {
    try {
        // Check if user is logged in
        const sessionResponse = await fetch('session_status.php');
        const sessionData = await sessionResponse.json();
        
        if (!sessionData.loggedIn) {
            alert('Please log in to like items');
            return;
        }
        
        const likeCount = document.getElementById('like-count');
        const likeBtn = document.getElementById('like-btn');
        const currentCount = parseInt(likeCount.textContent);
        const isCurrentlyLiked = likeBtn.classList.contains('liked');
        
        const action = isCurrentlyLiked ? 'unlike' : 'like';
        
        // Update UI immediately
        if (action === 'like') {
            likeCount.textContent = currentCount + 1;
            likeBtn.classList.add('liked');
        } else {
            likeCount.textContent = currentCount - 1;
            likeBtn.classList.remove('liked');
        }
        
        // Update database
        const response = await fetch('update_likes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: productId, action: action })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update with actual count from database
            likeCount.textContent = data.likes;
            
            // Update visual state based on server response
            if (data.userLiked) {
                likeBtn.classList.add('liked');
            } else {
                likeBtn.classList.remove('liked');
            }
        } else {
            // Revert changes if failed
            if (action === 'like') {
                likeCount.textContent = currentCount;
                likeBtn.classList.remove('liked');
            } else {
                likeCount.textContent = currentCount;
                likeBtn.classList.add('liked');
            }
            
            if (data.error) {
                console.error('Like error:', data.error);
            }
        }
        
        // Add animation
        likeBtn.classList.add('animate');
        setTimeout(() => likeBtn.classList.remove('animate'), 300);
        
    } catch (error) {
        console.error('Error updating likes:', error);
    }
}

// Update view count
async function updateViews(productId) {
    try {
        // Check if user has already viewed this product in this session
        const viewedProducts = JSON.parse(sessionStorage.getItem('viewedProducts') || '[]');
        const productKey = `product_${productId}`;
        
        if (viewedProducts.includes(productKey)) {
            // User has already viewed this product, don't increment
            console.log('Product already viewed in this session');
            return;
        }
        
        // Add this product to viewed list
        viewedProducts.push(productKey);
        sessionStorage.setItem('viewedProducts', JSON.stringify(viewedProducts));
        
        // Update view count in database
        const response = await fetch('update_views.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: productId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update the view count in the UI with the actual count from database
            const viewCount = document.getElementById('view-count');
            if (viewCount) {
                viewCount.textContent = data.views;
            }
        }
        
    } catch (error) {
        console.error('Error updating views:', error);
    }
}

// Share product
function shareProduct() {
    if (navigator.share && currentProduct) {
        navigator.share({
            title: currentProduct.title,
            text: currentProduct.description,
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Product URL copied to clipboard!');
        }).catch(err => {
            console.error('Could not copy text: ', err);
            // Further fallback: show URL in a prompt
            prompt('Copy this URL to share:', window.location.href);
        });
    }
}

// Navigate to another product
function goToProduct(productId) {
    window.location.href = `product.html?id=${productId}`;
}

// Utility functions
function showLoading() {
    document.getElementById('loading').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading').classList.add('hidden');
}

function showError(message) {
    const container = document.getElementById('product-container');
    container.innerHTML = `
        <div class="error-container">
            <h2>Oops!</h2>
            <p>${message}</p>
            <button onclick="loadProduct()" class="retry-btn">Try Again</button>
            <br><br>
            <a href="index.html" class="back-btn">Back to Home</a>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

console.log("Product page loaded successfully!");
