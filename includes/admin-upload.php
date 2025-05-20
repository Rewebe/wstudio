<?php
// Simpel uploadformular til udvælgelse

function wstudio_render_upload_ui() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['select_images'])) {
        // Find upload-mappen og sørg for at destinationsmappen eksisterer
        $upload_dir = wp_upload_dir();
        $target_base = $upload_dir['basedir'] . '/wstudio/select/original/';
        if (!file_exists($target_base)) {
            wp_mkdir_p($target_base);
        }

        foreach ($_FILES['select_images']['tmp_name'] as $index => $tmp_name) {
            $filename = basename($_FILES['select_images']['name'][$index]);
            $target_path = $target_base . $filename;

            if (move_uploaded_file($tmp_name, $target_path)) {
                echo '<div class="notice notice-success"><p>Filen <strong>' . esc_html($filename) . '</strong> blev uploadet.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Kunne ikke uploade <strong>' . esc_html($filename) . '</strong>.</p></div>';
            }
        }
    }
    ?>
    <form method="post" enctype="multipart/form-data">
        <h3>Upload billeder til Udvælgelse</h3>
        <input type="file" name="select_images[]" multiple>
        <p>
            <button class="button button-primary" type="submit">Upload</button>
        </p>
    </form>
    <?php
}
?>
