<?php
/*
Plugin Name: Weigang Kundegalleri
Description: Komplet galleri-plugin med AJAX upload, vandmærke, e-mail funktion, loginbeskyttelse, automatisk udløbsstyring, sikre billednavne og .htaccess-beskyttelse.
Version: 3.2
Author: Weigang
*/

if (!defined('ABSPATH')) exit;

// Inkluder nødvendige filer
require_once plugin_dir_path(__FILE__) . 'includes/register-posttype.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/gallery-acceptance.php';
require_once plugin_dir_path(__FILE__) . 'includes/gallery-download.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-login.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-gallery.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/image-watermark.php';
require_once plugin_dir_path(__FILE__) . 'includes/expiration.php';
require_once plugin_dir_path(__FILE__) . 'includes/opret-galleri.php';
require_once plugin_dir_path(__FILE__) . 'includes/send-email.php';

// Opret .htaccess ved aktivering
register_activation_hook(__FILE__, function() {
    // Opret upload-mappe og .htaccess
    $upload_dir = wp_upload_dir();
    $kundegalleri_dir = trailingslashit($upload_dir['basedir']) . 'kundegalleri';
    if (!file_exists($kundegalleri_dir)) {
        wp_mkdir_p($kundegalleri_dir);
    }
    $htaccess_file = trailingslashit($kundegalleri_dir) . '.htaccess';
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, "<FilesMatch \"\\.(php|php5|phtml|phar)\$\">\nDeny from all\n</FilesMatch>\n");
    }

    // Kopiér tema-filer
    $plugin_base = plugin_dir_path(__FILE__);
    $theme_dir = get_template_directory();

    // 1. single-kundegalleri.php
    $src_single = $plugin_base . 'theme-templates/single-kundegalleri.php';
    $dest_single = $theme_dir . '/single-kundegalleri.php';
    if (!file_exists($dest_single) && file_exists($src_single)) {
        copy($src_single, $dest_single);
    }

    // 2. template-parts/kundegalleri/topbar.php
    $src_topbar = $plugin_base . 'theme-templates/topbar.php';
    $dest_dir = $theme_dir . '/template-parts/kundegalleri';
    $dest_topbar = $dest_dir . '/topbar.php';
    if (!is_dir($dest_dir)) {
        wp_mkdir_p($dest_dir);
    }
    if (!file_exists($dest_topbar) && file_exists($src_topbar)) {
        copy($src_topbar, $dest_topbar);
    }
});

// Admin scripts (upload og email)
add_action('admin_enqueue_scripts', function($hook) {
    global $post_type, $post;
    if ('kundegalleri' !== $post_type) return;

    wp_enqueue_script('kundegalleri-upload', plugin_dir_url(__FILE__) . 'assets/js/public.js', ['jquery'], '2.3', true);
    wp_localize_script('kundegalleri-upload', 'kundegalleri_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('kundegalleri_secure_upload'),
        'post_id' => $post->ID ?? 0,
    ]);

    if ('post.php' === $hook) {
        wp_enqueue_script('kundegalleri-email', plugin_dir_url(__FILE__) . 'assets/js/kundegalleri-email.js', ['jquery'], '1.0', true);
        wp_localize_script('kundegalleri-email', 'kundegalleri_email_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
});

// Frontend scripts (galleri + login)
add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    
    wp_enqueue_style('lightgallery-css', plugin_dir_url(__FILE__) . 'assets/css/lightgallery.min.css', [], '2.7.2');
    wp_enqueue_script('lightgallery-js', plugin_dir_url(__FILE__) . 'assets/js/lightgallery.min.js', ['jquery'], '2.7.2', true);

    $css_file = plugin_dir_path(__FILE__) . 'assets/css/gallery.css';
    $version = file_exists($css_file) ? filemtime($css_file) : time();
    wp_enqueue_style('kundegalleri-gallery', plugin_dir_url(__FILE__) . 'assets/css/gallery.css', [], $version);
    wp_enqueue_script('kundegalleri-gallery', plugin_dir_url(__FILE__) . 'assets/js/gallery.js', ['jquery', 'lightgallery-js'], '1.3', true);

    // Disable højreklik
    wp_add_inline_script('kundegalleri-gallery', 'document.addEventListener("contextmenu",function(e){e.preventDefault();},false);');

    // Hvis login shortcode bruges
    if (is_page() && has_shortcode(get_post()->post_content, 'kundegalleri_login')) {
        wp_enqueue_script('kundegalleri-login-js', plugin_dir_url(__FILE__) . 'assets/js/kundegalleri-login.js', ['jquery'], '1.0', true);
        wp_localize_script('kundegalleri-login-js', 'kundegalleri_login_vars', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
});

// AJAX upload billeder
add_action('wp_ajax_kundegalleri_ajax_upload', function() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kundegalleri_secure_upload')) wp_send_json_error('Ugyldig nonce');
    if (!current_user_can('upload_files')) wp_send_json_error('Ingen adgang');

    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_send_json_error('Ugyldigt post-ID.');

    $file = $_FILES['file'] ?? null;
    if (!$file) wp_send_json_error('Ingen fil valgt');

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowed_types)) wp_send_json_error('Kun jpg, png eller gif tilladt.');

    preg_match('/(\d{3,})/', $file['name'], $matches);
    $number = !empty($matches[1]) ? $matches[1] : time();
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_id = substr(uniqid(), -5);
    $filename = 'weigang_' . $number . '-' . $unique_id . '.' . $extension;

    $upload_dir = wp_upload_dir();
    $base_dir = trailingslashit($upload_dir['basedir']) . "kundegalleri/{$post_id}/";
    foreach (['original', 'watermarked', 'webversion'] as $subdir) {
        wp_mkdir_p(trailingslashit($base_dir) . $subdir);
    }

    $original_path = trailingslashit($base_dir) . 'original/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $original_path)) {
        $settings = [
            'enabled' => empty(get_post_meta($post_id, '_kundegalleri_disable_watermark', true)) && get_option('kundegalleri_watermark_enabled', 'no') === 'yes',
            'opacity' => get_option('kundegalleri_watermark_opacity', 30),
            'position' => get_option('kundegalleri_watermark_position', 'center'),
            'watermark_text' => get_option('kundegalleri_watermark_text', ''),
        ];

        $watermarked_path = trailingslashit($base_dir) . 'watermarked/' . $filename;
        copy($original_path, $watermarked_path);

        $img = wp_get_image_editor($watermarked_path);
        if (!is_wp_error($img)) {
            $img->resize(1200, 1200, false);
            $img->set_quality(70);
            $img->save($watermarked_path);
            apply_watermark_to_image($watermarked_path, $settings, $post_id);
        }

        $web_path = trailingslashit($base_dir) . 'webversion/' . $filename;
        $web_img = wp_get_image_editor($original_path);
        if (!is_wp_error($web_img)) {
            $web_img->resize(1600, 1600, false);
            $web_img->set_quality(75);
            $web_img->save($web_path);
        }

        wp_send_json_success([
            'original_url' => trailingslashit($upload_dir['baseurl']) . "kundegalleri/{$post_id}/original/{$filename}",
            'watermarked_url' => trailingslashit($upload_dir['baseurl']) . "kundegalleri/{$post_id}/watermarked/{$filename}",
            'webversion_url' => trailingslashit($upload_dir['baseurl']) . "kundegalleri/{$post_id}/webversion/{$filename}",
        ]);
    }

    wp_send_json_error('Upload fejlede.');
});

// AJAX slet billeder
add_action('wp_ajax_kundegalleri_ajax_delete', function() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kundegalleri_secure_upload')) wp_send_json_error('Ugyldig nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Ingen adgang');

    $post_id = intval($_POST['post_id']);
    $file_name = sanitize_file_name($_POST['file_name']);
    $upload_dir = wp_upload_dir();

    $deleted = false;
    foreach (['original', 'watermarked', 'webversion'] as $subdir) {
        $path = trailingslashit($upload_dir['basedir']) . "kundegalleri/{$post_id}/{$subdir}/{$file_name}";
        if (file_exists($path)) {
            unlink($path);
            $deleted = true;
        }
    }

    $deleted ? wp_send_json_success('Slettet.') : wp_send_json_error('Fandt ikke fil.');
});

// Admin menu
add_action('admin_menu', function() {
    add_submenu_page('edit.php?post_type=kundegalleri', 'Dagens Bookinger', 'Dagens Bookinger', 'edit_posts', 'kundegalleri-dagens-bookinger', function() {
        include plugin_dir_path(__FILE__) . 'includes/admin-today-bookings.php';
    });
    add_submenu_page(null, 'Opret Galleri', 'Opret Galleri', 'edit_posts', 'opret-galleri', 'kundegalleri_opret_page_handler');
});
function kundegalleri_opret_page_handler() {
    include plugin_dir_path(__FILE__) . 'includes/opret-galleri.php';
}

// Automatisk expiration ved oprettelse
add_action('save_post_kundegalleri', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!get_post_meta($post_id, '_kundegalleri_expiration', true)) {
        update_post_meta($post_id, '_kundegalleri_expiration', date('Y-m-d', strtotime('+15 days')));
    }
});

// Flyt udløbne gallerier til Trash
add_action('init', function() {
    $today = date('Y-m-d');
    $expired = get_posts([
        'post_type' => 'kundegalleri',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [[
            'key' => '_kundegalleri_expiration',
            'value' => $today,
            'compare' => '<=',
            'type' => 'DATE'
        ]]
    ]);

    foreach ($expired as $post) {
        wp_trash_post($post->ID);
    }
});
?>