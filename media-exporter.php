<?php
/*
Plugin Name: Media Exporter
Description: Exports media files information to a JSON file.
Version: 1.0.0
Author: alfi4000
Author URI: https://github.com/alfi4000
Plugin URI: https://github.com/alfi4000/simple-wordpress-plugins/blob/main/media-exporter.php
Plugin Home Page URI: https://github.com/alfi4000/simple-wordpress-plugins
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function me_add_admin_menu() {
    add_menu_page('Media Exporter', 'Media Exporter', 'manage_options', 'media-exporter', 'me_admin_page', 'dashicons-download', 6);
}
add_action('admin_menu', 'me_add_admin_menu');

// Admin page content
function me_admin_page() {
    ?>
    <div class="wrap">
        <h1>Media Exporter</h1>
        <p>Click the button below to export media files information as a JSON file.</p>
        <form method="post">
            <input type="submit" name="export_media" class="button button-primary" value="Export Media">
        </form>
        <?php
        if (isset($_POST['export_media'])) {
            me_export_media();
        }
        ?>
    </div>
    <?php
}

// Export media files
function me_export_media() {
    $media_files = me_get_media_files();
    if (empty($media_files)) {
        echo '<div class="error"><p>No media files found to export.</p></div>';
        return;
    }

    $upload_dir = wp_upload_dir();
    $file_name = 'media-export-' . date('Y-m-d-H-i-s') . '.json';
    $file_path = $upload_dir['path'] . '/' . $file_name;

    $json_data = json_encode($media_files, JSON_PRETTY_PRINT);

    if (file_put_contents($file_path, $json_data) !== false) {
        $file_url = $upload_dir['url'] . '/' . $file_name;
        echo '<div class="updated"><p>Media exported successfully! <a href="' . esc_url($file_url) . '" download>Download JSON File</a></p></div>';
    } else {
        echo '<div class="error"><p>Failed to export media data. Please try again.</p></div>';
    }
}

// Retrieve media files
function me_get_media_files() {
    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $media_files = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $media_files[] = array(
                'id'            => get_the_ID(),
                'date'          => get_the_date('Y-m-d H:i:s'),
                'url'           => wp_get_attachment_url(get_the_ID()),
            );
        }
        wp_reset_postdata();
    }

    return $media_files;
}
