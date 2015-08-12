<?php
// Admin functions

if (!defined('WPINC')) {
    die;
}

// add filter to scan content for activating
add_filter('the_content', 'unc_gallery', 0);
// initialize the plugin, create the upload folder
add_action('admin_init', 'unc_gallery_admin_init');
// add an admin menu
add_action('admin_menu', 'unc_gallery_admin_menu');

function unc_gallery_admin_menu() {
    add_menu_page(
        'Uncovery Gallery Options', // $page_title,
        'Uncovery Gallery', // $menu_title,
        'manage_options', // $capability,
        'unc_gallery_admin_menu', // $menu_slug,
        'unc_gallery_options' // $function, $icon_url, $position
    );
    $page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'Upload Images',  // $page_title
        'Upload Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_submenu', // menu_slug
        'unc_gallery_backend_image_upload' // function
    );
    add_action('admin_print_scripts-' . $page_hook_suffix, 'unc_gallery_admin_add_css_and_js');
}

function unc_gallery_admin_init() {
    global $WPG_CONFIG;
    register_setting( 'unc_gallery_settings_group', 'unc_gallery_setting' );
    add_settings_section( 'basic_settings', 'Upload images', 'unc_gallery_backend_image_upload', 'unc_gallery');
    //add_settings_field( 'field-one', 'Field One', 'unc_gallery_backend_image_upload', 'unc_gallery', 'basic_settings');
    // check if the upload folder exists:
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (!file_exists($dirPath)) {
        echo "There was an error creating the upload folder $dirPath!";
    }
    wp_register_script('jquery-form');
    wp_register_script('jquery-ui-datepicker');
}

/**
 * add additional CSS and JS
 */
function unc_gallery_admin_add_css_and_js() {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-form');
    wp_enqueue_style('bootstrap-css', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css');
    wp_enqueue_style('magnific-popup-css', plugin_dir_url( __FILE__ ) . 'css/magnific-popup.css');
    wp_enqueue_style('plugin_style-css', plugin_dir_url( __FILE__ ) . 'css/plugin_style.css');
    wp_enqueue_style('plugin_style-css', plugin_dir_url( __FILE__ ) . 'css/style1.css');
}

function unc_gallery_backend_image_upload() {
    ?>
    <div class="wrap">
        <h2>Upload Images</h2>
    </div>
    <form id="uploadForm" action="<?php echo plugins_url(); ?>/unc_gallery/unc_upload.php" method="POST" enctype="multipart/form-data">
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $(document).ready(function() {
                    var status = $('#targetLayer');
                    var options = {
                        target: '#targetLayer',
                        beforeSubmit: beforeSubmit,
                        uploadProgress: uploadProgress,
                        resetForm: true,
                        complete: complete
                    };
                    $('#uploadForm').submit(function() {
                        //if($('#userImage').val()) {
                            $('#loader-icon').show();
                            $(this).ajaxSubmit(options);
                            return false;
                        //}
                   });
                });
                function complete(xhr) {
                    $('#loader-icon').hide();
                    $('#targetLayer').html(xhr.responseText);
                }
                function uploadProgress(event, position, total, percentComplete) {
                    $("#progress-bar").width(percentComplete + '%');
                    $("#progress-bar").html('<div id="progress-status">' + percentComplete +' %</div>');
                }
                function beforeSubmit(formData, jqForm, options) {
                    $("#progress-bar").width('0%');
                    $('#targetLayer').html('');
                    return true;
                }

            });
        </script>
        <div class="image_upload_input">
            <label>Select files to upload:</label>
            <input type="file" id="userImage" name="userImage[]" class="demoInputBox" multiple required/>
        </div>
        <div class="image_upload_submit"><input type="submit" id="btnSubmit" value="submit" class="btnSubmit" /></div>
        <div id="progress-div"><div id="progress-bar"></div></div>
        <div id="targetLayer"></div>
    </form>
    <div id="loader-icon" style="display:none;"><img src="<?php echo plugins_url(); ?>/unc_gallery/images/LoaderIcon.gif" /></div>
    <?php
}