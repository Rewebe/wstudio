jQuery(document).ready(function($) {
    let mediaUploader;

    $('#upload_image_button').click(function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Vælg billede til vandmærke',
            button: {
                text: 'Vælg billede'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#kundegalleri_watermark_image').val(attachment.url);
            $('#watermark-image-preview').html('<img src="'+attachment.url+'" style="max-width:150px;height:auto;">');
        });

        mediaUploader.open();
    });
});