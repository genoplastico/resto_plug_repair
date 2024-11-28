jQuery(document).ready(function($) {
    // Debug logging function
    function logDebug(message, data = {}) {
        if (window.console && window.console.log) {
            console.log('ARM Debug:', message, data);
        }
    }

    // Initialize select2
    if ($.fn.select2) {
        $('.arm-select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

    // View repair details handler
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var repairId = $(this).data('repair-id');
        var $modal = $('#repair-details-modal');
        var $content = $('#repair-details-content');
        
        logDebug('Opening repair details', { repairId: repairId });
        
        $content.html('<div class="arm-loading">' + armL10n.loading + '</div>');
        $modal.show();

        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                logDebug('Repair details loaded', { response: response });
                
                if (response.success && response.data.html) {
                    $content.html(response.data.html);
                } else {
                    $content.html('<div class="arm-error">' + armL10n.errorLoadingRepairDetails + '</div>');
                    console.error('ARM Error:', response);
                }
            },
            error: function(xhr, status, error) {
                logDebug('Error loading repair details', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                $content.html('<div class="arm-error">' + armL10n.errorLoadingRepairDetails + '</div>');
            }
        });
    });

    // Handle note form submission
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $notesList = $form.closest('.arm-detail-section').find('.arm-notes-list');
        
        // Validate note content
        var noteText = $form.find('textarea[name="note"]').val().trim();
        if (!noteText) {
            alert(armL10n.pleaseEnterNote);
            return;
        }

        logDebug('Submitting note', {
            repairId: $form.find('input[name="repair_id"]').val(),
            isPublic: $form.find('input[name="is_public"]').is(':checked')
        });

        $submitButton.prop('disabled', true);

        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_add_note',
                repair_id: $form.find('input[name="repair_id"]').val(),
                note: noteText,
                is_public: $form.find('input[name="is_public"]').is(':checked'),
                nonce: $form.find('input[name="note_nonce"]').val()
            },
            success: function(response) {
                logDebug('Note submission response', { response: response });
                
                if (response.success) {
                    $notesList.html(response.data.html);
                    $form.find('textarea[name="note"]').val('');
                    $form.find('input[name="is_public"]').prop('checked', false);
                } else {
                    alert(response.data.message || armL10n.errorAddingNote);
                }
            },
            error: function(xhr, status, error) {
                logDebug('Error adding note', {
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

    // Delete note handler
    $(document).on('click', '.arm-delete-note', function(e) {
        e.preventDefault();
        
        if (!confirm(armL10n.confirmDeleteNote)) {
            return;
        }

        var $button = $(this);
        var $note = $button.closest('.arm-note');
        var noteId = $note.data('note-id');
        var $notesList = $note.closest('.arm-notes-list');

        logDebug('Deleting note', { noteId: noteId });

        $button.prop('disabled', true);

        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_delete_note',
                note_id: noteId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                logDebug('Note deletion response', { response: response });
                
                if (response.success) {
                    $notesList.html(response.data.html);
                } else {
                    alert(response.data.message || armL10n.errorDeletingNote);
                    $button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                logDebug('Error deleting note', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(armL10n.errorDeletingNote);
                $button.prop('disabled', false);
            }
        });
    });

    // Client selection -> Load appliances
    $('#client_select').on('change', function() {
        var clientId = $(this).val();
        var $applianceSelect = $('#appliance_id');
        
        logDebug('Client selected', { clientId: clientId });
        
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
                logDebug('Appliances loaded', { response: response });
                
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
                logDebug('Error loading appliances', {
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
});