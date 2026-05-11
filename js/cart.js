// Add an item to the cart
// courseId: the course ID (or null if it's a workshop)
// workshopId: the workshop ID (or null if it's a course)
async function addToCart(courseId, workshopId, price, quantity) {
    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action:      'add',
                course_id:   courseId,
                workshop_id: workshopId,
                price:       price,
                quantity:    quantity
            })
        });

        const result = await response.json();

        if (result.success) {
            updateCartBadge(result.count);
            showCartMessage('Item added to cart!', 'success');
        } else {
            // If not logged in, redirect to login page
            if (response.status === 401 && result.redirect) {
                window.location.href = result.redirect;
            } else {
                showCartMessage(result.message || 'Could not add item to cart', 'error');
            }
        }

    } catch (error) {
        console.error('Error adding to cart:', error);
        showCartMessage('Something went wrong. Please try again.', 'error');
    }
}

// Update the cart badge in the header (the little number on the cart icon)
function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

// Fetch the cart count from the server and update the badge
async function loadCartBadge() {
    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php?action=count');
        const result = await response.json();
        if (result.success) {
            updateCartBadge(result.count);
        }
    } catch (error) {
        console.error('Could not load cart count:', error);
    }
}

// Show a small message below the Add to Cart button
function showCartMessage(message, type) {
    const existing = document.getElementById('cart-msg');
    if (existing) existing.remove();

    const msg = document.createElement('div');
    msg.id = 'cart-msg';
    msg.textContent = message;
    msg.style.cssText = `
        margin-top: 0.75rem;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-size: 0.9rem;
        text-align: center;
        background-color: ${type === 'success' ? '#d1fae5' : '#fee2e2'};
        color: ${type === 'success' ? '#065f46' : '#991b1b'};
    `;

    const addBtn = document.getElementById('addToCartBtn');
    if (addBtn) {
        addBtn.parentNode.insertBefore(msg, addBtn.nextSibling);
    }

    setTimeout(() => {
        const el = document.getElementById('cart-msg');
        if (el) el.remove();
    }, 3000);
}

// Build the HTML for one cart item
function renderCartItem(item) {
    const badgeClass = item.Type === 'course' ? 'badge-online' : 'badge-onsite';
    const badgeText  = item.Type === 'course' ? 'Online' : 'On-site';
    const thumbnail  = item.ThumbnailUrl
        ? '../../images/' + item.ThumbnailUrl
        : '../../images/placeholder.png';
    const subtotal = parseFloat(item.Subtotal).toFixed(2);

    return `
        <div class="past-purchases-container cart-item" data-item-id="${item.CartItemId}">
            <div class="purchased-courses-container">
                <div class="card-image-container cart-img-wrapper">
                    <img src="${thumbnail}" alt="${item.Title}" class="card-img">
                </div>
                <div>
                    <h3 class="card-title cart-item-title">${item.Title}</h3>
                    <span class="badge ${badgeClass} cart-badge-static">${badgeText}</span>
                    <p style="margin-top: 0.5rem; color: var(--text-muted); font-size: 0.9rem;">
                        Qty: ${item.Quantity}
                    </p>
                </div>
            </div>
            <div class="card-footer cart-item-actions">
                <span class="price">${subtotal} SAR</span>
                <button class="cart-btn" title="Remove Item">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>
    `;
}

// Fetch cart items from the server and display them on the cart page
async function loadCartItems() {
    const container     = document.getElementById('cart-items-container');
    const originalPrice = document.getElementById('cart-original-price');
    const totalPrice    = document.getElementById('cart-total-price');

    if (!container) return;

    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php?action=items');
        const result   = await response.json();

        if (!result.success) {
            container.innerHTML = '<p style="color:var(--text-muted);">Could not load cart. Please refresh.</p>';
            return;
        }

        // Empty cart state
        if (result.items.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem;">
                    <span class="material-symbols-outlined" style="font-size: 4rem; color: var(--text-muted);">shopping_cart</span>
                    <h3 style="margin: 1rem 0 0.5rem;">Your cart is empty</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Explore our courses and add something you like!</p>
                    <a href="../explore.html" class="btn btn-primary">Explore Courses</a>
                </div>
            `;
            if (originalPrice) originalPrice.textContent = '0.00 SAR';
            if (totalPrice)    totalPrice.textContent    = '0.00 SAR';
            updateCartBadge(0);
            return;
        }

        // Render all items
        let html = '';
        result.items.forEach(function(item) {
            html += renderCartItem(item);
        });
        container.innerHTML = html;

        // Update the order summary totals
        const total = parseFloat(result.total).toFixed(2);
        if (originalPrice) originalPrice.textContent = total + ' SAR';
        if (totalPrice)    totalPrice.textContent    = total + ' SAR';

        // Update the header badge
        updateCartBadge(result.count);

    } catch (error) {
        console.error('Error loading cart items:', error);
        container.innerHTML = '<p style="color:var(--text-muted);">Something went wrong. Please refresh the page.</p>';
    }
}

// Load the cart badge when the page loads
document.addEventListener('DOMContentLoaded', function () {
    loadCartBadge();

    // If we are on the cart page, load the items
    if (document.getElementById('cart-items-container')) {
        loadCartItems();
    }
});
