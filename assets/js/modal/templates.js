/**
 * Modal Templates Manager
 */
class ArmModalTemplates {
    constructor() {
        this.templates = new Map();
        this.registerDefaultTemplates();
    }

    registerDefaultTemplates() {
        this.templates.set('loading', this.createLoadingTemplate());
        this.templates.set('error', this.createErrorTemplate());
    }

    createLoadingTemplate() {
        return `
            <div class="arm-modal-loading">
                <div class="arm-modal-loading-spinner"></div>
                <p>${window.armModalConfig?.i18n?.loading || 'Loading...'}</p>
            </div>
        `;
    }

    createErrorTemplate() {
        return `
            <div class="arm-modal-error">
                <p>{{message}}</p>
                <button type="button" class="button arm-modal-close">
                    ${window.armModalConfig?.i18n?.close || 'Close'}
                </button>
            </div>
        `;
    }

    register(name, template) {
        this.templates.set(name, template);
    }

    get(name, data = {}) {
        const template = this.templates.get(name);
        if (!template) return '';

        return this.render(template, data);
    }

    render(template, data) {
        return template.replace(/\{\{(\w+)\}\}/g, (match, key) => {
            return data[key] || '';
        });
    }
}

// Make templates available globally
window.armModalTemplates = new ArmModalTemplates();