/**
 * VIN Decoder Component
 * Handles VIN validation and NHTSA API decoding
 */
class VINDecoder {
    constructor(options = {}) {
        this.inputSelector = options.inputSelector || '#vinInput';
        this.decodeButtonSelector = options.decodeButtonSelector || '#decodeVinBtn';
        this.resultsSelector = options.resultsSelector || '#vinResults';
        this.decodeUrl = options.decodeUrl || '/api/vehicles/decode-vin';
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        this.onDecode = options.onDecode || function() {};
        this.onError = options.onError || function() {};

        this.init();
    }

    init() {
        this.input = document.querySelector(this.inputSelector);
        this.button = document.querySelector(this.decodeButtonSelector);
        this.results = document.querySelector(this.resultsSelector);

        if (!this.input) return;

        this.bindEvents();
    }

    bindEvents() {
        // Format VIN as user types (uppercase, no invalid chars)
        this.input.addEventListener('input', (e) => {
            let value = e.target.value.toUpperCase();
            // Remove invalid characters (I, O, Q are not used in VINs)
            value = value.replace(/[IOQ]/g, '').replace(/[^A-HJ-NPR-Z0-9]/g, '');
            e.target.value = value.substring(0, 17);

            this.validateInput();
        });

        // Decode on button click
        if (this.button) {
            this.button.addEventListener('click', () => this.decode());
        }

        // Decode on Enter key
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.decode();
            }
        });
    }

    validateInput() {
        const vin = this.input.value;
        const isValid = this.isValidVIN(vin);

        this.input.classList.toggle('is-valid', isValid);
        this.input.classList.toggle('is-invalid', vin.length > 0 && !isValid);

        if (this.button) {
            this.button.disabled = !isValid;
        }

        return isValid;
    }

    isValidVIN(vin) {
        if (vin.length !== 17) return false;

        // Check for invalid characters
        if (/[IOQ]/i.test(vin)) return false;

        // Check digit validation (position 9)
        const weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
        const transliteration = {
            'A': 1, 'B': 2, 'C': 3, 'D': 4, 'E': 5, 'F': 6, 'G': 7, 'H': 8,
            'J': 1, 'K': 2, 'L': 3, 'M': 4, 'N': 5, 'P': 7, 'R': 9,
            'S': 2, 'T': 3, 'U': 4, 'V': 5, 'W': 6, 'X': 7, 'Y': 8, 'Z': 9
        };

        let sum = 0;
        for (let i = 0; i < 17; i++) {
            const char = vin[i];
            let value = parseInt(char);

            if (isNaN(value)) {
                value = transliteration[char] || 0;
            }

            sum += value * weights[i];
        }

        const checkDigit = sum % 11;
        const expected = checkDigit === 10 ? 'X' : checkDigit.toString();

        return vin[8] === expected;
    }

    async decode() {
        const vin = this.input.value;

        if (!this.validateInput()) {
            this.onError({ message: 'Please enter a valid 17-character VIN' });
            return;
        }

        // Show loading state
        if (this.button) {
            this.button.disabled = true;
            this.button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Decoding...';
        }

        try {
            const response = await fetch(this.decodeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ vin })
            });

            const data = await response.json();

            if (data.success) {
                this.displayResults(data.data);
                this.onDecode(data.data);
            } else {
                this.onError({ message: data.message || 'Failed to decode VIN' });
            }
        } catch (error) {
            this.onError({ message: 'Network error. Please try again.' });
        } finally {
            // Reset button
            if (this.button) {
                this.button.disabled = false;
                this.button.innerHTML = '<i data-feather="search"></i> Decode VIN';
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        }
    }

    displayResults(data) {
        if (!this.results) return;

        this.results.innerHTML = `
            <div class="alert alert-success mb-3">
                <i data-feather="check-circle"></i> VIN decoded successfully!
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Year</label>
                    <p class="form-control-plaintext fw-bold">${data.year || '-'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Make</label>
                    <p class="form-control-plaintext fw-bold">${data.make || '-'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Model</label>
                    <p class="form-control-plaintext fw-bold">${data.model || '-'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Body Style</label>
                    <p class="form-control-plaintext">${data.body_style || '-'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Engine</label>
                    <p class="form-control-plaintext">${data.engine || '-'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Transmission</label>
                    <p class="form-control-plaintext">${data.transmission || '-'}</p>
                </div>
            </div>
        `;

        this.results.style.display = 'block';

        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    getDecodedData() {
        // Returns the last decoded data for form population
        return this._lastDecodedData || null;
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VINDecoder;
}
