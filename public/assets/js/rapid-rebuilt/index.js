/**
 * Rapid Rebuilt - Main JavaScript Bundle
 *
 * This file imports and exposes all Rapid Rebuilt JavaScript components.
 * Include this file in your layouts to access all components.
 *
 * Usage:
 *   <script src="/assets/js/rapid-rebuilt/index.js"></script>
 *
 * Or include individual files:
 *   <script src="/assets/js/rapid-rebuilt/utilities.js"></script>
 *   <script src="/assets/js/rapid-rebuilt/document-upload.js"></script>
 *   <script src="/assets/js/rapid-rebuilt/vin-decoder.js"></script>
 *   <script src="/assets/js/rapid-rebuilt/case-status.js"></script>
 *
 * Components:
 *
 * 1. RapidRebuilt (utilities.js)
 *    - Common utility functions
 *    - Toast notifications
 *    - Form helpers
 *    - Date/currency formatting
 *
 *    Example:
 *    RapidRebuilt.toast('Success!', 'success');
 *    RapidRebuilt.formatCurrency(150.00);
 *    RapidRebuilt.isValidVIN('1HGBH41JXMN109186');
 *
 * 2. DocumentUpload (document-upload.js)
 *    - Drag & drop file upload
 *    - Progress tracking
 *    - File type validation
 *
 *    Example:
 *    new DocumentUpload({
 *        container: '#documentUpload',
 *        uploadUrl: '/api/documents',
 *        onSuccess: (data) => console.log('Uploaded:', data),
 *        onError: (error) => console.error('Error:', error)
 *    });
 *
 * 3. VINDecoder (vin-decoder.js)
 *    - VIN validation
 *    - NHTSA API integration
 *    - Auto-formatting
 *
 *    Example:
 *    new VINDecoder({
 *        inputSelector: '#vinInput',
 *        decodeButtonSelector: '#decodeBtn',
 *        resultsSelector: '#vinResults',
 *        onDecode: (data) => {
 *            document.getElementById('year').value = data.year;
 *            document.getElementById('make').value = data.make;
 *            document.getElementById('model').value = data.model;
 *        }
 *    });
 *
 * 4. CaseStatus (case-status.js)
 *    - Status badge rendering
 *    - Progress bar visualization
 *    - Timeline rendering
 *    - Auto-refresh polling
 *
 *    Example:
 *    const caseStatus = new CaseStatus({
 *        onStatusChange: (data) => {
 *            document.getElementById('statusBadge').innerHTML =
 *                caseStatus.renderBadge(data.status);
 *        }
 *    });
 *    caseStatus.startPolling(caseId);
 */

// Load all component scripts dynamically if needed
(function() {
    const basePath = '/assets/js/rapid-rebuilt/';
    const scripts = [
        'utilities.js',
        'document-upload.js',
        'vin-decoder.js',
        'case-status.js'
    ];

    // Check if scripts are already loaded
    if (typeof RapidRebuilt !== 'undefined') {
        console.log('Rapid Rebuilt utilities already loaded');
        return;
    }

    // Load scripts sequentially
    function loadScript(index) {
        if (index >= scripts.length) {
            console.log('Rapid Rebuilt components loaded');
            document.dispatchEvent(new CustomEvent('rapidrebuilt:loaded'));
            return;
        }

        const script = document.createElement('script');
        script.src = basePath + scripts[index];
        script.onload = () => loadScript(index + 1);
        script.onerror = () => {
            console.error('Failed to load:', scripts[index]);
            loadScript(index + 1);
        };
        document.head.appendChild(script);
    }

    // Start loading
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => loadScript(0));
    } else {
        loadScript(0);
    }
})();
