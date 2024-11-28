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

    // Ver detalles de reparación
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var repairId = $(this).data('repair-id');
        var modal = $('#repair-details-modal');
        var modalContent = $('#repair-details-content');
        
        $.ajax({
            url: armL10n.ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                nonce: $('#arm_ajax_nonce').val(),
                is_public: window.location.href.indexOf('arm_action=view_client_appliances') > -1
            },
            beforeSend: function() {
                modalContent.html('<div class="arm-loading">' + armL10n.loading + '</div>');
                modal.fadeIn(300);
            },
            success: function(response) {
                if (response.success && response.data && response.data.html) {
                    modalContent.html(response.data.html);
                } else {
                    modalContent.html('<div class="arm-error">' + armL10n.errorLoadingRepairDetails + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('ARM Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                modalContent.html('<div class="arm-error">' + armL10n.errorLoadingRepairDetails + '</div>');
            }
        });
    });

    // Copiar URL pública
    $('.copy-public-url').click(function() {
        var url = $(this).data('url');
        if (!url) {
            alert(armL10n.errorCopyingUrl);
            return;
        }
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function() {
                alert(armL10n.publicUrlCopied);
            }).catch(function() {
                fallbackCopyToClipboard(url);
            });
        } else {
            fallbackCopyToClipboard(url);
        }
    });

    function fallbackCopyToClipboard(text) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert(armL10n.publicUrlCopied);
        } catch (err) {
            console.error('ARM Error: Fallback clipboard copy failed', err);
            alert(armL10n.errorCopyingUrl);
        }
        document.body.removeChild(textArea);
    }

    // Modal handling
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