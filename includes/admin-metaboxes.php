<?php
add_action('add_meta_boxes', function () {
    add_meta_box(
        'wstudio_upload_box',
        'Upload billeder',
        'wstudio_render_upload_box',
        'wstudio_gallery',
        'normal',
        'default'
    );
});

function wstudio_render_upload_box($post) {
    echo '<p>Her kan du uploade billeder til dette galleri.</p>';
    echo '<input type="file" name="wstudio_upload[]" multiple />';
    echo '<p style="margin-top:8px;"><button type="button" class="button">Upload</button></p>';
}