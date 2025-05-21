<?php
// Dynamically load all modules from the modules/ directory
add_action('init', function () {
    $modules_dir = plugin_dir_path(__DIR__) . 'modules/';
    $module_folders = glob($modules_dir . '*', GLOB_ONLYDIR);

    foreach ($module_folders as $folder) {
        $init_file = $folder . '/init.php';
        if (file_exists($init_file)) {
            include_once $init_file;
            error_log('✅ Modul indlæst: ' . basename($folder));
        }
    }
});
error_log('📥 module-loader.php blev indlæst');
