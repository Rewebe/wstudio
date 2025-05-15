<?php
if (!defined('ABSPATH')) exit;

function wstudio_generate_image_versions($tmp_path, $filename, $post_id) {
    $result = [];

    try {
        $image = new Imagick($tmp_path);

        // === Web (1920px) ===
        $web_path = sys_get_temp_dir() . "/web_$filename";
        $web = clone $image;
        $web->resizeImage(1920, 1920, Imagick::FILTER_LANCZOS, 1, true);
        $web->setImageCompression(Imagick::COMPRESSION_JPEG);
        $web->setImageCompressionQuality(85);
        $web->writeImage($web_path);
        $result['web'] = $web_path;

        // === Web med vandmærke ===
        $webwm_path = sys_get_temp_dir() . "/webwm_$filename";
        $webwm = clone $web;
        $draw = new ImagickDraw();
        $draw->setFillColor('rgba(255,255,255,0.4)');
        $draw->setFontSize(24);
        $draw->setGravity(Imagick::GRAVITY_SOUTHEAST);
        $webwm->annotateImage($draw, 10, 12, 0, 'weigang.dk');
        $webwm->writeImage($webwm_path);
        $result['webwm'] = $webwm_path;

        // === Thumbnail (400px) ===
        $thumb_path = sys_get_temp_dir() . "/thumb_$filename";
        $thumb = clone $image;
        $thumb->resizeImage(400, 400, Imagick::FILTER_LANCZOS, 1, true);
        $thumb->setImageCompression(Imagick::COMPRESSION_JPEG);
        $thumb->setImageCompressionQuality(75);
        $thumb->writeImage($thumb_path);
        $result['thumb'] = $thumb_path;

        $image->clear(); $web->clear(); $webwm->clear(); $thumb->clear();

    } catch (Exception $e) {
        error_log('[wstudio] Billedbehandling fejl: ' . $e->getMessage());
    }

    return $result;
}
