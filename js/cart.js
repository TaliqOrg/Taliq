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

// Load the cart badge when the page loads
document.addEventListener('DOMContentLoaded', function () {
    loadCartBadge();
});
