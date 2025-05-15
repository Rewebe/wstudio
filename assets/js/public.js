jQuery(document).ready(function($) {
    const dropzone = $('#dropzone');
    const fileInput = $('#file-input');

    dropzone.on('click', function() { fileInput.click(); });
    dropzone.on('dragover', function(e) { e.preventDefault(); dropzone.css('border-color', '#0073aa'); });
    dropzone.on('dragleave', function() { dropzone.css('border-color', '#ccc'); });
    dropzone.on('drop', function(e) {
        e.preventDefault();
        dropzone.css('border-color', '#ccc');
        handleFiles(e.originalEvent.dataTransfer.files);
    });

    fileInput.on('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (!files.length) {
            alert('Vælg mindst ét billede.');
            return;
        }
        for (let i = 0; i < files.length; i++) {
            uploadFile(files[i]);
        }
    }

    function uploadFile(file) {
        var formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'wstudio_ajax_upload');
        formData.append('nonce', wstudio_vars.nonce);
        formData.append('post_id', wstudio_vars.post_id);

        $.ajax({
            url: wstudio_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Vigtigt: Brug watermarked_url her
                    $('#uploaded-images').append('<img src="'+response.data.watermarked_url+'" style="max-width:100px;margin:5px;">');
                } else {
                    alert('Fejl: ' + response.data);
                }
            },
            error: function() {
                alert('Fejl ved upload.');
            }
        });
    }

    // AJAX-slettefunktion
    $(document).on('click', '.delete-image', function(e) {
        e.preventDefault();
        const button = $(this);
        const wrapper = button.closest('.image-wrapper');
        const fileName = wrapper.data('filename');

        if (confirm('Er du sikker på, at du vil slette dette billede?')) {
            $.ajax({
                url: wstudio_vars.ajax_url,
                method: 'POST',
                data: {
                    action: 'wk_delete_image',
                    nonce: wstudio_vars.nonce,
                    post_id: wstudio_vars.post_id,
                    file_name: fileName
                },
                success: function(response) {
                    if (response.success) {
                        wrapper.fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Fejl: ' + response.data);
                    }
                },
                error: function() {
                    alert('Fejl ved sletning.');
                }
            });
        }
    });
});