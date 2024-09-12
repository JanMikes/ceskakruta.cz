import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    submitForm(event) {
        const checkbox = event.target;
        const form = checkbox.closest('form');
        if (form) {
            form.submit();
        }
    }

    submitFormSilently(event) {
        const checkbox = event.target;
        const form = checkbox.closest('form');
        if (form) {
            const formData = new FormData(form);

            fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
                .then(response => {})
                .catch(error => {
                    console.error('Error during form submission', error);
                });
        }
    }
}
