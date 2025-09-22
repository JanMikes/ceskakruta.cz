import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['packageInput', 'rowTotal', 'grandTotal'];

    connect() {
        this.calculate();
    }

    calculate() {
        let grandTotal = 0;

        // Find each table row that has a rowTotal target
        this.element.querySelectorAll('tr').forEach((row) => {
            const rowTotalEl = row.querySelector('[data-weight-calculator-target="rowTotal"]');
            if (!rowTotalEl) return; // skip header/footer rows

            let rowSum = 0;
            // Sum package-based weight within this row
            row.querySelectorAll('[data-weight-calculator-target="packageInput"]').forEach((input) => {
                const count = parseFloat(input.value) || 0;
                const pkgWeight = parseFloat(input.dataset.packageWeight) || 0;
                rowSum += count * pkgWeight;
            });

            // Dynamically add/remove classes based on weight
            this.updateRowClass(row, rowSum);

            // Update row total display
            rowTotalEl.textContent = `${rowSum.toFixed(2)} kg`;
            grandTotal += rowSum;
        });

        // Update the grand total
        this.grandTotalTarget.textContent = `${grandTotal.toFixed(2)} kg`;
    }

    updateRowClass(row, weight) {
        const table = row.closest('table');
        const isExtraTable = table.closest('.card.border-warning') !== null;
        const className = isExtraTable ? 'table-warning' : 'table-primary';

        if (weight > 0) {
            row.classList.add(className);
        } else {
            row.classList.remove(className);
        }
    }
}
