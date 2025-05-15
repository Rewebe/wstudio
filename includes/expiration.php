<?php
if (!defined('ABSPATH')) exit;

/**
 * expiration.php
 *
 * Tilføjer udløbsdato til Kundegalleri CPT:
 * - Metabox for date picker (uden tid)
 * - Save post_meta kundegalleri_expiration_date
 * - Daglig cron-job for sletning af udløbne gallerier
 * - Frontend-meddelelse om udløbsdato (kun dato)
 */

// 1) Metabox: Vælg udløbsdato (date only)
add_action('add_meta_boxes', 'wstudio_add_expiration_metabox');
function wstudio_add_expiration_metabox() {
    add_meta_box(
        'kundegalleri_expiration_meta_box',
        __('Udløbsdato', 'text-domain'),
        'wstudio_expiration_metabox_callback',
        'kundegalleri',
        'side',
        'core'
    );
}

function wstudio_expiration_metabox_callback($post) {
    wp_nonce_field('wg_save_expiration_date', 'wg_expiration_nonce');
    $value = get_post_meta($post->ID, 'kundegalleri_expiration_date', true);
    // Gem kun datoen i inputfelt, intern tid sættes til 23:59 når gemt
    $date = '';
    if ($value) {
        $date = date('Y-m-d', strtotime($value));
    } else {
        $date = date('Y-m-d', current_time('timestamp'));
    }
    echo '<label for="kundegalleri_expiration_date">' . esc_html__('Vælg udløbsdato', 'text-domain') . '</label><br>';
    echo '<input type="date" id="kundegalleri_expiration_date" name="kundegalleri_expiration_date" value="' . esc_attr($date) . '" style="width:100%;" />';
}

// 2) Gem expiration_date
add_action('save_post', 'wstudio_save_expiration_date');
function wstudio_save_expiration_date($post_id) {
    if (!isset($_POST['wg_expiration_nonce']) || !wp_verify_nonce($_POST['wg_expiration_nonce'], 'wg_save_expiration_date')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (!empty($_POST['kundegalleri_expiration_date'])) {
        $raw = sanitize_text_field($_POST['kundegalleri_expiration_date']);
        // Sæt intern tid til 23:59 for hele dagen
        $gmt = date('Y-m-d H:i:s', strtotime($raw . ' 23:59'));
        update_post_meta($post_id, 'kundegalleri_expiration_date', $gmt);
    } else {
        delete_post_meta($post_id, 'kundegalleri_expiration_date');
    }
}

// 3) Planlæg cron-job på init
add_action('init', 'wstudio_schedule_expiration_cron');
function wstudio_schedule_expiration_cron() {
    if (!wp_next_scheduled('wstudio_check_expired_galleries_daily')) {
        $timestamp = strtotime('tomorrow midnight', current_time('timestamp'));
        wp_schedule_event($timestamp, 'daily', 'wstudio_check_expired_galleries_daily');
    }
}

// 4) Cron callback: slet udløbne gallerier
add_action('wstudio_check_expired_galleries_daily', 'wstudio_delete_expired_galleries');
function wstudio_delete_expired_galleries() {
    $today = current_time('Y-m-d H:i:s');
    $args  = [
        'post_type'      => 'kundegalleri',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'kundegalleri_expiration_date',
                'value'   => $today,
                'compare' => '<=',
                'type'    => 'DATETIME',
            ],
        ],
    ];
    $expired = get_posts($args);
    if ($expired) {
        foreach ($expired as $pid) {
            wp_delete_post($pid, true);
            $up  = wp_upload_dir();
            $dir = trailingslashit($up['basedir']) . "kundegalleri/{$pid}";
            wstudio_rrmdir($dir);
        }
    }
}

// 5) Helper: rekursiv mappe-sletning
function wstudio_rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
        $path = "{$dir}/{$file}";
        is_dir($path) ? wstudio_rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

// 6) Frontend: vis expiration notice før galleri (kun dato)
add_action('kundegalleri_before_gallery', 'wstudio_show_expiration_notice');
function wstudio_show_expiration_notice($post_id) {
    $expiration = get_post_meta($post_id, 'kundegalleri_expiration_date', true);
    if ($expiration && get_post_status($post_id) === 'publish') {
        $formatted = date_i18n('d-m-Y', strtotime($expiration));
        echo '<p class="wg-expiration">Dette galleri er tilgængeligt frem til d. ' . esc_html($formatted) . '</p>';
    }
}
