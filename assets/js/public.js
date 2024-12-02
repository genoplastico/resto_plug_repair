jQuery(document).ready(function($) {
    // Debug logging function
    function logDebug(message, data = {}) {
        if (window.console && window.console.log) {
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
        
        $content.html('<div class="arm-loading">' + armPublicL10n.loading + '</div>');
        $modal.show();

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
                    $content.html(response.data.html);
                } else {
                    var errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : armPublicL10n.errorLoadingRepairDetails;
                    
                    $content.html('<div class="arm-error">' + errorMessage + '</div>');
                    console.error('ARM Error:', response);
                }
            },
            error: function(xhr, status, error) {
                logDebug('Error loading public repair details', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                $content.html('<div class="arm-error">' + armPublicL10n.errorLoadingRepairDetails + '</div>');
            }
        });
    });

    // Close modal handler
    $(document).on('click', '.arm-modal-close', function() {
        $(this).closest('.arm-modal').hide();
    });

    // Close modal when clicking outside
    $(document).on('click', '.arm-modal', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
            $(this).hide();
        }
    });

    // Close modal with ESC key
    $(document).keyup(function(e) {
        if (e.key === 'Escape') {
            $('.arm-modal').hide();
        }
    });
});