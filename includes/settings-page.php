<?php
if (!defined('ABSPATH')) exit;

// Menu og registrering
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=kundegalleri', 'Indstillinger', 'Indstillinger', 'manage_options', 'wstudio-settings', 'wstudio_settings_page');
});

add_action('admin_init', function () {
    register_setting('wstudio_settings_group', 'wstudio_watermark_text');
    register_setting('wstudio_settings_group', 'wstudio_watermark_opacity_text');
    register_setting('wstudio_settings_group', 'wstudio_watermark_opacity_image');
    register_setting('wstudio_settings_group', 'wstudio_watermark_fontsize');
    register_setting('wstudio_settings_group', 'wstudio_watermark_position');
    register_setting('wstudio_settings_group', 'wstudio_watermark_font');
    register_setting('wstudio_settings_group', 'wstudio_watermark_image');
    register_setting('wstudio_settings_group', 'wstudio_watermark_selected');
    register_setting('wstudio_settings_group', 'wstudio_watermark_count');
    register_setting('wstudio_settings_group', 'wstudio_watermark_rotation');
    register_setting('wstudio_settings_group', 'wstudio_resize_maxwidth');
    register_setting('wstudio_settings_group', 'wstudio_resize_quality');
    register_setting('wstudio_settings_group', 'wstudio_watermark_enabled', ['sanitize_callback' => fn($v) => $v ? 'yes' : 'no']);
    register_setting('wstudio_settings_group', 'wstudio_use_name_in_slug');
    // register_setting('wstudio_settings_group', 'wstudio_storage_type');
});

function wstudio_settings_page() {
    $image_url = get_option('wstudio_watermark_image');
    $selected = get_option('wstudio_watermark_selected', 'watermark.png');
    $dir = plugin_dir_path(__FILE__) . '../assets/watermark/';
    $url_base = plugin_dir_url(__FILE__) . '../assets/watermark/';
    $files = glob($dir . '*.{png,jpg,jpeg}', GLOB_BRACE);
?>
<div class="wrap">
<h1>WStudio Indstillinger</h1>
<form method="post" action="options.php">
<?php settings_fields('wstudio_settings_group'); ?>

<h2>Vandmærke</h2>

<table class="form-table">
<tr><th>Tekst</th><td><input type="text" name="wstudio_watermark_text" value="<?php echo esc_attr(get_option('wstudio_watermark_text')); ?>" style="width:300px;"></td></tr>
<tr><th>Gennemsigtighed (tekst)</th><td><input type="number" name="wstudio_watermark_opacity_text" value="<?php echo esc_attr(get_option('wstudio_watermark_opacity_text', 30)); ?>" min="1" max="100"></td></tr>
<tr><th>Gennemsigtighed (billede)</th><td><input type="number" name="wstudio_watermark_opacity_image" value="<?php echo esc_attr(get_option('wstudio_watermark_opacity_image', 30)); ?>" min="1" max="100"></td></tr>
<tr><th>Antal placeringer</th><td><input type="number" name="wstudio_watermark_count" value="<?php echo esc_attr(get_option('wstudio_watermark_count', 3)); ?>" min="1" max="10"></td></tr>
<tr><th>Rotation</th><td><input type="number" name="wstudio_watermark_rotation" value="<?php echo esc_attr(get_option('wstudio_watermark_rotation', 0)); ?>" min="-180" max="180"> Â°</td></tr>
<tr><th>Tekststørrelse</th><td><input type="number" name="wstudio_watermark_fontsize" value="<?php echo esc_attr(get_option('wstudio_watermark_fontsize', 36)); ?>"></td></tr>
<tr>
<th>Placering</th>
<td><select name="wstudio_watermark_position">
<?php foreach (['top-left','top-right','bottom-left','bottom-right','center'] as $opt): ?>
<option value="<?php echo $opt; ?>" <?php selected(get_option('wstudio_watermark_position', 'bottom-right'), $opt); ?>><?php echo ucfirst(str_replace('-', ' ', $opt)); ?></option>
<?php endforeach; ?>
</select></td>
</tr>
<tr>
<th>Max bredde (px)</th>
<td><input type="number" name="wstudio_resize_maxwidth" value="<?php echo esc_attr(get_option('wstudio_resize_maxwidth', 1600)); ?>" min="500" max="5000"></td>
</tr>
<tr>
<th>JPG-kvalitet (1–100)</th>
<td><input type="number" name="wstudio_resize_quality" value="<?php echo esc_attr(get_option('wstudio_resize_quality', 75)); ?>" min="10" max="100"></td>
</tr>
<tr><th>Aktiver</th><td><label><input type="checkbox" name="wstudio_watermark_enabled" value="yes" <?php checked('yes', get_option('wstudio_watermark_enabled', 'no')); ?>> Ja</label></td></tr>
<tr><th>Upload billede</th><td>
<input type="text" name="wstudio_watermark_image" id="wstudio_watermark_image" value="<?php echo esc_attr($image_url); ?>" style="width:60%;">
<button type="button" class="button" id="upload_wstudio_watermark_image">Vælg billede</button>
<?php if ($image_url): ?><div style="margin-top:10px;"><img src="<?php echo esc_url($image_url); ?>" style="max-width:200px;"></div><?php endif; ?>
</td></tr>
<tr><th>Vælg fra mappe</th><td>
<?php if ($files) {
    echo '<div style="display:flex; gap:20px; flex-wrap:wrap;">';
    foreach ($files as $file) {
        $basename = basename($file);
        echo '<label style="text-align:center;">';
        echo '<input type="radio" name="wstudio_watermark_selected" value="' . esc_attr($basename) . '" ' . checked($selected, $basename, false) . '>';
        echo '<br><img src="' . esc_url($url_base . $basename) . '" style="width:100px;"><br>' . esc_html($basename);
        echo '</label>';
    }
    echo '</div>';
} ?>
</td></tr>
</table>

<hr><h2>Privatliv</h2>
<table class="form-table">
<tr><th>Navn i slug</th><td><label><input type="checkbox" name="wstudio_use_name_in_slug" value="1" <?php checked(1, get_option('wstudio_use_name_in_slug')); ?>> <strong style="color:red;">⚠️ Ikke GDPR-compliant uden samtykke.</strong></label></td></tr>
</table>

<?php submit_button(); ?>
</form>
</div>
<script>
jQuery(function($){
    $('#upload_wstudio_watermark_image').on('click', function(e){
        e.preventDefault();
        wp.media({title: 'Vælg billede', button: {text:'Brug'}, multiple: false}).on('select', function(){
            var attachment = wp.media.editor.state().get('selection').first().toJSON();
            $('#wstudio_watermark_image').val(attachment.url);
        }).open();
    });
});
</script>
<?php } ?>
