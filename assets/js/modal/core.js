/**
 * Modal Manager Core
 */
class ArmModalManager {
    constructor() {
        this.activeModals = new Set();
        this.config = window.armModalConfig || {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.debug = window.armDebug || false;
        
        if (this.debug) {
            console.log('Modal Manager initialized with config:', this.config);
        }
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
            if (e.target.classList.contains('arm-modal') && this.config.overlay.closeOnClick) {
                e.preventDefault();
                e.stopPropagation();
                this.closeModal(e.target);
            }
        });

        // ESC key handler
        document.addEventListener('keyup', (e) => {
            if (e.key === 'Escape' && this.config.accessibility.closeOnEscape) {
                const lastModal = this.getLastActiveModal();
                if (lastModal) {
                    this.closeModal(lastModal);
                }
            }
        });

        // Focus trap
        if (this.config.accessibility.trapFocus) {
            this.setupFocusTrap();
        }
    }

    setupFocusTrap() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const modal = this.getLastActiveModal();
                if (!modal) return;

                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                
                const firstFocusable = focusableElements[0];
                const lastFocusable = focusableElements[focusableElements.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            this.logError('Modal not found:', modalId);
            return;
        }

        this.log('Opening modal', { modalId, modal });
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Show modal
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        
        // Animate in
        requestAnimationFrame(() => {
            modal.classList.add('arm-modal-visible');
        });
        
        this.activeModals.add(modal);
        this.setFocus(modal);
        this.triggerEvent('modalOpened', modalId);
    }

    closeModal(modal) {
        if (!modal) {
            this.logError('Invalid modal element:', modal);
            return;
        }

        this.log('Closing modal', { modal });

        // Remove visible class to trigger animation
        modal.classList.remove('arm-modal-visible');
        modal.setAttribute('aria-hidden', 'true');

        // Remove from active modals
        this.activeModals.delete(modal);
        
        // Wait for animation to complete
        setTimeout(() => {
            modal.style.display = 'none';
            
            // Only restore body scroll if no other modals are active
            if (this.activeModals.size === 0) {
                document.body.style.overflow = '';
            }

            this.triggerEvent('modalClosed', modal.id);
        }, this.config.animation.duration);
    }

    setFocus(modal) {
        const focusElement = modal.querySelector('[autofocus]') || 
                           modal.querySelector('.arm-modal-close');
        
        if (focusElement) {
            focusElement.focus();
        }
    }

    getLastActiveModal() {
        return Array.from(this.activeModals).pop();
    }

    triggerEvent(name, detail) {
        const event = new CustomEvent('armModal:' + name, {
            detail: detail,
            bubbles: true
        });
        document.dispatchEvent(event);
    }

    log(message, data = {}) {
        if (!this.debug) return;
        console.log(`ARM Modal: ${message}`, data);
    }

    logError(message, data = {}) {
        console.error(`ARM Modal Error: ${message}`, data);
    }
}

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    window.armModalManager = new ArmModalManager();
});