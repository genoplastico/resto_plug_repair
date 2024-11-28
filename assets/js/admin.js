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
            url: armL10n.ajaxurl,
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
                } else {
                    console.error('ARM Error:', response);
                    alert(response.data.message || armL10n.errorLoadingAppliances);
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(armL10n.errorLoadingAppliances);
            }
        });
    });

    // Ver detalles de reparación
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var repairId = $(this).data('repair-id');
        var modal = $('#repair-details-modal');
        
        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#repair-details-content').html(response.data.html);
                    modal.fadeIn(300);
                } else {
                    console.error('ARM Error:', response);
                    alert(response.data.message || armL10n.errorLoadingRepairDetails);
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(armL10n.errorLoadingRepairDetails);
            }
        });
    });

    // Agregar nota vía AJAX
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var noteInput = form.find('textarea[name="note"]');
        var notesList = form.closest('.arm-detail-section').find('.arm-notes-list');
        var isPublic = form.find('input[name="is_public"]').is(':checked') ? '1' : '0';

        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_add_note_ajax',
                repair_id: form.find('input[name="repair_id"]').val(),
                note: noteInput.val(),
                is_public: isPublic,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    if (notesList.find('.arm-note').length === 0) {
                        notesList.empty(); // Remove "No notes available" message
                    }
                    notesList.prepend(response.data.note_html);
                    noteInput.val('');
                    form.find('input[name="is_public"]').prop('checked', false);
                } else {
                    console.error('ARM Error:', response);
                    alert(response.data.message || armL10n.errorAddingNote);
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(armL10n.errorAddingNote);
            }
        });
    });

    // Cerrar modales
    $(document).on('click', '.arm-modal-close', function() {
        $(this).closest('.arm-modal').fadeOut(300);
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
            $('.arm-modal').fadeOut(300);
        }
    });

    // Copiar URL pública
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