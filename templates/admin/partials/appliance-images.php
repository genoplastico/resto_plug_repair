<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-appliance-images">
    <div class="arm-images-grid">
        <?php foreach ($images as $image): ?>
            <div class="arm-image-item" data-id="<?php echo esc_attr($image['id']); ?>">
                <img src="<?php echo esc_url($image['url']); ?>" 
                     data-full="<?php echo esc_url($image['full_url']); ?>"
                     alt="<?php esc_attr_e('Appliance photo', 'appliance-repair-manager'); ?>">
                <button type="button" class="arm-delete-image" title="<?php esc_attr_e('Delete image', 'appliance-repair-manager'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="arm-upload-section">
        <input type="file" id="arm-image-upload" accept="image/*" style="display: none;">
        <button type="button" class="button" id="arm-upload-trigger">
            <?php _e('Add Photos', 'appliance-repair-manager'); ?>
        </button>
        <div class="arm-upload-progress" style="display: none;">
            <div class="arm-progress-bar"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const applianceId = <?php echo esc_js($post->ID); ?>;
    
    $('#arm-upload-trigger').click(function() {
        $('#arm-image-upload').click();
    });

    $('#arm-image-upload').change(function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'arm_upload_appliance_image');
        formData.append('nonce', $('#arm_ajax_nonce').val());
        formData.append('appliance_id', applianceId);
        formData.append('image', file);

        $('.arm-upload-progress').show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        $('.arm-progress-bar').css('width', percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    const imageHtml = `
                        <div class="arm-image-item" data-id="${response.data.id}">
                            <img src="${response.data.url}" data-full="${response.data.full_url}" 
                                 alt="<?php esc_attr_e('Appliance photo', 'appliance-repair-manager'); ?>">
                            <button type="button" class="arm-delete-image" 
                                    title="<?php esc_attr_e('Delete image', 'appliance-repair-manager'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    `;
                    $('.arm-images-grid').append(imageHtml);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php esc_attr_e('Error uploading image', 'appliance-repair-manager'); ?>');
            },
            complete: function() {
                $('.arm-upload-progress').hide();
                $('.arm-progress-bar').css('width', '0%');
                $('#arm-image-upload').val('');
            }
        });
    });

    $(document).on('click', '.arm-delete-image', function() {
        const item = $(this).closest('.arm-image-item');
        const imageId = item.data('id');

        if (confirm('<?php esc_attr_e('Are you sure you want to delete this image?', 'appliance-repair-manager'); ?>')) {
            $.post(ajaxurl, {
                action: 'arm_delete_appliance_image',
                nonce: $('#arm_ajax_nonce').val(),
                appliance_id: applianceId,
                attachment_id: imageId
            }, function(response) {
                if (response.success) {
                    item.remove();
                }
            });
        }
    });

    // Image preview modal
    $(document).on('click', '.arm-image-item img', function() {
        const fullUrl = $(this).data('full');
        const modal = $(`
            <div class="arm-modal">
                <div class="arm-modal-content">
                    <span class="arm-modal-close">&times;</span>
                    <img src="${fullUrl}" style="max-width: 100%; height: auto;">
                </div>
            </div>
        `).appendTo('body');

        modal.show();

        modal.find('.arm-modal-close').click(function() {
            modal.remove();
        });

        $(window).click(function(e) {
            if ($(e.target).hasClass('arm-modal')) {
                modal.remove();
            }
        });
    });
});
</script>

<style>
.arm-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.arm-image-item {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.arm-image-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    cursor: pointer;
}

.arm-delete-image {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0,0,0,0.5);
    border: none;
    color: #fff;
    padding: 5px;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
}

.arm-image-item:hover .arm-delete-image {
    opacity: 1;
}

.arm-upload-progress {
    margin-top: 10px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.arm-progress-bar {
    height: 5px;
    background: #2271b1;
    width: 0;
    transition: width 0.3s;
}

.arm-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
}

.arm-modal-content {
    position: relative;
    margin: auto;
    padding: 20px;
    max-width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.arm-modal-close {
    position: absolute;
    right: 25px;
    top: 10px;
    color: #f1f1f1;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
}
</style>