<?php
// Indlæs funktioner til vandmærke og upload, hvis de ikke allerede er defineret
if (!function_exists('apply_watermark_to_image')) {
    require_once plugin_dir_path(__FILE__) . 'functions/image-watermark.php';
}

if (!function_exists('wupload_process_image_from_attachment')) {
    require_once plugin_dir_path(__FILE__) . 'functions/uploader.php';
}

// Inkluder formular hvis ikke allerede defineret
if (!function_exists('wupload_render_upload_form')) {
    require_once plugin_dir_path(__FILE__) . 'functions/upload-form.php';
    error_log('🧩 upload-form.php er blevet inkluderet');
    if (!function_exists('wupload_render_upload_form')) {
        error_log('❌ wupload_render_upload_form findes IKKE');
    } else {
        error_log('✅ wupload_render_upload_form ER defineret');
    }
}

// Debug-log for at sikre init.php er aktiv
add_action('init', function () {
    error_log('✅ [wupload] init.php er nu aktiv og funktioner er indlæst.');
});