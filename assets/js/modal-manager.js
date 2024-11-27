// Modal Manager with Enhanced Error Handling
class ModalManager {
    constructor() {
        this.debugMode = typeof armAjax !== 'undefined' && armAjax.debug;
        this.debugInfo();
        this.initializeModals();
        this.setupEventListeners();
    }

    debugInfo() {
        if (this.debugMode) {
            console.group('ARM Modal Manager Initialization');
            console.log('Debug Info:', armAjax.debug);
            console.log('User Agent:', navigator.userAgent);
            console.log('Timestamp:', new Date().toISOString());
            this.verifyAssets();
            console.groupEnd();
        }
    }

    verifyAssets() {
        if (!this.debugMode) return;

        const requiredAssets = armAjax.debug.assets_check;
        console.group('ARM Asset Verification');
        Object.entries(requiredAssets).forEach(([asset, loaded]) => {
            console.log(`${asset}: ${loaded ? '✓' : '✗'}`);
            if (!loaded) {
                this.logError(`Required asset not loaded: ${asset}`, new Error('Asset loading failed'));
            }
        });
        console.groupEnd();
    }

    initializeModals() {
        try {
            this.modals = document.querySelectorAll('.arm-modal');
            if (this.debugMode) {
                console.log('ARM Modals found:', this.modals.length);
                this.modals.forEach((modal, index) => {
                    console.log(`Modal ${index + 1}:`, {
                        id: modal.id,
                        display: window.getComputedStyle(modal).display,
                        zIndex: window.getComputedStyle(modal).zIndex
                    });
                });
            }
        } catch (error) {
            this.logError('Modal initialization failed', error);
        }
    }

    setupEventListeners() {
        try {
            // Close button listeners
            document.querySelectorAll('.arm-modal-close').forEach(button => {
                button.addEventListener('click', (e) => this.handleCloseClick(e));
            });

            // Outside click listener
            window.addEventListener('click', (e) => this.handleOutsideClick(e));

            // ESC key listener
            document.addEventListener('keyup', (e) => this.handleEscKey(e));

            if (this.debugMode) {
                console.log('ARM Event listeners configured');
            }
        } catch (error) {
            this.logError('Event listener setup failed', error);
        }
    }

    handleCloseClick(e) {
        try {
            const modal = e.target.closest('.arm-modal');
            if (modal) {
                this.closeModal(modal);
            } else {
                throw new Error('Parent modal not found');
            }
        } catch (error) {
            this.logError('Close button handler failed', error);
        }
    }

    handleOutsideClick(e) {
        try {
            if (e.target.classList.contains('arm-modal')) {
                this.closeModal(e.target);
            }
        } catch (error) {
            this.logError('Outside click handler failed', error);
        }
    }

    handleEscKey(e) {
        try {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.arm-modal[style*="display: block"]');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        } catch (error) {
            this.logError('ESC key handler failed', error);
        }
    }

    openModal(modalId) {
        try {
            if (this.debugMode) console.log('Opening modal:', modalId);
            
            const modal = document.getElementById(modalId);
            if (!modal) {
                throw new Error(`Modal not found: ${modalId}`);
            }

            // Verify modal structure
            if (!modal.querySelector('.arm-modal-content')) {
                throw new Error(`Invalid modal structure: ${modalId}`);
            }

            modal.style.display = 'block';
            
            if (this.debugMode) {
                console.log('Modal opened successfully:', {
                    id: modalId,
                    display: modal.style.display,
                    zIndex: window.getComputedStyle(modal).zIndex
                });
            }
        } catch (error) {
            this.logError(`Failed to open modal: ${modalId}`, error);
        }
    }

    closeModal(modal) {
        try {
            if (this.debugMode) console.log('Closing modal:', modal.id);
            
            modal.style.display = 'none';
            
            if (this.debugMode) {
                console.log('Modal closed successfully:', {
                    id: modal.id,
                    display: modal.style.display
                });
            }
        } catch (error) {
            this.logError('Failed to close modal', error);
        }
    }

    logError(message, error) {
        console.error('ARM Error:', {
            message: message,
            error: error,
            stack: error.stack,
            timestamp: new Date().toISOString()
        });

        this.showErrorNotification(message);
    }

    showErrorNotification(message) {
        const container = document.createElement('div');
        container.className = 'arm-error-notification';
        container.innerHTML = `
            <div class="arm-error-content">
                <span class="arm-error-message">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        document.body.appendChild(container);
        setTimeout(() => container.remove(), 5000);
    }
}

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    console.log('ARM: Initializing Modal Manager...');
    window.armModalManager = new ModalManager();
});