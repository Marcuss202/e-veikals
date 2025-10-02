// Global variables
let allItems = [];
let currentCategory = 'all';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    loadItems();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Category filter listeners
    const categoryLinks = document.querySelectorAll('.secondLevelNavBarLinks a');
    categoryLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state
            categoryLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Filter items
            currentCategory = this.dataset.category;
            filterItems(currentCategory);
        });
    });
}

// Load items from the database
async function loadItems() {
    try {
        showLoading();
        const response = await fetch('get_items.php');
        
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
    
    // Add click listeners to like buttons
    setupLikeButtons();
}

// Create HTML for a single item card
function createItemCard(item) {
    return `
        <div class="item-card" onclick="viewItem(${item.id})">
            <img src="${item.image_url}" alt="${item.title}" class="item-image" 
                 onerror="this.src='https://via.placeholder.com/400x200?text=Image+Not+Found'">
            <div class="item-content">
                <h3 class="item-title">${escapeHtml(item.title)}</h3>
                <p class="item-description">${escapeHtml(item.description)}</p>
                <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
                <span class="item-category">${escapeHtml(item.category)}</span>
                <div class="item-stats">
                    <div class="stat-item likes" onclick="event.stopPropagation(); toggleLike(${item.id}, this)">
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
function filterItems(category) {
    if (category === 'all') {
        displayItems(allItems);
    } else {
        const filteredItems = allItems.filter(item => item.category === category);
        displayItems(filteredItems);
    }
}

// Toggle like status
function toggleLike(itemId, element) {
    const likeCountSpan = element.querySelector('.like-count');
    const currentCount = parseInt(likeCountSpan.textContent);
    const isLiked = element.classList.contains('liked');
    
    if (isLiked) {
        // Unlike
        likeCountSpan.textContent = currentCount - 1;
        element.classList.remove('liked');
    } else {
        // Like
        likeCountSpan.textContent = currentCount + 1;
        element.classList.add('liked');
    }
    
    // Here you could also send an AJAX request to update the database
    // updateLikeInDatabase(itemId, !isLiked);
}

// View item details (placeholder function)
function viewItem(itemId) {
    // Update view count
    const item = allItems.find(item => item.id === itemId);
    if (item) {
        item.views++;
        // Here you could send an AJAX request to update views in database
        // updateViewsInDatabase(itemId);
    }
    
    // For now, just show an alert - you could implement a modal or redirect to detail page
    alert(`Viewing item: ${item ? item.title : 'Unknown'}`);
}

// Setup like button event listeners
function setupLikeButtons() {
    // Event listeners are handled inline in the HTML for simplicity
    // In a larger app, you might want to use event delegation
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

console.log("E-veikals application loaded successfully!");