class ModalManager {
    debug = false;

    constructor() {
        this.debug = window.armDebug || false;
        this.init();
        this.activeModals = new Set();
    }

    log(message, data = {}) {
        if (!this.debug) return;
        
        const timestamp = new Date().toISOString();
        console.groupCollapsed(`ARM Modal Manager [${timestamp}]`);
        console.log('%cMessage:', 'color: #2271b1; font-weight: bold;', message);
        console.log('%cData:', 'color: #2271b1;', data);
        console.trace('Call Stack');
        console.groupEnd();
    }

    init() {
        this.setupEventListeners();
        this.log('Modal Manager initialized');
    }

    setupEventListeners() {
        // Close button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal-close')) {
                const modal = e.target.closest('.arm-modal');
                if (modal) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.closeModal(modal);
                }
            }
        });

        // Outside click handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('arm-modal')) {
                e.preventDefault();
                e.stopPropagation();
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
            this.log('Opening modal', { modalId, modal });
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            // Animate in
            requestAnimationFrame(() => {
                modal.classList.add('arm-modal-visible');
            });
            
            this.activeModals.add(modal);
        } else {
            console.error('Modal not found:', modalId);
        }
    }

    closeModal(modal) {
        if (modal) {
            this.log('Closing modal', { modal });

            // Remove visible class to trigger animation
            modal.classList.remove('arm-modal-visible');

            // Remove from active modals
            this.activeModals.delete(modal);
            
            // Wait for animation to complete
            setTimeout(() => {
                modal.style.display = 'none';
                
                // Only restore body scroll if no other modals are active
                if (this.activeModals.size === 0) {
                    document.body.style.overflow = '';
                }
            }, 200);
        } else {
            console.error('Invalid modal element:', modal);
        }
    }
}
}

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    window.armModalManager = new ModalManager();
    window.armDebug = true; // Enable debug mode
});