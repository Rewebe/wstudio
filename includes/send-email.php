<?php
// includes/send-email.php

if (!defined('ABSPATH')) exit;

// --- SEND KUNDE E-MAIL VIA AJAX ---
add_action('wp_ajax_send_customer_email', 'weigang_send_customer_email');

function weigang_send_customer_email() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Ingen adgang.');
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $email_subject = sanitize_text_field($_POST['email_subject'] ?? 'Dine billeder er klar!');
    $email_body = wp_kses_post($_POST['email_body'] ?? '');

    if (!$post_id || empty($email_body)) {
        wp_send_json_error('Ugyldige data.');
    }

    $to_email = get_post_meta($post_id, 'kunde_email', true);
    if (empty($to_email)) {
        wp_send_json_error('Ingen kunde-e-mail fundet.');
    }

    // Send e-mail
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent = wp_mail($to_email, $email_subject, nl2br($email_body), $headers);

    if ($sent) {
        // Log i post_meta
        update_post_meta($post_id, 'email_sent_at', current_time('mysql'));
        update_post_meta($post_id, 'email_sent_content', wp_strip_all_tags($email_body));

        // Log på serverfil
        $upload_dir = wp_upload_dir();
        $log_file = trailingslashit($upload_dir['basedir']) . 'kundegalleri-email-log.php';

        if (!file_exists($log_file)) {
            file_put_contents($log_file, "<?php // Silence is golden.\n\n");
        }

        $log_entry  = '[' . current_time('mysql') . '] E-mail sendt til ' . $to_email . ' (Galleri ID: ' . $post_id . ')' . PHP_EOL;
        $log_entry .= 'Emne: ' . $email_subject . PHP_EOL;
        $log_entry .= '---' . PHP_EOL . strip_tags($email_body) . PHP_EOL . '---' . PHP_EOL . PHP_EOL;

        file_put_contents($log_file, $log_entry, FILE_APPEND);

        wp_send_json_success('E-mail sendt succesfuldt!');
    } else {
        wp_send_json_error('E-mail kunne ikke sendes.');
    }
}
?>