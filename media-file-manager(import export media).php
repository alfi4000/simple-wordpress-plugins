<?php
/*
Plugin Name: Media Manager (Import Export)
Description: Combines Media Exporter and Importer into a single plugin with tabs and a modern design.
Version: 1.4.0
Author: alfi4000
Author URI: https://github.com/alfi4000
Plugin URI: https://github.com/alfi4000/simple-wordpress-plugins/blob/main/media-file-manager(import export media).php
Plugin Home Page URI: https://github.com/alfi4000/simple-wordpress-plugins
Requires at least: 5.8
Tested up to: 6.7.1
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function mm_add_admin_menu() {
    add_menu_page(
        'Media Manager',
        'Media Manager',
        'manage_options',
        'media-manager',
        'mm_admin_page',
        'dashicons-admin-media',
        6
    );
}
add_action('admin_menu', 'mm_add_admin_menu');

// Enqueue scripts
function mm_enqueue_scripts($hook) {
    if ('toplevel_page_media-manager' !== $hook) {
        return;
    }
    wp_enqueue_script('mm-admin-script', plugin_dir_url(__FILE__) . 'mm-admin.js', array('jquery'), null, true);
    wp_localize_script('mm-admin-script', 'mm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'mm_enqueue_scripts');

// Admin page content
function mm_admin_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'welcome';
    ?>
    <div class="wrap">
        <h1>Media Manager</h1>
        <div class="nav-tab-wrapper">
            <a href="?page=media-manager&tab=welcome" class="nav-tab <?php echo $active_tab == 'welcome' ? 'nav-tab-active' : ''; ?>">Welcome</a>
            <a href="?page=media-manager&tab=importer" class="nav-tab <?php echo $active_tab == 'importer' ? 'nav-tab-active' : ''; ?>">Importer</a>
            <a href="?page=media-manager&tab=exporter" class="nav-tab <?php echo $active_tab == 'exporter' ? 'nav-tab-active' : ''; ?>">Exporter</a>
            <a href="?page=media-manager&tab=json-files" class="nav-tab <?php echo $active_tab == 'json-files' ? 'nav-tab-active' : ''; ?>">JSON Files</a>
            <a href="?page=media-manager&tab=credits" class="nav-tab <?php echo $active_tab == 'credits' ? 'nav-tab-active' : ''; ?>">Credits</a>
        </div>

        <div id="welcome" class="tab-content <?php echo $active_tab == 'welcome' ? 'tab-content-active' : ''; ?>">
            <h2>Welcome to Media Manager</h2>
            <p>This plugin allows you to import and export media files with ease. You can manage your media files, keep your media library organized, and ensure that your media files are always up-to-date.</p>
            <p>Use the tabs above to navigate through the different functionalities of the plugin.</p>
        </div>

        <div id="importer" class="tab-content <?php echo $active_tab == 'importer' ? 'tab-content-active' : ''; ?>">
            <h2>Media Importer</h2>
            <form id="import-form" method="post" enctype="multipart/form-data">
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

        <div id="exporter" class="tab-content <?php echo $active_tab == 'exporter' ? 'tab-content-active' : ''; ?>">
            <h2>Media Exporter</h2>
            <p>Click the button below to export media files information as a JSON file.</p>
            <form id="export-form" method="post">
                <input type="submit" name="export_media" class="button button-primary" value="Export Media">
            </form>
            <?php
            if (isset($_POST['export_media'])) {
                me_export_media();
            }
            ?>
        </div>

        <div id="json-files" class="tab-content <?php echo $active_tab == 'json-files' ? 'tab-content-active' : ''; ?>">
            <h2>JSON Files</h2>
            <p>List of created JSON files:</p>
            <?php me_list_json_files(); ?>
        </div>

        <div id="credits" class="tab-content <?php echo $active_tab == 'credits' ? 'tab-content-active' : ''; ?>">
            <h2>Credits</h2>
            <p>This plugin was developed by <a href="https://github.com/alfi4000" target="_blank">alfi4000</a>.</p>
            <p>For more information, visit the <a href="https://github.com/alfi4000/simple-wordpress-plugins" target="_blank">plugin home page</a>.</p>
        </div>
    </div>

    <div id="edit-popup" class="edit-popup">
        <div class="edit-popup-content">
            <span class="close-btn">&times;</span>
            <h2>Edit JSON File</h2>
            <form id="edit-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_file_path" id="edit_file_path">
                <textarea name="edit_file_content" id="edit_file_content" rows="20" cols="80"></textarea>
                <input type="submit" name="save_edit_file" class="button button-primary" value="Save">
            </form>
        </div>
    </div>

    <style>
        .nav-tab-wrapper {
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .nav-tab {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            border: 1px solid #ddd;
            border-bottom: none;
            background-color: #f9f9f9;
            cursor: pointer;
        }
        .nav-tab-active {
            border-bottom: 2px solid #0073aa;
            background-color: #fff;
            color: #0073aa;
        }
        .tab-content {
            display: none;
        }
        .tab-content-active {
            display: block;
        }
        .json-file-list {
            margin-top: 20px;
        }
        .json-file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .json-file-item button {
            background-color: #dc3232;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .edit-button {
            background-color: #0073aa;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            margin-left: 10px;
        }
        .edit-popup {
            display: none;
            position: fixed;
            z-index: 1;
            left: 10%; /* Adjusted to move the popup to the right */
            top: 0;
            width: 80%; /* Adjusted to fit within the screen */
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .edit-popup-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .updated {
            background-color: #dff2bf;
            color: #444;
            border: 1px solid #c6ef8e;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
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
                        mi_insert_attachment($file_path, $file_name, $_POST['media_date'][$id], $original_id);
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
                        mi_insert_attachment($file_path, $file_name, $_POST['media_date'][$id], $original_id);
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

// Handle editing JSON file
function mm_handle_save_edit_file() {
    check_ajax_referer('mm_save_edit_file');
    if (isset($_POST['edit_file_path']) && isset($_POST['edit_file_content'])) {
        $file_path = sanitize_text_field($_POST['edit_file_path']);
        $file_content = sanitize_textarea_field($_POST['edit_file_content']);

        if (file_exists($file_path)) {
            file_put_contents($file_path, $file_content);
            wp_send_json_success('File updated successfully!');
        } else {
            wp_send_json_error('File not found.');
        }
    } else {
        wp_send_json_error('Invalid request.');
    }
}
add_action('wp_ajax_mm_save_edit_file', 'mm_handle_save_edit_file');

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

// List JSON files
function me_list_json_files() {
    $upload_dir = wp_upload_dir();
    $json_files = glob($upload_dir['path'] . '/media-export-*.json');

    if (empty($json_files)) {
        echo '<p>No JSON files found.</p>';
        return;
    }

    echo '<div class="json-file-list">';
    foreach ($json_files as $file_path) {
        $file_name = basename($file_path);
        echo '<div class="json-file-item">';
        echo '<span>' . esc_html($file_name) . '</span>';
        echo '<button class="edit-button" data-file-path="' . esc_attr($file_path) . '">Edit</button>';
        echo '<form method="post" style="display:inline;">';
        echo '<input type="hidden" name="delete_file" value="' . esc_attr($file_path) . '">';
        echo '<button type="submit" name="delete_json_file">Delete</button>';
        echo '</form>';
        echo '</div>';
    }
    echo '</div>';
}

// Handle deleting JSON files
function mm_handle_delete_json_file() {
    check_ajax_referer('mm_delete_json_file');
    if (isset($_POST['delete_file'])) {
        $file_path = sanitize_text_field($_POST['delete_file']);
        if (file_exists($file_path)) {
            unlink($file_path);
            wp_send_json_success('File deleted successfully!');
        } else {
            wp_send_json_error('File not found.');
        }
    } else {
        wp_send_json_error('Invalid request.');
    }
}
add_action('wp_ajax_mm_delete_json_file', 'mm_handle_delete_json_file');

function mm_handle_load_file_content() {
    if (isset($_POST['file_path'])) {
        $file_path = sanitize_text_field($_POST['file_path']);
        if (file_exists($file_path)) {
            $file_content = file_get_contents($file_path);
            wp_send_json_success(array('content' => $file_content));
        } else {
            wp_send_json_error(array('error' => 'File not found.'));
        }
    } else {
        wp_send_json_error(array('error' => 'Invalid request.'));
    }
}
add_action('wp_ajax_mm_load_file_content', 'mm_handle_load_file_content');




mm-admin.js:
jQuery(document).ready(function($){
    // Show the edit popup with the file content
    $('.edit-button').click(function() {
        var filePath = $(this).data('file-path');
        $('#edit_file_path').val(filePath);
        
        $.ajax({
            url: mm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mm_load_file_content',
                file_path: filePath
            },
            success: function(response) {
                if (response.success) {
                    $('#edit_file_content').val(response.data.content);
                    $('#edit-popup').fadeIn();
                } else {
                    alert('Could not load file content: ' + response.data.error);
                }
            }
        });
    });

    // Save the edited file content
    $('#edit-form').submit(function(e) {
        e.preventDefault();
        var filePath = $('#edit_file_path').val();
        var fileContent = $('#edit_file_content').val();

        $.ajax({
            url: mm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mm_save_edit_file',
                file_path: filePath,
                file_content: fileContent
            },
            success: function(response) {
                if (response.success) {
                    alert('File updated successfully!');
                    $('#edit-popup').fadeOut();
                } else {
                    alert('Error: ' + response.data.error);
                }
            }
        });
    });

    // Close the edit popup
    $('.close-btn').click(function() {
        $('#edit-popup').fadeOut();
    });

    // Handle delete button click
    $(document).on('click', 'button[name="delete_json_file"]', function(event) {
        event.preventDefault();
        var deleteFile = $(this).closest('form').find('input[name="delete_file"]').val();

        $.ajax({
            url: mm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mm_delete_json_file',
                delete_file: deleteFile
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });
});
