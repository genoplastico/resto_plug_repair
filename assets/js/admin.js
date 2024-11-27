jQuery(document).ready(function($) {
    // Debug function
    function armDebug(message, data = {}) {
        if (typeof console !== 'undefined' && armL10n.debug) {
            console.log('[ARM Debug]', message, data);
            
            // Send debug info to server
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'arm_debug_log',
                    message: message,
                    context: data,
                    nonce: $('#arm_ajax_nonce').val()
                }
            });
        }
    }

    // Initialize select2 for dropdown menus
    if ($.fn.select2) {
        $('.arm-select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

    // Cliente seleccionado -> Cargar aparatos
    $('#client_select').on('change', function() {
        var clientId = $(this).val();
        var applianceSelect = $('#appliance_id');
        
        armDebug('Client selected', { clientId: clientId });
        
        // Reset y deshabilitar el select de aparatos
        applianceSelect.empty().prop('disabled', true);
        applianceSelect.append($('<option></option>').val('').text(armL10n.selectAppliance));
        
        if (!clientId) {
            applianceSelect.trigger('change').prop('disabled', false);
            return;
        }

        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'arm_get_client_appliances',
                client_id: clientId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                armDebug('Appliances loaded', { response: response });
                if (response.success && response.data) {
                    response.data.forEach(function(appliance) {
                        var text = appliance.brand + ' ' + appliance.type + ' - ' + appliance.model;
                        applianceSelect.append($('<option></option>')
                            .val(appliance.id)
                            .text(text));
                    });
                }
            },
            error: function(xhr, status, error) {
                armDebug('Error loading appliances', { xhr: xhr, status: status, error: error });
                console.error('Error loading appliances');
            },
            complete: function() {
                applianceSelect.prop('disabled', false).trigger('change');
            }
        });
    });

    // Ver detalles de reparación
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var repairId = $(this).data('repair-id');
        
        armDebug('View repair details clicked', { repairId: repairId });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                armDebug('Repair details response', { response: response });
                if (response.success) {
                    $('#repair-details-modal .arm-modal-content').html(response.data.html);
                    $('#repair-details-modal').fadeIn(300);
                }
            },
            error: function(xhr, status, error) {
                armDebug('Error loading repair details', { xhr: xhr, status: status, error: error });
            }
        });
    });

    // Ver historial de aparato
    $(document).on('click', '.view-appliance-history', function(e) {
        e.preventDefault();
        var applianceId = $(this).data('appliance-id');
        
        armDebug('View appliance history clicked', { applianceId: applianceId });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_appliance_history',
                appliance_id: applianceId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                armDebug('Appliance history response', { response: response });
                if (response.success) {
                    $('#appliance-history-modal .arm-modal-content').html(response.data.html);
                    $('#appliance-history-modal').fadeIn(300);
                }
            },
            error: function(xhr, status, error) {
                armDebug('Error loading appliance history', { xhr: xhr, status: status, error: error });
            }
        });
    });

    // Cerrar modales al hacer clic en el botón de cerrar
    $(document).on('click', '.arm-modal-close', function() {
        armDebug('Modal close button clicked');
        $(this).closest('.arm-modal').fadeOut(300);
    });

    // Cerrar modal al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
            armDebug('Modal background clicked');
            $('.arm-modal').fadeOut(300);
        }
    });

    // Prevenir que el clic dentro del modal lo cierre
    $(document).on('click', '.arm-modal-content', function(e) {
        e.stopPropagation();
    });

    // Tecla ESC para cerrar modales
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            armDebug('ESC key pressed');
            $('.arm-modal').fadeOut(300);
        }
    });

    // Agregar nota vía AJAX
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var noteInput = form.find('textarea[name="note"]');
        var notesList = form.closest('.arm-detail-section').find('.arm-notes-list');

        armDebug('Adding note', { formData: form.serialize() });

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
                armDebug('Note add response', { response: response });
                if (response.success) {
                    var noteHtml = '<div class="arm-note">' + response.data.note + '</div>';
                    notesList.prepend(noteHtml);
                    noteInput.val('');
                    notesList.scrollTop(0);
                    
                    var repairId = form.find('input[name="repair_id"]').val();
                    var mainNotesList = $('.repair-row-' + repairId + ' .arm-notes-list');
                    if (mainNotesList.length) {
                        mainNotesList.prepend(noteHtml);
                    }
                } else {
                    alert(response.data.message || armL10n.errorAddingNote);
                }
            },
            error: function(xhr, status, error) {
                armDebug('Error adding note', { xhr: xhr, status: status, error: error });
                alert(armL10n.errorAddingNote);
            }
        });
    });

    // Add status class to status cells
    $('.arm-status-cell').each(function() {
        var status = $(this).text().toLowerCase().replace(/\s+/g, '-');
        $(this).addClass('arm-status arm-status-' + status);
    });

    // Confirm status changes
    $('select[name="status"]').on('change', function() {
        if (!confirm(armL10n.confirmStatusChange)) {
            return false;
        }
    });

    // Format currency inputs
    $('input[name="cost"]').on('blur', function() {
        var value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });

    // Initialize tooltips
    if ($.fn.tooltip) {
        $('.arm-tooltip').tooltip();
    }

    // Handle form submission
    $('.arm-form').on('submit', function(e) {
        var requiredFields = $(this).find('[required]');
        var valid = true;

        requiredFields.each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('arm-error');
            } else {
                $(this).removeClass('arm-error');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert(armL10n.fillRequiredFields);
        }
    });

    // Copiar URL pública al portapapeles
    $('.copy-public-url').click(function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            alert(armL10n.publicUrlCopied);
        }).catch(function() {
            // Fallback para navegadores que no soportan clipboard API
            var temp = $("<input>");
            $("body").append(temp);
            temp.val(url).select();
            document.execCommand("copy");
            temp.remove();
            alert(armL10n.publicUrlCopied);
        });
    });
});