/**
 * Modal Manager Events
 */
class ArmModalEvents {
    constructor(manager) {
        this.manager = manager;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Modal trigger buttons
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-arm-modal]');
            if (trigger) {
                e.preventDefault();
                this.handleModalTrigger(trigger);
            }
        });

        // AJAX content loading
        document.addEventListener('armModal:beforeOpen', (e) => {
            const modalId = e.detail;
            const modal = document.getElementById(modalId);
            
            if (modal && modal.dataset.armAjax) {
                this.loadAjaxContent(modal);
            }
        });

        // Form submission within modal
        document.addEventListener('submit', (e) => {
            const form = e.target.closest('.arm-modal form');
            if (form && form.dataset.armAjaxSubmit) {
                e.preventDefault();
                this.handleFormSubmission(form);
            }
        });
    }

    handleModalTrigger(trigger) {
        const modalId = trigger.dataset.armModal;
        const ajaxAction = trigger.dataset.armAjaxAction;
        
        if (ajaxAction) {
            this.loadModalContent(modalId, ajaxAction, trigger.dataset);
        } else {
            this.manager.openModal(modalId);
        }
    }

    loadAjaxContent(modal) {
        const loadingTemplate = this.getLoadingTemplate();
        const contentContainer = modal.querySelector('.arm-modal-body');
        
        if (contentContainer) {
            contentContainer.innerHTML = loadingTemplate;
        }

        this.makeAjaxRequest({
            action: modal.dataset.armAjax,
            modal_id: modal.id,
            ...this.parseDataAttributes(modal.dataset)
        })
        .then(response => {
            if (response.success && response.data.html) {
                contentContainer.innerHTML = response.data.html;
                this.manager.triggerEvent('contentLoaded', modal.id);
            } else {
                this.showError(contentContainer, response.data.message);
            }
        })
        .catch(error => {
            this.showError(contentContainer, error.message);
        });
    }

    handleFormSubmission(form) {
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = this.getLoadingTemplate('small');

        const formData = new FormData(form);
        formData.append('action', form.dataset.armAjaxSubmit);
        formData.append('nonce', window.armModalConfig.nonce);

        this.makeAjaxRequest(formData)
            .then(response => {
                if (response.success) {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else if (response.data.html) {
                        const modal = form.closest('.arm-modal');
                        modal.querySelector('.arm-modal-body').innerHTML = response.data.html;
                    }
                    this.manager.triggerEvent('formSubmitted', form.id);
                } else {
                    this.showFormErrors(form, response.data.errors);
                }
            })
            .catch(error => {
                this.showFormErrors(form, { general: error.message });
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
    }

    makeAjaxRequest(data) {
        return fetch(window.armModalConfig.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data instanceof FormData ? data : new URLSearchParams(data)
        }).then(response => response.json());
    }

    parseDataAttributes(dataset) {
        const data = {};
        Object.keys(dataset).forEach(key => {
            if (key.startsWith('arm')) {
                const normalizedKey = key.replace('arm', '').toLowerCase();
                data[normalizedKey] = dataset[key];
            }
        });
        return data;
    }

    getLoadingTemplate(size = 'normal') {
        return `
            <div class="arm-modal-loading">
                <div class="arm-modal-loading-spinner ${size}"></div>
                <p>${window.armModalConfig.i18n.loading}</p>
            </div>
        `;
    }

    showError(container, message) {
        container.innerHTML = `
            <div class="arm-modal-error">
                <p>${message || window.armModalConfig.i18n.errorGeneral}</p>
            </div>
        `;
    }

    showFormErrors(form, errors) {
        // Clear previous errors
        form.querySelectorAll('.arm-error-message').forEach(el => el.remove());
        form.querySelectorAll('.arm-error-field').forEach(el => {
            el.classList.remove('arm-error-field');
        });

        // Show new errors
        Object.entries(errors).forEach(([field, message]) => {
            const input = form.querySelector(`[name="${field}"]`); ```javascript
            if (input) {
                input.classList.add('arm-error-field');
                const error = document.createElement('div');
                error.className = 'arm-error-message';
                error.textContent = message;
                input.parentNode.insertBefore(error, input.nextSibling);
            } else if (field === 'general') {
                const error = document.createElement('div');
                error.className = 'arm-error-message arm-error-general';
                error.textContent = message;
                form.insertBefore(error, form.firstChild);
            }
        });
    }
}

// Initialize Modal Events
document.addEventListener('DOMContentLoaded', () => {
    if (window.armModalManager) {
        new ArmModalEvents(window.armModalManager);
    }
});