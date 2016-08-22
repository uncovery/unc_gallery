<?php
/**
 * This file handles all filter-type shortcodes variables and
 * output.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Process filter vars
 *
 * @global type $UNC_GALLERY
 * @param type $a
 */
function unc_filter_var_init($a) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $filter_str = $a['filter'];
    if ($filter_str == '') {
        $filter_arr = false;
    } else {
        $filter_arr = explode("|", $filter_str);
    }
    if (count($filter_arr) == 3) {
        $files = unc_filter_image_list($filter_arr);
    } else {
        $files = array();
    }

    // get all the files that apply to the filter

    $UNC_GALLERY['display']['files'] = $files;
    $UNC_GALLERY['display']['file'] = false;
    $UNC_GALLERY['display']['filter_arr'] = $filter_arr;

    //$valid_options = $UNC_GALLERY['keywords']['type']['filter'];
    //$options = $a['options'];
    return true;
}

/**
 * find all files with a certain filter value
 *
 * @param array $filter_arr
 */
function unc_filter_image_list($filter_arr) {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $group_filter = esc_sql($filter_arr[0]);

    $sql_str_filter = '';
    $sql_str_arr = array('', 'att_name', 'att_value');
    for ($i=1; $i<= count($filter_arr); $i++) {
        if (isset($filter_arr[$i])) {
            $filter_arr_str = esc_sql($filter_arr[$i]);
            $sql_str_filter .= "AND {$sql_str_arr[$i]}='$filter_arr_str' ";
        }
    }

    $sql = "SELECT `file_path`, `file_time` FROM $att_table_name
        LEFT JOIN $img_table_name ON id=file_id
        WHERE `att_group`='$group_filter' $sql_str_filter
        ORDER BY $img_table_name.file_time DESC
        LIMIT 50";

    $files = $wpdb->get_results($sql, 'ARRAY_A');
    $myfiles = array();
    foreach ($files as $file) {
        $file_path = $file['file_path'];
        $file_date = $file['file_time'];
        $F = unc_image_info_read($file_path);
        $F['featured'] = false;
        $myfiles[$file_date] = $F;
    }

    ksort($myfiles);
    return $myfiles;
}

function unc_filter_choice($filter_arr) {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $group_filter = esc_sql($filter_arr[0]);

    $filter_group = false;
    $filter_name = false;

    $desc2 = '';
    $desc1 = '';
    if (!$filter_arr || count($filter_arr) == 0) {
        $names_sql = "SELECT `att_group` as `term` FROM $att_table_name
            LEFT JOIN $img_table_name ON id=file_id
            GROUP BY `att_group`";
        $filter_key = 'att_group';
        $desc1 = "Please select filter group:&nbsp;";
    } else if (count($filter_arr) == 1) {
        $names_sql = "SELECT `att_name` as `term` FROM $att_table_name
            LEFT JOIN $img_table_name ON id=file_id
            WHERE `att_group`='$group_filter'
            GROUP BY att_name
            ORDER By att_name";
        $filter_key = 'att_name';
        $filter_group = $filter_arr[0];
        $desc1 = "Please select filter field:&nbsp;";
    } else if (count($filter_arr) > 1 ) { // if we have all 3, always dislay the last one
        $att_name_filter = esc_sql($filter_arr[1]);
        $names_sql = "SELECT `att_value` as `term` FROM $att_table_name
            LEFT JOIN $img_table_name ON id=file_id
            WHERE `att_group`='$group_filter' AND `att_name`='$att_name_filter'
            GROUP BY att_value
            ORDER BY att_value";
        $filter_key = 'att_value';
        $filter_group = $filter_arr[0];
        $filter_name = $filter_arr[1];
        if (count($filter_arr) < 3 ) {
            $desc1 = "Please select filter field value:&nbsp;";
        }
    }
    if (count($filter_arr) == 3 ) { // we have enough info for the image list, count result
        $att_value_filter = esc_sql($filter_arr[2]);
        $count_sql = "SELECT count(`file_id`) as `counter` FROM $att_table_name
            LEFT JOIN $img_table_name ON id=file_id
            WHERE `att_group`='$group_filter' AND `att_name`='$att_name_filter' AND `att_value`='$att_value_filter'";
        $counter = $wpdb->get_results($count_sql, 'ARRAY_A');
        $row_count = $counter[0]['counter'];
        $desc2 = "Filtering for $att_name_filter: $att_value_filter";
        if ($row_count > 49) {
            $desc2  .= "<br>More than 50 images found! ($row_count results)";
        }
    }


    $names = $wpdb->get_results($names_sql, 'ARRAY_A');

    // add the given filter_vars
    $options = $UNC_GALLERY['display']['options'];

    // display the optional tag list
    $out = '';
    if (in_array('dropdown', $options)) {
        $out .=  $desc1 . "<select id=\"filter\" onchange=\"filter_change('$filter_key', '$filter_group', '$filter_name', 'dropdown')\">\n"
            . "<option value=\"false\" selected=\"selected\">Please select</option>\n";
        foreach ($names as $N) {
            $nice_term = ucwords(str_replace("_", " ", $N['term']));
            $out .= "<option value=\"{$N['term']}\">$nice_term</option>\n";
        }
        $out .="</select>\n$desc2\n";
    } else if (in_array('list', $options)) {
        $columns = 4;
        $out .= $desc1;
        if (count($names) < 20) { // we make colums only for large lists
            $out .= "<ul>\n";
            foreach ($names as $N) {
                $nice_term = ucwords(str_replace("_", " ", $N['term']));
                $out .= "<li onclick=\"filter_select('$filter_key', '{$N['term']}', '$filter_group', '$filter_name', 'list')\">$nice_term</li>\n";
            }
            $out .= "</ul>\n";
        } else { // make columns
            $columns = 4;
            $last_letter = false;
            $start = true;
            $this_column = 0;
            $out .= "<div class=\"filter_row\">\n";
            foreach ($names as $N) {
                $nice_term = ucwords(str_replace("_", " ", $N['term']));
                $first_letter = mb_substr($nice_term, 0, 1);
                if ($first_letter <> $last_letter) {
                    if (!$start) {
                        $this_column++;
                        $out .= "</ul>\n</div>\n";
                        if ($this_column == $columns) {
                            $this_column = 0;
                            $out .= "</div>\n";
                            $out .= "<div class=\"filter_row\">\n";
                        }
                    }
                    // make letter header
                    $out .= "<div class=\"filter_column\">\n<h3>$first_letter</h3>\n"
                        . "<ul>\n";
                }
                $out .= "<li onclick=\"filter_select('$filter_key', '{$N['term']}', '$filter_group', '$filter_name', 'list')\">$nice_term</li>\n";
                $last_letter = $first_letter;
                $start = false;
            }
            $out .= "</ul>\n</div>\n</div>\n";
        }
        $out .= $desc2;
    }  else if (in_array('map', $options)) {
        $out .= ''; //unc_filter_map_data();
    } else {
        $valid_options = $UNC_GALLERY['keywords']['type']['filter'];
        $val_opt_text = implode(", ", $valid_options);
        $out .= unc_display_errormsg("You have an option set that is not compatible with filters! Valid options are: $val_opt_text");
    }

    return $out;
}

function unc_filter_map_data($type) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (strlen($UNC_GALLERY['google_api_key']) < 1) {
        return "You need to set a google API key in your Uncovery Gallery Configuration to use Google Maps!";
    }


    if ($type == 'xmp') {
        $levels = array('country', 'state', 'city', 'location');
    } else { //ipct
        $levels = array('country', 'province_state', 'city');
    }

    $my_levels = array();
    // lets construct a new $levels array from the GET data
    $i = 0;
    $queries = array();
    foreach ($levels as $level) {
        $temp_value = filter_input(INPUT_GET, $level, FILTER_SANITIZE_STRING);
        // if one value is missing or empty we take what we got so far.
        if (is_null($temp_value)) {
            break;
        }
        $my_levels[$level] = $temp_value;
        $queries[] = "$level=$temp_value";
        $i++;
    }

    $link_code = 'window.location.href = this.url;';
    if (count($levels) == ($i + 1)) { // the last level is always the link to the data
        $link_code = 'map_filter(this.gps_raw);';
    }


    // we need to always add one level to the existing so that it will be shown
    $add_level = $levels[$i];
    // false is the wildcard to display all contents
    $my_levels[$add_level] = false;

    // let's get all the locations from the DB:
    $locations_details = unc_filter_map_locations($my_levels, $type, $add_level);
    // var_dump($locations);
    if (count($locations_details) == 0) {
        return "Could not find any results on this location;";
    }

    // we are interested only in the average GPS data per location
    $locations = unc_filter_gps_avg($locations_details);

    $markers_list = "var points = [\n";
    $z_index = 100;

    $all_long = 0;
    $all_lat = 0;
    $max_lat = - 200;
    $min_lat = 200;
    $max_long = - 200;
    $min_long = 200;
    foreach ($locations as $L) {
        foreach ($L as $loc_name => $gps) {
            $loc_name_encoded = addslashes($loc_name);
            $z_index++;

            $this_queries = $queries;
            $this_queries[] = "$level=$loc_name_encoded";
            if (count($queries) == count($levels)) {
                $link = "https://uncovery.net/";
            } else {
                $link = "?" . implode("&", $this_queries);
            }

            $lat = $gps['lat'];
            $all_lat += $lat;
            $long = $gps['long'];
            $all_long += $long;

            $markers_list .= "['$loc_name_encoded',$lat,$long,$z_index,'$link',''],\n";
            $max_lat = max($max_lat, $lat);
            $max_long = max($max_long, $long);
            $min_lat = min($min_lat, $lat);
            $min_long = min($min_long, $long);
        }
    }

    $avg_long = $all_long / count($locations);
    $avg_lat = $all_lat / count($locations);

    if (count($locations) == 1) {
        $zoom = pow(count($my_levels), 2);
    } else {
        $zoom = unc_filter_map_zoom_level($max_lat, $max_long, $min_lat, $min_long);
    }

    $markers_list .= "\n];\n";

    // mapwithmarker reference:
    // http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerwithlabel/docs/reference.html

    // on-hover visibility method from
    // http://stackoverflow.com/questions/25981512/markerwithlabel-mouseover-issue

    // google maps API
    // https://developers.google.com/maps/documentation/javascript/examples/event-simple


    $map_type = $UNC_GALLERY['google_maps_type'];

    $out = '
    <div id="map" style="height:600px"></div>

    <script>
        var map;
        var marker;
        function initMap() {
            map = new google.maps.Map(document.getElementById(\'map\'), {
                center: {lat: '.$avg_lat.', lng: '.$avg_long.'},
                zoom: '.$zoom.',
                mapTypeId: google.maps.MapTypeId.'.$map_type.'
            });
            ' . $markers_list . '
            for (var i = 0; i < points.length; i++) {
                var point = points[i];
                var location = point[1] + "," + point[2];
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
                        gps_raw: point[1] + "," + point[2],
                    })
                );
                google.maps.event.addListener(marker, \'click\', function() {
                    '.$link_code.'
                });
            }
        }
        google.maps.event.addDomListener(window, \'load\', initMap);
    </script>';
    return $out;
}

function unc_filter_map_zoom_level($lat1, $lon1, $lat2, $lon2) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $max = 40000000; // world is 40k KM large, Zoom 2
    $distance = intval(unc_filter_map_gps_convert($lat1, $lon1, $lat2, $lon2));
    $fraction = intval($max / $distance);

    // the following variable are estimates
    $min_zoom = 1;
    $max_zoom = 17;
    $steps = 5;
    // this formula tries to create a scale between min_zoom and max_zoom that
    // corresponds with the google maps zoom levels from the
    // fraction of the distance between the available points and the circumference of the world.
    $zoom_index = $min_zoom + pow($fraction, log10($max_zoom) / $steps);

    // since 2 is the widest we need to invert
    $zoom_level = intval($zoom_index);
    return $zoom_level;
}

/**
 * Calculates the great-circle distance between two points, with
 * the Haversine formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function unc_filter_map_gps_convert($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
      cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function unc_filter_map_locations($levels, $type, $next_level) {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}


    // var_dump($levels);

    $att_table_name = $wpdb->prefix . "unc_gallery_att";

    if (count($levels) == 1) { // we have no input, look for countries
        $sql = "SELECT loc_list.att_value as country, gps_list.att_value as gps FROM `$att_table_name` as loc_list
            LEFT JOIN `$att_table_name` as gps_list ON loc_list.file_id=gps_list.file_id
            WHERE loc_list.`att_group`='$type' AND loc_list.att_name = '$next_level' AND gps_list.att_name='gps'
            GROUP BY gps_list.att_value;";
    } else {
        $levels_sql = implode("|", $levels) . '%';
        $sql = "SELECT loc_list.att_value as loc_str, gps_list.att_value as gps, item_list.att_value as item
            FROM `$att_table_name` as loc_list
            LEFT JOIN `$att_table_name` as gps_list ON loc_list.file_id=gps_list.file_id
            LEFT JOIN `$att_table_name` as item_list on loc_list.file_id=item_list.file_id
            WHERE loc_list.`att_group`='xmp' AND loc_list.att_name = 'loc_str' AND loc_list.att_value LIKE '$levels_sql' AND item_list.att_name='$next_level' AND gps_list.att_name='gps'
            GROUP BY item_list.att_value";
    }

    $locations = $wpdb->get_results($sql, 'ARRAY_A');

    $final = array();
    foreach ($locations as $L) {
        $gps_arr = explode(",", $L['gps']);
        if (count($levels) == 1) {
            $country = $L['country'];
            $final[$country]['gps']['lat'][] = floatval($gps_arr[0]);
            $final[$country]['gps']['long'][] = floatval($gps_arr[1]);
        } else {
            $item = $L['item'];
            $final[$item]['gps']['lat'][] = floatval($gps_arr[0]);
            $final[$item]['gps']['long'][] = floatval($gps_arr[1]);
        }
    }

    return $final;
}


/**
 * iterate an array and
 */
function unc_filter_gps_avg($array) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    foreach ($array as $name => $lower) {
        if (isset($lower['gps'])) {
            $lat = array_sum($lower['gps']['lat']) / count($lower['gps']['lat']);
            $lon = array_sum($lower['gps']['long']) / count($lower['gps']['long']);
            $next_step[] = array($name => array('lat'=>$lat, 'long'=>$lon));
        } else {
            $next_step = unc_filter_gps_avg($lower);
        }
    }
    return $next_step;
}


/**
 * This is the function called by Ajax when the filter dropdown on the website is updated
 *
 * @global type $wpdb
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_filter_update(){
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__);}

    $filter_key = filter_input(INPUT_GET, 'filter_key', FILTER_SANITIZE_STRING);
    $filter_value = filter_input(INPUT_GET, 'filter_value', FILTER_SANITIZE_STRING);
    $filter_group = filter_input(INPUT_GET, 'filter_group', FILTER_SANITIZE_STRING);
    $filter_name = filter_input(INPUT_GET, 'filter_name', FILTER_SANITIZE_STRING);
    $options = filter_input(INPUT_GET, 'options', FILTER_SANITIZE_STRING);

    $filter_str = '';
    if (strlen($filter_group) > 1) { // we have a group set
        $filter_str = $filter_group;
    } else if ($filter_key == 'group' ) { // the group was chosen
        $filter_str = $filter_value;
    } else {
        echo "error in filter group!";
    }

    if (strlen($filter_name) > 0) { // we have a name set
        $filter_str .= "|$filter_name";
    } else if ($filter_key == 'att_name') { // the group was chosen
        $filter_str .= "|$filter_value";
    }
    if ($filter_key == 'att_value' && $filter_value <> 'false') {
        $filter_str .= "|$filter_value";
    }
    // the following line has ECHO = FALSE because we do the echo here
    unc_gallery_display_var_init(array('type' => 'filter', 'filter' => $filter_str, 'echo' => false, 'options' => $options));
    ob_clean();
    echo unc_gallery_display_page();
    wp_die();
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
            $image_tags = array($FD[$selected_tags]['keywords']);
        }
        // now, we have tags, go through them
        foreach ($image_tags as $tag) {
            // we lowercase them to make them comparable
            $photo_tags[] = ucwords(strtolower($tag));
        }
    }
    if (count($photo_tags) == 0) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "collected zero keywords from array");}
        return false;
    }

    // TODO: Create a second Array that does not lowercase the tags for the final application
    $photo_tags_unique = array_unique($photo_tags);
    asort($photo_tags_unique);

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
            $post_tags[] = ucwords(strtolower($tag->name));
        }
    }

    $post_tags_unique = array_unique($post_tags);
    asort($post_tags_unique);

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