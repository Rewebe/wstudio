<?php
if (!defined('ABSPATH')) exit;

// === ENQUEUE CSS & JS ===
add_action('wp_enqueue_scripts', function() {
    if (!is_singular()) return;
    global $post;
    if (!is_a($post, 'WP_Post')) return;
    if (!has_shortcode($post->post_content, 'kundegalleri') && get_post_type() !== 'kundegalleri') return;

    $base = plugin_dir_url(__FILE__) . '../assets/';
    $path = plugin_dir_path(__FILE__) . '../assets/';

    wp_enqueue_style('wg-approval-style', $base . 'css/approval.css', [], filemtime($path . 'css/approval.css'));
    wp_enqueue_style('kundegalleri-gallery', $base . 'css/gallery.css', [], filemtime($path . 'css/gallery.css'));
    wp_enqueue_style('lightgallery-css', 'https://cdn.jsdelivr.net/npm/lightgallery/dist/css/lightgallery.min.css', [], '2.7.1');

    wp_enqueue_script('lightgallery-js', 'https://cdn.jsdelivr.net/npm/lightgallery/dist/js/lightgallery.min.js', ['jquery'], '2.7.1', true);
    wp_enqueue_script('wg-approval-ui', $base . 'js/approval-ui.js', ['jquery'], filemtime($path . 'js/approval-ui.js'), true);
    wp_enqueue_script('wg-approval-actions', $base . 'js/approval-actions.js', ['jquery', 'wg-approval-ui'], filemtime($path . 'js/approval-actions.js'), true);

    wp_add_inline_script('lightgallery-js', "jQuery(function(){ jQuery('#kundegalleri-container').lightGallery({ selector: 'a.gallery-lightbox', download: false }); });");

    wp_localize_script('wg-approval-actions', 'wgAjax', [
        'ajax_url'   => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('wg_approve_nonce'),
        'gallery_id' => isset($post->ID) ? intval($post->ID) : 0,
        'labels'     => [
            'approve_button' => get_option('wg_approval_button_label', 'Accepter billederne'),
            'processing'     => 'Indlæser...'
        ]
    ]);
});

// === SHORTCODE: [kundegalleri] ===
add_shortcode('kundegalleri', function($atts) {
    if (!current_user_can('manage_options') && empty($_COOKIE['kundegalleri_loggedin'])) {
        // Forsøg at finde en side med [kundegalleri_login]
        $pages = get_pages();
        foreach ($pages as $page) {
            if (has_shortcode($page->post_content, 'kundegalleri_login')) {
                wp_redirect(get_permalink($page->ID));
                exit;
            }
        }

        // Fallback redirect
        wp_redirect(home_url());
        exit;
    }

    $atts = shortcode_atts(['id' => 0], $atts, 'kundegalleri');
    $post_id = $atts['id'] ? intval($atts['id']) : get_the_ID();
    if (!$post_id) return '<p>Ingen gyldig galleri-ID.</p>';

    $uploads  = wp_upload_dir();
    $baseurl  = trailingslashit($uploads['baseurl']) . "kundegalleri/{$post_id}/watermarked/";
    $weburl   = trailingslashit($uploads['baseurl']) . "kundegalleri/{$post_id}/webversion/";
    $origurl  = trailingslashit($uploads['baseurl']) . "kundegalleri/{$post_id}/original/";
    $basedir  = trailingslashit($uploads['basedir']) . "kundegalleri/{$post_id}/watermarked/";
    $accepted = get_post_meta($post_id, 'wg_accepted_time', true);

    ob_start();
    ?>

    <!-- Infotekst -->
    <div class="gallery-info-text">
        <?php echo wp_kses_post(get_option('wg_approval_text', 'Når du har godkendt billederne, vil du få mulighed for at downloade dem.')); ?>
    </div>

    <!-- Ny wrapper for topbar og galleri -->
    <div class="gallery-body-wrapper">

        <!-- Topbar (template-part) -->
        <?php
        get_template_part('template-parts/kundegalleri/topbar', null, [
            'post_id'  => $post_id,
            'accepted' => $accepted
        ]);
        ?>

        <!-- Galleri -->
        <div id="kundegalleri-container" class="lightgallery kundegalleri-wrapper">
            <?php
            if (is_dir($basedir)) {
                $files = glob($basedir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                foreach ($files as $file_path) {
                    $filename = basename($file_path);
                    $url = $baseurl . $filename;
                    $web_url = $weburl . $filename;
                    $orig_url = $origurl . $filename;
                    ?>
                    <div class="gallery-item">
                        <a class="gallery-lightbox" href="<?php echo esc_url($url); ?>">
                            <img src="<?php echo esc_url($url); ?>" alt="">
                        </a>
                        <div class="image-footer">
                            <div class="download-buttons">
                                <a class="image-download<?php echo $accepted ? '' : ' disabled'; ?>"
                                   href="<?php echo esc_url($web_url); ?>" download <?php if (!$accepted) echo 'aria-disabled="true" tabindex="-1"'; ?>>
                                    <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/images/download.png'; ?>" alt="Download"> Web
                                </a>
                                <a class="image-download<?php echo $accepted ? '' : ' disabled'; ?>"
                                   href="<?php echo esc_url($orig_url); ?>" download <?php if (!$accepted) echo 'aria-disabled="true" tabindex="-1"'; ?>>
                                    <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/images/download.png'; ?>" alt="Download"> Original
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
});