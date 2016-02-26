<?php
// Admin functions

if (!defined('WPINC')) {
    die;
}

function unc_gallery_admin_menu() {
    // the main page where we manage the options
    $main_options_page_hook_suffix = add_menu_page(
        'Uncovery Gallery Options', // $page_title,
        'Uncovery Gallery', // $menu_title,
        'manage_options', // $capability,
        'unc_gallery_admin_menu', // $menu_slug,
        'unc_gallery_admin_settings' // $function, $icon_url, $position
    );
    add_action('admin_print_scripts-' . $main_options_page_hook_suffix, 'unc_gallery_add_css_and_js');
    // let's rename that
    add_submenu_page(
        'unc_gallery_admin_menu',
        'Uncovery Gallery Settings',
        'Settings & Upload',
        'read',
        'unc_gallery_admin_menu',
        'unc_gallery_admin_settings'
    );
    // where we list up all the images
    $view_page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'Manage Images',  // $page_title
        'Manage Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_view', // menu_slug
        'unc_gallery_admin_display_images' // function
    );
    add_action('admin_print_scripts-' . $view_page_hook_suffix, 'unc_gallery_add_css_and_js');
}

/**
 * displayes the complete image catalogue for the admin
 * TODO: the content should only show the past few dates and collapse the rest
 * and then provide buttons for AJAX-loading of older content
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_display_images() {
    $out = "<h2>Uncovery Gallery: All Images</h2>\n";

    // we do not want to convert linebreaks
    remove_filter('the_content', 'wpautop');
    // check first if there is a folder to delete:
    $folder_del = filter_input(INPUT_GET, 'folder_del', FILTER_SANITIZE_STRING);
    if (!is_null($folder_del)) {
        // TODO: the return here should be in a notifcation area
        $out .= unc_date_folder_delete($folder_del);
    }

    // get a standard short-tag output for latest date with datepicker
    $out .= unc_gallery_apply(array('options'=> 'dateselector'));
    echo $out;
}

/**
 * This adds the Wordpress features for the admin pages
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_init() {
    global $UNC_GALLERY;


    add_settings_section(
        'unc_gallery_pluginPage_section',
        __('Uncovery Gallery Settings', 'wordpress'),
        'unc_gallery_settings_section_callback',
        'unc_gallery_settings_page' // need to match menu_slug
    );

    foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
        $prefix = $UNC_GALLERY['settings_prefix'];
        register_setting('unc_gallery_settings_page', $prefix . $setting);
        $setting_value = get_option($prefix . $setting, $D['default']);
        $args = array('setting' => $prefix . $setting, 'value'=> $setting_value, 'help'=> $D['help']);
        add_settings_field(
            $prefix . $setting,
            __(ucwords(str_replace("_", " ", $setting)), 'wordpress'),
            'unc_gallery_setting_text_field_render',
            'unc_gallery_settings_page',
            'unc_gallery_pluginPage_section',
            $args
        );
    }

    //add_settings_field( 'field-one', 'Field One', 'unc_gallery_backend_image_upload', 'unc_gallery', 'basic_settings');
    // check if the upload folder exists:
    $dirPath =  WP_CONTENT_DIR . $UNC_GALLERY['upload'];
    if (!file_exists($dirPath)) {
        echo unc_tools_errormsg("The upload folder $dirPath does not exist!");
        unc_gallery_plugin_activate();
    }
    wp_register_script('jquery-form', '/wp-includes/js/jquery/jquery.form.js');
    wp_register_script('jquery-ui-datepicker', '/wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js');
}

function unc_gallery_setting_text_field_render($A) {
    $out = "<input type='text' name='{$A['setting']}' value='{$A['value']}'> {$A['help']}\n";
    echo $out;
}

function unc_gallery_settings_section_callback(  ) {
    echo __( 'Basic Settings', 'wordpress' );
}

/**
 * this will manage the settings
 */
function unc_gallery_admin_settings() {
    echo '<div class="wrap">';
    echo '<form method="post" action="options.php">'. "\n";
    settings_fields('unc_gallery_settings_page');
    do_settings_sections( 'unc_gallery_settings_page');
    submit_button();
    echo "</form>\n";
    echo unc_gallery_admin_upload();
    echo unc_gallery_admin_rebuild_thumbs();
    echo unc_gallery_admin_delete_everything();
    echo "</div>";
}

function unc_gallery_admin_rebuild_thumbs() {
    // delete all thumbnails

    // iterate all image folders

    // create thumbnaisl


}

function unc_gallery_admin_delete_everything() {

}