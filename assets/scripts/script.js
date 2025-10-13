// Global variables
let allItems = [];

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadItems();
    setupFilterButtons();
});


function setupEventListeners() {
    const categoryContainer = document.getElementById('category-links');
    if (categoryContainer) {
        categoryContainer.addEventListener('click', function(e) {
            e.preventDefault();
            const target = e.target.closest('a[data-category]');
            if (!target) return;

            const links = categoryContainer.querySelectorAll('a[data-category]');
            if (target.classList.contains('active')) {
                target.classList.remove('active');
                displayItems(allItems);
            } else {
                links.forEach(l => l.classList.remove('active'));
                target.classList.add('active');
                const category = target.dataset.category;
                applyCategoryFilter(category);
            }
        });
    }
}

// Load from database
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
            const activeLink = document.querySelector('#category-links a.active');
            const activeCategory = activeLink ? activeLink.dataset.category : null;
            applyCategoryFilter(activeCategory);
        hideLoading();
        
    } catch (error) {
        console.error('Error loading items:', error);
        hideLoading();
        showError('Failed to load items. Please try again later.');
    }
}

// Display items in grid
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
}

// HTML for single card
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


async function toggleLike(itemId, element) {
    try {
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
            likeCountSpan.textContent = data.likes;
            
            if (data.userLiked) {
                element.classList.add('liked');
            } else {
                element.classList.remove('liked');
            }
        } else {
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

function viewItem(itemId) {
    window.location.href = `./views/product.html?id=${itemId}`;
}

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

function setupFilterButtons() {
    const filterRow = document.querySelector('.filterRow');
    if (!filterRow) return;

    filterRow.addEventListener('click', function(e) {
        e.preventDefault();
        const target = e.target.closest('.filterSettings');
        if (!target) return;

        const buttons = filterRow.querySelectorAll('.filterSettings');
        if (target.classList.contains('active')) {
            target.classList.remove('active');
            displayItems(allItems);
        } else {
            buttons.forEach(b => b.classList.remove('active'));
            target.classList.add('active');
            const filter = target.textContent.trim().toLowerCase();
            applyFilter(filter);
        }
    });
}

function applyFilter(filter) {
    if (!Array.isArray(allItems)) return;

    let itemsCopy = [...allItems];

    if (filter === 'recent' || filter === 'recently added' || filter === 'recently') {
        itemsCopy.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    } else if (filter.includes('liked')) {
        itemsCopy.sort((a, b) => (b.likes || 0) - (a.likes || 0));
    } else if (filter.includes('view')) {
        itemsCopy.sort((a, b) => (b.views || 0) - (a.views || 0));
    }

    displayItems(itemsCopy);
}

function applyCategoryFilter(category) {
    if (!category || !Array.isArray(allItems)) {
        displayItems(allItems);
        return;
    }

    let itemsCopy = [...allItems];

    if (category === 'popular') {
        const values = itemsCopy.map(i => i.likes || i.views || 0).sort((a,b)=>b-a);
        const thresholdIndex = Math.max(0, Math.floor(values.length * 0.3) - 1);
        const threshold = values[thresholdIndex] || 0;
        itemsCopy = itemsCopy.filter(i => (i.likes || i.views || 0) >= threshold);
        itemsCopy.sort((a,b) => (b.likes || b.views || 0) - (a.likes || a.views || 0));
    } else if (category === 'recent') {
        const now = Date.now();
        const THIRTY_DAYS = 1000 * 60 * 60 * 24 * 30;
        const recent = itemsCopy.filter(i => i.created_at && (now - new Date(i.created_at)) <= THIRTY_DAYS);
        if (recent.length > 0) {
            recent.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
            itemsCopy = recent;
        } else {
            itemsCopy.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
        }
    } else if (category === 'clonable') {
        itemsCopy = itemsCopy.filter(i => {
            const cat = (i.category || '').toLowerCase();
            const title = (i.title || '').toLowerCase();
            const desc = (i.description || '').toLowerCase();
            return cat === 'clonable' || title.includes('clone') || desc.includes('clone');
        });
        itemsCopy.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
    } else {
        itemsCopy.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
    }

    displayItems(itemsCopy);
}