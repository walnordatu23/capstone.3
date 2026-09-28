document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(form => {
        form.addEventListener('submit', event => {
            const required = form.querySelectorAll('[required]');
            let valid = true;

            required.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!valid) {
                event.preventDefault();
                alert('Please complete all required fields before submitting.');
            }
        });
    });
});
