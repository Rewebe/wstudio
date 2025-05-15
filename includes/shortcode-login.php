<?php
if (!defined('ABSPATH')) exit;

// === SHORTCODE: [kundegalleri_login] ===
add_shortcode('kundegalleri_login', function() {
    ob_start();
    ?>
    <div class="kundegalleri-login-wrapper">
        <form id="kundegalleri-login-form">
            <p>
                <label for="kunde_email">E-mail:</label><br>
                <input type="email" name="kunde_email" id="kunde_email" required>
            </p>
            <p>
                <label for="kunde_password">Adgangskode:</label><br>
                <input type="password" name="kunde_password" id="kunde_password" required>
            </p>
            <p>
                <button type="submit" class="button button-primary">Login</button>
            </p>
            <div id="kundegalleri-login-response" class="kundegalleri-login-error"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
});

// === ENQUEUE SCRIPTS & STYLES ===
add_action('wp_enqueue_scripts', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'kundegalleri_login')) {
        wp_enqueue_style('kundegalleri-login-style', plugin_dir_url(__FILE__) . '../assets/css/login.css', [], '1.0');
        wp_enqueue_script('kundegalleri-login', plugin_dir_url(__FILE__) . '../assets/js/kundegalleri-login.js', ['jquery'], '1.1', true);
        wp_localize_script('kundegalleri-login', 'kundegalleri_email_vars', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
});

// === AJAX LOGIN HANDLER ===
add_action('wp_ajax_kundegalleri_ajax_login', 'kundegalleri_ajax_login');
add_action('wp_ajax_nopriv_kundegalleri_ajax_login', 'kundegalleri_ajax_login');

function kundegalleri_ajax_login() {
    $email = sanitize_email($_POST['kunde_email'] ?? '');
    $password = sanitize_text_field($_POST['kunde_password'] ?? '');

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