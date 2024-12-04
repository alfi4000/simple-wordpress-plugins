<?php
/*
Plugin Name: Media Importer WEBP
Description: Imports media files with their upload date and time and supports replacing files. Converts non-document files to WebP format.
Version: 1.2
Author: alfi4000
Author URI: https://github.com/alfi4000
Plugin URI: https://github.com/alfi4000/simple-wordpress-plugins/blob/main/media-importer.php
Plugin Home Page URI: https://github.com/alfi4000/simple-wordpress-plugins
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function mi_add_admin_menu() {
    add_menu_page(
        'Media Importer',
        'Media Importer',
        'manage_options',
        'media-importer',
        'mi_admin_page',
        'dashicons-admin-media',
        6
    );
}
add_action('admin_menu', 'mi_add_admin_menu');

// Admin page content
function mi_admin_page() {
    ?>
    <div class="wrap">
        <h1>Media Importer</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="import_file" accept=".json">
            <input type="submit" name="list_media" class="button button-primary" value="List Media for Import">
        </form>
        <?php
        if (isset($_POST['list_media'])) {
            mi_handle_list_media();
        } elseif (isset($_POST['import_media'])) {
            mi_handle_import_media();
        }
        ?>
    </div>
    <?php
}

// Handle listing media for replacement
function mi_handle_list_media() {
    if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == UPLOAD_ERR_OK) {
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $media_files = json_decode($file_content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            ?>
            <form method="post" enctype="multipart/form-data">
                <?php
                foreach ($media_files as $media) {
                    ?>
                    <div>
                        <p><strong><?php echo esc_html($media['url']); ?></strong> (<?php echo esc_html($media['date']); ?>)</p>
                        <input type="file" name="replace_file[<?php echo esc_attr($media['id']); ?>]" accept="*">
                        <input type="hidden" name="media_id[<?php echo esc_attr($media['id']); ?>]" value="<?php echo esc_attr($media['id']); ?>">
                        <input type="hidden" name="media_date[<?php echo esc_attr($media['id']); ?>]" value="<?php echo esc_attr($media['date']); ?>">
                        <input type="hidden" name="media_url[<?php echo esc_attr($media['id']); ?>]" value="<?php echo esc_url($media['url']); ?>">
                    </div>
                    <?php
                }
                ?>
                <input type="submit" name="import_media" class="button button-primary" value="Import Media">
            </form>
            <?php
        } else {
            echo '<div class="error"><p>Invalid JSON file.</p></div>';
        }
    } else {
        echo '<div class="error"><p>File upload failed.</p></div>';
    }
}

// Handle importing media
function mi_handle_import_media() {
    if (isset($_POST['media_id'], $_POST['media_date'], $_POST['media_url'])) {
        foreach ($_POST['media_id'] as $id => $original_id) {
            $replacement_uploaded = !empty($_FILES['replace_file']['name'][$id]);

            if ($replacement_uploaded) {
                $tmp_name = $_FILES['replace_file']['tmp_name'][$id];
                $file_error = $_FILES['replace_file']['error'][$id];

                if ($file_error == UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['replace_file']['name'][$id]);
                    $upload_dir = wp_upload_dir();
                    $file_path = $upload_dir['path'] . '/' . $file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        if (mi_is_document_file($file_name)) {
                            mi_insert_attachment($file_path, $file_name, $_POST['media_date'][$id], $original_id);
                        } else {
                            $converted_file_path = mi_convert_to_webp_format($file_path);
                            if ($converted_file_path) {
                                mi_insert_attachment($converted_file_path, basename($converted_file_path), $_POST['media_date'][$id], $original_id);
                                unlink($file_path); // Remove the original file
                            } else {
                                echo '<div class="error"><p>Failed to convert file to WebP format: ' . esc_html($file_name) . '.</p></div>';
                            }
                        }
                    } else {
                        echo '<div class="error"><p>Failed to move uploaded file ' . esc_html($file_name) . '.</p></div>';
                    }
                } else {
                    echo '<div class="error"><p>File upload error for ' . esc_html($_FILES['replace_file']['name'][$id]) . ': ' . esc_html($file_error) . '</p></div>';
                }
            } else {
                $media_url = $_POST['media_url'][$id] ?? null;
                if ($media_url) {
                    $file_name = basename($media_url);
                    $upload_dir = wp_upload_dir();
                    $file_path = $upload_dir['path'] . '/' . $file_name;

                    if (mi_download_remote_file($media_url, $file_path)) {
                        if (mi_is_document_file($file_name)) {
                            mi_insert_attachment($file_path, $file_name, $_POST['media_date'][$id], $original_id);
                        } else {
                            $converted_file_path = mi_convert_to_webp_format($file_path);
                            if ($converted_file_path) {
                                mi_insert_attachment($converted_file_path, basename($converted_file_path), $_POST['media_date'][$id], $original_id);
                                unlink($file_path); // Remove the original file
                            } else {
                                echo '<div class="error"><p>Failed to convert file to WebP format: ' . esc_html($file_name) . '.</p></div>';
                            }
                        }
                    } else {
                        echo '<div class="error"><p>Failed to download file from URL: ' . esc_html($media_url) . '.</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-warning"><p>No replacement file or URL provided for media ID: ' . esc_html($original_id) . '.</p></div>';
                }
            }
        }
    } else {
        echo '<div class="error"><p>File upload failed. Please check the uploaded files and try again.</p></div>';
    }
}

// Helper function to insert an attachment into the media library
function mi_insert_attachment($file_path, $file_name, $post_date, $original_id) {
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

        wp_update_post(array(
            'ID' => $original_id,
            'post_date' => $post_date,
        ));

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

// Helper function to check if a file is a document
function mi_is_document_file($file_name) {
    $document_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv');
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    return in_array(strtolower($file_extension), $document_extensions);
}

// Helper function to convert a file to WebP format
function mi_convert_to_webp_format($file_path) {
    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $new_file_path = str_replace('.' . $file_extension, '.webp', $file_path);

    if (in_array(strtolower($file_extension), array('jpg', 'jpeg', 'png', 'gif'))) {
        $image = wp_get_image_editor($file_path);
        if (!is_wp_error($image)) {
            $image->set_quality(80); // Set quality for WebP
            $image->save($new_file_path, 'image/webp');
            return $new_file_path;
        }
    }

    return false;
}
