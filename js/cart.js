// Escape HTML special characters before inserting DB data into innerHTML
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

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
                    <img src="${thumbnail}" alt="${escapeHtml(item.Title)}" class="card-img">
                </div>
                <div>
                    <h3 class="card-title cart-item-title">${escapeHtml(item.Title)}</h3>
                    <span class="badge ${badgeClass} cart-badge-static">${badgeText}</span>

                    <!-- Quantity controls -->
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                        <button onclick="updateCartItem(${item.CartItemId}, ${item.Quantity - 1})"
                            class="cart-btn" title="Decrease quantity">
                            <span class="material-symbols-outlined">remove</span>
                        </button>
                        <span style="font-weight: 600; min-width: 1.5rem; text-align: center;">${item.Quantity}</span>
                        <button onclick="updateCartItem(${item.CartItemId}, ${item.Quantity + 1})"
                            class="cart-btn" title="Increase quantity">
                            <span class="material-symbols-outlined">add</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer cart-item-actions">
                <span class="price">${subtotal} SAR</span>
                <button onclick="deleteCartItem(${item.CartItemId})" class="cart-btn" title="Remove Item">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>
    `;
}

// Send a quantity update to the server and refresh the cart display
async function updateCartItem(cartItemId, newQty) {
    if (newQty < 1) return; // minimum is 1, delete handles removal

    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action:       'update',
                cart_item_id: cartItemId,
                quantity:     newQty
            })
        });

        const result = await response.json();

        if (result.success) {
            // Re-render the updated items and totals
            const container     = document.getElementById('cart-items-container');
            const originalPrice = document.getElementById('cart-original-price');
            const totalPrice    = document.getElementById('cart-total-price');

            let html = '';
            result.items.forEach(function(item) {
                html += renderCartItem(item);
            });
            container.innerHTML = html;

            const total = parseFloat(result.total).toFixed(2);
            if (originalPrice) originalPrice.textContent = total + ' SAR';
            if (totalPrice)    totalPrice.textContent    = total + ' SAR';

            updateCartBadge(result.count);
        }

    } catch (error) {
        console.error('Error updating cart item:', error);
    }
}

// Empty the entire cart
async function emptyCart() {
    if (!confirm('Are you sure you want to remove all items from your cart?')) return;

    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'empty' })
        });

        const result = await response.json();

        if (result.success) {
            const container     = document.getElementById('cart-items-container');
            const originalPrice = document.getElementById('cart-original-price');
            const totalPrice    = document.getElementById('cart-total-price');

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
        }

    } catch (error) {
        console.error('Error emptying cart:', error);
    }
}

// Remove one item from the cart and refresh the display
async function deleteCartItem(cartItemId) {
    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action:       'delete',
                cart_item_id: cartItemId
            })
        });

        const result = await response.json();

        if (result.success) {
            const container     = document.getElementById('cart-items-container');
            const originalPrice = document.getElementById('cart-original-price');
            const totalPrice    = document.getElementById('cart-total-price');

            // If cart is now empty show the empty state
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

            // Re-render remaining items
            let html = '';
            result.items.forEach(function(item) {
                html += renderCartItem(item);
            });
            container.innerHTML = html;

            const total = parseFloat(result.total).toFixed(2);
            if (originalPrice) originalPrice.textContent = total + ' SAR';
            if (totalPrice)    totalPrice.textContent    = total + ' SAR';

            updateCartBadge(result.count);
        }

    } catch (error) {
        console.error('Error deleting cart item:', error);
    }
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

// Build the HTML for one item in the checkout summary
function renderCheckoutItem(item) {
    const subtotal = parseFloat(item.Subtotal).toFixed(2);
    return `
        <div class="summary-item">
            <span class="summary-item-name">${item.Quantity}x ${escapeHtml(item.Title)}</span>
            <span class="summary-item-price">${subtotal} SAR</span>
        </div>
    `;
}

// Load the checkout page: fill in user info and order summary
async function loadCheckoutPage() {
    const itemsContainer = document.getElementById('checkout-items-container');
    const originalPrice  = document.getElementById('checkout-original-price');
    const totalPrice     = document.getElementById('checkout-total-price');

    if (!itemsContainer) return;

    try {
        // Load cart items
        const cartResponse = await fetch('/taleeq/Taliq/api/cart.php?action=items');
        const cartResult   = await cartResponse.json();

        if (!cartResult.success || cartResult.items.length === 0) {
            // Cart is empty — send back to cart page
            window.location.href = 'cart.html';
            return;
        }

        // Render order summary items
        let html = '';
        cartResult.items.forEach(function(item) {
            html += renderCheckoutItem(item);
        });
        itemsContainer.innerHTML = html;

        // Update totals
        const total = parseFloat(cartResult.total).toFixed(2);
        if (originalPrice) originalPrice.textContent = total + ' SAR';
        if (totalPrice)    totalPrice.textContent    = total + ' SAR';

        updateCartBadge(cartResult.count);

        // Pre-fill billing form with the logged-in user's info
        const session = await checkSession();
        if (session.authenticated) {
            const nameInput  = document.getElementById('billingName');
            const emailInput = document.getElementById('billingEmail');
            if (nameInput)  nameInput.value  = session.user.first_name + ' ' + session.user.last_name;
            if (emailInput) emailInput.value = session.user.email;
        }

    } catch (error) {
        console.error('Error loading checkout page:', error);
    }
}

// Complete the purchase — validate form, send to API, redirect on success
async function completePurchase() {
    const billingName  = document.getElementById('billingName').value.trim();
    const billingEmail = document.getElementById('billingEmail').value.trim();
    const cardName     = document.getElementById('cardName').value.trim();
    const cardNumber   = document.getElementById('cardNumber').value.trim();
    const cardExpiry   = document.getElementById('cardExpiry').value.trim();
    const cardCvc      = document.getElementById('cardCvc').value.trim();

    // Basic validation — all fields must be filled
    if (!billingName || !billingEmail || !cardName || !cardNumber || !cardExpiry || !cardCvc) {
        alert('Please fill in all fields before completing your purchase.');
        return;
    }

    const confirmBtn = document.getElementById('confirmPayBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Processing...';

    try {
        const response = await fetch('/taleeq/Taliq/api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'buy' })
        });

        const result = await response.json();

        if (result.success) {
            // Clear the badge and redirect to user home
            updateCartBadge(0);
            window.location.href = '/taleeq/Taliq/pages/user/user_home.html';
        } else {
            alert(result.message || 'Purchase failed. Please try again.');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<span class="material-symbols-outlined">lock</span> Confirm & Pay';
        }

    } catch (error) {
        console.error('Error completing purchase:', error);
        alert('Something went wrong. Please try again.');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<span class="material-symbols-outlined">lock</span> Confirm & Pay';
    }
}

// Load the cart badge when the page loads
document.addEventListener('DOMContentLoaded', function () {
    loadCartBadge();

    // Cart page
    if (document.getElementById('cart-items-container')) {
        loadCartItems();
    }

    // Checkout page
    if (document.getElementById('checkout-items-container')) {
        loadCheckoutPage();
    }
});
