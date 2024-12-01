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
            // Force a reflow before changing styles
            modal.style.opacity = '0';
            modal.style.display = 'block';
            // Use requestAnimationFrame to batch style changes
            requestAnimationFrame(() => {
                modal.style.opacity = '1';
            });
            this.log('Opening modal', {
                modalId: modalId,
                modal: modal
            });
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
            modal.style.opacity = '0';
            // Wait for fade out animation
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 150);
        } else {
            console.error('Invalid modal element:', modal);
        }
    }
}

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    window.armModalManager = new ModalManager();
    window.armDebug = true; // Enable debug mode
});