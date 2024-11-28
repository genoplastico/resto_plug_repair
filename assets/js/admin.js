jQuery(document).ready(function($) {
    // Initialize select2
    if ($.fn.select2) {
        $('.arm-select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

    // Debug logging function
    function logDebug(message, data = {}) {
        if (window.console && window.console.log) {
            console.log('ARM Debug:', message, data);
        }
    }

    // Form submission handler
    $('.arm-form').on('submit', function(e) {
        logDebug('Form submission started', {
            form: this.id || 'unnamed-form',
            action: this.action
        });

        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        
        // Validate required fields
        var isValid = true;
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('arm-error-field');
                logDebug('Required field empty', {
                    field: this.name,
                    id: this.id
                });
            } else {
                $(this).removeClass('arm-error-field');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert(armL10n.fillRequiredFields);
            return false;
        }

        // Disable submit button to prevent double submission
        $submitButton.prop('disabled', true);
        
        logDebug('Form validation passed, submitting', {
            formData: $form.serialize()
        });
    });

    // Client selection -> Load appliances
    $('#client_select').on('change', function() {
        var clientId = $(this).val();
        var $applianceSelect = $('#appliance_id');
        
        logDebug('Client selected', {
            clientId: clientId
        });
        
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
                logDebug('Loading appliances', {
                    clientId: clientId
                });
            },
            success: function(response) {
                logDebug('Appliances loaded', {
                    response: response
                });
                
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

    // Rest of your existing JavaScript code...
});