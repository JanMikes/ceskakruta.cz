import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["panel"];

    static values = {
        googleTagId: String,
        facebookPixelId: String,
    }

    connect() {
        this.checkConsent();
        document.addEventListener("turbo:load", () => this.checkConsent());
    }

    checkConsent() {
        const consent = localStorage.getItem('trackingConsent');

        if (consent === null) {
            this.panelTarget.classList.remove('hidden');  // Show consent panel if no consent is stored
        } else if (consent === 'accepted') {
            this.loadTrackingScripts();  // Load scripts if consent was accepted
        }
        // If consent was rejected, the panel stays hidden
    }

    accept(event) {
        event.preventDefault();
        localStorage.setItem('trackingConsent', 'accepted');
        this.panelTarget.classList.add('hidden');  // Hide consent panel
        this.loadTrackingScripts();
    }

    reject(event) {
        event.preventDefault();
        localStorage.setItem('trackingConsent', 'rejected');
        this.panelTarget.classList.add('hidden'); // Hide consent panel
    }

    reopen(event) {
        event.preventDefault();
        this.panelTarget.classList.remove('hidden');
    }

    loadTrackingScripts() {
        if (!this.isTrackingLoaded()) {
            // Load Google Tag Manager
            const gtmScript = document.createElement('script');
            gtmScript.src = `https://www.googletagmanager.com/gtag/js?id=${this.googleTagIdValue}`;
            gtmScript.async = true;
            document.head.appendChild(gtmScript);

            const gtmConfig = document.createElement('script');
            gtmConfig.innerHTML = `
                window.dataLayer = window.dataLayer || [];
                function gtag(){ dataLayer.push(arguments); }
                gtag('js', new Date());
                gtag('config', '${this.googleTagIdValue}');
            `;
            document.head.appendChild(gtmConfig);

            // Load Facebook Pixel
            const fbScript = document.createElement('script');
            fbScript.innerHTML = `
                !function(f,b,e,v,n,t,s)
                { if(f.fbq)return;n=f.fbq=function(){ n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments) };
                    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                    n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t,s)}(window,document,'script',
                    'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '${this.facebookPixelIdValue}');
                fbq('track', 'PageView');
            `;
            document.head.appendChild(fbScript);
        }
    }

    isTrackingLoaded() {
        // Check if Google Tag Manager or Facebook Pixel scripts are already loaded
        return !!document.querySelector(`script[src*='gtag/js?id=${this.googleTagIdValue}']`) ||
            !!document.querySelector(`script[src*='fbevents.js']`);
    }
}
