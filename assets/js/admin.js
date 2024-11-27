jQuery(document).ready(function($) {
    // Initialize select2 for dropdown menus
    if ($.fn.select2) {
        $('.arm-select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

    // Cliente seleccionado -> Cargar aparatos
    $('#client_select').on('change', function() {
        try {
            var clientId = $(this).val();
            if (!clientId) {
                $('#appliance_id').html('<option value="">' + armL10n.selectAppliance + '</option>').trigger('change');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'arm_get_client_appliances',
                    client_id: clientId,
                    nonce: $('#arm_ajax_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">' + armL10n.selectAppliance + '</option>';
                        response.data.forEach(function(appliance) {
                            options += '<option value="' + appliance.id + '">' + 
                                      appliance.brand + ' ' + appliance.type + ' - ' + appliance.model + 
                                      '</option>';
                        });
                        $('#appliance_id').html(options).trigger('change');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ARM Error: Error loading appliances', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    window.armShowError(armL10n.errorLoadingAppliances);
                }
            });
        } catch (error) {
            console.error('ARM Error: Client selection error', error);
            window.armShowError(armL10n.generalError);
        }
    });

    // Ver detalles de reparación
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        try {
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
                        window.armModalManager.openModal('repair-details-modal');
                    } else {
                        throw new Error(response.data.message || 'Error desconocido');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ARM Error: Error loading repair details', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    window.armShowError(armL10n.errorLoadingRepairDetails);
                }
            });
        } catch (error) {
            console.error('ARM Error: View repair details error', error);
            window.armShowError(armL10n.generalError);
        }
    });

    // Ver historial de aparato
    $(document).on('click', '.view-appliance-history', function(e) {
        e.preventDefault();
        try {
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
                        window.armModalManager.openModal('appliance-history-modal');
                    } else {
                        throw new Error(response.data.message || 'Error desconocido');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ARM Error: Error loading appliance history', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    window.armShowError(armL10n.errorLoadingHistory);
                }
            });
        } catch (error) {
            console.error('ARM Error: View appliance history error', error);
            window.armShowError(armL10n.generalError);
        }
    });

    // Agregar nota vía AJAX
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
        try {
            var form = $(this);
            var noteInput = form.find('textarea[name="note"]');
            var notesList = form.closest('.arm-detail-section').find('.arm-notes-list');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'arm_add_note_ajax',
                    repair_id: form.find('input[name="repair_id"]').val(),
                    note: noteInput.val(),
                    nonce: $('#arm_ajax_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        var noteHtml = '<div class="arm-note">' + response.data.note + '</div>';
                        notesList.append(noteHtml);
                        noteInput.val('');
                        notesList.scrollTop(notesList[0].scrollHeight);
                        
                        var repairId = form.find('input[name="repair_id"]').val();
                        var mainNotesList = $('.repair-row-' + repairId + ' .arm-notes-list');
                        if (mainNotesList.length) {
                            mainNotesList.append(noteHtml);
                        }
                    } else {
                        throw new Error(response.data.message || armL10n.errorAddingNote);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ARM Error: Error adding note', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    window.armShowError(armL10n.errorAddingNote);
                }
            });
        } catch (error) {
            console.error('ARM Error: Add note error', error);
            window.armShowError(armL10n.generalError);
        }
    });

    // Copiar URL pública
    $('.copy-public-url').click(function() {
        try {
            var url = $(this).data('url');
            navigator.clipboard.writeText(url).then(function() {
                window.armShowError(armL10n.publicUrlCopied);
            }).catch(function(err) {
                // Fallback para navegadores que no soportan clipboard API
                var temp = $("<input>");
                $("body").append(temp);
                temp.val(url).select();
                document.execCommand("copy");
                temp.remove();
                window.armShowError(armL10n.publicUrlCopied);
            });
        } catch (error) {
            console.error('ARM Error: Copy URL error', error);
            window.armShowError(armL10n.generalError);
        }
    });
});