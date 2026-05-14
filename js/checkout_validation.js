/**
 * Checkout Payment Form Validation
 * Real-time validation and auto-formatting for payment fields
 */

document.addEventListener('DOMContentLoaded', () => {
    initPaymentValidation();
});

function initPaymentValidation() {
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCvc = document.getElementById('cardCvc');
    const cardName = document.getElementById('cardName');
    const billingName = document.getElementById('billingName');
    const billingEmail = document.getElementById('billingEmail');
    const confirmPayBtn = document.getElementById('confirmPayBtn');

    // Card Number: Numbers only, auto-space every 4 digits, max 19 chars (16 digits + 3 spaces)
    if (cardNumber) {
        cardNumber.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            value = value.substring(0, 16); // Limit to 16 digits
            
            // Add spaces every 4 digits
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            
            e.target.value = formatted;
            validateField(e.target, value.length >= 13 && value.length <= 16);
        });

        cardNumber.addEventListener('keypress', (e) => {
            if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // Expiry Date: Auto-format to MM/YY, prevent invalid months
    if (cardExpiry) {
        cardExpiry.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            value = value.substring(0, 4); // Limit to 4 digits (MMYY)
            
            // Validate and format month
            if (value.length >= 1) {
                let month = value.substring(0, 2);
                
                // Auto-correct month
                if (value.length === 1 && parseInt(value) > 1) {
                    value = '0' + value;
                }
                if (value.length >= 2) {
                    month = parseInt(value.substring(0, 2));
                    if (month > 12) {
                        value = '12' + value.substring(2);
                    } else if (month === 0) {
                        value = '01' + value.substring(2);
                    }
                }
            }
            
            // Add slash after month
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            
            e.target.value = value;
            
            // Validate: must be MM/YY format and not expired
            const isValid = validateExpiry(value);
            validateField(e.target, isValid);
        });

        cardExpiry.addEventListener('keypress', (e) => {
            if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // CVC: Numbers only, limit to 3-4 digits
    if (cardCvc) {
        cardCvc.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            value = value.substring(0, 4); // Limit to 4 digits (some cards have 4)
            e.target.value = value;
            validateField(e.target, value.length >= 3 && value.length <= 4);
        });

        cardCvc.addEventListener('keypress', (e) => {
            if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // Card Holder Name: Letters, spaces, hyphens, apostrophes only
    if (cardName) {
        cardName.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^a-zA-Z\s\-']/g, ''); // Remove invalid chars
            e.target.value = value;
            validateField(e.target, value.trim().length >= 2);
        });

        cardName.addEventListener('keypress', (e) => {
            if (!/[a-zA-Z\s\-']/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // Billing Name: Same as card name
    if (billingName) {
        billingName.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^a-zA-Z\s\-']/g, '');
            e.target.value = value;
            validateField(e.target, value.trim().length >= 2);
        });

        billingName.addEventListener('keypress', (e) => {
            if (!/[a-zA-Z\s\-']/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // Billing Email: Standard email validation
    if (billingEmail) {
        billingEmail.addEventListener('input', (e) => {
            const value = e.target.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            validateField(e.target, emailRegex.test(value));
        });
    }

    // Override the confirm button to validate before submission
    if (confirmPayBtn) {
        const originalOnclick = confirmPayBtn.onclick;
        confirmPayBtn.onclick = (e) => {
            if (!validateAllFields()) {
                e.preventDefault();
                e.stopPropagation();
                showValidationError('Please fill in all fields correctly before proceeding.');
                return false;
            }
            if (originalOnclick) {
                return originalOnclick(e);
            }
        };
    }
}

function validateExpiry(value) {
    if (value.length !== 5 || !value.includes('/')) {
        return false;
    }
    
    const parts = value.split('/');
    if (parts.length !== 2) return false;
    
    const month = parseInt(parts[0]);
    const year = parseInt('20' + parts[1]);
    
    if (month < 1 || month > 12) return false;
    
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1;
    
    // Check if card is expired
    if (year < currentYear) return false;
    if (year === currentYear && month < currentMonth) return false;
    
    // Check if year is too far in the future (max 20 years)
    if (year > currentYear + 20) return false;
    
    return true;
}

function validateField(input, isValid) {
    if (isValid) {
        input.classList.remove('invalid');
        input.classList.add('valid');
    } else if (input.value.length > 0) {
        input.classList.remove('valid');
        input.classList.add('invalid');
    } else {
        input.classList.remove('valid', 'invalid');
    }
}

function validateAllFields() {
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCvc = document.getElementById('cardCvc');
    const cardName = document.getElementById('cardName');
    const billingName = document.getElementById('billingName');
    const billingEmail = document.getElementById('billingEmail');
    
    let isValid = true;
    
    // Card Number: 13-16 digits
    if (cardNumber) {
        const digits = cardNumber.value.replace(/\D/g, '');
        const valid = digits.length >= 13 && digits.length <= 16;
        validateField(cardNumber, valid);
        if (!valid) isValid = false;
    }
    
    // Expiry: Valid MM/YY
    if (cardExpiry) {
        const valid = validateExpiry(cardExpiry.value);
        validateField(cardExpiry, valid);
        if (!valid) isValid = false;
    }
    
    // CVC: 3-4 digits
    if (cardCvc) {
        const digits = cardCvc.value.replace(/\D/g, '');
        const valid = digits.length >= 3 && digits.length <= 4;
        validateField(cardCvc, valid);
        if (!valid) isValid = false;
    }
    
    // Card Name: At least 2 characters
    if (cardName) {
        const valid = cardName.value.trim().length >= 2;
        validateField(cardName, valid);
        if (!valid) isValid = false;
    }
    
    // Billing Name: At least 2 characters
    if (billingName) {
        const valid = billingName.value.trim().length >= 2;
        validateField(billingName, valid);
        if (!valid) isValid = false;
    }
    
    // Billing Email: Valid email format
    if (billingEmail) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const valid = emailRegex.test(billingEmail.value);
        validateField(billingEmail, valid);
        if (!valid) isValid = false;
    }
    
    return isValid;
}

function showValidationError(message) {
    // Check if error message already exists
    let errorDiv = document.querySelector('.checkout-validation-error');
    
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'checkout-validation-error';
        
        const confirmBtn = document.getElementById('confirmPayBtn');
        if (confirmBtn) {
            confirmBtn.parentNode.insertBefore(errorDiv, confirmBtn);
        }
    }
    
    errorDiv.innerHTML = `
        <span class="material-symbols-outlined">error</span>
        ${message}
    `;
    errorDiv.style.display = 'flex';
    
    // Hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}
