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
            }
        });
    });

    // Agregar nota vía AJAX
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
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
                    // Crear el HTML para la nueva nota
                    var noteHtml = '<div class="arm-note">' + response.data.note + '</div>';
                    
                    // Agregar la nota al listado
                    notesList.append(noteHtml);
                    noteInput.val('');
                    
                    // Scroll al final de la lista de notas
                    notesList.scrollTop(notesList[0].scrollHeight);
                    
                    // Actualizar también la lista de notas en la tabla principal si existe
                    var repairId = form.find('input[name="repair_id"]').val();
                    var mainNotesList = $('.repair-row-' + repairId + ' .arm-notes-list');
                    if (mainNotesList.length) {
                        mainNotesList.append(noteHtml);
                    }
                } else {
                    alert(response.data.message || armL10n.errorAddingNote);
                }
            },
            error: function() {
                alert(armL10n.errorAddingNote);
            }
        });
    });

    // Mostrar detalles de reparación
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

    // Mostrar historial de aparato
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

    // Cerrar modales al hacer clic en el botón de cerrar
    $(document).on('click', '.arm-modal-close', function() {
        $(this).closest('.arm-modal').fadeOut(300);
    });

    // Cerrar modal al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
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
            $('.arm-modal').fadeOut(300);
        }
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
});