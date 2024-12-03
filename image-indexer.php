<?php
/*
Plugin Name: Image Indexer
Description: Scans the wp-content/uploads folder and imports unindexed images into the WordPress Media Library, using the year and month folders as the upload date.
Version: 1.0
Author: alfi4000
Author URI: https://github.com/alfi4000
Plugin URI: https://github.com/alfi4000/simple-wordpress-plugins/blob/main/image-indexer.php
Plugin Home Page URI: https://github.com/alfi4000/simple-wordpress-plugins
*/

function import_server_images() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $base_url = $upload_dir['baseurl'];

    // Recursively scan the uploads directory
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isFile()) continue;

        $filepath = $file->getPathname();
        $filename = $file->getFilename();

        // Skip non-image files
        $mime_type = wp_check_filetype($filename);
        if (!in_array($mime_type['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) continue;

        // Check if the file is already indexed in the Media Library
        $file_url = str_replace($base_dir, $base_url, $filepath);
        if (attachment_url_to_postid($file_url)) continue;

        // Extract year and month from the folder structure
        $relative_path = str_replace($base_dir . '/', '', $filepath);
        $path_parts = explode('/', $relative_path);

        $year = $path_parts[0] ?? null;
        $month = $path_parts[1] ?? null;

        if (!is_numeric($year) || !is_numeric($month)) continue;

        // Prepare attachment data
        $attachment = array(
            'guid'           => $file_url,
            'post_mime_type' => $mime_type['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_date'      => "$year-$month-01 00:00:00",
        );

        // Insert the attachment into the database
        $attach_id = wp_insert_attachment($attachment, $filepath);

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);
    }

    echo 'Images have been successfully imported!';
}

// Add an admin menu option to trigger the import
function import_server_images_menu() {
    add_submenu_page(
        'tools.php',
        'Import Server Images',
        'Import Server Images',
        'manage_options',
        'import-server-images',
        'import_server_images_page'
    );
}
add_action('admin_menu', 'import_server_images_menu');

// Admin page callback
function import_server_images_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap"><h1>Import Server Images</h1>';
    echo '<p>Click the button below to scan your uploads folder and import unindexed images into the Media Library.</p>';
    echo '<form method="post">';
    echo '<input type="hidden" name="import_images" value="1" />';
    submit_button('Import Images');
    echo '</form></div>';

    // Handle the form submission
    if (isset($_POST['import_images'])) {
        import_server_images();
    }
}
