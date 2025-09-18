import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['input']

    connect() {
        this.inputTargets.forEach(input => {
            this.adjustWidth(input)
        })
    }

    inputTargetConnected(input) {
        this.adjustWidth(input)
    }

    adjust(event) {
        this.adjustWidth(event.target)
    }

    adjustWidth(input) {
        const value = input.value || ''
        const length = value.length

        if (length <= 4) {
            input.style.width = '40px'
        } else {
            const extraChars = length - 4
            const newWidth = 40 + (extraChars * 8)
            input.style.width = newWidth + 'px'
        }
    }
}
