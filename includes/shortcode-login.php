<?php
if (!defined('ABSPATH')) exit;

// === SHORTCODE: [kundegalleri_login] ===
add_shortcode('kundegalleri_login', function() {
    ob_start();
    ?>
    <div class="wstudio-login-wrapper">
        <form id="wstudio-login-form">
            <p>
                <label for="wstudio_email">E-mail:</label><br>
                <input type="email" name="wstudio_email" id="wstudio_email" required>
            </p>
            <p>
                <label for="wstudio_password">Adgangskode:</label><br>
                <input type="password" name="wstudio_password" id="wstudio_password" required>
            </p>
            <p>
                <button type="submit" class="button button-primary">Login</button>
            </p>
            <div id="wstudio-login-response" class="wstudio-login-error"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
});

// === ENQUEUE SCRIPTS & STYLES ===
add_action('wp_enqueue_scripts', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'kundegalleri_login')) {
        wp_enqueue_style('wstudio-login-style', plugin_dir_url(__FILE__) . '../assets/css/login.css', [], '1.0');
        wp_enqueue_script('wstudio-login', plugin_dir_url(__FILE__) . '../assets/js/kundegalleri-login.js', ['jquery'], '1.1', true);
        wp_localize_script('wstudio-login', 'wstudio_login_vars', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
});

// === AJAX LOGIN HANDLER ===
add_action('wp_ajax_wstudio_ajax_login', 'wstudio_ajax_login');
add_action('wp_ajax_nopriv_wstudio_ajax_login', 'wstudio_ajax_login');

function wstudio_ajax_login() {
    $email = sanitize_email($_POST['wstudio_email'] ?? '');
    $password = sanitize_text_field($_POST['wstudio_password'] ?? '');

    if (empty($email) || empty($password)) {
        wp_send_json_error('Udfyld både e-mail og adgangskode.');
        wp_die(); // <- vigtigt
    }

    $query = new WP_Query([
        'post_type' => 'kundegalleri',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'kunde_email',
                'value' => $email,
                'compare' => '='
            ],
            [
                'key' => 'kunde_password',
                'value' => $password,
                'compare' => '='
            ]
        ]
    ]);

    if ($query->have_posts()) {
        $galleri = $query->posts[0];
        wp_send_json_success([
            'goto' => get_permalink($galleri->ID)
        ]);
    } else {
        wp_send_json_error('Forkert e-mail eller adgangskode.');
    }

    wp_die(); // <- vigtig afslutning
}
?>