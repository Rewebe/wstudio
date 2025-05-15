<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_wstudio_ajax_upload', 'wstudio_ajax_upload');

function wstudio_ajax_upload() {
    check_ajax_referer('wstudio_secure_upload', 'nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id || !isset($_FILES['file'])) {
        wp_send_json_error(['message' => 'Ugyldig anmodning.']);
    }

    $file = $_FILES['file'];
    $filename = sanitize_file_name($file['name']);
    $upload_dir = wp_upload_dir();
    $target_dir = trailingslashit($upload_dir['basedir']) . "kundegalleri/{$post_id}/original/";
    $target_url = trailingslashit($upload_dir['baseurl']) . "kundegalleri/{$post_id}/original/";

    if (!file_exists($target_dir)) wp_mkdir_p($target_dir);

    $target_path = $target_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        wp_send_json_error(['message' => 'Kunne ikke flytte fil.']);
    }

    $storage_type = get_option('wstudio_storage_type', 'local');
    if ($storage_type === 'scaleway') {
        do_action('wstudio_upload_image', [
            'name' => $filename,
            'tmp_name' => $target_path,
            'type' => $file['type'],
            'size' => $file['size']
        ], $post_id);
    } else {
        do_action('wstudio_upload_image', [
            'name' => $filename,
            'tmp_name' => $target_path,
            'type' => $file['type'],
            'size' => $file['size']
        ], $post_id);
    }

    if (file_exists($target_path)) {
        unlink($target_path);
    }

    wp_send_json_success(['url' => $target_url . $filename]);
}
