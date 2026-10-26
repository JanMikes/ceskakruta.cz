import { Controller } from '@hotwired/stimulus';

// Shows the `content` targets only while the `checkbox` target is ticked (old Nette form `->toggle()`)
export default class extends Controller {
    static targets = ['checkbox', 'content'];

    connect() {
        this.toggle();
    }

    toggle() {
        const visible = this.checkboxTarget.checked;

        this.contentTargets.forEach((content) => content.classList.toggle('d-none', !visible));
    }
}
