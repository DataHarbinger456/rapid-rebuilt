/**
 * Rapid Rebuilt Utilities
 * Common utility functions used across the application
 */
const RapidRebuilt = {
    /**
     * AJAX Setup
     */
    initAjax() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken && typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        }
    },

    /**
     * Format currency
     */
    formatCurrency(amount, locale = 'en-US', currency = 'USD') {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Format date
     */
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return new Date(date).toLocaleDateString('en-US', { ...defaultOptions, ...options });
    },

    /**
     * Format date with time
     */
    formatDateTime(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        };
        return new Date(date).toLocaleString('en-US', { ...defaultOptions, ...options });
    },

    /**
     * Format file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Format phone number
     */
    formatPhone(phone) {
        const cleaned = ('' + phone).replace(/\D/g, '');
        const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
        if (match) {
            return '(' + match[1] + ') ' + match[2] + '-' + match[3];
        }
        return phone;
    },

    /**
     * Show toast notification
     */
    toast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container') || this.createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
        return container;
    },

    /**
     * Confirm dialog
     */
    async confirm(message, title = 'Confirm') {
        if (typeof swal !== 'undefined') {
            const result = await swal({
                title: title,
                text: message,
                icon: 'warning',
                buttons: ['Cancel', 'Confirm'],
                dangerMode: true,
            });
            return result;
        }
        return window.confirm(message);
    },

    /**
     * Copy to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.toast('Copied to clipboard!', 'success');
            return true;
        } catch (err) {
            console.error('Failed to copy:', err);
            return false;
        }
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Form serialization to object
     */
    serializeForm(form) {
        const formData = new FormData(form);
        const object = {};
        formData.forEach((value, key) => {
            if (object[key]) {
                if (!Array.isArray(object[key])) {
                    object[key] = [object[key]];
                }
                object[key].push(value);
            } else {
                object[key] = value;
            }
        });
        return object;
    },

    /**
     * Load content via AJAX
     */
    async loadContent(url, target, options = {}) {
        const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
        if (!targetEl) return;

        // Show loading
        targetEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                    ...options.headers
                }
            });

            if (!response.ok) throw new Error('Request failed');

            const html = await response.text();
            targetEl.innerHTML = html;

            // Re-initialize feather icons if available
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            return true;
        } catch (error) {
            targetEl.innerHTML = '<div class="alert alert-danger">Failed to load content</div>';
            return false;
        }
    },

    /**
     * Print specific element
     */
    printElement(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print</title>
                <link rel="stylesheet" href="/assets/css/bootstrap.css">
                <style>
                    body { padding: 20px; }
                    @media print { body { padding: 0; } }
                </style>
            </head>
            <body>
                ${element.innerHTML}
                <script>window.onload = function() { window.print(); window.close(); }<\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    },

    /**
     * Validate VIN format
     */
    isValidVIN(vin) {
        if (!vin || vin.length !== 17) return false;
        if (/[IOQ]/i.test(vin)) return false;

        const weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
        const transliteration = {
            'A': 1, 'B': 2, 'C': 3, 'D': 4, 'E': 5, 'F': 6, 'G': 7, 'H': 8,
            'J': 1, 'K': 2, 'L': 3, 'M': 4, 'N': 5, 'P': 7, 'R': 9,
            'S': 2, 'T': 3, 'U': 4, 'V': 5, 'W': 6, 'X': 7, 'Y': 8, 'Z': 9
        };

        let sum = 0;
        for (let i = 0; i < 17; i++) {
            const char = vin[i].toUpperCase();
            let value = parseInt(char);
            if (isNaN(value)) value = transliteration[char] || 0;
            sum += value * weights[i];
        }

        const checkDigit = sum % 11;
        const expected = checkDigit === 10 ? 'X' : checkDigit.toString();
        return vin[8].toUpperCase() === expected;
    },

    /**
     * Initialize auto-save for forms
     */
    initAutoSave(formSelector, saveUrl, interval = 30000) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        let saveTimer;

        const save = async () => {
            const formData = new FormData(form);
            formData.append('auto_save', '1');

            try {
                await fetch(saveUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                console.log('Auto-saved at', new Date().toLocaleTimeString());
            } catch (error) {
                console.error('Auto-save failed:', error);
            }
        };

        form.addEventListener('input', this.debounce(() => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(save, interval);
        }, 1000));

        return {
            saveNow: save,
            stop: () => clearTimeout(saveTimer)
        };
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    RapidRebuilt.initAjax();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RapidRebuilt;
}
