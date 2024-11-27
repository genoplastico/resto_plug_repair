// Modal Manager with Error Handling
class ModalManager {
    constructor() {
        this.debugInfo();
        this.initializeModals();
        this.setupEventListeners();
    }

    debugInfo() {
        if (typeof armAjax !== 'undefined' && armAjax.debug) {
            console.log('ARM Debug Info:', {
                pluginUrl: armAjax.debug.pluginUrl,
                adminUrl: armAjax.debug.adminUrl,
                ajaxUrl: armAjax.debug.ajaxUrl,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            });
        }
    }

    initializeModals() {
        try {
            this.modals = document.querySelectorAll('.arm-modal');
            if (!this.modals.length) {
                console.warn('ARM: No se encontraron modales en la página');
            }
            console.log('ARM: Modales inicializados:', this.modals.length);
        } catch (error) {
            this.logError('Error al inicializar modales', error);
        }
    }

    setupEventListeners() {
        try {
            // Close button listeners
            document.querySelectorAll('.arm-modal-close').forEach(button => {
                button.addEventListener('click', (e) => {
                    try {
                        const modal = e.target.closest('.arm-modal');
                        if (modal) {
                            this.closeModal(modal);
                        } else {
                            throw new Error('No se encontró el modal padre');
                        }
                    } catch (error) {
                        this.logError('Error al cerrar modal desde botón', error);
                    }
                });
            });

            // Outside click listener
            window.addEventListener('click', (e) => {
                try {
                    if (e.target.classList.contains('arm-modal')) {
                        this.closeModal(e.target);
                    }
                } catch (error) {
                    this.logError('Error al cerrar modal por clic exterior', error);
                }
            });

            // ESC key listener
            document.addEventListener('keyup', (e) => {
                try {
                    if (e.key === 'Escape') {
                        const openModal = document.querySelector('.arm-modal[style*="display: block"]');
                        if (openModal) {
                            this.closeModal(openModal);
                        }
                    }
                } catch (error) {
                    this.logError('Error al cerrar modal con tecla ESC', error);
                }
            });

            console.log('ARM: Event listeners configurados correctamente');
        } catch (error) {
            this.logError('Error al configurar event listeners', error);
        }
    }

    openModal(modalId) {
        try {
            console.log('ARM: Intentando abrir modal:', modalId);
            const modal = document.getElementById(modalId);
            if (!modal) {
                throw new Error(`Modal no encontrado: ${modalId}`);
            }
            modal.style.display = 'block';
            console.log(`ARM: Modal abierto: ${modalId}`);
        } catch (error) {
            this.logError(`Error al abrir modal: ${modalId}`, error);
        }
    }

    closeModal(modal) {
        try {
            modal.style.display = 'none';
            console.log('ARM: Modal cerrado');
        } catch (error) {
            this.logError('Error al cerrar modal', error);
        }
    }

    logError(message, error) {
        console.error('ARM Error:', message, {
            error: error,
            stack: error.stack,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        });

        // Mostrar mensaje de error al usuario
        const errorMessage = `${message}. Por favor, revise la consola para más detalles.`;
        if (window.armShowError) {
            window.armShowError(errorMessage);
        } else {
            alert(errorMessage);
        }
    }
}

// Error notification helper
window.armShowError = function(message) {
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
};

// Initialize Modal Manager
document.addEventListener('DOMContentLoaded', () => {
    console.log('ARM: Inicializando Modal Manager...');
    window.armModalManager = new ModalManager();
});