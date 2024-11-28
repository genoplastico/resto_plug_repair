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
        var repairId = $(this).data('repair-id');
        var $modal = $('#repair-details-modal');
        var $content = $('#repair-details-content');
        
        logDebug('Opening public repair details', { repairId: repairId });
        
        $content.html('<div class="arm-loading">' + armPublicL10n.loading + '</div>');
        $modal.show();

        $.ajax({
            url: armPublicL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val(),
                is_public: true
            },
            success: function(response) {
                logDebug('Public repair details loaded', { response: response });
                
                if (response.success && response.data.html) {
                    $content.html(response.data.html);
                } else {
                    $content.html('<div class="arm-error">' + armPublicL10n.errorLoadingRepairDetails + '</div>');
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
});