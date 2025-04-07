import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        orderTotal: Number,
    }

    connect() {
        this.loadConversionScript();
    }

    loadConversionScript() {
        const script = document.createElement('script');
        const retargetingConsent = localStorage.getItem('trackingConsent');
        script.src = "https://c.seznam.cz/js/rc.js";
        script.async = true;
        script.onload = () => {
            // Optionally update identities; here it's using a placeholder null value
            /*
            if (window.sznIVA && window.sznIVA.IS && typeof window.sznIVA.IS.updateIdentities === 'function') {
                window.sznIVA.IS.updateIdentities({
                    eid: null // You can pass the email or hashed email if available
                });
            }
            */

            // Configure the conversion parameters, using the passed values
            const conversionConf = {
                id: 100233187,
                value: this.hasOrderTotalValue ? this.orderTotalValue : null,
                consent: retargetingConsent === 'accepted' ? 1 : (retargetingConsent === 'rejected' ? 0 : null)
            };

            // Execute the conversion hit if available
            if (window.rc && typeof window.rc.conversionHit === 'function') {
                window.rc.conversionHit(conversionConf);
                alert('conversion');
            }
        };
        document.head.appendChild(script);
    }
}
