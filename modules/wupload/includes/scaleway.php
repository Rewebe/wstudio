<?php

if (!class_exists('Aws\S3\S3Client')) {
    require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';
}
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function wupload_scaleway_file_exists($key) {
    $access_key = get_option('wupload_scaleway_access_key');
    $secret_key = get_option('wupload_scaleway_secret_key');
    $bucket     = get_option('wupload_scaleway_bucket');
    $region     = get_option('wupload_scaleway_region', 'nl-ams');
    $endpoint   = "https://s3.nl-ams.scw.cloud";

    error_log("🔐 Testing Scaleway credentials:");
    error_log("🔑 Access key prefix: " . substr($access_key, 0, 6) . "...");
    error_log("📦 Bucket: $bucket");
    error_log("🌍 Region: $region");
    error_log("🔗 Endpoint: $endpoint");
    error_log("🔑 Test key: $key");

    try {
        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => $region,
            'endpoint'    => $endpoint,
            'credentials' => [
                'key'    => $access_key,
                'secret' => $secret_key,
            ],
        ]);

        // Phase 1: Validate API access
        $s3->listObjectsV2([
            'Bucket' => $bucket,
            'MaxKeys' => 1,
        ]);

        // Phase 2: If a key is provided, check if the object exists
        if (!empty($key)) {
            return $s3->doesObjectExist($bucket, $key);
        }

        return true;

    } catch (AwsException $e) {
        error_log("❌ [Scaleway/AWS SDK] " . $e->getAwsErrorMessage());
        error_log("🔍 Params: bucket=$bucket | region=$region | key=$key");
        return false;
    } catch (\Throwable $e) {
        error_log("❌ [Scaleway Throwable] " . $e->getMessage());
        error_log("🔍 Params: bucket=$bucket | region=$region | key=$key");
        return false;
    }
}
