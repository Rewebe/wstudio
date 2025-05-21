<?php
// Watermark Settings Page for wupload plugin

// Register settings
function wupload_register_watermark_settings() {
    register_setting('wupload_watermark_options', 'wupload_watermark_active');
    register_setting('wupload_watermark_options', 'wupload_watermark_image');
    register_setting('wupload_watermark_options', 'wupload_watermark_opacity');
    register_setting('wupload_watermark_options', 'wupload_watermark_rotation');
    register_setting('wupload_watermark_options', 'wupload_watermark_count');
    register_setting('wupload_watermark_options', 'wupload_watermark_fallback_text');
    register_setting('wupload_watermark_options', 'wupload_watermark_position_text');
    register_setting('wupload_watermark_options', 'wupload_watermark_position_image');
    register_setting('wupload_watermark_options', 'wupload_watermark_font_size');
    register_setting('wupload_watermark_options', 'wupload_watermark_scale');
    // Scaleway API settings
    register_setting('wupload_watermark_options', 'wupload_scaleway_access_key');
    register_setting('wupload_watermark_options', 'wupload_scaleway_secret_key');
    register_setting('wupload_watermark_options', 'wupload_scaleway_bucket');
    register_setting('wupload_watermark_options', 'wupload_scaleway_region');
    register_setting('wupload_watermark_options', 'wupload_local_only');
}
add_action('admin_init', 'wupload_register_watermark_settings');

// Add settings page    
function wupload_add_watermark_settings_page() {
    add_menu_page(
        'Watermark Settings',
        'Wupload',
        'manage_options',
        'wupload-watermark-settings',
        'wupload_render_watermark_settings_page',
        'dashicons-format-image',
        80
    );
}
add_action('admin_menu', 'wupload_add_watermark_settings_page');

// Helper: List watermark images in assets/watermark/
function wupload_list_watermark_images() {
    $base = plugin_dir_path(dirname(__FILE__)); // go up from /includes/
    $dir = $base . 'assets/watermark/';
    $url = plugin_dir_url(dirname(__FILE__)) . 'assets/watermark/';

    $images = array();
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), array('png', 'jpg', 'jpeg', 'gif', 'svg'))) {
                $images[] = array(
                    'file' => $file,
                    'url' => $url . $file
                );
            }
        }
    }
    return $images;
}

// Render settings page
function wupload_render_watermark_settings_page() {
    $active = get_option('wupload_watermark_active', 0);
    $selected_image = get_option('wupload_watermark_image', '');
    $opacity = get_option('wupload_watermark_opacity', 50);
    $rotation = get_option('wupload_watermark_rotation', 0);
    $count = get_option('wupload_watermark_count', 1);
    $fallback_text = get_option('wupload_watermark_fallback_text', '');
    $position_text = get_option('wupload_watermark_position_text', 'center');
    $position_image = get_option('wupload_watermark_position_image', 'center');
    $font_size = get_option('wupload_watermark_font_size', 'medium');
    $scale = get_option('wupload_watermark_scale', 100);
    $images = wupload_list_watermark_images();
    ?>
    <div class="wrap">
        <h1>Watermark Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wupload_watermark_options'); ?>
            <table class="form-table" role="presentation">
                <tr valign="top">
                    <th scope="row">Activate Watermark</th>
                    <td>
                        <input type="checkbox" name="wupload_watermark_active" value="1" <?php checked(1, $active); ?> />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Select Watermark Image</th>
                    <td>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <?php foreach ($images as $img): ?>
                                <label style="display:inline-block;text-align:center;">
                                    <input type="radio" name="wupload_watermark_image" value="<?php echo esc_attr($img['file']); ?>" <?php checked($selected_image, $img['file']); ?> />
                                    <br>
                                    <img src="<?php echo esc_url($img['url']); ?>" alt="" style="width:60px;height:auto;border:1px solid #ccc;padding:2px;background:#fff;">
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (empty($images)): ?>
                            <p><em>No watermark images found in <code>assets/watermark/</code>.</em></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Opacity</th>
                    <td>
                        <input type="range" name="wupload_watermark_opacity" min="0" max="100" value="<?php echo esc_attr($opacity); ?>" oninput="this.nextElementSibling.value = this.value">
                        <output style="margin-left:10px;"><?php echo esc_attr($opacity); ?></output> %
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Rotation</th>
                    <td>
                        <input type="range" name="wupload_watermark_rotation" min="-180" max="180" value="<?php echo esc_attr($rotation); ?>" oninput="this.nextElementSibling.value = this.value">
                        <output style="margin-left:10px;"><?php echo esc_attr($rotation); ?></output> °
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Count</th>
                    <td>
                        <input type="range" name="wupload_watermark_count" min="1" max="10" value="<?php echo esc_attr($count); ?>" oninput="this.nextElementSibling.value = this.value">
                        <output style="margin-left:10px;"><?php echo esc_attr($count); ?></output>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Scale</th>
                    <td>
                        <input type="range" name="wupload_watermark_scale" min="10" max="200" value="<?php echo esc_attr($scale); ?>" oninput="this.nextElementSibling.value = this.value">
                        <output style="margin-left:10px;"><?php echo esc_attr($scale); ?></output> %
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Fallback Text</th>
                    <td>
                        <input type="text" name="wupload_watermark_fallback_text" value="<?php echo esc_attr($fallback_text); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Text Position</th>
                    <td>
                        <select name="wupload_watermark_position_text">
                            <option value="center" <?php selected($position_text, 'center'); ?>>Center</option>
                            <option value="top-left" <?php selected($position_text, 'top-left'); ?>>Top Left</option>
                            <option value="top-right" <?php selected($position_text, 'top-right'); ?>>Top Right</option>
                            <option value="bottom-left" <?php selected($position_text, 'bottom-left'); ?>>Bottom Left</option>
                            <option value="bottom-right" <?php selected($position_text, 'bottom-right'); ?>>Bottom Right</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Position</th>
                    <td>
                        <select name="wupload_watermark_position_image">
                            <option value="center" <?php selected($position_image, 'center'); ?>>Center</option>
                            <option value="top-left" <?php selected($position_image, 'top-left'); ?>>Top Left</option>
                            <option value="top-right" <?php selected($position_image, 'top-right'); ?>>Top Right</option>
                            <option value="bottom-left" <?php selected($position_image, 'bottom-left'); ?>>Bottom Left</option>
                            <option value="bottom-right" <?php selected($position_image, 'bottom-right'); ?>>Bottom Right</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Font Size</th>
                    <td>
                        <select name="wupload_watermark_font_size">
                            <option value="small" <?php selected($font_size, 'small'); ?>>Small</option>
                            <option value="medium" <?php selected($font_size, 'medium'); ?>>Medium</option>
                            <option value="large" <?php selected($font_size, 'large'); ?>>Large</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Kun lokal lagring</th>
                    <td>
                        <input type="checkbox" name="wupload_local_only" value="1" <?php checked(1, get_option('wupload_local_only', 0)); ?> />
                        <label for="wupload_local_only">Gem kun filer lokalt og undlad upload til Scaleway</label>
                    </td>
                </tr>
            </table>
            <table class="form-table" role="presentation">
                <tr><th colspan="2"><h2>Scaleway API Settings</h2></th></tr>
                <tr valign="top">
                    <th scope="row">Access Key</th>
                    <td><input type="text" name="wupload_scaleway_access_key" value="<?php echo esc_attr(get_option('wupload_scaleway_access_key', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Secret Key</th>
                    <td><input type="password" name="wupload_scaleway_secret_key" value="<?php echo esc_attr(get_option('wupload_scaleway_secret_key', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Bucket Name</th>
                    <td><input type="text" name="wupload_scaleway_bucket" value="<?php echo esc_attr(get_option('wupload_scaleway_bucket', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Region</th>
                    <td><input type="text" name="wupload_scaleway_region" value="<?php echo esc_attr(get_option('wupload_scaleway_region', 'nl-ams')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php if (isset($_GET['scaleway_test']) && current_user_can('manage_options')): ?>
                <div style="margin-top:20px;padding:10px;border-left:4px solid #0073aa;background:#f0f8ff;">
                    <?php
                    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/scaleway.php';
                    $bucket = get_option('wupload_scaleway_bucket');
                    $region = get_option('wupload_scaleway_region', 'nl-ams');
                    $exists = wupload_scaleway_file_exists('test-connection.txt'); // Check connection with dummy key
                    echo $exists !== false
                        ? '<strong>✅ Forbindelse til Scaleway lykkedes.</strong>'
                        : '<strong style="color:red;">❌ Forbindelse mislykkedes – tjek API-info.</strong>';
                    ?>
                </div>
            <?php endif; ?>

            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wupload-watermark-settings&scaleway_test=1')); ?>" class="button">Test Scaleway-forbindelse</a>
            </p>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}?>