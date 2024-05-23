import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["toggler", "whole", "half"]

    connect() {
        // Ensure it handles the absence of the toggler target gracefully
        if (this.hasTogglerTarget) {
            this.updateVisibility()
            this.togglerTarget.addEventListener('change', () => this.updateVisibility())
        }
    }

    updateVisibility() {
        const shouldShowWhole = !this.togglerTarget.checked

        this.wholeTargets.forEach(element => {
            if (shouldShowWhole) {
                element.classList.remove('d-none')
                element.classList.add('d-block')
            } else {
                element.classList.remove('d-block')
                element.classList.add('d-none')
            }
        })

        this.halfTargets.forEach(element => {
            if (shouldShowWhole) {
                element.classList.remove('d-block')
                element.classList.add('d-none')
            } else {
                element.classList.remove('d-none')
                element.classList.add('d-block')
            }
        })
    }
}
