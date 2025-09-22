// toggle_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content'];

    toggle(event) {
        event.preventDefault();

        // Toggle the hidden class on the content target
        this.contentTarget.classList.toggle('hidden');

        // Toggle button text/icon if needed
        const button = event.currentTarget;
        const icon = button.querySelector('i');

        if (this.contentTarget.classList.contains('hidden')) {
            icon.className = 'ci ci-add-circle me-1';
        } else {
            icon.className = 'ci ci-close-circle me-1';
        }
    }
}
