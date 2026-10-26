import { Controller } from '@hotwired/stimulus';

const DAY_NAMES = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
const TIME_PATTERN = /^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/;

// One weekday of the place form (old managePlace.js): the checkbox toggles the inputs (disabled inputs are not
// submitted, the server stores NULL), next to "days before" the weekday on which ordering closes is shown.
export default class extends Controller {
    static targets = ['enable', 'inputs', 'daysBefore', 'time', 'hint'];
    static values = { day: Number };

    connect() {
        this.toggle();
        this.update();
        this.validateTime();
    }

    toggle() {
        const enabled = this.enableTarget.checked;

        this.inputsTarget.classList.toggle('d-none', !enabled);
        this.daysBeforeTarget.disabled = !enabled;
        this.timeTarget.disabled = !enabled;
    }

    update() {
        if (!this.hasHintTarget) {
            return;
        }

        const value = this.daysBeforeTarget.value.trim();
        const daysBefore = Number.parseInt(value, 10);

        if (value === '' || !/^\d+$/.test(value) || Number.isNaN(daysBefore)) {
            this.hintTarget.textContent = '';
            return;
        }

        let day = (this.dayValue - daysBefore) % 7;
        if (day <= 0) {
            day += 7;
        }

        this.hintTarget.textContent = `Objednávky se uzavírají: ${DAY_NAMES[day - 1]}`;
    }

    validateTime() {
        const value = this.timeTarget.value.trim();

        this.timeTarget.classList.toggle('is-invalid', value !== '' && !TIME_PATTERN.test(value));
    }
}
