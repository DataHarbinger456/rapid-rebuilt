/**
 * Document Upload Component
 * Handles drag & drop file uploads with preview and progress
 */
class DocumentUpload {
    constructor(options = {}) {
        this.container = options.container || '#documentUpload';
        this.uploadUrl = options.uploadUrl || '/api/documents';
        this.maxFileSize = options.maxFileSize || 10 * 1024 * 1024; // 10MB
        this.acceptedTypes = options.acceptedTypes || ['application/pdf', 'image/jpeg', 'image/png'];
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        this.onSuccess = options.onSuccess || function() {};
        this.onError = options.onError || function() {};
        this.onProgress = options.onProgress || function() {};

        this.init();
    }

    init() {
        const container = document.querySelector(this.container);
        if (!container) return;

        this.createDropzone(container);
        this.bindEvents();
    }

    createDropzone(container) {
        container.innerHTML = `
            <div class="document-dropzone">
                <div class="dropzone-content">
                    <i data-feather="upload-cloud" class="dropzone-icon"></i>
                    <h5>Drag & Drop files here</h5>
                    <p class="text-muted">or click to browse</p>
                    <input type="file" class="dropzone-input" multiple accept="${this.acceptedTypes.join(',')}">
                </div>
                <div class="dropzone-preview" style="display: none;">
                    <div class="preview-list"></div>
                </div>
            </div>
        `;

        // Apply styles
        const style = document.createElement('style');
        style.textContent = `
            .document-dropzone {
                border: 2px dashed #dee2e6;
                border-radius: 8px;
                padding: 2rem;
                text-align: center;
                transition: all 0.3s;
                cursor: pointer;
            }
            .document-dropzone.dragover {
                border-color: #7366ff;
                background-color: rgba(115, 102, 255, 0.05);
            }
            .dropzone-icon {
                width: 48px;
                height: 48px;
                color: #7366ff;
                margin-bottom: 1rem;
            }
            .dropzone-input {
                display: none;
            }
            .preview-item {
                display: flex;
                align-items: center;
                padding: 0.75rem;
                background: #f8f9fa;
                border-radius: 6px;
                margin-bottom: 0.5rem;
            }
            .preview-item .preview-icon {
                width: 40px;
                height: 40px;
                margin-right: 1rem;
            }
            .preview-item .preview-info {
                flex: 1;
            }
            .preview-item .preview-name {
                font-weight: 500;
                margin-bottom: 0.25rem;
            }
            .preview-item .preview-size {
                font-size: 0.875rem;
                color: #6c757d;
            }
            .preview-item .preview-progress {
                height: 4px;
                margin-top: 0.5rem;
            }
            .preview-item .preview-actions {
                margin-left: 1rem;
            }
        `;
        document.head.appendChild(style);

        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    bindEvents() {
        const dropzone = document.querySelector(`${this.container} .document-dropzone`);
        const input = document.querySelector(`${this.container} .dropzone-input`);

        if (!dropzone || !input) return;

        // Click to browse
        dropzone.addEventListener('click', () => input.click());

        // File input change
        input.addEventListener('change', (e) => this.handleFiles(e.target.files));

        // Drag events
        ['dragenter', 'dragover'].forEach(event => {
            dropzone.addEventListener(event, (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            dropzone.addEventListener(event, (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            this.handleFiles(e.dataTransfer.files);
        });
    }

    handleFiles(files) {
        const previewContainer = document.querySelector(`${this.container} .dropzone-preview`);
        const previewList = document.querySelector(`${this.container} .preview-list`);

        previewContainer.style.display = 'block';

        Array.from(files).forEach(file => {
            // Validate file
            if (!this.validateFile(file)) return;

            // Create preview
            const previewId = 'preview-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const preview = this.createPreview(file, previewId);
            previewList.appendChild(preview);

            // Upload file
            this.uploadFile(file, previewId);
        });
    }

    validateFile(file) {
        if (file.size > this.maxFileSize) {
            this.onError({ message: `File ${file.name} is too large. Maximum size is ${this.formatFileSize(this.maxFileSize)}` });
            return false;
        }

        if (!this.acceptedTypes.some(type => file.type.match(type))) {
            this.onError({ message: `File ${file.name} has an unsupported format` });
            return false;
        }

        return true;
    }

    createPreview(file, previewId) {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.id = previewId;

        const icon = file.type.includes('image') ? 'image' : 'file-text';

        div.innerHTML = `
            <i data-feather="${icon}" class="preview-icon"></i>
            <div class="preview-info">
                <div class="preview-name">${file.name}</div>
                <div class="preview-size">${this.formatFileSize(file.size)}</div>
                <div class="progress preview-progress">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
            </div>
            <div class="preview-actions">
                <span class="preview-status"></span>
            </div>
        `;

        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        return div;
    }

    uploadFile(file, previewId) {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                this.updateProgress(previewId, percent);
                this.onProgress({ file, percent });
            }
        });

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                this.updateStatus(previewId, 'success');
                this.onSuccess({ file, response });
            } else {
                this.updateStatus(previewId, 'error');
                this.onError({ file, message: 'Upload failed' });
            }
        });

        xhr.addEventListener('error', () => {
            this.updateStatus(previewId, 'error');
            this.onError({ file, message: 'Upload failed' });
        });

        xhr.open('POST', this.uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    }

    updateProgress(previewId, percent) {
        const preview = document.getElementById(previewId);
        if (preview) {
            const progressBar = preview.querySelector('.progress-bar');
            progressBar.style.width = percent + '%';
        }
    }

    updateStatus(previewId, status) {
        const preview = document.getElementById(previewId);
        if (preview) {
            const statusEl = preview.querySelector('.preview-status');
            const progressBar = preview.querySelector('.progress-bar');

            if (status === 'success') {
                progressBar.classList.add('bg-success');
                statusEl.innerHTML = '<i data-feather="check-circle" class="text-success"></i>';
            } else {
                progressBar.classList.add('bg-danger');
                statusEl.innerHTML = '<i data-feather="x-circle" class="text-danger"></i>';
            }

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DocumentUpload;
}
