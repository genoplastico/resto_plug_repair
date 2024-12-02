jQuery(document).ready(function($) {
    // Debug logging function
    function logDebug(message, data = {}) {
        if (window.console && window.armDebug) {
            console.log('ARM Public Debug:', message, data);
        }
    }

    // View repair details handler for public view
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var $button = $(this);
        var repairId = $button.data('repair-id');
        var token = $button.data('token');
        var $modal = $('#repair-details-modal');
        var $content = $('#repair-details-content');
        
        logDebug('Opening public repair details', { 
            repairId: repairId,
            hasToken: !!token
        });

        if (window.armModalSystem) {
            window.armModalSystem.openModal('repair-details-modal');
            window.armModalSystem.showLoading('repair-details-modal');
        } else {
            console.error('Modal system not initialized');
            return;
        }

        $.ajax({
            url: armPublicL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                token: token,
                is_public: true,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                logDebug('Public repair details response', { response: response });
                
                if (response.success && response.data.html) {
                    window.armModalSystem.setContent('repair-details-modal', response.data.html);
                } else {
                    var errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : armPublicL10n.errorLoadingRepairDetails;
                    
                    window.armModalSystem.showError('repair-details-modal', errorMessage);
                }
            },
            error: function(xhr, status, error) {
                logDebug('Error loading public repair details', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                window.armModalSystem.showError(
                    'repair-details-modal', 
                    armPublicL10n.errorLoadingRepairDetails
                );
            }
        });
    });
});