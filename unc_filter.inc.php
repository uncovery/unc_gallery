<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Process the filter
 *
 * @global type $UNC_GALLERY
 * @global type $wpdb
 * @return string
 */
function unc_filter() {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__);}

    $D = $UNC_GALLERY['display'];
    $valid_options = $UNC_GALLERY['keywords']['type']['filter'];

    $options = $D['options'];

    $filter_str = $D['filter'];
    $filter_arr = explode("|", $filter_str);
    $filter_type = $filter_arr[0];
    $filter_key = $filter_arr[1];

    // get the possible filter values from the codes
    $check = unc_filter_check_type($filter_type, $filter_key);
    if ($check) {
        return $check;
    }

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $sql = "SELECT file_name, att_value FROM $att_table_name
        LEFT JOIN $img_table_name ON id=file_id
        WHERE `group` = '$filter_type' AND att_name = '$filter_key'
        GROUP BY att_value;";
    $filter_data = $wpdb->get_results($sql, 'ARRAY_A');

    $values = array();
    foreach ($filter_data as $F) {
        $values[] = $F['att_value'];
    }
    $unique_vals = array_unique($values);
    asort($unique_vals);

    // display the optional tag list
    if (in_array('dropdown', $options)) {
        $out = "<select id=\"filter\" onchange=\"filter_change('$filter_type', '$filter_key')\">\n"
            . "<option value=\"false\" selected=\"selected\">Please select</option>\n";
        foreach ($unique_vals as $keyword) {
            $out .= "<option value=\"$keyword\">$keyword</option>\n";
        }
        $out .="</select>\n";

    } else if (in_array('list', $options)) {

    } else if (in_array('map', $options)) {
        // only works with GPS
        return "Map";
    } else {
        $val_opt_text = implode(", ", $valid_options);
        $out = unc_display_errormsg("You have an option set () that us not compatible with filters! Valid options are: $val_opt_text");
    }
    $out .= "<div id=\"filter_result\"></div>";

    return $out;
}

function unc_filter_result(){
    global $wpdb, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__);}
    ob_clean();
    $filter_group = filter_input(INPUT_GET, 'filter_group', FILTER_SANITIZE_STRING);
    $filter_key = filter_input(INPUT_GET, 'filter_key', FILTER_SANITIZE_STRING);
    $filter_value = filter_input(INPUT_GET, 'filter_value', FILTER_SANITIZE_STRING);

    $check = unc_filter_check_type($filter_group, $filter_key);
    if ($check) {
        return $check;
    }

    if ($filter_group == 'xmp') {
        $filter_value = htmlentities($filter_value);
    }

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $sql = "SELECT path_table.att_value as file_path, file_time FROM $att_table_name AS search_table
        LEFT JOIN $img_table_name ON search_table.file_id=id
        LEFT JOIN $att_table_name AS path_table ON search_table.file_id = path_table.file_id
        WHERE search_table.`group` = %s AND search_table.att_name = %s AND search_table.att_value = %s AND path_table.att_name='file_path'";
    $filter_data = $wpdb->get_results($wpdb->prepare($sql, $filter_group, $filter_key, $filter_value), 'ARRAY_A');

    $files = array();
    foreach ($filter_data as $D) {
        $F = unc_image_info_read($D['file_path']);
        $files[$D['file_time']] = $F;
    }
    unc_gallery_display_var_init(array('files' => $files, 'echo' => true));
    return unc_display_folder_images();
}

function unc_filter_check_type($group, $key) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // get the possible filter values from the codes
    $codes = $UNC_GALLERY['codes'];
    if (!isset($codes[$group])) {
        $valid_filter_types_arr = array_keys($codes);
        $valid_filter_types = implode(",", $valid_filter_types_arr);
        return unc_display_errormsg("You have an invalid filter type set. Possible values are: $valid_filter_types");
    }
    $code_group = $codes[$group];
    if (!isset($code_group[$key])) {
        $valid_filter_keys_arr = array_keys($code_group);
        $valid_filter_keys = implode(",", $valid_filter_keys_arr);
        return unc_display_errormsg("You have an invalid filter key set. Possible values are: $valid_filter_keys");
    }
    return false;
}


/**
 * Compare existing post tags with the image and fix missing ones.
 *
 * @global type $UNC_GALLERY
 * @param type $F
 * @return boolean
 */
function unc_tags_apply($F) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "file_data");}

    // do we havea post? If so get the id, otherwise bail
    $post_id = get_the_ID();
    if (!$post_id) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "No post ID available");}
        return;
    }

    // we assume first we append tags
    $append_tags = true;
    // get the system setting
    $setting = $UNC_GALLERY['post_keywords'];
    // it's a string a_b_c, split it
    $set_split = explode("_", $setting);
    //
    $selected_tags = $set_split[0];
    if (isset($set_split[1])) {
        $append_tags = false;
    }

    // let's create an array that will hold a list of unique tags of this post
    $photo_tags = array();
    // lets iterate all files
    foreach ($F as $FD) {
        // if the file has no keywords, continue to next one
        if (!isset($FD[$selected_tags]['keywords'])) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "No $selected_tags Keywords set");}
            continue;
        }
        // otherwise, the field is set, check if we have keywords in it
        $image_tags = $FD[$selected_tags]['keywords'];
        if (!is_array($image_tags)) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "Keyword set is not an array (i.e. no keywords)");}
            continue;
        }
        // now, we have tags, go through them
        foreach ($image_tags as $tag) {
            // we lowercase them to make them comparable
            $photo_tags[] = strtolower($tag);
        }
    }
    if (count($photo_tags) == 0) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "collected zero keywords from array");}
        return false;
    }

    //$photo_tags_unique = array_unique($photo_tags);
    //asort($photo_tags_unique);

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("new collected tags from Photos:", $photo_tags_unique);}

    // in case there are no tags in the photos, we won't do anything
    if (count($photo_tags_unique) == 0) {
        return;
    }

    // get all post tags
    $post_tags = array();
    $posttags_obj = get_the_tags();
    if ($posttags_obj) {
        foreach($posttags_obj as $tag) {
            $post_tags[] = strtolower($tag->name);
        }
    }

    //$post_tags_unique = array_unique($post_tags);
    //asort($post_tags_unique);

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("new collected tags from Post:", $post_tags_unique);}


    $comp_result = unc_tools_array_analyse($photo_tags_unique, $post_tags_unique);
    $complete_set = $comp_result['complete_set'];
    asort($complete_set);
    $missing_tags = $comp_result['only_in_1'];

    $retval = false;
    // if we append tags, we only look for the missing ones.
    if ($append_tags) {
        if (count($missing_tags) > 0) {
            $retval = true;
            wp_set_post_tags($post_id, $missing_tags, $append_tags);
        }
    } else if ($complete_set != $post_tags_unique) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, array('post' => $post_tags_unique, 'set' => $complete_set));}
        // if we replace tags, we overwrite only if the tags are not identical
        wp_set_post_tags($post_id, $photo_tags_unique, $append_tags);
        $retval = true;
    }
    return $retval;
}

/**
 * Compare existing post categories with the image and fixing the missing
 *
 * @global type $UNC_GALLERY
 * @param type $file_data
 * @return type
 */
function unc_categories_apply($file_data) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "File_data");}

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

    $has_cats = false;
    // we go through all files in the post and get all categories for this post uniquely
    foreach ($file_data as $F) {
        // we go through the wanted fields from the setting
        $file_cats = array();
        foreach ($setting_array as $exif_code) {
            $cat_sets[$exif_code] = false; // with this we also catch empty levels
            if (!isset($F[$data_type][$exif_code])) {
                $value = '%%none%%';
            } else {
                $has_cats = true;
                $value = $F[$data_type][$exif_code];
            }
            $file_cats[] = $value;
        }
        // we try to create a code to make sure we do not make duplicates
        $cats_id = implode("-", $file_cats);
        $cat_sets[$cats_id] = $file_cats;
    }
    if (!$has_cats) {
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
                $next_parent = $curr_cats[$cat_id]['id'];
                continue;
            }
            // check if the current cat already exists in wordpress
            if (!isset($all_cat_index[$cat_id])) {
                $this_id = wp_create_category($cat, $next_parent);
                //XMPP_ERROR_trace("Creating category $cat, ID: $this_id, Parent $next_parent");
            } else {
                //XMPP_ERROR_trace("Cat exists already, get parent for next level", $all_cat_index[$cat_id]['parent']);
                $this_id = $all_cat_index[$cat_id]['id'];

            }
            $post_categories[] = $this_id; // collect the categories to add them to the post
            $next_parent = $this_id;
            $depth++;
        }
    }

    // TODO only update if we need to!
    // we need to check if the categories we added have the right hierarchy, so let's get the whole list first
    //XMPP_ERROR_trace("assign final list of cats", $post_categories);
    wp_set_post_categories($post_id, $post_categories, false); // true means cats will be added, not replaced
    //XMPP_ERROR_trigger("test");
}


function unc_map_shortcode($atts = array()) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    // first, get all categories and their data
    if (strlen($UNC_GALLERY['google_api_key']) < 1) {
        return "You need to set a google api key in the configuration to use this feature!";
    }

    $cats = get_categories();


    $lats = array();
    $lens = array();
    $markers_list = "var points = [\n";
    $z_index = 100;

    $levels = array(
        'Countries' => 3,
        'Region' => 5,
        'Divesite'  => 10,
    );

    $level = filter_input(INPUT_GET, 'level', FILTER_SANITIZE_STRING);
    $level_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if (!isset($levels[$level])) {
        $level = 'Countries';
    }

    $level_name = '';

    foreach ($cats as $C){
        if ($level_id == $C->cat_ID) {
            $level_name = $C->name;
        }

        if ($level == 'Countries' && $C->parent <> 0) {
            continue;
        } else if ($level == 'Countries') { // link to region map
            $link = get_page_link() . '?level=Region&id=' . $C->cat_ID;
        } else if (($level == 'Region' || $level == 'Divesite') && $C->parent <> $level_id) {
            continue;
        } else if ($level == 'Region') {
            $link = get_page_link() . '?level=Divesite&id=' . $C->cat_ID;
        } else if ($level == 'Divesite') {
            $link = get_category_link($C->cat_ID);
        }
        // var_dump($C);
        $location_name = $C->name . " (" . $C->count . " dives)";

        //echo $C->parent;
        //echo $C->description;
        //echo $C->slug;
        //echo $C->count;

        $coords = explode(",", $C->description);
        if (count($coords) != 2) {
            continue;
        }
        $lat = trim($coords[0]);
        $lats[] = $lat;
        $len = trim($coords[1]);
        $lens[] = $len;
        $slug = $C->slug;

        $markers_list .= "['$location_name',$lat,$len,$z_index,'$link','$slug'],\n";
        $z_index ++;
    }
    $markers_list .= "\n];\n";
    $avg_lat = array_sum($lats) / count($lats);
    $avg_len = array_sum($lens) / count($lens);

    $zoom = $levels[$level];
    $out = "<h2>Detail Level: $level $level_name</h2>";

    // mapwithmarker reference:
    // http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerwithlabel/docs/reference.html

    // on-hover visibility method from
    // http://stackoverflow.com/questions/25981512/markerwithlabel-mouseover-issue

    $out .= '
    <div id="map" style="height:600px"></div>

    <script>
        var map;
        var marker;
        function initMap() {
            map = new google.maps.Map(document.getElementById(\'map\'), {
                center: {lat: '.$avg_lat.', lng: '.$avg_len.'},
                zoom: '.$zoom.',
                mapTypeId: google.maps.MapTypeId.HYBRID
            });
            ' . $markers_list . '
            for (var i = 0; i < points.length; i++) {
                var point = points[i];
                marker = MarkerWithLabelAndHover(
                    new MarkerWithLabel({
                        pane: "overlayMouseTarget",
                        position: new google.maps.LatLng(point[1], point[2]),
                        labelContent: "",
                        hoverContent: point[0] + "<br>Click to open",
                        labelAnchor: new google.maps.Point(40, -5),
                        map: map,
                        labelClass: "labels",
                        hoverClass: "hoverlabels",
                        url: point[4],
                    })
                );
                google.maps.event.addListener(marker, \'click\', function() {
                    window.location.href = this.url;
                });
            }
        }
        google.maps.event.addDomListener(window, \'load\', initMap);
    </script>';
    return $out;
}