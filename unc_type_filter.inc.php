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

    $filter_str = $a['filter'];
    if ($filter_str == '') {
        $filter_arr = false;
    } else {
        $filter_arr = explode("|", $filter_str);
    }
    // if we have 4 filters, it means that we have enough info to display the
    // image list
    if (count($filter_arr) == 4) {
        $files = unc_filter_image_list($filter_arr);
    } else { // if we have less filters, we still show the options to drill down
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
    global $wpdb, $UNC_GALLERY;

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $group_filter = esc_sql($filter_arr[0]);

    $sql_str_filter = '';
    $sql_str_arr = array(false, 'att_name', 'att_value', false); // we skip the last value since that is the page fo the offset
    for ($i=0; $i<= count($filter_arr); $i++) {
        if (isset($filter_arr[$i]) && $sql_str_arr[$i]) {
            $filter_arr_str = esc_sql(urldecode($filter_arr[$i]));
            $sql_str_filter .= "AND `{$sql_str_arr[$i]}`='$filter_arr_str' ";
        }
    }

    $limit = 54;
    $offset_str = '';

    $last_val = count($filter_arr) - 1;
    $page = $filter_arr[$last_val];
    if ($page > 0) {
        $offset = $page * $limit;
        $offset_str = " OFFSET $offset";
    }

    $sql = "SELECT `file_path`, `file_time` FROM $att_table_name
        LEFT JOIN $img_table_name ON id=file_id
        WHERE `att_group`='$group_filter' $sql_str_filter
        ORDER BY $img_table_name.file_time DESC
        LIMIT 50$offset_str;";

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
    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $group_filter = esc_sql($filter_arr[0]);

    $filter_group = false;
    $filter_name = false;

    $desc2 = '';
    $desc1 = '';

    // depending on the depth of filters we have, we show more and more
    // specific information
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
        if (count($filter_arr) < 4 ) {
            $desc1 = "Please select filter field value:&nbsp;";
        }
    }

    if (count($filter_arr) >= 3 ) { // we have enough info for the image list, count result
        $att_value_filter = esc_sql(urldecode($filter_arr[2]));
        $count_sql = "SELECT count(`file_id`) as `counter` FROM $att_table_name
            LEFT JOIN $img_table_name ON id=file_id
            WHERE `att_group`='$group_filter' AND `att_name`='$att_name_filter' AND `att_value`='$att_value_filter'";
        $counter = $wpdb->get_results($count_sql, 'ARRAY_A');
        $row_count = $counter[0]['counter'];
        $desc2 = "Filtering for $att_name_filter: " . urldecode($filter_arr[2]);
        if ($row_count > 50) {
            $next_page = $filter_arr[3] + 1;
            $display_page = $next_page;
            $next_page_link = " <a
                class=\"next_page_link\"
                onclick=\"filter_select(
                    '$filter_key',
                    '{$filter_arr[2]}',
                    '$filter_group',
                    '$filter_name',
                    'list',
                    $next_page)\">
                        Next Page
            </a>";
            $desc2  .= "<br>More than 50 images found, showing page $display_page ($row_count results)." . $next_page_link;
        } else if ($row_count == 0) {
            unc_tools_debug_trace('unc_filter_choice no images found!',  $count_sql);
        }
    }


    $names = $wpdb->get_results($names_sql, 'ARRAY_A');

    // add the given filter_vars
    $options = $UNC_GALLERY['display']['options'];

    // display the optional tag list
    $out = '';
    if (in_array('dropdown', $options)) {
        $out .=  $desc1 . "<select id=\"filter\" onchange=\"filter_change('$filter_key', '$filter_group', '$filter_name', 'dropdown', 0)\">\n"
            . "<option value=\"false\" selected=\"selected\">Please select</option>\n";
        foreach ($names as $N) {
            $nice_term = ucwords(str_replace("_", " ", $N['term']));
            $out .= "<option value=\"{$N['term']}\">$nice_term</option>\n";
        }
        $out .="</select>\n$desc2\n";
    } else if (in_array('list', $options) || in_array('link_list', $options)) {
        $columns = 4;
        $out .= $desc1;

        $options_string = implode(" ", $options);

        if (count($names) < 20) { // we make colums only for large lists
            $out .= "<ul>\n";
            foreach ($names as $N) {
                $nice_term = ucwords(str_replace("_", " ", $N['term']));
                $fixed_term = urlencode($N['term']);
                $out .= "<li onclick=\"filter_select('$filter_key', '$fixed_term', '$filter_group', '$filter_name', '$options_string', 0)\">$nice_term</li>\n";
            }
            $out .= "</ul>\n";
        } else { // make columns
            $columns = 3;
            $last_letter = false;
            $start = true;
            $this_column = 0;
            $colwidth = (100 / $columns) - 1;
            $out .= "<div class=\"filter_row\">\n";
            foreach ($names as $N) {
                $nice_term = ucwords(str_replace("_", " ", $N['term']));
                $fixed_term = urlencode($N['term']);
                $first_letter = mb_substr($nice_term, 0, 1);
                if ($first_letter !== $last_letter) {
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
                    $out .= "<div class=\"filter_column\" style=\"width:$colwidth%\">\n<h3>$first_letter</h3>\n"
                        . "<ul>\n";
                }
                $out .= "<li onclick=\"filter_select('$filter_key', '$fixed_term', '$filter_group', '$filter_name', '$options_string', 0)\">$nice_term</li>\n";
                $last_letter = $first_letter;
                $start = false;
            }
            $out .= "</ul>\n</div>\n</div>\n"; // TODO why do we close 2 divs here?
        }
        $out .= $desc2;
    } else if (in_array('map', $options)) {
        $out .= ''; //unc_filter_map_data();
    } else {
        $valid_options = $UNC_GALLERY['keywords']['type']['filter'];
        $val_opt_text = implode(", ", $valid_options);
        $out .= unc_display_errormsg("You have an option set that is not compatible with filters! Valid options are: $val_opt_text");
    }

    return $out;
}

/**
 * Displays the actual map
 *
 * @global type $UNC_GALLERY
 * @param type $type
 * @return string
 */
function unc_filter_map_data($type, $zoom_to = null) {
    global $UNC_GALLERY;

    if (strlen($UNC_GALLERY['google_api_key']) < 1) {
        return "You need to set a google API key in your Uncovery Gallery Configuration to use Google Maps!";
    }

    if ($type == 'xmp') {
        $levels = array('country', 'state', 'city', 'location');
    } else { //iptc
        $levels = array('country', 'province_state', 'city');
    }

    $my_levels = array();

    // lets construct a new $levels array from the GET data
    $i = 0;
    $queries = array();
    foreach ($levels as $level) {
        $temp_value = filter_input(INPUT_GET, $level);
        // if one value is missing or empty we take what we got so far.
        if (is_null($temp_value)) {
            break;
        }
        $my_levels[$level] = urldecode($temp_value);
        $queries[] = "$level=" . urlencode($temp_value);
        $i++;
    }

    $link_code = 'window.location.href = this.url;';
    // in case we have reached the end of the levels (except for clusters), we show the photos
    if (count($levels) == ($i + 1) || $UNC_GALLERY['google_maps_markerstyle'] == 'cluster') { // the last level is always the link to the data
        if ($UNC_GALLERY['google_maps_resultstyle'] == 'posts') {
            $link_code = 'show_category(this.category_id);';
        } else {
            $link_code = 'map_filter(this.gps_raw);';
        }
    }

    $add_level = false;
    if ($UNC_GALLERY['google_maps_markerstyle'] == 'layer') {
        // we need to always add one level to the existing so that it will be shown
        $add_level = $levels[$i];
        // false is the wildcard to display all contents
        $my_levels[$add_level] = false;
    }
    // let's get all the locations from the DB:
    $locations_details = unc_filter_map_locations($my_levels, $type, $add_level);
    // var_dump($locations);
    if (count($locations_details) == 0) {
        return "Could not find any results on this location;";
    }

    return unc_filter_map_display($locations_details, $levels, $level, $my_levels, $queries, $link_code, $zoom_to);
}


/**
 * display the actual map
 *
 * @global type $UNC_GALLERY
 * @param type $locations_details
 * @param type $levels
 * @param type $level
 * @param type $my_levels
 * @param type $queries
 * @return string
 */
function unc_filter_map_display($locations_details, $levels, $level, $my_levels, $queries, $link_code, $zoom_to = false) {
    global $UNC_GALLERY;

    $error = '';

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
            $z_index++;
            $loc_name_display = addslashes(urldecode($loc_name));
            if ($UNC_GALLERY['google_maps_markerstyle'] == 'cluster') {
                $loc_name_array = explode("|", $loc_name_display);
                $i = 0;
                $link_tmp = "?";
                foreach ($loc_name_array as $loc_name) {
                    $link_tmp .= urlencode($levels[$i]) . "=" . urlencode($loc_name) . "&";
                    $i++;
                }
                $link = substr($link_tmp, 0, -1);
            } else {
                $this_queries = $queries;
                $this_queries[] = "$level=" . urlencode($loc_name);
                if (count($queries) == count($levels)) {
                    $link = "/";
                } else {
                    $link = "?" . implode("&", $this_queries);
                }
            }

            $lat = $gps['lat'];
            $all_lat += $lat;
            $long = $gps['long'];
            $all_long += $long;
            if ($UNC_GALLERY['google_maps_markerstyle'] == 'cluster') {
                // we take only the city and location
                $loc_name_array_short = array_slice($loc_name_array, -2, 2);
                $loc_name_display = $loc_name_array_short[1] . " (" .$loc_name_array_short[0] . ")";
            }
            $loc_string = implode("-", $loc_name_array);
            $category_id = unc_categories_link_read($loc_string);
            if (!$category_id) {
                $error .= "<!-- Could not find category_id for $loc_string -->";
            } else {
                $markers_list .= "['$loc_name_display',$lat,$long,$z_index,'$link',$category_id],\n";
                $max_lat = max($max_lat, $lat);
                $max_long = max($max_long, $long);
                $min_lat = min($min_lat, $lat);
                $min_long = min($min_long, $long);
            }
        }
    }

    $avg_long = $all_long / count($locations);
    $avg_lat = $all_lat / count($locations);

    if (count($locations) == 1) {
        $zoom = 'zoom: ' . pow(count($my_levels), 2) . ',';
        $bounds = '';
    } else {
        $zoom = '';
        // this will automatically create the right zoom for the map by fitting the two
        // opposite extremes on the map. Ideally. those should be the max SW and the max NE points.
        // from http://stackoverflow.com/questions/10268033/google-maps-api-v3-method-fitbounds
        // and http://jsfiddle.net/gaby/22qte/
        $bounds = "
            var bounds = new google.maps.LatLngBounds();
            bounds.extend(new google.maps.LatLng ($max_lat,$max_long));
            bounds.extend(new google.maps.LatLng ($min_lat,$min_long));
            map.fitBounds(bounds);
        ";
    }

    if ($zoom_to) {
        $avg_long = $zoom_to[1];
        $avg_lat = $zoom_to[0];
        $zoom = 'zoom: 16,';
        $bounds = '';
    }

    $cluster = '';
    if ($UNC_GALLERY['google_maps_markerstyle'] == 'cluster') {
        $cluster = '
            var mcOptions = {imagePath: \''.plugin_dir_url( __FILE__ ) .'images/m\', pane: "floatPane", gridSize: 20};
            var markerCluster = new MarkerClusterer(map, markers, mcOptions);';
    }

    $markers_list .= "\n];\n";

    // mapwithmarker reference:
    // https://developers.google.com/maps/documentation/javascript/tutorial

    // Marker Cluster
    // https://developers.google.com/maps/documentation/javascript/marker-clustering

    // on-hover visibility method from
    // http://stackoverflow.com/questions/25981512/markerwithlabel-mouseover-issue

    // google maps API
    // https://developers.google.com/maps/documentation/javascript/examples/event-simple


    // fix the z-index:
    // https://stackoverflow.com/questions/9339431/changing-z-index-of-marker-on-hover-to-make-it-visible/9340671

    $map_type = $UNC_GALLERY['google_maps_type'];

    $out = '
    <div id="map" style="height:600px"></div>
    <script>
        var map;
        var marker;
        var infowindow;

        function initMap() {
            map = new google.maps.Map(document.getElementById(\'map\'), {
                center: {lat: '.$avg_lat.', lng: '.$avg_long.'},
                '.$zoom.'
                mapTypeId: google.maps.MapTypeId.'.$map_type.'
            });
            ' . $markers_list . '
            var markers = new Array();
            for (var i = 0; i < points.length; i++) {
                var point = points[i];
                marker = MarkerWithLabelAndHover(
                    new MarkerWithLabel({
                        pane: "floatPane",
                        position: new google.maps.LatLng(point[1], point[2]),
                        hoverContent: point[0] + "<br>Click to show images",
                        labelAnchor: new google.maps.Point(40, -5),
                        map: map,
                        hoverClass: "google_map_labels_hover",
                        url: point[4],
                        gps_raw: point[1] + "," + point[2],
                        category_id: point[5],
                        zIndex: 1,
                    })
                );
                google.maps.event.addListener(marker, \'click\', function() {
                    '.$link_code.'
                });
                markers.push(marker);
            }
            '. $bounds .'
            '. $cluster . '

        }
        google.maps.event.addDomListener(window, \'load\', initMap);

    </script>' . $error;
    return $out;
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


/**
 * Get all the map locations for the current level
 *
 * @global type $UNC_GALLERY
 * @global type $wpdb
 * @param type $levels
 * @param type $type
 * @param type $next_level
 * @return type
 */
function unc_filter_map_locations($levels, $type, $next_level) {
    global $wpdb, $UNC_GALLERY;

    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    if ($UNC_GALLERY['google_maps_markerstyle'] == 'layer') {
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
                WHERE loc_list.`att_group`='xmp'
                    AND loc_list.att_name = 'loc_str'
                    AND loc_list.att_value LIKE '$levels_sql'
                    AND item_list.att_name='$next_level'
                    AND gps_list.att_name='gps'
                GROUP BY gps_list.att_value"; //
        }
    } else { // cluster
        $levels = 3;
        $sql = "SELECT  loc_list.att_value as loc_str, gps_list.att_value as gps, item_list.att_value as item
            FROM `$att_table_name` as loc_list
            LEFT JOIN `$att_table_name` as gps_list ON loc_list.file_id=gps_list.file_id
            LEFT JOIN `$att_table_name` as item_list on loc_list.file_id=item_list.file_id
            WHERE loc_list.`att_group`='xmp'
                AND loc_list.att_name = 'loc_str'
                AND gps_list.att_name='gps'
            GROUP BY gps_list.att_value"; //
    }

    // echo "<!-- DEBUG $sql -->\n";
    $locations = $wpdb->get_results($sql, 'ARRAY_A');

    $final = array();
    foreach ($locations as $L) {
        $gps_arr = explode(",", $L['gps']);

        $gps_0 = unc_filter_gps_round($gps_arr[0]);
        $gps_1 = unc_filter_gps_round($gps_arr[1]);

        if (!is_array($levels)) {
            $levels = array();
        }

        if (count($levels) == 1 && $UNC_GALLERY['google_maps_markerstyle'] == 'layer') {
            $country = $L['country'];
            $final[$country]['gps']['lat'][] = $gps_0;
            $final[$country]['gps']['long'][] = $gps_1;
        } else {
            if ($UNC_GALLERY['google_maps_markerstyle'] == 'layer') {
                $item = $L['item'];
            } else {
                $item = $L['loc_str'];
            }
            $final[$item]['gps']['lat'][] = $gps_0;
            $final[$item]['gps']['long'][] = $gps_1;
        }
    }
    return $final;
}

/**
 * convert GPS coordinates and round them according to settings.
 *
 * @global type $UNC_GALLERY
 * @param type $value
 * @return type
 */
function unc_filter_gps_round($value) {
    global $UNC_GALLERY;

    $round_digits = false;
    if ($UNC_GALLERY['gps_round_data'] != false) {
        $round_digits = $UNC_GALLERY['gps_round_data'];
    }
    $gps = floatval($value);
    if ($round_digits) {
        $gps_out = round($gps, $round_digits);
    } else {
        $gps_out = $gps;
    }
    return $gps_out;
}



/**
 * get the average of all locations in the list to center the GPS
 *
 */
function unc_filter_gps_avg($array) {
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
 * or a link in the tags list is clicked
 *
 * @global type $wpdb
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_filter_update(){
    global $UNC_GALLERY;

    $filter_key = filter_input(INPUT_GET, 'filter_key');
    $filter_value = filter_input(INPUT_GET, 'filter_value');
    $filter_group = filter_input(INPUT_GET, 'filter_group');
    $filter_name = filter_input(INPUT_GET, 'filter_name');
    $options = filter_input(INPUT_GET, 'options');
    $page_raw = filter_input(INPUT_GET, 'page');

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

    $page = intval($page_raw);

    $filter_str .= "|$page";

    if ($options == 'map' && $UNC_GALLERY['google_maps_resultstyle'] == 'posts' && $UNC_GALLERY['post_categories'] != 'none') {
        ob_clean();
        unc_categories_show_posts($filter_value);
        wp_die();
    }

    // the following line has ECHO = FALSE because we do the echo here
    unc_gallery_display_var_init(array('type' => 'filter', 'filter' => $filter_str, 'ajax_show' => 'all', 'options' => $options));
    ob_clean();
    echo unc_display_images();
    wp_die();
}

function unc_categories_show_posts($cat_id) {
    $limit = 10;
    query_posts("cat=$cat_id&posts_per_page=$limit");

    echo '<div class="archive" style="margin-top:25px;">' . "\n";
    $i = 0;
    $num_posts = number_postpercat($cat_id);

    if (have_posts()) {
        while ( have_posts() ) {
            $i++;
            the_post();
            get_template_part('template-parts/post/content', get_post_format() );
        }
        $link = get_category_link($cat_id);
        echo "<div style=\"clear:both\">$i of $num_posts posts shown. <a href=\"$link\">Click here to see all posts from this location</a></div>";

    } else {
        get_template_part( 'template-parts/post/content', 'none' );
    }

    echo '</div>' . "\n";
    return;
}

function number_postpercat($idcat) {
    global $wpdb;

    $query = "SELECT count FROM $wpdb->term_taxonomy WHERE term_id = \"$idcat\"";
    $num = $wpdb->get_col($query);
    return $num[0];
}


function unc_filter_check_type($group, $key) {
    global $UNC_GALLERY;

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
 * Compare existing tags assigned to a post with the image's tags
 * and assign missing ones to the post.
 *
 * @global type $UNC_GALLERY
 * @param type $F
 * @return boolean
 */
function unc_tags_apply($F) {
    global $UNC_GALLERY;

    // do we havea post? If so get the id, otherwise bail
    $post_id = get_the_ID();
    if (!$post_id) {

        return;
    }

    // we assume first we append tags
    $append_tags = true;
    // get the system setting
    $setting = $UNC_GALLERY['post_keywords'];
    // it's a string a_b_c, split it
    $set_split = explode("_", $setting);
    //
    // example: xmp_force <- if "force" exists, we don't append, we replace
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
            $photo_tags[] = ucwords(strtolower($tag), " (");
        }
    }
    if (count($photo_tags) == 0) {

        return false;
    }

    // TODO: Create a second Array that does not lowercase the tags for the final application
    $photo_tags_unique = array_unique($photo_tags);
    asort($photo_tags_unique);

    // in case there are no tags in the photos, we won't do anything
    if (count($photo_tags_unique) == 0) {
        return;
    }

    // get all post tags
    $post_tags = array();
    $posttags_obj = get_the_tags();
    if ($posttags_obj) {
        foreach($posttags_obj as $tag) {
            $post_tags[] = ucwords(strtolower($tag->name), " (");
        }
    }

    $post_tags_unique = array_unique($post_tags);
    asort($post_tags_unique);

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
            // echo "Appended tags";
        } else {
            // echo "Tags OK";
        }
    } else if ($photo_tags_unique != $post_tags_unique) {
        // if we replace tags, we overwrite only if the tags are not identical
        wp_set_post_tags($post_id, $photo_tags_unique, $append_tags);
        // echo "Replaced tags";
        $retval = true;
    } else {
        // echo "No tag changes";
    }
    return $retval;
}

/**
 * Compare existing post categories with the image and fixing the missing
 *
 * TODO: We need to check that a category has the proper upper level so that we
 * can have the same sub-category name in 2 different upper categories.
 *
 * @global type $UNC_GALLERY
 * @param type $file_data
 * @return type
 */
function unc_categories_apply($file_data) {
    global $UNC_GALLERY;

    $post_id = get_the_ID();
    if (!$post_id) {
        return;
    }

    // need to include taxonomy to use category stuff
    $admin_directory = ABSPATH . '/wp-admin/';
    require_once($admin_directory . 'includes/taxonomy.php');

    $curr_cats = array();
    // re-format the currnet categories so we can compare them

    // get the categories applied for this post
    foreach (get_the_category($post_id) as $c_cat) {
        $cat_name_id = strtolower($c_cat->name);
        $curr_cats[$cat_name_id]['name'] = $c_cat->name;
        $curr_cats[$cat_name_id]['id'] = $c_cat->cat_ID;
    }

    // get all cats in the system
    $wp_all_cats = get_categories();
    $all_cat_index = array();
    // reformat them so we can search easier
    foreach ($wp_all_cats as $C) {
        $lower_name = strtolower($C->name);
        $all_cat_index[$lower_name]['id'] = $C->cat_ID;
        $all_cat_index[$lower_name]['parent'] = $C->parent;
    }

    $cat_sets = array();
    $has_cats = false;

    // LOCATION categories
    if ($UNC_GALLERY['post_categories'] != 'none') {
        // find out what the current config setting is
        $setting = $UNC_GALLERY['post_categories'];
        // split into array: e.g. 'xmp_country_state_city_location'
        $setting_array = explode("_", $setting); // this will be filled with strings such as 'city' etc
        $data_type = array_shift($setting_array); // remove the XPM/EXIF from the front of the array
        // iterate all files and get all the different levels of categories

        // we go through all files in the post and get all categories for this post uniquely
        foreach ($file_data as $F) {
            // we go through the wanted fields from the setting
            $file_cats = array();
            foreach ($setting_array as $exif_code) { // country... state ... city... location
                $cat_sets[$exif_code] = false; // we assume it does not exist, so with this we also catch empty levels
                if (!isset($F[$data_type][$exif_code])) { // we look for $F['xmp']['city'] etc
                    $value = '%%none%%';
                } else {
                    $has_cats = true;
                    $value = $F[$data_type][$exif_code];
                }
                $file_cats[] = $value;
            }
            // we try to create a code to make sure we do not make duplicates
            $cats_id = implode("-", $file_cats); // this will look like 'hongkong-hongkongisland-central-grappas'
            // so we created a array key that has the whole list of location names as the line above and then contains an
            // array of the individual names of the location.
            if (!strstr($cats_id, "n/a")) {
                $cat_sets[$cats_id] = $file_cats;
            }
        }
    }

    // echo "\n<!-- UNC found cats: "  . var_export($cat_sets, true) . " -->\n";

    if ($UNC_GALLERY['event_categories'] != 'none') {
        $setting = $UNC_GALLERY['post_categories'];

        $regex = '/(?<event>.*) (?<year>\d{4})/';

        $file_cats = array();
        // iterate all the files
        foreach ($file_data as $F) {
            if (isset($F['xmp']['event'])) {
                $event_str = $F['xmp']['event'];
                $matches = array();
                $check = preg_match($regex, $event_str, $matches);
                $cat_sets[$event_str] = array(
                    0 => $matches['event'],
                    1 => $matches['event'] . " " . $matches['year']);
            }
        }
    }

    // EVENT categories
    // if we did not find any, just stop here
    if (!$has_cats) {
        // echo "\n<!-- UNC No event cats -->\n";
        return;
    }

    $post_categories = array();

    // now we go through the collected categories and apply them to the post
    // and create them in the system if required
    foreach ($cat_sets as $cat_set) {
        // iterate each level
        $depth = 1; // depth of the hierarchical cats
        $next_parent = 0;
        if (!$cat_set) {
            continue;
        }
        $cat_string = '';
        // lets iterate the categories from country -> location
        foreach ($cat_set as $cat) {
            // we re-build a unique string for the current cat hierarchy to act
            // as an index for the category/location link.
            $cat_string .= "-" . $cat;
            // check if the post has a category of that name already
            $cat_id = strtolower($cat);
            if ($cat == '%%none%%') {
                continue;
            } else if (isset($curr_cats[$cat_id])) { // we check if this exists in the current post categories
                // get the existing cat ID and add it to the post
                $post_categories[] = $curr_cats[$cat_id]['id'];
                // since it exists, we declare this one as a parent and move to the next
                // we use the parent info to make sure that the next category is the right one.
                $next_parent = $curr_cats[$cat_id]['id'];
                continue;
            }
            // check if the current cat already exists in wordpress, make sure it has the same parent
            // this has a potential
            if (isset($all_cat_index[$cat_id]) && $all_cat_index[$cat_id]['parent'] == $next_parent) {
                $this_id = $all_cat_index[$cat_id]['id'];
            } else {
                $this_id = wp_create_category($cat, $next_parent);
            }
            // make sure we remember the link between the location and the category:
            // strip the first "-"
            $cat_string_index = substr($cat_string, 1);
            unc_categories_link_create($this_id, $cat_string_index);

            $post_categories[] = $this_id; // collect the categories to add them to the post
            $next_parent = $this_id;
            $depth++;
        }
    }
    wp_set_post_categories($post_id, $post_categories, false); // true means cats will be added, not replaced
}

/**
 * Since we want to show all the posts that are related to a location (exif), we need to link the location to
 * the category that we created. This here creates a link in a table so we can look it up later.
 *
 * @param type $category_id
 * @param type $exif_code
 */
function unc_categories_link_create($category_id, $exif_code) {
    global $wpdb;
    // we check first if we have this already
    $check = unc_categories_link_read($exif_code);

    if ($check) {
        return;
    }

    // since the key does not exist yet, insert it
    $insert_sql = "INSERT INTO {$wpdb->prefix}unc_gallery_cat_links (location_code,category_id) VALUES (%s,%d);";
    $insert_prepared_sql = $wpdb->prepare($insert_sql, $exif_code, $category_id);
    $wpdb->query($insert_prepared_sql);
}

/**
 * This here reads a link in a table so we can look it up later.
 *
 * @param type $exif_code
 */
function unc_categories_link_read($exif_code) {
    global $wpdb;
    // we check first if we have this already
    $select_sql = "SELECT category_id FROM {$wpdb->prefix}unc_gallery_cat_links WHERE location_code LIKE '%s';";
    $select_prepared_sql = $wpdb->prepare($select_sql, $exif_code);

    $data = $wpdb->get_results($select_prepared_sql, 'ARRAY_A');
    if ($wpdb->num_rows == 0) {
        return false;
    } else {
        return $data[0]['category_id'];
    }
}

/**
 * if we have the numeric ID of a category, let's find the GPS for it so we can display a map.
 *.
 * @param type $category_id
 */
function unc_category_get_gps($category_id) {
    global $wpdb;
    // we check first if we have this already
    $select_sql = "SELECT location_code FROM {$wpdb->prefix}unc_gallery_cat_links WHERE category_id = '%d';";
    $select_prepared_sql = $wpdb->prepare($select_sql, $category_id);

    $data = $wpdb->get_results($select_prepared_sql, 'ARRAY_A');
    if ($wpdb->num_rows == 0) {
        echo "could not find category in link list";
        return false;
    } else {
        // we just take the first one
        $loc_string = $data[0]['location_code'];
    }
    // need to replace - with |.

    $loc_string = str_replace("-", "|", $loc_string);

    $gps_select = "SELECT gps_table.att_value FROM `wp_unc_gallery_att` as loc_table
        LEFT JOIN `wp_unc_gallery_att` as gps_table ON loc_table.file_id=gps_table.file_id
        WHERE loc_table.att_group='xmp'
        AND loc_table.att_name='loc_str'
        AND loc_table.att_value='%s'
        AND gps_table.att_name='gps'
        LIMIT 1;";
    $gps_prepared_sql = $wpdb->prepare($gps_select, $loc_string);

    $gps_data = $wpdb->get_results($gps_prepared_sql, 'ARRAY_A');
    if ($wpdb->num_rows == 0) {
        return false;
    } else {
        // we just take the first one
        $gps_string = $gps_data[0]['att_value'];
    }

    $arr = explode(",", $gps_string);
    return $arr;
}


