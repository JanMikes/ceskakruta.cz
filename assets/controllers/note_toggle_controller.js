// note_toggle_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['hide', 'show'];

    toggle(event) {
        event.preventDefault();

        // Hide the element with 'hide' target
        this.hideTarget.classList.add('hidden');

        // Show the element with 'show' target
        this.showTarget.classList.remove('hidden');
    }
}
