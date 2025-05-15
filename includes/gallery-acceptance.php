<?php
// includes/gallery-acceptance.php

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Godkend billeder (Approve gallery)
 */
add_action('wp_ajax_wstudio_approve_gallery', 'wstudio_approve_gallery_handler');
add_action('wp_ajax_nopriv_wstudio_approve_gallery', 'wstudio_approve_gallery_handler');

function wstudio_approve_gallery_handler() {
    // Sikkerhedstjek
    if (empty($_POST['gallery_id']) || empty($_POST['security'])) {
        wp_send_json_error('Manglende data.');
    }

    if (!check_ajax_referer('wg_approve_nonce', 'security', false)) {
        wp_send_json_error('Sikkerhedsfejl.');
    }

    $post_id = intval($_POST['gallery_id']);

    if (!$post_id || get_post_type($post_id) !== 'kundegalleri') {
        wp_send_json_error('Ugyldigt galleri.');
    }

    // Gem accepttidspunkt
    $timestamp = current_time('mysql');
    update_post_meta($post_id, 'wg_accepted_time', $timestamp);

    // Returner success + pænt format
    wp_send_json_success([
        'accepted_time' => date_i18n('d-m-Y H:i', strtotime($timestamp))
    ]);
}
?>