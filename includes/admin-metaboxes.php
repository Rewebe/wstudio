<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin-metaboxes til wstudio
 * - Upload billeder
 * - Vandmærkeindstilling
 * - Kundeinfo
 * - Send e-mail
 * - Vis godkendelsestidspunkt
 */

// --- Upload billeder + vandmærke
add_action('add_meta_boxes', function() {
    add_meta_box('wstudio_upload', 'Upload billeder til kundemappe', 'wk_gallery_upload_metabox_html', 'kundegalleri');
    add_meta_box('wstudio_watermark_meta_box', 'Vandmærke', 'wstudio_watermark_meta_box_callback', 'kundegalleri', 'side');
});

function wk_gallery_upload_metabox_html($post) {
    $upload_url = wp_upload_dir()['baseurl'] . '/kundegalleri/' . $post->ID . '/original/';
    ?>
    <div class="wstudio-upload-wrapper" style="margin-top:20px;">
        <p>Træk billeder ind herunder eller klik for at vælge filer. Gemmes i:</p>
        <code><?php echo esc_html($upload_url); ?></code>
        <div id="dropzone" style="border:2px dashed #ccc;padding:20px;text-align:center;margin-top:10px;cursor:pointer;">Klik eller træk filer her</div>
        <input type="file" id="file-input" multiple style="display:none;">
        <div id="uploaded-images" style="margin-top:15px;display:flex;flex-wrap:wrap;gap:10px;"></div>
    </div>
    <hr>
    <h4>Eksisterende billeder:</h4>
    <?php
    $folder  = wp_upload_dir()['basedir'] . '/kundegalleri/' . $post->ID . '/original/';
    $baseurl = wp_upload_dir()['baseurl'] . '/kundegalleri/' . $post->ID . '/original/';
    if (is_dir($folder)) {
        $files = glob($folder . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        if ($files) {
            echo '<div style="display:flex;flex-wrap:wrap;gap:10px;">';
            foreach ($files as $file) {
                $filename = basename($file);
                $url = $baseurl . $filename;
                echo '<div style="position:relative;">';
                echo '<img src="' . esc_url($url) . '" style="max-width:100px;border:1px solid #ccc;">';
                echo '<button class="delete-image" style="position:absolute;top:0;right:0;background:red;color:white;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;">×</button>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>Ingen billeder fundet.</p>';
        }
    } else {
        echo '<p>Mappen findes ikke endnu.</p>';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file-input']['name'][0])) {
        $files = $_FILES['file-input'];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $name = sanitize_file_name($files['name'][$i]);

                // Gem midlertidigt i lokal mappe
                $upload_dir = wp_upload_dir()['basedir'] . '/kundegalleri/' . $post->ID . '/original/';
                if (!is_dir($upload_dir)) wp_mkdir_p($upload_dir);
                $dest_path = $upload_dir . $name;
                move_uploaded_file($tmp_name, $dest_path);

                // Kald eventuel ekstern lagring (fx Scaleway)
                do_action('wstudio_upload_image', [
                    'name' => $name,
                    'tmp_name' => $dest_path,
                    'type' => $files['type'][$i],
                    'size' => $files['size'][$i]
                ], $post->ID);
            }
        }
    }
}

// --- Vandmærke metabox
function wstudio_watermark_meta_box_callback($post) {
    wp_nonce_field('wstudio_save_watermark_meta', 'wstudio_watermark_meta_nonce');
    $value = get_post_meta($post->ID, '_wstudio_watermark_setting', true);
    ?>
    <label for="wstudio_watermark_setting">Vandmærkeindstilling:</label><br>
    <select name="wstudio_watermark_setting" id="wstudio_watermark_setting">
        <option value="default" <?php selected($value, 'default'); ?>>Standard (følger global indstilling)</option>
        <option value="always" <?php selected($value, 'always'); ?>>Altid anvend vandmærke</option>
        <option value="never" <?php selected($value, 'never'); ?>>Aldrig anvend vandmærke</option>
    </select>
    <?php
}
add_action('save_post', function($post_id) {
    if (!isset($_POST['wstudio_watermark_meta_nonce']) || !wp_verify_nonce($_POST['wstudio_watermark_meta_nonce'], 'wstudio_save_watermark_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $allowed = ['default', 'always', 'never'];
    $setting = isset($_POST['wstudio_watermark_setting']) && in_array($_POST['wstudio_watermark_setting'], $allowed)
        ? $_POST['wstudio_watermark_setting'] : 'default';
    update_post_meta($post_id, '_wstudio_watermark_setting', $setting);
});

// --- Kundeinformation
add_action('add_meta_boxes', function() {
    add_meta_box('wstudio_customer_meta_box', 'Kundeinformation', 'wstudio_customer_meta_box_callback', 'kundegalleri', 'normal', 'high');
});

function wstudio_customer_meta_box_callback($post) {
    $fields = [
        'kunde_first_name' => 'Fornavn',
        'kunde_last_name'  => 'Efternavn',
        'kunde_email'      => 'E-mail',
        'kunde_password'   => 'Password',
        'booking_code'     => 'Bookingkode',
        'booking_status'   => 'Bookingstatus',
        'booking_start_datetime' => 'Booking dato og tid',
        'service_id'       => 'Service ID',
        'service_name'     => 'Service navn',
    ];
    foreach ($fields as $key => $label) {
        $value = esc_attr(get_post_meta($post->ID, $key, true));
        echo '<p><label><strong>' . esc_html($label) . ':</strong><br>';
        echo '<input type="text" name="' . esc_attr($key) . '" value="' . $value . '" style="width:100%;"></label></p>';
    }
}

add_action('save_post_kundegalleri', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = [
        'kunde_first_name', 'kunde_last_name', 'kunde_email', 'kunde_password',
        'booking_code', 'booking_status', 'booking_start_datetime', 'service_id', 'service_name'
    ];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
});

// --- Godkendelse badge (accept tid)
add_action('add_meta_boxes', function() {
    add_meta_box('wg_accepted_time_meta_box', 'Billeder godkendt', 'wg_accepted_time_metabox_callback', 'kundegalleri', 'side', 'low');
});

function wg_accepted_time_metabox_callback($post) {
    $time = get_post_meta($post->ID, 'wg_accepted_time', true);
    if ($time) {
        echo '<p><span style="background:#d4edda;color:#155724;padding:6px 12px;border-radius:4px;">'
            . esc_html(date_i18n('d-m-Y \k\l\. H:i', strtotime($time)))
            . '</span></p>';
    } else {
        echo '<p>Ikke godkendt endnu</p>';
    }
}

// --- E-mail til kunde
add_action('add_meta_boxes', function() {
    add_meta_box('wstudio_send_email', 'Send e-mail til kunde', 'wstudio_send_email_metabox_callback', 'kundegalleri', 'side', 'default');
});

function wstudio_send_email_metabox_callback($post) {
    $first_name = get_post_meta($post->ID, 'kunde_first_name', true);
    $email      = get_post_meta($post->ID, 'kunde_email', true);
    $password   = get_post_meta($post->ID, 'kunde_password', true);
    $gallery_link = get_permalink($post->ID);

    $default_subject = 'Dine billeder er klar!';
    $default_body = "Hej {$first_name},\n\nTak for din tid! Dine billeder er nu klar.\n\nLogin e-mail: {$email}\nPassword: {$password}\n\nSe dit galleri her: {$gallery_link}\n\nVenlig hilsen\nWeigang Photography";

    echo '<div id="email-metabox-wrapper">';
    echo '<p><strong>Til:</strong> ' . esc_html($email) . '</p>';
    echo '<p><label for="email_subject">Emne:</label><br>';
    echo '<input type="text" id="email_subject" name="email_subject" value="' . esc_attr($default_subject) . '" style="width:100%;"></p>';
    echo '<p><label for="email_body">Besked:</label><br>';
    echo '<textarea id="email_body" name="email_body" rows="10" style="width:100%;">' . esc_textarea($default_body) . '</textarea></p>';

    // Senest sendt
    $sent = get_post_meta($post->ID, 'email_sent_at', true);
    if ($sent) {
        echo '<p><strong>Sidst sendt:</strong><br>' . esc_html(date_i18n('d-m-Y H:i', strtotime($sent))) . '</p>';
    } else {
        echo '<p><strong>Sidst sendt:</strong> Ikke sendt endnu</p>';
    }

    echo '<p><button type="button" class="button button-primary" onclick="sendWstudioEmail(' . esc_attr($post->ID) . ')">Send e-mail</button></p>';
    echo '<div id="email-response-' . esc_attr($post->ID) . '" style="margin-top:10px;"></div>';
    echo '</div>';
}
?>