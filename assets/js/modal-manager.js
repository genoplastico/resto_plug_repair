class ModalManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Close button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal-close')) {
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
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
}

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    window.armModalManager = new ModalManager();
});