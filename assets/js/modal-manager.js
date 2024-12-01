class ModalManager {
    debug = false;

    constructor() {
        this.debug = window.armDebug || false;
        this.init();
    }

    log(message, data = {}) {
        if (!this.debug) return;
        
        console.group('ARM Modal Manager');
        console.log(message);
        if (Object.keys(data).length > 0) {
            console.log('Data:', data);
        }
        console.groupEnd();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Close button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal-close')) {
                this.log('Modal close button clicked', {
                    target: e.target,
                    modal: e.target.closest('.arm-modal')
                });
                this.closeModal(e.target.closest('.arm-modal'));
            }
        });

        // Outside click handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal')) {
                this.closeModal(e.target);
            }
        });

        // ESC key handler
        document.addEventListener('keyup', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.arm-modal[style*="display: block"]');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            this.log('Opening modal', {
                modalId: modalId,
                modal: modal
            });
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        } else {
            console.error('Modal not found:', modalId);
        }
    }

    closeModal(modal) {
        if (modal) {
            this.log('Closing modal', {
                modal: modal
            });
        } else {
            console.error('Invalid modal element:', modal);
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
}

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    window.armModalManager = new ModalManager();
    window.armDebug = true; // Enable debug mode
});