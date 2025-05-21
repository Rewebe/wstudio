<?php
add_action('add_meta_boxes', function () {
    add_meta_box(
        'wstudio_upload_box',
        'Upload billeder',
        'wstudio_render_upload_box',
        'wstudio',
        'normal',
        'default'
    );
});

function wstudio_render_upload_box($post) {
    // Sikkerhed
    wp_nonce_field('wstudio_upload_action', 'wstudio_upload_nonce');

    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="post_ID" value="' . esc_attr($post->ID) . '" />';

    echo '<div class="wstudio-upload-wrapper">';
    echo '<p>Her kan du uploade billeder til dette galleri.</p>';
    echo '
    <p>
        <label for="wstudio_upload_type">Uploadtype:</label><br>
        <select name="wstudio_upload_type" id="wstudio_upload_type">
            <option value="delivery">Levering (færdige billeder)</option>
            <option value="select">Udvælgelse (klargøring med mapper)</option>
        </select>
    </p>

    <p>
        <label for="wstudio_upload_subfolder">Mappe/Set navn (valgfrit):</label><br>
        <input type="text" name="wstudio_upload_subfolder" id="wstudio_upload_subfolder" placeholder="fx. set1, set2..." style="width: 100%;" />
    </p>
    ';
    echo '<input type="file" name="wstudio_upload[]" multiple accept=".jpg,.jpeg,.png" />';
    echo '<p><small>Kun JPG og PNG filer er tilladt.</small></p>';

    echo '<div id="wstudio-preview" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; border: 1px solid #ccc; padding: 10px; min-height: 50px;">';
    echo '<p style="margin: 0; color: #888;">Ingen billeder valgt endnu.</p>';
    echo '</div>';

    echo '<p style="margin-top: 12px;">';
    echo '<button type="submit" class="button button-primary">Start upload</button>';
    echo '</p>';
    echo '</div>';
    echo '</form>';
}