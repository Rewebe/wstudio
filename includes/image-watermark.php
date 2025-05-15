<?php
if (!defined('ABSPATH')) exit;

function apply_watermark_to_image($image_path, $settings, $post_id) {
    error_log('🔍 Starter apply_watermark_to_image() for post_id: ' . $post_id);

    $global_watermark_enabled = get_option('kundegalleri_watermark_enabled', 1);
    $individual_setting = get_post_meta($post_id, '_kundegalleri_watermark_setting', true);

    if ($individual_setting === 'always') {
        $should_apply_watermark = true;
    } elseif ($individual_setting === 'never') {
        $should_apply_watermark = false;
    } else {
        $should_apply_watermark = ($global_watermark_enabled == 1 || $global_watermark_enabled === 'yes');
    }

    if (!$should_apply_watermark) {
        error_log('⏩ Springer watermark over.');
        return true;
    }

    if (!file_exists($image_path)) {
        error_log('🚫 Billedfil findes ikke: ' . $image_path);
        return 'Billedfil ikke fundet.';
    }

    if (!extension_loaded('imagick')) {
        error_log('🚫 Imagick ikke tilgængelig.');
        return 'Imagick ikke installeret.';
    }

    try {
        $base_image = new Imagick($image_path);
        $base_image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        $base_image->setImageDepth(8);
        $base_image->setImageFormat('jpeg');

        $plugin_path = plugin_dir_path(__FILE__) . '../assets/watermark/' . get_option('kundegalleri_watermark_selected', 'watermark.png');
        $opacity_image = intval(get_option('kundegalleri_watermark_opacity_image', 30)) / 100;
        $count = intval(get_option('kundegalleri_watermark_count', 3));
        $rotation = intval(get_option('kundegalleri_watermark_rotation', 0));

        if (file_exists($plugin_path)) {
            error_log('🖼️ Bruger billed-vandmærke: ' . $plugin_path);
            error_log('🔄 Antal: ' . $count . ' | 🔁 Rotation: ' . $rotation . '° | 🌫️ Opacitet: ' . $opacity_image);

            $watermark = new Imagick($plugin_path);
            if ($rotation !== 0) {
                $watermark->rotateImage(new ImagickPixel('none'), $rotation);
            }

            $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity_image, Imagick::CHANNEL_ALPHA);

            $w = $base_image->getImageWidth();
            $h = $base_image->getImageHeight();

            for ($i = 0; $i < $count; $i++) {
                $x = rand(50, max(50, $w - $watermark->getImageWidth() - 50));
                $y = rand(50, max(50, $h - $watermark->getImageHeight() - 50));
                $base_image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y, Imagick::CHANNEL_ALL);
            }

        } else {
            $watermark_text = $settings['watermark_text'] ?? '';
            if (empty($watermark_text)) {
                error_log('🚫 Vandmærketekst mangler og intet billede fundet.');
                return 'Intet brugbart vandmærke.';
            }

            $font_path = plugin_dir_path(__FILE__) . '../assets/fonts/arial.ttf';
            if (!file_exists($font_path)) {
                error_log('🚫 Fontfil mangler: ' . $font_path);
                return 'Font mangler.';
            }

            $opacity_text = intval(get_option('kundegalleri_watermark_opacity_text', 30)) / 100;
            $font_size = intval(get_option('kundegalleri_watermark_fontsize', 24));
            $position = get_option('kundegalleri_watermark_position', 'bottom-right');
            $gravity = match ($position) {
                'bottom-left' => Imagick::GRAVITY_SOUTHWEST,
                'top-left'    => Imagick::GRAVITY_NORTHWEST,
                'top-right'   => Imagick::GRAVITY_NORTHEAST,
                'center'      => Imagick::GRAVITY_CENTER,
                default       => Imagick::GRAVITY_SOUTHEAST,
            };

            error_log('✏️ Fallback til tekst-vandmærke');
            error_log("🖋️ '$watermark_text', font=$font_size, opacity=$opacity_text");

            $draw = new ImagickDraw();
            $pixel = new ImagickPixel('white');

            $draw->setFont($font_path);
            $draw->setFontSize($font_size);
            $draw->setFillColor($pixel);
            $draw->setFillOpacity($opacity_text);
            $draw->setGravity($gravity);

            $base_image->annotateImage($draw, 0, 0, 0, $watermark_text);
        }

        $base_image->writeImage($image_path);
        $base_image->clear();
        error_log('✅ Vandmærke påført succesfuldt.');
        return true;

    } catch (Exception $e) {
        error_log('❌ Fejl under watermark: ' . $e->getMessage());
        return 'Imagick-fejl: ' . $e->getMessage();
    }
}
