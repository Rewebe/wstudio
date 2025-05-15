<?php
if (!defined('ABSPATH')) exit;

add_action('wstudio_upload_image', function($file, $post_id) {
    error_log('[wstudio] Lokal upload aktiv.');

    $filename = basename($file['name']);
    $tmp_path = $file['tmp_name'];

    if (!file_exists($tmp_path)) {
        error_log("[wstudio] Lokal upload – filen findes ikke: $tmp_path");
        return;
    }

    // Generér billedversioner
    $versions = wstudio_generate_image_versions($tmp_path, $filename, $post_id);

    // Målmappe
    $base_dir = wp_upload_dir()['basedir'] . "/kundegalleri/$post_id/";
    wp_mkdir_p($base_dir . 'original/');
    wp_mkdir_p($base_dir . 'web/');
    wp_mkdir_p($base_dir . 'webwm/');
    wp_mkdir_p($base_dir . 'thumb/');

    // Flyt original
    $original_dest = $base_dir . 'original/' . $filename;
    move_uploaded_file($tmp_path, $original_dest);
    error_log("[wstudio] Original flyttet til: $original_dest");

    // Gem versioner
    foreach ($versions['paths'] as $type => $path) {
        $dest = $base_dir . $type . '/' . $filename;
        copy($path, $dest);
        unlink($path);
        error_log("[wstudio] $type gemt lokalt: $dest");
    }
}, 10, 2);
