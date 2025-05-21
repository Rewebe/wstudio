<?php
error_log('✅ [wupload] upload-form.php er nu inkluderet');

function wupload_render_upload_form($post_id) {
    ?>
    <div id="wupload-form-wrapper">
        <div>
            <label for="wupload_upload_type"><strong>Vælg type:</strong></label><br>
            <select name="wupload_upload_type" id="wupload_upload_type">
                <option value="delivery">Endelige billeder (delivery)</option>
                <option value="select">Udvælgelse (select)</option>
            </select>
        </div>

        <div style="margin-top:10px;">
            <label for="wupload_file_input"><strong>Upload billeder:</strong></label><br>
            <input type="file" id="wupload_file_input" name="wupload_files[]" multiple />
        </div>

        <input type="hidden" id="wupload_post_id" value="<?php echo esc_attr($post_id); ?>" />
        <input type="hidden" name="wupload_nonce" id="wupload_nonce" value="<?php echo wp_create_nonce('wupload_form_action'); ?>">

        <div style="margin-top:10px;">
            <button type="button" id="wupload_submit_btn" class="button button-primary">Upload via AJAX</button>
        </div>

        <div id="wupload_status" style="margin-top:10px;"></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadBtn = document.getElementById('wupload_submit_btn');
        const statusEl = document.getElementById('wupload_status');

        uploadBtn.addEventListener('click', function () {
            const files = document.getElementById('wupload_file_input').files;
            const type = document.getElementById('wupload_upload_type').value;
            const postId = document.getElementById('wupload_post_id').value;
            const nonce = document.getElementById('wupload_nonce').value;

            if (!files.length) {
                statusEl.innerText = 'Vælg mindst én fil.';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'wupload_handle_upload_ajax');
            formData.append('wupload_upload_type', type);
            formData.append('wupload_post_id', postId);
            formData.append('wupload_nonce', nonce);

            for (let i = 0; i < files.length; i++) {
                formData.append('wupload_files[]', files[i]);
            }

            statusEl.innerText = 'Uploader...';

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                statusEl.innerText = data.message || 'Upload gennemført.';
            })
            .catch(error => {
                statusEl.innerText = 'Fejl ved upload.';
            });
        });

        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    });
    </script>
    <?php
}

// Process upload when form is submitted
add_action('admin_post_wupload_handle_upload', function () {
    error_log('🌀 admin_post_wupload_handle_upload callback kaldt');
    error_log('🧾 POST: ' . print_r($_POST, true));
    error_log('📂 FILES: ' . print_r($_FILES, true));
    if (!empty($_FILES['wupload_files']) && !empty($_POST['wupload_post_id'])) {

        if (!isset($_POST['wupload_nonce']) || !wp_verify_nonce($_POST['wupload_nonce'], 'wupload_form_action')) {
            error_log('❌ Upload formular nonce check fejlede.');
            return;
        }

        $post_id = intval($_POST['wupload_post_id']);
        $type = sanitize_text_field($_POST['wupload_upload_type']);
        $files = $_FILES['wupload_files'];

        if (isset($files['name']) && is_array($files['name'])) {
            $normalized = [];
            foreach ($files['name'] as $i => $name) {
                $normalized[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
            }
            $files = $normalized;
        }

        error_log('📦 wupload_handle_upload_multiple kaldt med filer: ' . print_r($files, true));
        $slug = get_post_field('post_name', $post_id);
        $results = wupload_handle_upload_multiple($files, $slug);

        error_log('📥 Upload resultater: ' . print_r($results, true));
    }

    if (!empty($_POST['redirect_to'])) {
        wp_safe_redirect(esc_url_raw($_POST['redirect_to']));
        exit;
    }
});

add_action('wp_ajax_wupload_handle_upload_ajax', function () {
    error_log('⚡ AJAX upload kaldt');
    check_ajax_referer('wupload_form_action', 'wupload_nonce');

    if (!isset($_POST['wupload_post_id']) || empty($_FILES['wupload_files'])) {
        wp_send_json_error(['message' => 'Manglende data.']);
    }

    $post_id = intval($_POST['wupload_post_id']);
    $slug = get_post_field('post_name', $post_id);

    $files = $_FILES['wupload_files'];

    if (isset($files['name']) && is_array($files['name'])) {
        $normalized = [];
        foreach ($files['name'] as $i => $name) {
            $normalized[] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
        $files = $normalized;
    }

    error_log('📦 wupload_handle_upload_multiple kaldt med filer: ' . print_r($files, true));
    $results = wupload_handle_upload_multiple($files, $slug);

    error_log('📥 Upload resultater: ' . print_r($results, true));
    wp_send_json_success(['message' => 'Upload fuldført.', 'results' => $results]);
});