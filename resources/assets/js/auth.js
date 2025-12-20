const initLoginForm = () => {
    const body = document.body;
    if (!body || !body.classList.contains('login')) return;

    const form = document.querySelector('form');
    const submit = document.querySelector('button[type="submit"]');
    const email = document.querySelector('[name="email"]');
    const password = document.querySelector('[name="password"]');

    if (!form || !submit) return;

    submit.addEventListener('click', (event) => {
        if (typeof form.checkValidity === 'function' && form.checkValidity()) {
            const signingIn = submit.querySelector('.signingin');
            const signIn = submit.querySelector('.signin');
            if (signingIn) signingIn.className = 'signingin';
            if (signIn) signIn.className = 'signin hidden';
            return;
        }
        event.preventDefault();
    });

    const emailGroup = document.getElementById('emailGroup');
    const passwordGroup = document.getElementById('passwordGroup');

    if (email) {
        email.focus();
        emailGroup && emailGroup.classList.add('focused');
        email.addEventListener('focusin', () => emailGroup && emailGroup.classList.add('focused'));
        email.addEventListener('focusout', () => emailGroup && emailGroup.classList.remove('focused'));
    }

    if (password) {
        password.addEventListener('focusin', () => passwordGroup && passwordGroup.classList.add('focused'));
        password.addEventListener('focusout', () => passwordGroup && passwordGroup.classList.remove('focused'));
    }
};

document.addEventListener('DOMContentLoaded', () => {
    initLoginForm();
});

