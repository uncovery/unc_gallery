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
    // we get the date from the GET value
    $date_str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
    unc_gallery_display_var_init(array('date' => $date_str, 'ajax_show' => 'images'));
    return unc_display_images();
}

/**
 * displays the admin images after an upload
 *
 * @return type
 */
function unc_gallery_images_refresh() {
    global $UNC_GALLERY;
    unc_gallery_display_var_init(array('ajax_show' => 'images'));
    return unc_display_images();
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

    $check = unc_gallery_display_var_init($atts);
    if ($check) {
        return unc_gallery_display_page();
    } else {
        $err_text = implode("<br>", $UNC_GALLERY['errors']);
        $UNC_GALLERY['errors'] = array();
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

    $possible_attributes = array(
        'type' => 'day',    // display type
        'date' => 'latest', // which date?
        'file' => false,    // specific file?
        'featured' => false,  // is there a featured file?
        'options' => false, // we cannot set it to an array here
        'start_time' => false, // time of the day when we start displaying this date
        'end_time' => false, // time of the day when we stop displaying this date
        'description' => false, // description for the whole day
        'details' => false, // description for individual files
        'offset' => false, // offset for date string to cover photos after midnight
        'limit_rows' => false,
        'limit_images' => false,
        'ajax_show' => false, // what to refresh in case of an ajax call
        'filter' => false, // if type=tag, the tags will be here
        'files' => false, // internal variable, used by filters
        'chrono' => false // for chronological display
    );

    // defaults
    $UNC_GALLERY['not_shown'] = false;
    $UNC_GALLERY['errors'] = array();

    // check if all the attributes exist
    if (!is_array($atts)) {
        unc_tools_debug_trace(__FUNCTION__, $atts);
    }
    
    foreach ($atts as $key => $value) {
        if (!isset($possible_attributes[$key])) {
            echo unc_display_errormsg("You have an invalid setting '$key' in your gallery shortcode!");
            return false;
        }
    }

    // parse the attributes
    $a = shortcode_atts($possible_attributes, $atts);
    $type = $a['type'];

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
    $UNC_GALLERY['display']['options'] = $options;
    $UNC_GALLERY['display']['ajax_show'] = trim($a['ajax_show']);

    // icon or image?
    $thumb = false;
    if ($type == 'icon') {
        $thumb = true;
    }

    $UNC_GALLERY['display']['filter'] = trim($a['filter']);
    $UNC_GALLERY['display']['chrono'] = trim($a['chrono']);
    $UNC_GALLERY['display']['description'] = trim($a['description']);
    $UNC_GALLERY['display']['limit_rows'] = trim($a['limit_rows']);
    $UNC_GALLERY['display']['limit_images'] = trim($a['limit_images']);

    // date
    $keywords = $UNC_GALLERY['keywords'];

    $check = false;
    if ($type == 'day') {
        $check = unc_day_var_init($a);
    } else if ($type == 'filter') {
        $check = unc_filter_var_init($a);
    } else if ($type == 'image') {
        // $check = unc_image_var_init($a);
    } else if ($type == 'chrono') {
        $check = unc_chrono_var_init($a);
    }

    if (!$check) { // there was some critical error, let's return that
        return false;
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
        echo unc_display_errormsg("You have an invalid type ($type) value in your tag."
            . "<br>Valid options are: " . implode(", ", $keywords['type']));
        return false;
    }
    $possible_type_options = $keywords['type'][$type];
    foreach ($UNC_GALLERY['display']['options'] as $option) {
        if (!in_array($option, $possible_type_options)) {
            echo unc_display_errormsg("You have an invalid option for the display type \"option\" in your tag."
                . "<br>Valid options are: " . implode(", ", $keywords['type'][$type]));
            return false;
        }
    }
    $UNC_GALLERY['display']['type'] = $a['type'];

    // TODO: this is likely redundant
    $UNC_GALLERY['display']['link'] = false;
    if (in_array('link', $UNC_GALLERY['display']['options'])) {
        $UNC_GALLERY['display']['link'] = 'image';
    }

    $UNC_GALLERY['display']['slideshow'] = false;
    if (in_array('slideshow', $UNC_GALLERY['display']['options'])) {
        $UNC_GALLERY['display']['slideshow'] = true;
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

    $D = $UNC_GALLERY['display'];
    // do not let wp manipulate linebreaks
    remove_filter('the_content', 'wpautop');

    $out = '';
    $selector_div = '';

    // TODO: Thesee functions should be in the type_XXXX.inc.php file
    if ($D['type'] == 'day' && isset($D['dates'][0])) {
        $date = $D['dates'][0];

        $datepicker_div = '';
        if ($D['date_description']) {
            $datepicker_div = "<span id=\"photodate\">Showing $date</span>";
        }

        if ($D['date_selector'] == 'calendar' || $D['date_selector'] == 'datelist') {
            $avail_dates = unc_tools_folder_list();
            if (count($avail_dates) == 0) {
                return "There are no images in the libray yet. Please upload some first.";
            }
            if ($D['date_selector'] == 'calendar') {
                $out .= "\n     <script type=\"text/javascript\">
                var availableDates = [\"" . implode("\",\"", array_keys($avail_dates)) . "\"];
                var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
                jQuery(document).ready(function($) {
                    datepicker_ready('{$date}');
                });
                </script>";
                $selector_div = "Pick a Date: <input type=\"text\" id=\"datepicker\" value=\"$date\" size=\"10\">";
            } else if ($D['date_selector'] == 'datelist') {
                $selector_div = "<select id=\"datepicker\" onchange=\"datelist_change()\">\n";
                foreach ($avail_dates as $folder_date => $counter) {
                    $selector_div .= "<option value=\"$folder_date\">$folder_date ($counter)</option>\n";
                }
                $selector_div .="</select>\n";
            }
        }
        $date_path = unc_tools_date_path($D['dates'][0]);

        // TODO: This should check all dates
        if (!$date_path) {
            return;
        }
    } else if (isset($D['filter_arr'])) { // type = filter
        $filter_arr = $D['filter_arr'];

        if ($filter_arr[1] == 'map') {
            $selector_div = unc_filter_map_data($filter_arr[0])
                . "<div id=\"filter_selector\">\n</div>\n";
        } else {
            $selector_div = "<div id=\"filter_selector\">\n"
                . unc_filter_choice($filter_arr)
                . "</div>\n";
        }
    } else if ($D['type'] == 'chrono') {
        $chrono_arr = $D['chrono_arr'];
        $selector_div = unc_chrono_data($chrono_arr);
    } else {
        $selector_div = "No images in the database!";
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
        $file_path = $UNC_GALLERY['upload_path'] . "/" . $UNC_GALLERY['photos'] . "/" . $date_path . "/" . $file;
        if (isset($UNC_GALLERY['display']['options']) && in_array('link_post', $UNC_GALLERY['display']['options'])) {
            $link_type = 'link_post';
        } else {
            $link_type = false;
        }
        $out .= unc_display_image_html($file_path, $thumb, false, $link_type);
    } else { // type = day
        $images_html = unc_display_images();
        $delete_link = '';
        $limit_rows = '';
        if ($D['limit_rows']) {
            $limit_rows = 'limit_rows_' . $D['limit_rows'];
        }
        $out .= "
                $selector_div
                $delete_link
                <div class=\"photos $limit_rows\" id=\"selector_target\">
        $images_html
                </div>
";
        if ($D['ajax_show'] !== 'all') { // we wrap into this div except when we requild the page with ajax, in that case the div is kept
            $out = "
            <div class=\"unc_gallery\" id=\"unc_gallery\">
            $out
            </div>
            <span style=\"clear:both;\"></span>";
        }
    }
    return $out;
}

/**
 * Open a folder of a certain date and display all the images in there
 *
 * @global type $UNC_GALLERY
 * @return string
 */
function unc_display_images() {
    global $UNC_GALLERY;

    $D = $UNC_GALLERY['display'];

    $header = '';
    if (isset($D['dates']) && count($D['dates']) > 0) {
        $date_str = $D['dates'][0];
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
    }

    // get all the files in the folder with attributes
    $files = $D['files'];
    if (!$files || count($files) == 0) { // we have no files
        return;
    }

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
    $max_images = intval($D['limit_images']);
    $counter = 0;

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
        if (!$D['slideshow'] && $F['featured']) {
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
            $counter ++;
            $featured .= "<div class=\"featured_photo $height_css\">\n"
                . unc_display_image_html($F['file_path'], false, $F)
                . "</div>\n";
        /*} else if ($Df['slideshow']) { // slideshow does not have features
            $images .= "<li>\n"
                . unc_display_image_html($F['file_path'], false, $F)
                . '<p>' . unc_tools_file_desc($F) . '</p>'
                . "</li>\n";*/
        } else {
            $counter++;
            $images .= "<div class=\"one_photo\">\n"
                . unc_display_image_html($F['file_path'], true, $F)
                . "</div>\n";
        }
        $i++;
    }

    $photoswipe = '';
    if ($UNC_GALLERY['image_view_method'] == 'photoswipe') {
        $photoswipe = unc_display_photoswipe_js($files);
    }
    if ($UNC_GALLERY['post_keywords'] != 'none') {
        $check_tags = unc_tags_apply($files);
        if ($check_tags) {

        }
    }
    if ($UNC_GALLERY['post_categories'] != 'none') {
        $check_cats = unc_categories_apply($files);
        if ($check_cats) {
            
        }
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

    $summary = "<div class=\"images_summary\">$counter images</div>";
    $out = $header . $featured . $images . $photoswipe . $summary;

    if ($D['ajax_show'] == 'images') {
        ob_clean();
        echo $out;
        wp_die();
    } else {
        return $out;
    }
}

/**
 * Display one single image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @param type $show_thumb
 * @param type $file_data
 * @param type $link_type
 * @return string
 */
function unc_display_image_html($file_path, $show_thumb, $file_data = false, $link_type = false) {
    global $UNC_GALLERY;
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

    // what do we link to?
    if (!$link_type) {
        $link_url = $F['file_url'];
    } else if ($link_type == 'link_post') {
        $link_url = get_post_permalink();
    }

    $out .= "        <a href=\"$link_url\" $gal_text title=\"{$F['file_name']}\">
            <img alt=\"image\" src=\"$shown_image\">$overlay_text
        </a>\n";
    if (current_user_can('manage_options') && is_admin()) {
        $out .= "         <button class=\"delete_image_link\" title=\"Delete Image\" onClick=\"delete_image('{$F['file_name']}','{$F['date_str']}')\">
            <img src=\"" . plugin_dir_url( __FILE__ ) . "images/delete.png\" width=\"20\" height=\"20\" alt=\"Delete Image\">
            </button>";
    }
    return $out;
}

/**
 * generate the file list for photoswipe display
 *
 * @global type $UNC_GALLERY
 * @param type $files
 * @return type
 */
function unc_display_photoswipe_js($files) {
    global $UNC_GALLERY;

    $slug = $UNC_GALLERY['display']['slug'];
    $out = '
<script type="text/javascript">
    function unc_g_photoswipe_' . $slug . '(index) {
        var options = {
            index: index
        };
        var uncg_items_' . $slug . ' = [';
    foreach ($files as $F) {
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

/**
 * Display an error message
 *
 * @global type $UNC_GALLERY
 * @param type $error
 * @return type
 */
function unc_display_errormsg($error) {
    global $UNC_GALLERY;

    return "<div class=\"unc_gallery_error\">ERROR: $error</div>";
}

/**
 * Display the photswipe HTML. This comes at the bottom of the page, is normally not visible.
 *
 * @global type $UNC_GALLERY
 */
function unc_display_photoswipe() {
    global $UNC_GALLERY;
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
