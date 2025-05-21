<?php
/*
Plugin Name: Wupload
Description: Upload-plugin med understøttelse for Scaleway og lokal komprimering.
Version: 0.1
Author: René Weigang Beck
*/

error_log('✅ wupload loader: includes/uploader.php bliver forsøgt loaded');

// Autoload Composer dependencies if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Inkluder moduler
require_once __DIR__ . '/includes/uploader.php';
require_once __DIR__ . '/includes/scaleway.php';
require_once __DIR__ . '/includes/image-watermark.php';
require_once __DIR__ . '/includes/settings.php';

// Midlertidig test via URL
add_action('init', function () {
    if (!isset($_GET['wupload_test'])) return;

    $slug = 'testgallery';
    $test_file = __DIR__ . '/assets/test.jpg';
    $filename = basename($test_file);

    $result = wuploade_handle_upload($test_file, $filename, $slug);

    if ($result) {
        echo '<pre>' . print_r($result, true) . '</pre>';
    } else {
        echo 'Upload fejlede.';
    }
    exit;
});
