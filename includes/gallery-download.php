<?php
// includes/gallery-download.php

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Download ZIP med billeder
 */
add_action('wp_ajax_wstudio_download_zip', 'wstudio_download_zip_handler');
add_action('wp_ajax_nopriv_wstudio_download_zip', 'wstudio_download_zip_handler');

function wstudio_download_zip_handler() {
    $post_id = intval($_GET['gallery_id'] ?? 0);
    $type = sanitize_text_field($_GET['type'] ?? '');
    $uploads = wp_upload_dir();
    $basedir = trailingslashit($uploads['basedir']) . "kundegalleri/{$post_id}/";

    if (!$post_id || !in_array($type, ['web', 'original', 'both'])) {
        wp_die('Ugyldige parametre.', '', ['response' => 400]);
    }

    $subdirs = ($type === 'both') ? ['webversion', 'original'] : [($type === 'web' ? 'webversion' : 'original')];
    $files = [];

    foreach ($subdirs as $subdir) {
        $dir = $basedir . $subdir . '/';
        if (is_dir($dir)) {
            $files = array_merge($files, glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE));
        }
    }

    if (empty($files)) {
        wp_die('Ingen filer fundet.', '', ['response' => 404]);
    }

    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
        wp_die('Kunne ikke oprette ZIP-fil.', '', ['response' => 500]);
    }

    foreach ($files as $file) {
        $zip->addFile($file, basename(dirname($file)) . '/' . basename($file));
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=galleri_' . $post_id . '_' . $type . '.zip');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}
?>