import { Controller } from '@hotwired/stimulus';

/**
 * Toggles the visibility of the invoicing details
 * when the companyInvoicing checkbox is checked/unchecked.
 */
export default class extends Controller {
    static targets = ['details'];

    connect() {
        // Ensure correct initial state on page load
        this.toggle();
    }

    toggle(event) {
        // Determine checkbox state
        const checked = event
            ? event.target.checked
            : this.element.querySelector('input[type="checkbox"]').checked;

        // Show or hide the details container
        this.detailsTarget.classList.toggle('d-none', !checked);
    }
}
