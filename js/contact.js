document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const name    = document.getElementById('username').value.trim();
        const email   = document.getElementById('user-email').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();
        const msgEl   = document.getElementById('form-message');
        const btn     = document.getElementById('submit-btn');

        // Clear previous feedback
        msgEl.textContent = '';
        msgEl.className   = 'form-message';

        // Frontend validation
        if (!email) {
            showMessage(msgEl, 'Email is required.', 'error');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showMessage(msgEl, 'Please enter a valid email address.', 'error');
            return;
        }

        if (!subject) {
            showMessage(msgEl, 'Subject is required.', 'error');
            return;
        }

        if (!message) {
            showMessage(msgEl, 'Message is required.', 'error');
            return;
        }

        if (message.length < 10) {
            showMessage(msgEl, 'Message must be at least 10 characters.', 'error');
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'Sending...';

        try {
            const response = await fetch('../api/contact.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ name, email, subject, message })
            });

            const result = await response.json();

            if (result.success) {
                showMessage(msgEl, result.message, 'success');
                form.reset();
            } else {
                showMessage(msgEl, result.message, 'error');
            }
        } catch (err) {
            showMessage(msgEl, 'Something went wrong. Please try again.', 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Send';
        }
    });
});

function showMessage(el, text, type) {
    el.textContent = text;
    el.className   = 'form-message form-message-' + type;
}
