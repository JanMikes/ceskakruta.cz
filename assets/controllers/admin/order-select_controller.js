import { Controller } from '@hotwired/stimulus';

// "Select all" of the orders bulk Pohoda form with the counter "(checked/total)" (old OrdersList script)
export default class extends Controller {
    static targets = ['all', 'item', 'counter'];

    connect() {
        this.update();
    }

    toggleAll() {
        this.itemTargets.forEach((item) => { item.checked = this.allTarget.checked; });
        this.update();
    }

    update() {
        const checked = this.itemTargets.filter((item) => item.checked).length;
        const total = this.itemTargets.length;

        this.counterTarget.textContent = `(${checked}/${total})`;
        this.allTarget.checked = total > 0 && checked === total;
    }
}
