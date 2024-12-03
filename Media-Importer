<?php
/*
Plugin Name: Media Importer
Description: Imports media files with their upload date and time and supports replacing files.
Version: 1.1.2.3
Author: alfi4000
Author URL: https://github.com/alfi4000
Plugin URL: 
Plugin Home Page URL: https://github.com/alfi4000/simple-wordpress-plugins
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function mi_add_admin_menu() {
    add_menu_page('Media Importer', 'Media Importer', 'manage_options', 'media-importer', 'mi_admin_page', 'dashicons-admin-media', 6);
}
add_action('admin_menu', 'mi_add_admin_menu');

// Admin page content
function mi_admin_page() {
    ?>
    <div class="wrap">
        <h1>Media Importer</h1>
        <form id="mi-import-form" method="post">
            <div id="mi-fields-container">
                <div class="mi-field-group">
                    <input type="url" name="media_url[]" placeholder="Enter URL" required>
                    <input type="datetime-local" name="media_date[]" required>
                    <button type="button" class="mi-remove-field button">&ndash;</button>
                </div>
            </div>
            <button type="button" id="mi-add-field" class="button button-primary">+</button>
            <input type="submit" name="import_media" class="button button-primary" value="Import">
        </form>
        <?php
        if (isset($_POST['import_media'])) {
            mi_handle_import_media();
        }
        ?>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const addFieldButton = document.getElementById('mi-add-field');
            const fieldsContainer = document.getElementById('mi-fields-container');

            addFieldButton.addEventListener('click', function() {
                const fieldGroup = document.createElement('div');
                fieldGroup.className = 'mi-field-group';
                fieldGroup.innerHTML = `
                    <input type="url" name="media_url[]" placeholder="Enter URL" required>
                    <input type="datetime-local" name="media_date[]" required>
                    <button type="button" class="mi-remove-field button">&ndash;</button>
                `;
                fieldsContainer.appendChild(fieldGroup);

                const removeButtons = document.querySelectorAll('.mi-remove-field');
                removeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        this.parentElement.remove();
                    });
                });
            });

            const initialRemoveButtons = document.querySelectorAll('.mi-remove-field');
            initialRemoveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.remove();
                });
            });
        });
    </script>
    <?php
}

// Handle importing media
function mi_handle_import_media() {
    if (isset($_POST['media_url']) && isset($_POST['media_date'])) {
        $media_urls = $_POST['media_url'];
        $media_dates = $_POST['media_date'];

        foreach ($media_urls as $index => $media_url) {
            $media_date = $media_dates[$index];
            $file_name = basename($media_url);
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/' . $file_name;

            if (mi_download_remote_file($media_url, $file_path)) {
                mi_insert_attachment($file_path, $file_name, $media_date);
            } else {
                echo '<div class="error"><p>Failed to download file from URL: ' . esc_html($media_url) . '.</p></div>';
            }
        }
    } else {
        echo '<div class="error"><p>File upload failed. Please check the uploaded files and try again.</p></div>';
    }
}

// Helper function to insert an attachment into the media library
function mi_insert_attachment($file_path, $file_name, $post_date) {
    $upload_dir = wp_upload_dir();
    $attachment = array(
        'guid' => $upload_dir['url'] . '/' . $file_name,
        'post_mime_type' => wp_check_filetype($file_name)['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
        'post_content' => '',
        'post_status' => 'inherit',
        'post_date' => $post_date,
    );

    $attach_id = wp_insert_attachment($attachment, $file_path);
    if (!is_wp_error($attach_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        echo '<div class="updated"><p>Media file ' . esc_html($file_name) . ' imported successfully!</p></div>';
    } else {
        echo '<div class="error"><p>Failed to insert attachment for ' . esc_html($file_name) . ': ' . esc_html($attach_id->get_error_message()) . '</p></div>';
    }
}

// Helper function to download a remote file
function mi_download_remote_file($url, $file_path) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    if (file_put_contents($file_path, $body) === false) {
        return false;
    }

    return true;
}
