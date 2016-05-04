<?php

if (!defined('WPINC')) {
    die;
}

/**
 * displays folder images while getting values from AJAX
 *
 * @return type
 */
function unc_display_ajax_folder() {
    // we get the date from the GET value
    $date_str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
    unc_gallery_display_var_init(array('date' => $date_str, 'echo' => true));
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    unc_gallery_add_css_and_js();
    $check = unc_gallery_display_var_init($atts);
    if ($check) {
        return unc_gallery_display_page();
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
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $a = shortcode_atts( array(
        'type' => 'day',    // display type
        'date' => 'latest', // which date?
        'file' => false,    // specifix file?
        'featured' => false,  // is there a featured file?
        'options' => false, // we cannot set it to an array here
        'start_time' => false, // time of the day when we start displaying this date
        'end_time' => false, // time of the day when we stop displaying this date
        'description' => false, // description for the whole day
        'details' => false, // description for individual files
        'echo' => false, // internal variable, used by AJAX call
        'offset' => false, // offset for date string to cover photos after midnight
    ), $atts);

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


    $featured_file = unc_tools_filename_validate($a['featured']);
    if ($featured_file) {
        $UNC_GALLERY['display']['featured_image'] = $a['featured']; // TODO: Sanitize & verify filename
    } else {
        $UNC_GALLERY['display']['featured_image'] = false;
    }

    // there can be several options, separated by space
    if (!$a['options']) {
        $options = array();
    } else {
        $options = explode(" ", $a['options']);
    }
    $UNC_GALLERY['options'] = $options;
    $UNC_GALLERY['display']['echo'] = $a['echo'];

    // icon or image?
    $thumb = false;
    if ($type == 'icon') {
        $thumb = true;
    }

    $UNC_GALLERY['display']['description'] = $a['description'];

    // date
    $keywords = $UNC_GALLERY['keywords'];
    $date_raw = $a['date'];

    if ($a['end_time']) {
        $date_end_time = substr($a['end_time'], 0, 10);
    }

    if ($a['start_time']) {
        $date_start_time = substr($a['start_time'], 0, 10);
    }

    if ($date_raw && in_array($date_raw, $keywords['date'])) { // we have a latest or random date
        // get the latest or a random date if required
        if ($date_raw == 'latest') {
            $date_str = unc_tools_date_latest();
        } else if ($date_raw == 'random') {
            $date_str = unc_tools_date_random();
        }
        $UNC_GALLERY['display']['date_description'] = true;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($date_raw) {
        $date_str = unc_tools_validate_date($date_raw);
        if (!$date_str) {
            echo unc_tools_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        $UNC_GALLERY['display']['date_description'] = false;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($date_raw && strstr($date_raw, ",")) { // we have several dates in the string
        $dates = explode(",", $date_raw);
        // validate both dates
        $date_str1 = unc_tools_validate_date(trim($dates[0]));
        if (!$date_str1) {
            echo unc_tools_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        $date_str2 = unc_tools_validate_date(trim($dates[1]));
        if (!$date_str2) {
            echo unc_tools_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        // create a list of dates between the 1st and the 2nd
        $date_arr = unc_tools_date_span($dates[0], $dates[1]);
        $UNC_GALLERY['display']['dates'] = $date_arr;

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
            echo unc_tools_errormsg("File details are invalid!");
            return;
        }
        foreach ($file_details as $file_detail) {
            $tmp_arr = explode(":", $file_detail);
            if (count($tmp_arr) !== 2) {
                echo unc_tools_errormsg("File details are invalid!");
                return;
            }
            $details_filename = trim($tmp_arr[0]);
            $details_description = trim($tmp_arr[1]);
            $UNC_GALLERY['display']['details'][$details_filename] = $details_description;
        }
    }

    // options
    if (!isset($keywords['type'][$type])) {
        echo unc_tools_errormsg("You have an invalid type value in your tag."
            . "<br>Valid options are: " . implode(", ", $keywords['type']));
        return false;
    }
    $possible_type_options = $keywords['type'][$type];
    foreach ($UNC_GALLERY['options'] as $option) {
        if (!in_array($option, $possible_type_options)) {
            echo unc_tools_errormsg("You have an invalid option for the display type \"option\" in your tag."
                . "<br>Valid options are: " . implode(", ", $keywords['type'][$type]));
            return false;
        }
    }
    $UNC_GALLERY['display']['type'] = $a['type'];

    $UNC_GALLERY['display']['link'] = false;
    if (in_array('link', $UNC_GALLERY['options'])) {
        $UNC_GALLERY['display']['link'] = true;
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
    return true;
}

function unc_gallery_display_page() {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

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
    $avail_dates = unc_tools_folder_list($photo_folder);
    if (!$avail_dates) {
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
        $datepicker_div = "Date: <input type=\"text\" id=\"datepicker\" value=\"$date\">";
    } else if ($D['date_selector'] == 'datelist') {
        $datepicker_div = "<select id=\"datepicker\" onchange=\"datelist_change()\">\n";
        foreach ($avail_dates as $folder_date => $folder_files) {
            $counter = count($folder_files);
            $datepicker_div .= "<option value=\"$folder_date\">$folder_date ($counter)</option>\n";
        }
        $datepicker_div .="</select>\n";
    }


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
        $file_path = $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] . DIRECTORY_SEPARATOR . $file;
        $out = unc_display_image_html($file_path, $thumb, false);
    } else {
        $images = unc_display_folder_images();
        $delete_link = '';
        $out .= "
            <div class=\"unc_gallery\">
                $datepicker_div
                $delete_link
                <div id=\"photos\">
        $images
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $D = $UNC_GALLERY['display'];

    $date_str = $D['dates'][0];

    $header = '';
    if (is_admin()) {
        $header .= "
        <span class=\"delete_folder_link\">
            Sample shortcode for this day: <input type=\"text\" value=\"[unc_gallery date=&quot;$date_str&quot;]\">
            <a href=\"?page=unc_gallery_admin_view&amp;folder_del=$date_str\">
                Delete Date: $date_str
            </a>
        </span>\n";
    }

    // get all the files in the folder with attributes
    // this needs to include the
    $files = $D['files'];

    // display except for skipped files and files out of time range
    $images = '';
    $featured = '';
    $i = 0;
    foreach ($files as $F) {
        $F['index'] = $i;
        if ($F['featured'] == true){
            $featured .= "<div class=\"featured_photo\">\n"
                . unc_display_image_html($F['file_path'], false, $F)
                . "</div>\n";
        } else {
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

    $out = $header . $featured . $images . $photoswipe;

    if ($D['echo']) {
        ob_clean();
        echo $out;
        wp_die();
    } else {
        return $out;
    }
}

function unc_display_image_html($file_path, $show_thumb, $file_data = false) {
    global $UNC_GALLERY;
    if (!$file_data) {
        $F = unc_tools_image_info_get($file_path);
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
        global $post;
        $slug = '';
        if (isset($post->post_name)) {
            $slug = str_replace("-", "_", $post->post_name);
        }
        $gal_text = "onClick=\"unc_g_photoswipe_$slug({$F['index']}); return false;\"";
    } else if ($UNC_GALLERY['image_view_method'] == 'lightbox') {
        $gal_text = "data-lightbox=\"gallery_{$F['file_name']}\"";
    }


    $out = "        <a href=\"{$F['file_url']}\" $gal_text title=\"{$F['description']}\">\n"
         . "            <img alt=\"{$F['description']}\" src=\"$shown_image\">\n"
         . "        </a>\n";
    if (is_admin()) {
        $out .= "         <button class=\"delete_image_link\" title=\"Delete Image\" onClick=\"delete_image('{$F['file_name']}','{$F['date_str']}')\">&#9851;</button>";
    }
    return $out;
}

function unc_display_photoswipe_js($files) {
    global $post;
    $slug = '';
    if (isset($post->post_name)) {
        $slug = str_replace("-", "_", $post->post_name);
    }

    $out = '
<script type="text/javascript">
    function unc_g_photoswipe_' . $slug . '(index) {
        var options = {
            index: index
        };
        var uncg_items_' . $slug . ' = [';
    foreach ($files  as $F) {
        $out .= "
    {
        src: '{$F['file_url']}',
        w: {$F['file_width']},
        h: {$F['file_height']},
        msrc: '{$F['thumb_url']}',
        title: \"{$F['description']}\"
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

function unc_display_photoswipe() {
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