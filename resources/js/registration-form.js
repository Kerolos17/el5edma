document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const pwStrength = document.getElementById('pw-strength');
    const pwFill = document.getElementById('pw-fill');
    const pwLabel = document.getElementById('pw-label');

    if (!passwordInput || !pwStrength || !pwFill || !pwLabel) {
        return;
    }

    const labels = {
        weak: pwLabel.dataset.labelWeak || 'Weak',
        fair: pwLabel.dataset.labelFair || 'Fair',
        good: pwLabel.dataset.labelGood || 'Good',
        strong: pwLabel.dataset.labelStrong || 'Strong',
        great: pwLabel.dataset.labelGreat || 'Great',
    };

    const levels = [
        { pct: '20%', color: '#ef4444', text: labels.weak },
        { pct: '40%', color: '#f97316', text: labels.fair },
        { pct: '60%', color: '#eab308', text: labels.good },
        { pct: '80%', color: '#22c55e', text: labels.strong },
        { pct: '100%', color: '#0073A3', text: labels.great },
    ];

    function updateStrength(value) {
        if (!value) {
            pwStrength.style.display = 'none';
            return;
        }

        pwStrength.style.display = 'block';
        let score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        const level = levels[Math.min(score - 1, 4)] || levels[0];
        pwFill.style.width = level.pct;
        pwFill.style.background = level.color;
        pwLabel.textContent = level.text;
        pwLabel.style.color = level.color;
    }

    const eyePaths = {
        open: 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z',
        closed: 'M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228l-3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88',
    };

    const form = document.querySelector('form[action*="registration"]');

    passwordInput.addEventListener('input', (event) => {
        const value = event.currentTarget.value;
        updateStrength(value);
    });

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) {
            return;
        }

        const id = toggle.getAttribute('data-password-toggle');
        const input = document.getElementById(id);
        if (!input) {
            return;
        }

        const shouldShow = input.type !== 'text';
        input.type = shouldShow ? 'text' : 'password';

        const svg = toggle.querySelector('svg');
        if (!svg) {
            return;
        }

        const path = eyePaths[shouldShow ? 'open' : 'closed'];
        svg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="${path}"/>`;
    });
});
