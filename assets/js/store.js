document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.getElementById('mobileMenuButton');
    const mobileNav = document.getElementById('mobileNav');

    if (menuButton && mobileNav) {
        menuButton.addEventListener('click', () => {
            mobileNav.classList.toggle('open');
        });
    }

    document.querySelectorAll('[data-qty-control]').forEach((control) => {
        const input = control.querySelector('[data-qty-input]');
        const minus = control.querySelector('[data-qty-minus]');
        const plus = control.querySelector('[data-qty-plus]');
        if (!input) return;

        const min = parseFloat(input.dataset.min || input.min || '1');
        const step = parseFloat(input.dataset.step || input.step || '1');
        const max = input.dataset.max ? parseFloat(input.dataset.max) : null;

        const decimals = Math.max(
            (String(step).split('.')[1] || '').length,
            (String(min).split('.')[1] || '').length,
            0
        );

        const normalise = (value) => {
            value = Math.round(value * Math.pow(10, decimals)) / Math.pow(10, decimals);
            if (max !== null) value = Math.min(value, max);
            return Math.max(min, value);
        };

        if (minus) {
            minus.addEventListener('click', () => {
                input.value = normalise(parseFloat(input.value || min) - step).toFixed(decimals);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        if (plus) {
            plus.addEventListener('click', () => {
                input.value = normalise(parseFloat(input.value || min) + step).toFixed(decimals);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });
});
