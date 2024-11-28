jQuery(document).ready(function($) {
    // Initialize select2
    if ($.fn.select2) {
        $('.arm-select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

    // Client selection -> Load appliances
    $('#client_select').on('change', function() {
        var clientId = $(this).val();
        var $applianceSelect = $('#appliance_id');
        
        $applianceSelect.html('<option value="">' + armL10n.selectAppliance + '</option>').trigger('change');
        
        if (!clientId) {
            return;
        }

        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_client_appliances',
                client_id: clientId,
                nonce: $('#arm_ajax_nonce').val()
            },
            beforeSend: function() {
                $applianceSelect.prop('disabled', true);
            },
            success: function(response) {
                if (response.success && response.data.appliances) {
                    var options = '<option value="">' + armL10n.selectAppliance + '</option>';
                    response.data.appliances.forEach(function(appliance) {
                        options += '<option value="' + appliance.id + '">' + 
                                  appliance.brand + ' ' + appliance.type + ' - ' + appliance.model + 
                                  '</option>';
                    });
                    $applianceSelect.html(options);
                } else {
                    console.error('ARM Error:', response);
                    alert(armL10n.errorLoadingAppliances);
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Ajax Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(armL10n.errorLoadingAppliances);
            },
            complete: function() {
                $applianceSelect.prop('disabled', false);
            }
        });
    });

    // View repair details
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var repairId = $(this).data('repair-id');
        var $modal = $('#repair-details-modal');
        var $content = $('#repair-details-content');
        
        // Show modal first
        $modal.show();
        $content.html('<div class="arm-loading">' + armL10n.loading + '</div>');
        
        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $content.html(response.data.html);
                } else {
                    console.error('ARM Error:', response);
                    $content.html('<div class="arm-error">' + armL10n.errorLoadingRepairDetails + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Ajax Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                $content.html('<div class="arm-error">' + armL10n.errorLoadingRepairDetails + '</div>');
            }
        });
    });

    // Close modal handler
    $(document).on('click', '.arm-modal-close, .arm-modal', function(e) {
        if (e.target === this) {
            $(this).closest('.arm-modal').hide();
        }
    });

    // ESC key handler for modals
    $(document).keyup(function(e) {
        if (e.key === 'Escape') {
            $('.arm-modal').hide();
        }
    });

    // Handle notes form submission
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $notesList = $form.closest('.arm-notes-container').find('.arm-notes-list');

        var noteText = $form.find('textarea[name="note"]').val().trim();
        if (!noteText) {
            alert(armL10n.pleaseEnterNote);
            return;
        }

        $submitButton.prop('disabled', true);

        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_add_note',
                repair_id: $form.find('input[name="repair_id"]').val(),
                note: noteText,
                is_public: $form.find('input[name="is_public"]').is(':checked'),
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.html) {
                        $notesList.html(response.data.html);
                        $form.find('textarea[name="note"]').val('');
                        $form.find('input[name="is_public"]').prop('checked', false);
                    } else {
                        console.error('ARM Error: Invalid response format', response);
                        alert(armL10n.errorAddingNote);
                    }
                } else {
                    console.error('ARM Error:', response);
                    alert(response.data.message || armL10n.errorAddingNote);
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Ajax Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(armL10n.errorAddingNote);
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });
});