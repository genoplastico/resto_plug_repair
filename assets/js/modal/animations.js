/**
 * Modal Animations Manager
 */
class ArmModalAnimations {
    constructor() {
        this.config = window.armModalConfig?.animation || {
            duration: 200,
            easing: 'ease-out'
        };
    }

    animate(element, animation) {
        if (!element) return Promise.reject('No element provided');

        const animationClass = `arm-modal-${animation}`;
        element.classList.add(animationClass);

        return new Promise(resolve => {
            const cleanup = () => {
                element.classList.remove(animationClass);
                element.removeEventListener('animationend', cleanup);
                resolve();
            };

            element.addEventListener('animationend', cleanup);
        });
    }

    fadeIn(modal) {
        modal.style.display = 'block';
        return this.animate(modal, 'fade');
    }

    fadeOut(modal) {
        return this.animate(modal, 'fade-out').then(() => {
            modal.style.display = 'none';
        });
    }

    slideIn(content) {
        return this.animate(content, 'slide');
    }

    slideOut(content) {
        return this.animate(content, 'slide-out');
    }

    scale(content) {
        return this.animate(content, 'scale');
    }
}

// Make animations available globally
window.armModalAnimations = new ArmModalAnimations();