/**
 * Modal Helper Functions
 * Common modal utilities for the application
 */

// Modal helper functions
window.ModalHelper = {
    /**
     * Show a modal by ID
     */
    show: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    },

    /**
     * Hide a modal by ID
     */
    hide: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }
    },

    /**
     * Reset form in a modal
     */
    resetForm: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        }
    },

    /**
     * Show loading state in modal
     */
    showLoading: function(modalId, loadingText = 'Loading...') {
        const modal = document.getElementById(modalId);
        if (modal) {
            const body = modal.querySelector('.modal-body');
            if (body) {
                body.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">${loadingText}</p>
                    </div>
                `;
            }
        }
    }
};

// Common modal event handlers
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                // Respect Bootstrap modals that disable the keyboard (e.g. callback-due reminder).
                if (openModal.getAttribute('data-bs-keyboard') === 'false') {
                    return;
                }
                const bootstrapModal = bootstrap.Modal.getInstance(openModal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            }
        }
    });

    // Handle modal form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.closest('.modal')) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Re-enable button after 5 seconds as fallback
                setTimeout(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
                }, 5000);
            }
        }
    });

    // Store original button text for restore
    document.addEventListener('click', function(e) {
        if (e.target.matches('button[type="submit"]')) {
            const btn = e.target;
            if (!btn.getAttribute('data-original-text')) {
                btn.setAttribute('data-original-text', btn.innerHTML);
            }
        }
    });
});
