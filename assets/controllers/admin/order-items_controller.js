import { Controller } from '@hotwired/stimulus';

// Admin order edit: add / remove item rows (Symfony collection prototype) and show only the fields that make sense
// for the chosen product (weight class for whole turkeys, slicing / packing when the product allows it).
export default class extends Controller {
    static targets = ['list', 'row', 'product'];

    static values = {
        prototype: String,
        index: Number,
        products: Object,
    };

    connect() {
        this.rowTargets.forEach((row) => this.refresh(row));
    }

    add() {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = this.prototypeValue.replace(/__name__/g, String(this.indexValue));
        this.indexValue += 1;

        const row = wrapper.firstElementChild;
        this.listTarget.append(row);
        this.refresh(row);
        row.querySelector('select')?.focus();
    }

    remove(event) {
        event.currentTarget.closest('[data-admin--order-items-target="row"]')?.remove();
    }

    productChanged(event) {
        const row = event.currentTarget.closest('[data-admin--order-items-target="row"]');

        if (row) {
            this.refresh(row);
        }
    }

    refresh(row) {
        const select = row.querySelector('[data-admin--order-items-target="product"]');
        const product = select ? this.productsValue[select.value] : undefined;

        row.querySelectorAll('[data-turkey-only]').forEach((field) => field.classList.toggle('d-none', !product?.turkey));

        const options = { slice: product?.slice, pack: product?.pack };
        row.querySelectorAll('[data-option]').forEach((field) => {
            const checkbox = field.querySelector('input[type="checkbox"]');
            // Keep an already ticked option visible (e.g. set before the product changed)
            field.classList.toggle('d-none', !options[field.dataset.option] && !checkbox?.checked);
        });

        const unit = row.querySelector('[data-unit]');
        if (unit) {
            unit.textContent = product ? `v ${product.unit}` : '';
        }
    }
}
