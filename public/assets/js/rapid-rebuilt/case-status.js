/**
 * Case Status Component
 * Handles case progress visualization and status updates
 */
class CaseStatus {
    constructor(options = {}) {
        this.container = options.container || '#caseStatus';
        this.statusUrl = options.statusUrl || '/api/cases';
        this.pollInterval = options.pollInterval || 30000; // 30 seconds
        this.autoRefresh = options.autoRefresh !== false;
        this.onStatusChange = options.onStatusChange || function() {};

        this.statuses = [
            { key: 'draft', label: 'Draft', color: 'secondary', icon: 'file', progress: 5 },
            { key: 'submitted', label: 'Submitted', color: 'info', icon: 'send', progress: 10 },
            { key: 'pending_payment', label: 'Pending Payment', color: 'warning', icon: 'credit-card', progress: 15 },
            { key: 'paid', label: 'Paid', color: 'success', icon: 'check-circle', progress: 20 },
            { key: 'pending_documents', label: 'Pending Documents', color: 'warning', icon: 'file-text', progress: 25 },
            { key: 'documents_complete', label: 'Documents Complete', color: 'success', icon: 'folder-check', progress: 35 },
            { key: 'pending_scheduling', label: 'Ready to Schedule', color: 'info', icon: 'calendar', progress: 40 },
            { key: 'scheduled', label: 'Scheduled', color: 'primary', icon: 'clock', progress: 50 },
            { key: 'inspection_in_progress', label: 'Inspection In Progress', color: 'primary', icon: 'clipboard', progress: 60 },
            { key: 'pending_review', label: 'Pending Review', color: 'info', icon: 'eye', progress: 70 },
            { key: 'passed', label: 'Passed', color: 'success', icon: 'check', progress: 80 },
            { key: 'failed', label: 'Failed', color: 'danger', icon: 'x', progress: 75 },
            { key: 'generating_documents', label: 'Generating Documents', color: 'info', icon: 'file-plus', progress: 85 },
            { key: 'ready_for_dmv', label: 'Ready for DMV', color: 'success', icon: 'truck', progress: 90 },
            { key: 'sent_to_dmv', label: 'Sent to DMV', color: 'primary', icon: 'send', progress: 95 },
            { key: 'title_issued', label: 'Title Issued', color: 'success', icon: 'award', progress: 98 },
            { key: 'completed', label: 'Completed', color: 'success', icon: 'check-circle', progress: 100 },
            { key: 'cancelled', label: 'Cancelled', color: 'danger', icon: 'x-circle', progress: 0 },
        ];

        this.init();
    }

    init() {
        this.injectStyles();

        if (this.autoRefresh) {
            this.startPolling();
        }
    }

    injectStyles() {
        if (document.getElementById('case-status-styles')) return;

        const style = document.createElement('style');
        style.id = 'case-status-styles';
        style.textContent = `
            .case-status-timeline {
                position: relative;
                padding: 0;
                list-style: none;
            }
            .case-status-timeline::before {
                content: '';
                position: absolute;
                left: 20px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #e9ecef;
            }
            .case-status-item {
                position: relative;
                padding-left: 50px;
                padding-bottom: 1.5rem;
            }
            .case-status-item:last-child {
                padding-bottom: 0;
            }
            .case-status-item .status-icon {
                position: absolute;
                left: 10px;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                background: #e9ecef;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .case-status-item .status-icon svg {
                width: 12px;
                height: 12px;
                color: #6c757d;
            }
            .case-status-item.completed .status-icon {
                background: #28a745;
            }
            .case-status-item.completed .status-icon svg {
                color: white;
            }
            .case-status-item.current .status-icon {
                background: #7366ff;
                box-shadow: 0 0 0 4px rgba(115, 102, 255, 0.2);
            }
            .case-status-item.current .status-icon svg {
                color: white;
            }
            .case-status-item.pending .status-icon {
                background: #dee2e6;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.75rem;
                border-radius: 50px;
                font-size: 0.875rem;
                font-weight: 500;
            }
            .status-badge svg {
                width: 14px;
                height: 14px;
                margin-right: 0.5rem;
            }
        `;
        document.head.appendChild(style);
    }

    getStatusInfo(statusKey) {
        return this.statuses.find(s => s.key === statusKey) || {
            key: statusKey,
            label: statusKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
            color: 'secondary',
            icon: 'circle',
            progress: 0
        };
    }

    renderBadge(statusKey) {
        const status = this.getStatusInfo(statusKey);
        return `
            <span class="status-badge bg-${status.color} text-white">
                <i data-feather="${status.icon}"></i>
                ${status.label}
            </span>
        `;
    }

    renderProgress(statusKey) {
        const status = this.getStatusInfo(statusKey);
        return `
            <div class="progress" style="height: 20px;">
                <div class="progress-bar progress-bar-striped bg-${status.color}"
                     role="progressbar"
                     style="width: ${status.progress}%"
                     aria-valuenow="${status.progress}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    ${status.progress}%
                </div>
            </div>
        `;
    }

    renderTimeline(currentStatus, history = []) {
        const currentIndex = this.statuses.findIndex(s => s.key === currentStatus);

        const mainSteps = ['submitted', 'paid', 'scheduled', 'passed', 'ready_for_dmv', 'completed'];

        let html = '<ul class="case-status-timeline">';

        mainSteps.forEach((stepKey, index) => {
            const status = this.getStatusInfo(stepKey);
            const stepIndex = this.statuses.findIndex(s => s.key === stepKey);
            let state = 'pending';

            if (currentIndex > stepIndex) {
                state = 'completed';
            } else if (currentIndex === stepIndex) {
                state = 'current';
            }

            html += `
                <li class="case-status-item ${state}">
                    <div class="status-icon">
                        <i data-feather="${state === 'completed' ? 'check' : status.icon}"></i>
                    </div>
                    <div class="status-content">
                        <h6 class="mb-0 ${state === 'pending' ? 'text-muted' : ''}">${status.label}</h6>
                    </div>
                </li>
            `;
        });

        html += '</ul>';

        return html;
    }

    async checkStatus(caseId) {
        try {
            const response = await fetch(`${this.statusUrl}/${caseId}/status`);
            const data = await response.json();

            if (data.success && data.data.status) {
                this.onStatusChange(data.data);
                return data.data;
            }
        } catch (error) {
            console.error('Failed to check case status:', error);
        }
        return null;
    }

    startPolling(caseId) {
        if (this._pollTimer) {
            clearInterval(this._pollTimer);
        }

        if (caseId) {
            this._pollTimer = setInterval(() => {
                this.checkStatus(caseId);
            }, this.pollInterval);
        }
    }

    stopPolling() {
        if (this._pollTimer) {
            clearInterval(this._pollTimer);
            this._pollTimer = null;
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CaseStatus;
}
