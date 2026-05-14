// Wishlist functionality
let wishlistIds = [];

document.addEventListener('DOMContentLoaded', function() {
    loadWishlistIds();
    
    // Load wishlist items if on profile page with wishlist container
    const profileWishlistContainer = document.getElementById('profile-wishlist-container');
    if (profileWishlistContainer) {
        loadProfileWishlist();
    }
});

// Load all wishlist IDs for the current user
async function loadWishlistIds() {
    try {
        const response = await fetch('/taleeq/Taliq/api/wishlist.php?action=ids');
        const data = await response.json();
        if (data.success) {
            wishlistIds = data.ids || [];
            updateWishlistDetailButton();
        }
    } catch (error) {
        console.error('Error loading wishlist IDs:', error);
    }
}

// Check if an item is in the wishlist
function isInWishlist(courseId, workshopId) {
    const key = courseId ? `course_${courseId}` : `workshop_${workshopId}`;
    return wishlistIds.includes(key);
}

// Toggle wishlist status for an item
async function toggleWishlist(courseId, workshopId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    try {
        const response = await fetch('/taleeq/Taliq/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'toggle',
                course_id: courseId,
                workshop_id: workshopId
            })
        });

        const data = await response.json();

        if (response.status === 401 && data.redirect) {
            showToast(data.message, 'warning');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
            return;
        }

        if (data.success) {
            const key = courseId ? `course_${courseId}` : `workshop_${workshopId}`;
            
            if (data.action === 'added') {
                wishlistIds.push(key);
                showToast('Added to wishlist!', 'success');
            } else {
                wishlistIds = wishlistIds.filter(id => id !== key);
                showToast('Removed from wishlist', 'info');
            }
            
        } else {
            showToast(data.message || 'Error updating wishlist', 'error');
        }
    } catch (error) {
        console.error('Error toggling wishlist:', error);
        showToast('Error updating wishlist', 'error');
    }
}


// Show toast notification
function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined toast-icon">${getToastIcon(type)}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('toast-show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getToastIcon(type) {
    switch (type) {
        case 'success': return 'check_circle';
        case 'error': return 'error';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

// Fetch and display wishlist items (for profile page)
async function loadProfileWishlist() {
    const container = document.getElementById('profile-wishlist-container');
    if (!container) return;

    container.innerHTML = '<p class="loading-text">Loading your wishlist...</p>';

    try {
        const response = await fetch('/taleeq/Taliq/api/wishlist.php?action=items');
        
        // Handle non-JSON responses (like PHP warnings)
        const text = await response.text();
        let data;
        
        try {
            // Try to parse JSON, looking for the actual JSON part
            const jsonMatch = text.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                data = JSON.parse(jsonMatch[0]);
            } else {
                throw new Error('No JSON found in response');
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.log('Response text:', text);
            container.innerHTML = '<p class="error-message show">Error loading wishlist. Please refresh the page.</p>';
            return;
        }

        if (response.status === 401) {
            container.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined">login</span>
                    <h3>Please log in</h3>
                    <p>You need to be logged in to view your wishlist.</p>
                    <a href="/taleeq/Taliq/pages/login.html" class="btn btn-primary">Log In</a>
                </div>
            `;
            return;
        }

        if (data.success && data.items && data.items.length > 0) {
            container.innerHTML = '';
            data.items.forEach(item => {
                container.innerHTML += createProfileWishlistCard(item);
            });
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined">favorite_border</span>
                    <h3>Your wishlist is empty</h3>
                    <p>Browse courses and add them to your wishlist from the course details page.</p>
                    <a href="/taleeq/Taliq/pages/explore.html" class="btn btn-primary">Explore Courses</a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading wishlist:', error);
        container.innerHTML = '<p class="error-message show">Error loading wishlist. Please try again.</p>';
    }
}

// Create wishlist card for profile page
function createProfileWishlistCard(item) {
    const badgeClass = (item.CourseType === "course") ? 'badge-online' : 'badge-onsite';
    const PlaceOfCourse = (item.CourseType === "course") ? 'Online' : 'On-Site';
    const placeholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZmlsbD0iIzY0NzQ4YiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    const thumbnail = item.ThumbnailUrl || placeholder;

    const courseId = item.CourseType === 'course' ? item.CourseId : null;
    const workshopId = item.CourseType === 'workshop' ? item.WorkshopId : null;
    const itemId = courseId || workshopId;

    return `
        <a href="/taleeq/Taliq/pages/course_details.html?id=${itemId}&type=${item.CourseType}" class="card">
            <div class="card-image-container">
                <img class="card-img" src="${thumbnail}" alt="${item.Title}">
                <span class="badge ${badgeClass}">${PlaceOfCourse}</span>
                <button class="remove-wishlist-btn" 
                        onclick="event.preventDefault(); event.stopPropagation(); removeFromProfileWishlist(${courseId}, ${workshopId});">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="card-content">
                <h3 class="card-title">${item.Title}</h3>
                <div class="rating">
                    <span class="material-symbols-outlined star-icon">star</span>
                    <span class="rating-score">${parseFloat(item.AverageRating || 0).toFixed(1)}</span>
                    <span class="rating-count">(${item.RatingCount || 0} ratings)</span>
                </div>
                <div class="card-footer">
                    <span class="price">${item.Price} SAR</span>
                    <button class="add-cart-btn" onclick="event.preventDefault(); event.stopPropagation(); quickAddToCart(${courseId}, ${workshopId}, ${item.Price});">
                        <span class="material-symbols-outlined">add_shopping_cart</span>
                    </button>
                </div>
            </div>
        </a>
    `;
}

// Remove item from wishlist on profile page
async function removeFromProfileWishlist(courseId, workshopId) {
    try {
        const response = await fetch('/taleeq/Taliq/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'remove',
                course_id: courseId,
                workshop_id: workshopId
            })
        });

        const data = await response.json();

        if (data.success) {
            const key = courseId ? `course_${courseId}` : `workshop_${workshopId}`;
            wishlistIds = wishlistIds.filter(id => id !== key);
            showToast('Removed from wishlist', 'info');
            loadProfileWishlist();
        } else {
            showToast(data.message || 'Error removing from wishlist', 'error');
        }
    } catch (error) {
        console.error('Error removing from wishlist:', error);
        showToast('Error removing from wishlist', 'error');
    }
}

// Toggle wishlist from course detail page
async function toggleWishlistFromDetail() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    const type = urlParams.get('type');
    
    const courseId = type === 'course' ? id : null;
    const workshopId = type === 'workshop' ? id : null;
    
    await toggleWishlist(courseId, workshopId);
    updateWishlistDetailButton();
}

// Update wishlist button on course detail page
function updateWishlistDetailButton() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    const type = urlParams.get('type');
    
    const courseId = type === 'course' ? id : null;
    const workshopId = type === 'workshop' ? id : null;
    
    const btn = document.getElementById('wishlistBtn');
    const icon = document.getElementById('wishlistIcon');
    const text = document.getElementById('wishlistText');
    
    if (!btn || !icon || !text) return;
    
    if (isInWishlist(courseId, workshopId)) {
        btn.classList.add('active');
        icon.textContent = 'favorite';
        icon.style.fontVariationSettings = "'FILL' 1";
        text.textContent = 'Remove from Wishlist';
    } else {
        btn.classList.remove('active');
        icon.textContent = 'favorite_border';
        icon.style.fontVariationSettings = "'FILL' 0";
        text.textContent = 'Add to Wishlist';
    }
}

