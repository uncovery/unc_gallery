<?php

if (!defined('WPINC')) {
    die;
}

/**
 * displays folder images while getting values from AJAX
 * this happens from a datepicker
 *
 * @return type
 */
function unc_display_ajax_folder() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // we get the date from the GET value
    $date_str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
    unc_gallery_display_var_init(array('date' => $date_str, 'echo' => true));
    return unc_display_folder_images();
}

/**
 * displays the admin images after an upload
 *
 * @return type
 */
function unc_gallery_images_refresh() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    unc_gallery_display_var_init(array('echo' => true));
    return unc_display_folder_images();
}

/**
 * This is the core function that actually is called when a shortcode
 * is parsed
 *
 * @param string $atts
 * @return string
 */
function unc_gallery_apply($atts = array()) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $check = unc_gallery_display_var_init($atts);
    if ($check) {
        return unc_gallery_display_page();
    } else {
        $err_text = implode("<br>", $UNC_GALLERY['errors']);
        return $err_text;
    }
}

/**
 * Process and validate $UNC_GALLERY['display'] settings
 *
 * @global type $UNC_GALLERY
 * @param type $atts
 * @return type
 */
function unc_gallery_display_var_init($atts = array()) {
    global $UNC_GALLERY, $post;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $possible_attributes = array(
        'type' => 'day',    // display type
        'date' => false, // which date?
        'file' => false,    // specifix file?
        'featured' => false,  // is there a featured file?
        'options' => false, // we cannot set it to an array here
        'start_time' => false, // time of the day when we start displaying this date
        'end_time' => false, // time of the day when we stop displaying this date
        'description' => false, // description for the whole day
        'details' => false, // description for individual files
        'echo' => false, // internal variable, used by AJAX call
        'offset' => false, // offset for date string to cover photos after midnight
        'limit_rows' => false,
        'limit_images' => false,
    );

    // check if all the attributes exist
    foreach ($atts as $key => $value) {
        if (!isset($possible_attributes[$key])) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("You have an invalid setting '$key' in your gallery shortcode!");}
            echo unc_display_errormsg("You have an invalid setting '$key' in your gallery shortcode!");
            return false;
        }
    }

    // parse the attributes
    $a = shortcode_atts($possible_attributes, $atts);
    $type = $a['type'];
    // we convert the start time and end time to unix timestamp for better
    // comparison
    $UNC_GALLERY['display']['range'] = array('start_time' => false, 'end_time' => false);
    foreach ($UNC_GALLERY['display']['range'] as $key => $value) {
        if ($a[$key]) {
            // convert to UNIX timestamp
            $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $a[$key]);
            // TODO: catch here if the date is invalid
            $UNC_GALLERY['display']['range'][$key] = $dtime->getTimestamp();
            // get the date for the same
            $var_name = 'date_' . $key;
            $$var_name = substr($a[$key], 0, 10);
        }
    }


    // several featured
    // we do not need to validate featured files since we only compare with the list
    // of found files in a folder
    if (strstr($a['featured'], ",")) {
        $UNC_GALLERY['display']['featured_image'] = explode(",", trim($a['featured']));
    } else if ($a['featured']) {
        $UNC_GALLERY['display']['featured_image'] = array(trim($a['featured']));
    } else {
        $UNC_GALLERY['display']['featured_image'] = array();
    }

    // there can be several options, separated by space
    if (!$a['options']) {
        $options = array();
    } else {
        $options = explode(" ", trim($a['options']));
    }
    $UNC_GALLERY['options'] = $options;
    $UNC_GALLERY['display']['echo'] = trim($a['echo']);

    // icon or image?
    $thumb = false;
    if ($type == 'icon') {
        $thumb = true;
    }

    $UNC_GALLERY['display']['description'] = trim($a['description']);
    $UNC_GALLERY['display']['limit_rows'] = trim($a['limit_rows']);
    $UNC_GALLERY['display']['limit_images'] = trim($a['limit_images']);

    // date
    $keywords = $UNC_GALLERY['keywords'];

    if ($a['end_time']) {
        $date_end_time = substr(trim($a['end_time']), 0, 10);
    }

    if ($a['start_time']) {
        $date_start_time = substr(trim($a['start_time']), 0, 10);
    }
    $UNC_GALLERY['display']['date_description'] = false; // false by default, only true if not set explicitly (latest or random date)

    if ($a['date'] && in_array($a['date'], $keywords['date'])) { // we have a latest or random date
        // get the latest or a random date if required
        if ($a['date'] == 'latest') {
            $date_str = unc_tools_date_latest();
        } else if ($a['date'] == 'random') {
            $date_str = unc_tools_date_random();
        }
        $UNC_GALLERY['display']['date_description'] = true;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($a['date'] && strstr($a['date'], ",")) { // we have several dates in the string
        $dates = explode(",", $a['date']);
        if (count($dates) > 2) {
            echo unc_display_errormsg("You can only enter 2 dates!");
            return false;
        }
        // validate both dates
        $date_str1 = unc_tools_validate_date(trim($dates[0]));
        if (!$date_str1) {
            echo unc_display_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        $date_str2 = unc_tools_validate_date(trim($dates[1]));
        if (!$date_str2) {
            echo unc_display_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        // create a list of dates between the 1st and the 2nd
        $date_arr = unc_tools_date_span($dates[0], $dates[1]);
        $UNC_GALLERY['display']['dates'] = $date_arr;
    } else if ($a['date']) {
        $date_str = unc_tools_validate_date($a['date']);
        if (!$date_str) {
            echo unc_display_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($a['end_time'] && $a['start_time']) {
        $date_arr = unc_tools_date_span($date_start_time, $date_end_time);
        $UNC_GALLERY['display']['dates'] = $date_arr;
    } else if ($a['end_time']) {
        $date_str = $date_end_time;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($a['start_time']) {
        $date_str = $date_start_time;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else { // no date set at all, take latest
        $date_str = unc_tools_date_latest();
        $UNC_GALLERY['display']['dates'] = array($date_str);
    }

    // details
    $details_raw = $a['details'];
    $UNC_GALLERY['display']['details'] = false;
    if ($details_raw) {
        // explode by colon
        $file_details = explode(";", $details_raw);
        if (count($file_details) == 0) {
            echo unc_display_errormsg("File details are invalid!");
            return;
        }
        foreach ($file_details as $file_detail) {
            $tmp_arr = explode(":", $file_detail);
            if (count($tmp_arr) !== 2) {
                echo unc_display_errormsg("File details are invalid!");
                return;
            }
            $details_filename = trim($tmp_arr[0]);
            $details_description = trim($tmp_arr[1]);
            $UNC_GALLERY['display']['details'][$details_filename] = $details_description;
        }
    }

    // options
    if (!isset($keywords['type'][$type])) {
        echo unc_display_errormsg("You have an invalid type value in your tag."
            . "<br>Valid options are: " . implode(", ", $keywords['type']));
        return false;
    }
    $possible_type_options = $keywords['type'][$type];
    foreach ($UNC_GALLERY['options'] as $option) {
        if (!in_array($option, $possible_type_options)) {
            echo unc_display_errormsg("You have an invalid option for the display type \"option\" in your tag."
                . "<br>Valid options are: " . implode(", ", $keywords['type'][$type]));
            return false;
        }
    }
    $UNC_GALLERY['display']['type'] = $a['type'];

    $UNC_GALLERY['display']['link'] = false;
    if (in_array('link', $UNC_GALLERY['options'])) {
        $UNC_GALLERY['display']['link'] = true;
    }

    $UNC_GALLERY['display']['slideshow'] = false;
    if (in_array('slideshow', $UNC_GALLERY['options'])) {
        $UNC_GALLERY['display']['slideshow'] = true;
    }

    $UNC_GALLERY['display']['date_selector'] = false;
    if (in_array('calendar', $options)) {
        $UNC_GALLERY['display']['date_selector'] = 'calendar';
    } else if (in_array('datelist', $options)) {
        $UNC_GALLERY['display']['date_selector'] = 'datelist';
    }
    if ($a['file']) {
        $UNC_GALLERY['display']['file'] = unc_tools_filename_validate($a['file']);
    } else {
        $UNC_GALLERY['display']['file'] = false;
        $UNC_GALLERY['display']['files'] = unc_tools_images_list();
    }

    if (count($UNC_GALLERY['display']['files']) == 0) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("No files found in date range!");}
        if ($UNC_GALLERY['no_image_alert'] == 'error') {
            $UNC_GALLERY['errors'][] = unc_display_errormsg("No images found for this date!");
        } else if ($UNC_GALLERY['no_image_alert'] == 'not_found') {
            $UNC_GALLERY['errors'][] = "No images available.";
        }
        return false;
    }

    // this is needed to the JS function calls used for the displays
    $slug = '';
    if (isset($post->post_name)) {
        $slug = str_replace("-", "_", $post->post_name);
    } else {
        $slug = 'none';
    }
    // we list all the slugs to make sure we do not re-use
    // practically we could just use incremental numbers, but debugging is easier with a name
    if (isset($UNC_GALLERY['slugs']) && in_array($slug, $UNC_GALLERY['slugs'])) {
        $slug = $slug . count($UNC_GALLERY['slugs']);
    }
    $UNC_GALLERY['slugs'][] = $slug;
    $UNC_GALLERY['display']['slug'] = $slug;

    return true;
}

/**
 * Display one post/page
 *
 * @global type $UNC_GALLERY
 * @return string
 */
function unc_gallery_display_page() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $D = $UNC_GALLERY['display'];
    $date = $D['dates'][0];

    // do not let wp manipulate linebreaks
    remove_filter('the_content', 'wpautop');

    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];

    // get a json datepicker
    $datepicker_div = '';
    $out = '';
    if ($D['date_description']) {
        $datepicker_div = "<span id=\"photodate\">Showing $date</span>";
    }

    // we need a unique ID for datepicker div targets
    /* global $post;
    $slug = 'ID_';
    if (isset($post->post_name)) {
        $slug .= str_replace("-", "_", $post->post_name);
    }
     */
    if ($D['date_selector'] == 'calendar' || $D['date_selector'] == 'datelist') {
        $avail_dates = unc_tools_folder_list($photo_folder);
        $fixed_dates = unc_display_fix_timezones($avail_dates);
        if (!$fixed_dates) {
            return "There are no images in the libray yet. Please upload some first.";
        }
        if ($D['date_selector'] == 'calendar') {
            $out .= "\n     <script type=\"text/javascript\">
            var availableDates = [\"" . implode("\",\"", array_keys($fixed_dates)) . "\"];
            var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
            jQuery(document).ready(function($) {
                datepicker_ready('{$date}');
            });
            </script>";
            $datepicker_div = "Pick a Date: <input type=\"text\" id=\"datepicker\" value=\"$date\" size=\"10\">";
        } else if ($D['date_selector'] == 'datelist') {
            $datepicker_div = "<select id=\"datepicker\" onchange=\"datelist_change()\">\n";
            foreach ($fixed_dates as $folder_date => $folder_files) {
                $counter = count($folder_files);
                $datepicker_div .= "<option value=\"$folder_date\">$folder_date ($counter)</option>\n";
            }
            $datepicker_div .="</select>\n";
        }
    }

    /* if ($D['slideshow'] == true) {
        wp_register_script('unc_gallery_lightslider_js', plugin_dir_url( __FILE__ ) . 'js/lightslider.min.js', array(), '4.1.1', true);
        wp_enqueue_script('unc_gallery_lightslider_js');
        wp_enqueue_style('unc_gallery_lightslider_css', plugin_dir_url( __FILE__ ) . 'css/lightslider.css');
    } */

    $date_path = unc_tools_date_path($D['dates'][0]);
    // TODO: This should check all dates
    if (!$date_path) {
        return;
    }

    if ($D['type'] == 'image' || $D['type'] == 'thumb') {
        $thumb = false;
        if ($D['type'] == 'thumb') {
            $thumb = true;
        }
        if (!isset($D['file']) || $D['file'] == 'latest') {
            $file = unc_tools_file_latest($date_path);
        } else if ($D['file'] == 'random') {
            $file = unc_tools_file_random($date_path);
        } else {
            $file = $D['file'];
        }
        $file_path = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] . DIRECTORY_SEPARATOR . $date_path . DIRECTORY_SEPARATOR . $file;
        $out = unc_display_image_html($file_path, $thumb, false);
    } else {
        $images_html = unc_display_folder_images();
        $delete_link = '';
        $limit_rows = '';
        if ($D['limit_rows']) {
            $limit_rows = 'limit_rows_' . $D['limit_rows'];
        }
        $out .= "
            <div class=\"unc_gallery\">
                $datepicker_div
                $delete_link
                <div class=\"photos $limit_rows\" id=\"datepicker_target\">
        $images_html
            </div>
            </div>
            <span style=\"clear:both;\"></span>";
    }

    return $out;
}

/**
 * Open a folder of a certain date and display all the images in there
 *
 * @global type $UNC_GALLERY
 * @return string
 */
function unc_display_folder_images() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $D = $UNC_GALLERY['display'];

    $date_str = $D['dates'][0];

    $header = '';
    if (current_user_can('manage_options') && is_admin()) {
        $url = admin_url('admin.php?page=unc_gallery_admin_menu');
        $header .= "
        <span class=\"delete_folder_link\">
            Sample shortcode for this day: <input id=\"short_code_sample\" onClick=\"SelectAll('short_code_sample');\" type=\"text\" value=\"[unc_gallery date=&quot;$date_str&quot;]\">
            <a href=\"$url&amp;folder_del=$date_str\">
                Delete Date: $date_str
            </a>
        </span>\n";
    }

    // get all the files in the folder with attributes
    $files = $D['files'];

    // display except for skipped files and files out of time range
    $images = '';
    $featured = '';

    $featured_fixed = false;
    if ($UNC_GALLERY['featured_size_for_mixed_sizes'] <> 'dynamic' && count($D['featured_image']) > 1) {
        $featured_fixed = $UNC_GALLERY['featured_size_for_mixed_sizes'];
    }

    /*if ($D['slideshow']) {
        $images .= '<ul id="lightSlider">';
    } */

    $i = 0;

    // limit images
    $UNC_GALLERY['not_shown'] = false;
    $max_images = intval($D['limit_images']);

    foreach ($files as $F) {
        // stop looping once we have the max number of images
        if ($max_images && $i >= $max_images) {
            break;
        } else if ($max_images && ($i == $max_images - 1)) {
            $not_shown = count($files) - $max_images;
            if ($not_shown > 0) {
                $UNC_GALLERY['not_shown']  = $not_shown;
            }
        }
        $F['index'] = $i;
        if (!$D['slideshow'] && $F['featured']) { // slideshow does not have features
            // select size for featured images
            if ($featured_fixed) {
                $feat_size = $featured_fixed;
            } else if ($UNC_GALLERY['featured_size'] <> 'dynamic') {
                $feat_size = $UNC_GALLERY['featured_size'];
            } else {
                if ($F['orientation'] == 'portrait') {
                    $feat_size = $UNC_GALLERY['featured_size_for_portrait'];
                } else {
                    $feat_size = $UNC_GALLERY['featured_size_for_landscape'];
                }
            }

            $height_css = 'rows_' . $feat_size;
            $featured .= "<div class=\"featured_photo $height_css\">\n"
                . unc_display_image_html($F['file_path'], false, $F)
                . "</div>\n";
        } /* else if ($Df['slideshow']) {
            $images .= "<li>\n"
                . unc_display_image_html($F['file_path'], false, $F)
                . '<p>' . unc_tools_file_desc($F) . '</p>'
                . "</li>\n";
        } */ else {
            $images .= "<div class=\"one_photo\">\n"
                . unc_display_image_html($F['file_path'], true, $F)
                . "</div>\n";
        }
        $i++;
    }
    /* if ($D['slideshow']) {
        $images .= '</ul>';
    } **/

    $photoswipe = '';
    if ($UNC_GALLERY['image_view_method'] == 'photoswipe') {
        $photoswipe = unc_display_photoswipe_js($files);
    }
    if ($UNC_GALLERY['post_keywords'] != 'none') {
        unc_display_tags_compare($files);
    }
    if ($UNC_GALLERY['post_categories'] != 'none') {
        unc_display_categories_compare($files);
    }

    if ($D['slideshow']) {
        $photoswipe = '';  /*'<script type="text/javascript">
        jQuery(document).ready(function() {
            var slider = jQuery("#lightSlider").lightSlider({
                adaptiveHeight:true,
                item:1,
                auto:true,
                slideMargin:0,
                loop:true,
                adaptiveHeight:true,
                mode:\'fade\',
                speed:800,
                pause:4000,
                });
        });
        </script>'; */
    }

    $out = $header . $featured . $images . $photoswipe;

    if ($D['echo']) {
        ob_clean();
        echo $out;
        wp_die();
    } else {
        return $out;
    }
}

function unc_display_tags_compare($F) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // get all image tags

    $post_id = get_the_ID();
    if (!$post_id) {
        return;
    }

    $append_tags = true;
    $setting = $UNC_GALLERY['post_keywords'];
    $set_split = explode("_", $setting);
    $selected_tags = $set_split[0];
    if (isset($set_split[1])) {
        $append_tags = false;
    }

    $photo_tags = array();
    foreach ($F as $FD) {
        if (!isset($FD[$selected_tags]['keywords'])) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "No $selected_tags Keywords set");}
            continue;
        }
        $image_tags = $FD[$selected_tags]['keywords'];
        if (!is_array($image_tags)) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "Keyword set is not an array (i.e. no keywords)");}
            continue;
        }
        foreach ($image_tags as $tag) {
            $photo_tags[] = $tag;
        }
    }
    if (count($photo_tags) == 0) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "collected zero keywords from array");}
        return false;
    }
    $photo_tags_unique = array_unique($photo_tags);

    // get all post tags
    $post_tags = array();
    $posttags_obj = get_the_tags();
    if ($posttags_obj) {
        foreach($posttags_obj as $tag) {
            $post_tags[] = $tag->name;
        }
    }
    $post_tags_unique = array_unique($post_tags);

    //compare
    $comp_result = unc_array_analyse($photo_tags_unique, $post_tags_unique);
    $missing_tags = $comp_result['only_in_1'];

    wp_set_post_tags($post_id, $missing_tags, $append_tags);
}

function unc_display_categories_compare($file_data) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $post_id = get_the_ID();
    if (!$post_id) {
        return;
    }

    // need to include taxonomy to use category stuff
    $admin_directory = ABSPATH . '/wp-admin/';
    require_once($admin_directory . 'includes/taxonomy.php');

    $curr_cats = array();
    // re-format the currnet categories so we can compare them

    foreach (get_the_category($post_id) as $c_cat) {
        $cat_name_id = strtolower($c_cat->name);
        $curr_cats[$cat_name_id]['name'] = $c_cat->name;
        $curr_cats[$cat_name_id]['id'] = $c_cat->cat_ID;
    }
    //XMPP_ERROR_trace("got existing post categories:", $curr_cats);

    // get all cats in nthe system
    $wp_all_cats = get_categories();
    $all_cat_index = array();
    // reformat them so we can search easier
    foreach ($wp_all_cats as $C) {
        $lower_name = strtolower($C->name);
        $all_cat_index[$lower_name]['id'] = $C->cat_ID;
        $all_cat_index[$lower_name]['parent'] = $C->parent;
    }
    //XMPP_ERROR_trace("got all categories:", $all_cat_index);

    // find out what the current setting is
    $setting = $UNC_GALLERY['post_categories'];
    // split into array:
    $setting_array = explode("_", $setting);
    $data_type = array_shift($setting_array); // remove the XPM/EXIF from the front of the array
    // iterate all files and get all the different levels of categories
    $cat_sets = array();

    // we go through all files in the post and get all categories for this post uniquely
    foreach ($file_data as $F) {
        // we go through the wanted fields from the setting
        $file_cats = array();
        foreach ($setting_array as $exif_code) {
            $cat_sets[$exif_code] = false; // with this we also catch empty levels
            if (!isset($F[$data_type][$exif_code])) {
                $value = '%%none%%';
            } else {
                $value = $F[$data_type][$exif_code];
            }
            $file_cats[] = $value;
        }
        // we try to create a code to make sure we do not make duplicates
        $cats_id = implode("-", $file_cats);
        $cat_sets[$cats_id] = $file_cats;
    }
    if (count($cat_sets) < 1) {
        return;
    }

    //XMPP_ERROR_trace("iterated all files, got category sets:", $cat_sets);

    $post_categories = array();

    // now we go through the collected categories and apply them to the poat
    foreach ($cat_sets as $cat_set) {
        //XMPP_ERROR_trace("Checking cat set:", $cat_set);
        // iterate each level
        $depth = 1; // depth of the hierarchical cats
        $next_parent = 0;
        if (!$cat_set) {
            continue;
        }
        foreach ($cat_set as $cat) {
            //XMPP_ERROR_trace("Checking cat:", $cat);
            // check if the post has a category of that name already
            $cat_id = strtolower($cat);
            if ($cat == '%%none%%') {
                //XMPP_ERROR_trace("cat is emtpy, continue");
                continue;
            } else if (isset($curr_cats[$cat_id])) {
                // get the existing cat ID and add it to the post
                $post_categories[] = $curr_cats[$cat_id]['id'];
                //XMPP_ERROR_trace("cat is set already get ID for final assignment", $curr_cats[$cat_id]['id']);
                continue;
            }
            // check if the current cat already exists in wordpress
            if (!isset($all_cat_index[$cat_id])) {
                $this_id = wp_create_category($cat, $next_parent);
                $next_parent = $this_id;
                //XMPP_ERROR_trace("Creating category $cat, ID:", $this_id);
            } else {
                //XMPP_ERROR_trace("Cat exists already, get parent for next level", $all_cat_index[$cat_id]['parent']);
                $next_parent = $all_cat_index[$cat_id]['parent'];
                $this_id = $all_cat_index[$cat_id]['id'];

            }
            $post_categories[] = $this_id; // collect the categories to add them to the post
            $depth++;
        }
    }

    // we need to check if the categories we added have the right hierarchy, so let's get the whole list first

    //$comp_result = unc_array_analyse($photo_tags_unique, $post_tags_unique);
    //$missing_cats = $comp_result['only_in_1'];
    // XMPP_ERROR_trace("assign final list of cats", $post_categories);
    wp_set_post_categories($post_id, $post_categories, false); // true means cats will be added, not replaced

}


function unc_display_image_html($file_path, $show_thumb, $file_data = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $out = '';
    if (!$file_data) {
        $F = unc_image_info_read($file_path);
    } else {
        $F = $file_data;
    }

    if ($show_thumb) {
        $shown_image = $F['thumb_url'];
        $class = '';
    } else {
        $shown_image = $F['file_url'];
        $class = 'featured_image';
    }

    $gal_text = '';
    if ($UNC_GALLERY['image_view_method'] == 'photoswipe') {
        $slug = $UNC_GALLERY['display']['slug'];
        if (!isset($F['index'])) {
            $F['index'] = 0;
            $F['index']++;
        }
        $gal_text = "onClick=\"unc_g_photoswipe_$slug({$F['index']}); return false;\"";
    } else if ($UNC_GALLERY['image_view_method'] == 'lightbox') {
        $gal_text = "data-lightbox=\"gallery_{$F['file_name']}\"";
    }
    // TODO: Decide on what the imamge description in HTML should look like.
    if ($UNC_GALLERY['not_shown']) {
        $overlay_text = "<span class=\"not_shown_overlay\">+" . $UNC_GALLERY['not_shown'] . "</span>";
    } else {
        $overlay_text = '';
    }
    $out .= "        <a href=\"{$F['file_url']}\" $gal_text title=\"image\">
            <img alt=\"image\" src=\"$shown_image\">$overlay_text
        </a>\n";
    if (current_user_can('manage_options') && is_admin()) {
        $out .= "         <button class=\"delete_image_link\" title=\"Delete Image\" onClick=\"delete_image('{$F['file_name']}','{$F['date_str']}')\">
            <img src=\"" . plugin_dir_url( __FILE__ ) . "/images/delete.png\" width=\"20px\" height=\"20px\">
            </button>";
    }
    return $out;
}

function unc_display_photoswipe_js($files) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $slug = $UNC_GALLERY['display']['slug'];
    $out = '
<script type="text/javascript">
    function unc_g_photoswipe_' . $slug . '(index) {
        var options = {
            index: index
        };
        var uncg_items_' . $slug . ' = [';
    foreach ($files  as $F) {
        $desc = unc_tools_file_desc($F);
        $out .= "
    {
        src: '{$F['file_url']}',
        w: {$F['exif']['file_width']},
        h: {$F['exif']['file_height']},
        msrc: '{$F['thumb_url']}',
        title: \"$desc\"
    },";
    }
    $out .= "];
        var pswpElement = document.querySelectorAll('.pswp')[0];
        var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, uncg_items_$slug, options);
        gallery.init();
    }
</script>";
    return $out;
}

function unc_display_errormsg($error) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    return "<div class=\"unc_gallery_error\">ERROR: $error</div>";
}

function unc_display_photoswipe() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $out = '<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

    <!-- Background of PhotoSwipe.
         It\'s a separate element as animating opacity is faster than rgba(). -->
    <div class="pswp__bg"></div>

    <!-- Slides wrapper with overflow:hidden. -->
    <div class="pswp__scroll-wrap">

        <!-- Container that holds slides.
            PhotoSwipe keeps only 3 of them in the DOM to save memory.
            Don\'t modify these 3 pswp__item elements, data is added later on. -->
        <div class="pswp__container">
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
        </div>

        <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
        <div class="pswp__ui pswp__ui--hidden">

            <div class="pswp__top-bar">

                <!--  Controls are self-explanatory. Order can be changed. -->

                <div class="pswp__counter"></div>

                <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>

                <button class="pswp__button pswp__button--share" title="Share"></button>

                <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>

                <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>

                <!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
                <!-- element will get class pswp__preloader--active when preloader is running -->
                <div class="pswp__preloader">
                    <div class="pswp__preloader__icn">
                      <div class="pswp__preloader__cut">
                        <div class="pswp__preloader__donut"></div>
                      </div>
                    </div>
                </div>
            </div>

            <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                <div class="pswp__share-tooltip"></div>
            </div>

            <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
            </button>

            <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
            </button>

            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>

        </div>

    </div>

</div>
';
    echo $out;
}
