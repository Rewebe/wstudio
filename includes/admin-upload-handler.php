<?php
/**
 * This handler connects with core wstudio functionality.
 * Image resizing, watermarking, and Scaleway upload are handled via wupload functions.
 * This file only manages upload type and file meta registration.
 */
?>

<?php
// Stop if accessed directly
if (!defined('ABSPATH')) exit;
error_log('🧪 wstudio uploader kaldt');

// Upload handler logic
add_action('admin_post_wstudio_upload_images', 'wstudio_handle_image_upload');
function wstudio_handle_image_upload() {
    if (!current_user_can('upload_files')) {
        wp_die('Du har ikke adgang.');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $upload_type = sanitize_text_field($_POST['wstudio_upload_type'] ?? '');
    $set_name = sanitize_text_field($_POST['wstudio_set_name'] ?? '');

    if (!$post_id || empty($_FILES['wstudio_images'])) {
        wp_redirect(admin_url('edit.php?post_type=wstudio_gallery'));
        exit;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $files = $_FILES['wstudio_images'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $file_array = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i]
        ];

        error_log("📥 media_handle_sideload kaldes for: " . $file_array['name']);
        $attachment_id = media_handle_sideload($file_array, $post_id);
        error_log("📤 attachment_id returneret: " . print_r($attachment_id, true));
        error_log("🔍 Behandler fil: " . $file_array['name']);

        if (is_wp_error($attachment_id)) {
            error_log("❌ Upload-fejl: " . $attachment_id->get_error_message());
            continue;
        }

        // Herfra kalder vi evt. videre til wupload-billedbehandling
        error_log("🧠 Forbereder kald til wupload_process_image_from_attachment()");
        error_log("🧪 Parametre → ID: $attachment_id | Post: $post_id | Type: $upload_type | Set: $set_name");
        if (function_exists('wupload_process_image_from_attachment')) {
            wupload_process_image_from_attachment($attachment_id, $post_id, [
                'type' => $upload_type,
                'set'  => $set_name
            ]);
            error_log("✅ Kald til wupload_process_image_from_attachment() udført");
        }

        error_log("✅ Upload OK → ID: $attachment_id | Post: $post_id | Type: $upload_type | Set: $set_name");
    }

    update_post_meta($post_id, '_wstudio_upload_type', $upload_type);
    update_post_meta($post_id, '_wstudio_set_name', $set_name);

    wp_redirect(admin_url("post.php?post=$post_id&action=edit"));
    exit;
}