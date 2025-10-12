// Global variables
let allItems = [];
let currentCategory = 'all';

// Initialize the page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadCategories();
    loadItems();
});

// Load categories from database
async function loadCategories() {
    try {
        const response = await fetch('api/get_categories.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.success && data.categories) {
            displayCategories(data.categories);
        } else if (data.error) {
            throw new Error(data.error);
        } else {
            throw new Error('Invalid response format');
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        // Fallback to default categories if loading fails
        const defaultCategories = ['Electronics', 'Accessories'];
        displayCategories(defaultCategories);
    }
}

// Display categories in the navigation
function displayCategories(categories) {
    const categoryContainer = document.getElementById('category-links');
    if (!categoryContainer) return;
    
    categoryContainer.innerHTML = '';
    
    // Add "All" category first
    const allLink = document.createElement('a');
    allLink.href = '#';
    allLink.textContent = 'All';
    allLink.dataset.category = 'all';
    allLink.classList.add('active'); // Make "All" active by default
    categoryContainer.appendChild(allLink);
    
    // Add categories from database
    categories.forEach(category => {
        if (category && category.toLowerCase() !== 'all') { // Avoid duplicate "All"
            const link = document.createElement('a');
            link.href = '#';
            link.textContent = category;
            link.dataset.category = category; // Keep original casing
            categoryContainer.appendChild(link);
        }
    });
}

// Setup event listeners
function setupEventListeners() {
    // Category filter listeners - use event delegation since categories are loaded dynamically
    const categoryContainer = document.getElementById('category-links');
    
    if (categoryContainer) {
        categoryContainer.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if clicked element is a category link
            if (e.target.tagName === 'A' && e.target.dataset.category) {
                // Update active state
                const categoryLinks = categoryContainer.querySelectorAll('a');
                categoryLinks.forEach(l => l.classList.remove('active'));
                e.target.classList.add('active');
                
                // Filter items
                currentCategory = e.target.dataset.category;
                filterItems(currentCategory);
            }
        });
    }
}

// Load items from the database
async function loadItems() {
    try {
        showLoading();
        const response = await fetch('api/get_items.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        allItems = data;
        displayItems(allItems);
        hideLoading();
        
    } catch (error) {
        console.error('Error loading items:', error);
        hideLoading();
        showError('Failed to load items. Please try again later.');
    }
}

// Display items in the grid
function displayItems(items) {
    const grid = document.getElementById('items-grid');
    
    if (items.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #666;">
                <h3>No items found</h3>
                <p>Try selecting a different category or check back later.</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = items.map(item => createItemCard(item)).join('');
    
    // Mark already liked items
    markLikedItems();
    
    // Add click listeners to like buttons
    setupLikeButtons();
}

// Create HTML for a single item card
function createItemCard(item) {
    
    const likedClass = item.userLiked ? 'liked' : '';
    
    return `
        <div class="item-card" onclick="viewItem(${item.id})">
            <img src="${item.image_url}" alt="${item.title}" class="item-image" 
                 onerror="this.src='https://via.placeholder.com/400x200?text=Image+Not+Found'">
            <div class="item-content">
                <h3 class="item-title">${escapeHtml(item.title)}</h3>
                <p class="item-description">${escapeHtml(item.description)}</p>
                <span class="item-category">${escapeHtml(item.category)}</span>
                <div class="item-stats">
                    <div class="stat-item likes ${likedClass}" onclick="event.stopPropagation(); toggleLike(${item.id}, this)">
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="like-count">${item.likes}</span>
                    </div>
                    <div class="stat-item views">
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>${item.views}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Filter items by category
// Filter items by category (server-side)
async function filterItems(category) {
    try {
        showLoading();
        
        // Fetch filtered items from server
        const url = category === 'all' ? 'api/get_items.php' : `api/get_items.php?category=${encodeURIComponent(category)}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        allItems = data; // Update the global array
        displayItems(allItems);
        hideLoading();
        
    } catch (error) {
        console.error('Error filtering items:', error);
        hideLoading();
        // Fallback to client-side filtering if server-side fails
        if (category === 'all') {
            displayItems(allItems);
        } else {
            const filteredItems = allItems.filter(item => {
                return item.category && item.category.toLowerCase() === category.toLowerCase();
            });
            displayItems(filteredItems);
        }
    }
}

// Toggle like status
async function toggleLike(itemId, element) {
    try {
        // Check if user is logged in by checking session
        const sessionResponse = await fetch('api/session_status.php');
        const sessionData = await sessionResponse.json();
        
        if (!sessionData.loggedIn) {
            alert('Please log in to like items');
            return;
        }
        
        const likeCountSpan = element.querySelector('.like-count');
        const currentCount = parseInt(likeCountSpan.textContent);
        const isCurrentlyLiked = element.classList.contains('liked');
        
        const action = isCurrentlyLiked ? 'unlike' : 'like';
        
        // Update UI immediately for better UX
        if (action === 'like') {
            likeCountSpan.textContent = currentCount + 1;
            element.classList.add('liked');
        } else {
            likeCountSpan.textContent = currentCount - 1;
            element.classList.remove('liked');
        }
        
        // Update database
        const response = await fetch('api/update_likes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: itemId, action: action })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update with actual count from database
            likeCountSpan.textContent = data.likes;
            
            // Update visual state based on server response
            if (data.userLiked) {
                element.classList.add('liked');
            } else {
                element.classList.remove('liked');
            }
        } else {
            // Revert UI changes if database update failed
            if (action === 'like') {
                likeCountSpan.textContent = currentCount;
                element.classList.remove('liked');
            } else {
                likeCountSpan.textContent = currentCount;
                element.classList.add('liked');
            }
            
            if (data.error) {
                console.error('Like error:', data.error);
            }
        }
        
    } catch (error) {
        console.error('Error updating likes:', error);
        // Revert UI changes on error
        const likeCountSpan = element.querySelector('.like-count');
        const currentCount = parseInt(likeCountSpan.textContent);
        const isCurrentlyLiked = element.classList.contains('liked');
        
        if (isCurrentlyLiked) {
            likeCountSpan.textContent = currentCount - 1;
            element.classList.remove('liked');
        } else {
            likeCountSpan.textContent = currentCount + 1;
            element.classList.add('liked');
        }
    }
}

// View item details
function viewItem(itemId) {
    // Redirect to product detail page
    window.location.href = `./views/product.html?id=${itemId}`;
}

// Setup like button event listeners
function setupLikeButtons() {
    // Event listeners are handled inline in the HTML for simplicity
    // In a larger app, you might want to use event delegation
}

// Mark already liked items
function markLikedItems() {
    // This function is no longer needed since we get the liked status from the database
    // The liked status is now included in the item data from get_items.php
}

// Utility functions
function showLoading() {
    document.getElementById('loading').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading').classList.add('hidden');
}

function showError(message) {
    const grid = document.getElementById('items-grid');
    grid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #e74c3c;">
            <h3>Error</h3>
            <p>${message}</p>
            <button onclick="loadItems()" style="margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Try Again
            </button>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}