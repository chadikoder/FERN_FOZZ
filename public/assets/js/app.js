(() => {
    const whatsappTarget = document.querySelector('[data-whatsapp-url]');

    if (whatsappTarget) {
        const whatsappUrl = whatsappTarget.getAttribute('data-whatsapp-url');

        if (whatsappUrl) {
            window.setTimeout(() => {
                window.open(whatsappUrl, '_blank', 'noopener');
            }, 700);
        }
    }

    const cartForms = document.querySelectorAll('[data-cart-form]');

    if (!cartForms.length || !window.fetch) {
        return;
    }

    const updateCartCount = (count) => {
        document.querySelectorAll('.cart-count').forEach((badge) => {
            badge.textContent = count;
        });
    };

    const showCartMessage = (message, type = 'success') => {
        const feedback = document.querySelector('[data-cart-feedback]');

        if (!feedback || !message) {
            return;
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        feedback.classList.remove('is-hidden');
        feedback.replaceChildren(alert);
    };

    cartForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = event.submitter || form.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Working';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (typeof data.cart_count !== 'undefined') {
                    updateCartCount(data.cart_count);
                }

                showCartMessage(data.message || 'Cart updated.', response.ok ? 'success' : 'error');

                if (response.ok && form.hasAttribute('data-cart-refresh')) {
                    window.setTimeout(() => window.location.reload(), 250);
                }
            } catch (error) {
                showCartMessage('Cart is unavailable right now.', 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }
        });
    });
})();
