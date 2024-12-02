// Modal Manager for Appliance Repair Manager
jQuery(document).ready(function($) {
    // Modal open handlers
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var repairId = $(this).data('repair-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#repair-details-content').html(response.data.html);
                    $('#repair-details-modal').fadeIn(300);
                }
            }
        });
    });

    $(document).on('click', '.view-appliance-history', function(e) {
        e.preventDefault();
        var applianceId = $(this).data('appliance-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_appliance_history',
                appliance_id: applianceId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#appliance-history-content').html(response.data.html);
                    $('#appliance-history-modal').fadeIn(300);
                }
            }
        });
    });

    // Generic modal close handlers
    $(document).on('click', '.arm-modal-close', function() {
        $(this).closest('.arm-modal').fadeOut(300);
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
            $('.arm-modal').fadeOut(300);
        }
    });

    $('.arm-modal-content').on('click', function(e) {
        e.stopPropagation();
    });

    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('.arm-modal').fadeOut(300);
        }
    });
});