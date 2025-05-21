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
    // Inkluder upload-formular fra wupload modulet
    if (function_exists('wupload_render_upload_form')) {
        wupload_render_upload_form($post->ID);
    } else {
        echo '<p>Uploadmodul ikke indlæst korrekt.</p>';
    }
}