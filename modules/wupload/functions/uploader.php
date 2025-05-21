<?php
/**
 * Changelog:
 * - [2025-05-20] Tilføjet detaljeret debug-log til wupload_upload_to_scaleway (bucket, path, endpoint m.m.)
 * - [2025-05-20] Tilføjet lokal vs. Scaleway upload logik med `get_option('wupload_local_only')`.
 * - [2025-05-20] Vandmærke anvendes ved thumb-generation via apply_watermark_to_image.
 * - [2025-05-20] Tilføjet Scaleway-upload med fejl- og succes-logging.
 * - [2025-05-20] Understøttelse af manuel test via URL (?wupload_test_multiple=1).
 * - [2025-05-20] Brug af AWS SDK via composer autoload check.
 */
use Aws\S3\S3Client;

if (file_exists(plugin_dir_path(__DIR__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';
}

function wupload_process_image_from_attachment($file_path, $file_name, $slug, $force = false) {
    error_log("🛠️ [DEBUG] wuploade_handle_upload kaldt for $file_name i $slug");
    // Get WP upload directories
    $upload_dir = wp_upload_dir();
    $base_dir = trailingslashit($upload_dir['basedir']) . 'wupload/' . $slug . '/';
    $base_url = trailingslashit($upload_dir['baseurl']) . 'wupload/' . $slug . '/';

    $paths = [
        'original' => $base_dir . 'delivery/original/',
        'web' => $base_dir . 'delivery/web/',
        'thumb' => $base_dir . 'delivery/thumb/',
    ];

    // Create folders if they do not exist
    foreach ($paths as $path) {
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }
    }

    // Copy original file
    $original_dest = $paths['original'] . $file_name;
    if (!$force && file_exists($original_dest)) {
        return ['EXISTS'];
    }
    copy($file_path, $original_dest);

    // Create web version (max width 1200px, JPEG)
    $web_dest = $paths['web'] . $file_name;
    error_log("📐 Opretter web og thumb version for: $file_name");
    wupload_resize_image($original_dest, $web_dest, 1200, false);

    // Create thumb version (400px width, with watermark)
    $thumb_dest = $paths['thumb'] . $file_name;
    wupload_resize_image($original_dest, $thumb_dest, 400, true);

    // Upload to Scaleway with updated remote paths
    $local_only = get_option('wupload_local_only');
    error_log("🔧 Lokal only status: " . var_export($local_only, true));
    if ($local_only !== '1' && $local_only !== 1) {
        wupload_upload_to_scaleway($original_dest, 'wupload/' . $slug . '/delivery/original/' . $file_name);
        wupload_upload_to_scaleway($web_dest, 'wupload/' . $slug . '/delivery/web/' . $file_name);
        wupload_upload_to_scaleway($thumb_dest, 'wupload/' . $slug . '/delivery/thumb/' . $file_name);
    }

    return [
        'original' => $base_url . 'delivery/original/' . $file_name,
        'web' => $base_url . 'delivery/web/' . $file_name,
        'thumb' => $base_url . 'delivery/thumb/' . $file_name,
    ];
}

/**
 * Resize image and optionally add watermark for thumb.
 */
function wupload_resize_image($source, $destination, $max_width, $add_watermark = false) {
    // Use WP image editor
    $image = wp_get_image_editor($source);
    if (is_wp_error($image)) return;

    $size = $image->get_size();
    if ($size && $size['width'] > $max_width) {
        $image->resize($max_width, null, false);
    }

    // Always save as JPEG for web and thumb
    $ext = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
    $mime = ($add_watermark || $max_width <= 1200) ? 'image/jpeg' : null;
    $image->save($destination, $mime ? 'image/jpeg' : null);

    // For thumb, add watermark using apply_watermark_to_image
    if ($add_watermark) {
        error_log("💧 Tilføjer vandmærke til thumb: $destination");
        apply_watermark_to_image($destination, [
            'watermark_text' => 'WUPLOAD',
        ], 0);
    }
}
function wupload_handle_upload_multiple(array $files, string $slug): array {
    $results = [];

    foreach ($files as $file) {
        if (!isset($file['tmp_name'], $file['name'])) {
            continue;
        }

        $upload_result = wupload_process_image_from_attachment($file['tmp_name'], $file['name'], $slug);

        // Detect if any version was skipped
        $status = (in_array('EXISTS', $upload_result, true)) ? 'skipped' : 'uploaded';

        $results[] = [
            'name' => $file['name'],
            'status' => $status,
            'result' => $upload_result,
        ];
    }

    return $results;
}
// Test multiple uploads via URL (?wupload_test_multiple=1)
add_action('init', function () {
    // Manual force upload trigger moved to the top
    if (isset($_GET['wupload_force_upload'], $_GET['file'], $_GET['slug'])) {
        $slug = sanitize_text_field($_GET['slug']);
        $file = sanitize_file_name($_GET['file']);
        $source = plugin_dir_path(__DIR__) . 'assets/test/' . $file;

        if (file_exists($source)) {
            $result = wupload_process_image_from_attachment($source, $file, $slug, true);
            echo "<p style='color:green;'>✅ Fil overskrevet: $file</p>";
            echo '<pre>' . print_r($result, true) . '</pre>';
        } else {
            echo "<p style='color:red;'>🚫 Fil ikke fundet: $file</p>";
        }
        exit;
    }

    if (!isset($_GET['wupload_test_multiple'])) return;

    $slug = 'testgallery';
    $base = plugin_dir_path(__DIR__) . 'assets/test/';
    $files = [];

    foreach (glob($base . '*.{jpg,jpeg,png}', GLOB_BRACE) as $path) {
        $files[] = [
            'tmp_name' => $path,
            'name'     => basename($path),
        ];
    }

    $results = wupload_handle_upload_multiple($files, $slug);
    echo '<style>body{font-family:sans-serif;padding:20px;} .uploaded{color:green;} .skipped{color:orange;}</style>';
    echo "<h2>Uploadstatus</h2><ul>";
    foreach ($results as $item) {
        $status = $item['status'] === 'skipped' ? '⏭️ Skipped' : '✅ Uploaded';
        $css = $item['status'];
        echo "<li class=\"$css\"><strong>{$item['name']}</strong> — $status";

        if ($item['status'] === 'skipped') {
            $filename = urlencode($item['name']);
            echo " <a href='?wupload_force_upload=1&slug=$slug&file=$filename'>[Overskriv]</a>";
        }

        echo "</li>";
    }
    echo "</ul>";
});

function wupload_upload_to_scaleway($local_path, $remote_path, $force = false) {
    $access_key = get_option('wupload_scaleway_access_key');
    $secret_key = get_option('wupload_scaleway_secret_key');
    $bucket = get_option('wupload_scaleway_bucket');
    $region = get_option('wupload_scaleway_region', 'nl-ams');
    error_log("🚀 [DEBUG] Starter upload til Scaleway af $local_path som $remote_path");
    error_log("🧪 Debug API: key=$access_key | secret=" . substr($secret_key, 0, 5) . "**** | bucket=$bucket | region=$region");

    try {
        $endpoint = "https://s3.$region.scw.cloud";

        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $region,
            'endpoint' => $endpoint,
            'credentials' => [
                'key'    => $access_key,
                'secret' => $secret_key,
            ],
        ]);

        if (!$force) {
            $exists = $s3->doesObjectExist($bucket, $remote_path);
            error_log("📦 Bucket: $bucket");
            error_log("🔑 Remote path: $remote_path");
            error_log("📄 Local path: $local_path");
            error_log("🌍 Endpoint: $endpoint");
            if ($exists) {
                return;
            }
        }

        $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $remote_path,
            'SourceFile' => $local_path,
            'ACL'    => 'public-read',
        ]);
        error_log("📦 Bucket: $bucket");
        error_log("🔑 Remote path: $remote_path");
        error_log("📄 Local path: $local_path");
        error_log("🌍 Endpoint: $endpoint");
        error_log("✅ Upload til Scaleway OK: $remote_path");
    } catch (Exception $e) {
        if ($e instanceof Aws\Exception\AwsException) {
            error_log("❌ AWS Exception [{$e->getAwsErrorCode()}]: " . $e->getAwsErrorMessage());
        } else {
            error_log("❌ General Exception [" . get_class($e) . "]: " . $e->getMessage());
        }
    }
}
add_action('wp_ajax_wupload_ajax_upload', 'wupload_ajax_upload_handler');

function wupload_ajax_upload_handler() {
    error_log('🚀 AJAX upload handler kaldt');

    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'Ingen adgang.']);
    }

    check_ajax_referer('wupload_form_action', 'nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    $slug = get_post_field('post_name', $post_id);
    $files = $_FILES['wupload_files'] ?? [];

    if (empty($files)) {
        wp_send_json_error(['message' => 'Ingen filer modtaget.']);
    }

    $results = wupload_handle_upload_multiple($files, $slug);
    wp_send_json_success(['message' => 'Upload gennemført', 'results' => $results]);
}