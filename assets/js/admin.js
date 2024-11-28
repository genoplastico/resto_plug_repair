jQuery(document).ready(function($) {
    // Inicializar select2
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
                    alert(armL10n.errorLoadingAppliances);
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

    // Manejar envío de notas
    $(document).on('submit', '.arm-note-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $notesList = $form.closest('.arm-notes-container').find('.arm-notes-list');

        // Validar que haya texto en la nota
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
                console.log('ARM Note Response:', response);
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

    // ... resto del código JavaScript permanece igual ...
});