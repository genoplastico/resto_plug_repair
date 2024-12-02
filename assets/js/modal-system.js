/**
 * Modal System - Centralized modal management
 */
class ModalSystem {
    constructor() {
        this.activeModals = new Map();
        this.debug = window.armDebug || false;
        this.init();
    }

    init() {
        this.setupGlobalHandlers();
        this.log('Modal System Initialized');
    }

    log(message, data = {}) {
        if (!this.debug) return;
        console.group('ARM Modal System');
        console.log(`%c${message}`, 'color: #2271b1; font-weight: bold;');
        if (Object.keys(data).length) {
            console.log('Data:', data);
        }
        console.groupEnd();
    }

    setupGlobalHandlers() {
        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const lastModal = Array.from(this.activeModals.values()).pop();
                if (lastModal) {
                    this.closeModal(lastModal.id);
                }
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal')) {
                const modalId = e.target.id;
                if (this.activeModals.has(modalId)) {
                    this.closeModal(modalId);
                }
            }
        });

        // Close button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal-close')) {
                const modal = e.target.closest('.arm-modal');
                if (modal && this.activeModals.has(modal.id)) {
                    this.closeModal(modal.id);
                }
            }
        });
    }

    openModal(modalId, options = {}) {
        try {
            const modal = document.getElementById(modalId);
            if (!modal) {
                throw new Error(`Modal with id "${modalId}" not found`);
            }

            this.log('Opening modal', { modalId, options });

            // Store modal state
            this.activeModals.set(modalId, {
                id: modalId,
                element: modal,
                options: options,
                openedAt: new Date()
            });

            // Show modal with animation
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Trigger open animation in next frame
            requestAnimationFrame(() => {
                modal.classList.add('arm-modal-visible');
            });

            // Trigger custom event
            modal.dispatchEvent(new CustomEvent('arm:modal:opened', {
                detail: { modalId, options }
            }));

        } catch (error) {
            this.handleError('Error opening modal', error, { modalId, options });
        }
    }

    closeModal(modalId) {
        try {
            const modalData = this.activeModals.get(modalId);
            if (!modalData) {
                throw new Error(`No active modal found with id "${modalId}"`);
            }

            const { element, options } = modalData;
            this.log('Closing modal', { modalId, options });

            // Start close animation
            element.classList.remove('arm-modal-visible');

            // Remove after animation
            setTimeout(() => {
                element.style.display = 'none';
                this.activeModals.delete(modalId);

                // Restore body scroll if no more modals
                if (this.activeModals.size === 0) {
                    document.body.style.overflow = '';
                }

                // Trigger custom event
                element.dispatchEvent(new CustomEvent('arm:modal:closed', {
                    detail: { modalId, options }
                }));
            }, 300);

        } catch (error) {
            this.handleError('Error closing modal', error, { modalId });
        }
    }

    setContent(modalId, content) {
        try {
            const modalData = this.activeModals.get(modalId);
            if (!modalData) {
                throw new Error(`No active modal found with id "${modalId}"`);
            }

            const contentElement = modalData.element.querySelector('.arm-modal-content');
            if (!contentElement) {
                throw new Error(`Content element not found in modal "${modalId}"`);
            }

            this.log('Setting modal content', { modalId, contentLength: content.length });
            contentElement.innerHTML = content;

            // Trigger custom event
            modalData.element.dispatchEvent(new CustomEvent('arm:modal:contentUpdated', {
                detail: { modalId, content }
            }));

        } catch (error) {
            this.handleError('Error setting modal content', error, { modalId });
        }
    }

    showLoading(modalId) {
        const loadingHtml = `
            <div class="arm-modal-loading">
                <div class="arm-loading-spinner"></div>
                <p>${(window.armL10n || window.armPublicL10n || {}).loading || 'Loading...'}</p>
            </div>
        `;
        this.setContent(modalId, loadingHtml);
    }

    showError(modalId, message) {
        const errorHtml = `
            <div class="arm-modal-error">
                <p>${message}</p>
                <button type="button" class="button" onclick="armModalSystem.closeModal('${modalId}')">
                    ${(window.armL10n || window.armPublicL10n || {}).close || 'Close'}
                </button>
            </div>
        `;
        this.setContent(modalId, errorHtml);
    }

    handleError(context, error, data = {}) {
        this.log(context, { error, ...data });
        console.error('ARM Modal Error:', context, error, data);
    }

    getActiveModals() {
        return Array.from(this.activeModals.values());
    }

    closeAll() {
        this.getActiveModals().forEach(modal => {
            this.closeModal(modal.id);
        });
    }
}

// Initialize singleton instance
window.armModalSystem = new ModalSystem();