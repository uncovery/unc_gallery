<?php
/**
 * This file handles all day-type shortcodes (the default type) variables and
 * output.
 */


/**
 * Analyse the shortcode vars relevant for the day type.
 * @global type $UNC_GALLERY
 * @param type $a
 * @return boolean
 */
function unc_day_var_init($a) {
    global $UNC_GALLERY;
    unc_tools_debug_trace(__FUNCTION__ , func_get_args());
    // we convert the start time and end time to unix timestamp for better
        // comparison
    $UNC_GALLERY['display']['range'] = array('start_time' => false, 'end_time' => false);
    foreach ($UNC_GALLERY['display']['range'] as $key => $value) {

        if ($a[$key]) {
            //re-name the variable
            $UNC_GALLERY['display']['date_range'][$key] = trim($a[$key]);
            // convert to UNIX timestamp
            $dtime = DateTime::createFromFormat("Y-m-d G:i:s", trim($a[$key]));
            if (!$dtime) { // time format was invalid
                return false;
            }
            $UNC_GALLERY['display']['range'][$key] = $dtime->getTimestamp();
            // get the date for the same
            $var_name = 'date_' . $key;
            $$var_name = substr($a[$key], 0, 10);
        }
    }

    if ($a['end_time']) {
        $date_end_time = substr(trim($a['end_time']), 0, 10);
    }

    if ($a['start_time']) {
        $date_start_time = substr(trim($a['start_time']), 0, 10);
    }
    $UNC_GALLERY['display']['date_description'] = false; // false by default, only true if not set explicitly (latest or random date)

    if ($a['end_time'] && $a['start_time']) {
        $date_arr = unc_day_date_span($date_start_time, $date_end_time);
        $UNC_GALLERY['display']['dates'] = $date_arr;
    } else if ($a['end_time']) {
        $date_str = $date_end_time;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($a['start_time']) {
        $date_str = $date_start_time;
        $UNC_GALLERY['display']['dates'] = array($date_str);
    } else if ($a['date'] && in_array($a['date'], $UNC_GALLERY['keywords']['date'])) { // we have a latest or random date
        // get the latest or a random date if required
        if ($a['date'] == 'random') {
            $date_str = unc_day_date_random();
        } else if ($a['date'] == 'latest') {
            $date_str = unc_day_date_latest();
        }
        if (!$date_str) { // we have no images in the database
            $UNC_GALLERY['display']['files'] = array();
            $UNC_GALLERY['display']['file'] = false;
            $UNC_GALLERY['display']['dates'] = array();
        } else {
            $UNC_GALLERY['display']['date_description'] = true;
            $UNC_GALLERY['display']['dates'] = array($date_str);
        }
    } else if ($a['date'] && strstr($a['date'], ",")) { // we have several dates in the string
        $dates = explode(",", $a['date']);
        if (count($dates) > 2) {
            echo unc_display_errormsg("You can only enter 2 dates!");
            return false;
        }
        // validate both dates
        $date_str1 = unc_day_validate_date(trim($dates[0]));
        $date_str2 = unc_day_validate_date(trim($dates[1]));

        if (!$date_str1 || !$date_str2) {
            echo unc_display_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }

        // create a list of dates between the 1st and the 2nd
        $date_arr = unc_day_date_span($dates[0], $dates[1]);
        $UNC_GALLERY['display']['dates'] = $date_arr;
    } else if ($a['date']) {
        $date_str = unc_day_validate_date($a['date']);
        if (!$date_str) {
            echo unc_display_errormsg("All dates needs to be in the format '2016-01-31'");
            return false;
        }
        $UNC_GALLERY['display']['dates'] = array($date_str);
    }

    // get the actual images
    if ($a['file'] && $a['type'] == 'day') {
        $UNC_GALLERY['display']['file'] = unc_tools_filename_validate(trim($a['file']));
        $UNC_GALLERY['display']['files'] = array();
    } else {
        $UNC_GALLERY['display']['file'] = false;
        $UNC_GALLERY['display']['files'] = unc_day_images_list();
    }

    $UNC_GALLERY['display']['date_selector'] = false;
    if (in_array('calendar', $UNC_GALLERY['display']['options'])) {
        $UNC_GALLERY['display']['date_selector'] = 'calendar';
    } else if (in_array('datelist', $UNC_GALLERY['display']['options'])) {
        $UNC_GALLERY['display']['date_selector'] = 'datelist';
    }

    if (count($UNC_GALLERY['display']['files']) == 0 && !$UNC_GALLERY['display']['file']) {
        if ($UNC_GALLERY['no_image_alert'] == 'error') {
            $UNC_GALLERY['errors'][] = unc_display_errormsg("No images found for this date!");
        } else if ($UNC_GALLERY['no_image_alert'] == 'not_found') {
            $UNC_GALLERY['errors'][] = "No images available.";
        }
        return false;
    }

    return true;
}

/**
 * Iterate all files in a folder and make a list of all the images with all the info
 * for them
 *
 * @global type $UNC_GALLERY
 * @param type $folder
 * @return array
 */
function unc_day_images_list($D = false) {
    global $UNC_GALLERY, $wpdb;

    if (!$D) {
        $D = $UNC_GALLERY['display'];
    }

    $dates = $D['dates'];
    if (count($dates) == 0) {
        return false;
    }

    $files = array();
    $featured_list = array();

    // SQL construction
    $sql_filter = '';
    // both end_time and start_time are set
    if ($D['range']['end_time'] && $D['range']['start_time']) {
        $start_time = $D['date_range']['start_time'];
        $end_time = $D['date_range']['end_time'];
        $date = $D['dates'][0];
        if ($D['range']['start_time'] < $D['range']['end_time']) {
            $sql_filter = " (file_time >= '$start_time' AND file_time <= '$end_time')";
        } else if ($D['range']['start_time'] > $D['range']['end_time']){
            $sql_filter = " ((file_time >= '$date 00:00:00' AND file_time <= '$end_time') OR (file_time >= '$start_time' AND file_time <= '$date 23:59:59'))";
        }
    } else if ($D['range']['end_time']) { // get everything from day start until end time
        $end_time = $D['date_range']['end_time'];
        $date = $D['dates'][0];
        $sql_filter = " (file_time >= '$date 00:00:00' AND file_time <= '$end_time')";
    } else if ($D['range']['start_time']) { // get everything from start till day end
        $start_time = $D['date_range']['start_time'];
        $date = $D['dates'][0];
        $sql_filter = " (file_time >= '$start_time' AND file_time <= '$date 23:59:59')";
    } else {
        $dates = $D['dates'];
        $date_sql = implode($dates, "','");
        $sql_filter = " (att_value IN('$date_sql'))";
    }

    // get all images for the selected dates
    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $sql = "SELECT * FROM `$img_table_name`
        LEFT JOIN $att_table_name ON $img_table_name.id=$att_table_name.file_id
        WHERE ($att_table_name.att_name='date_str') AND $sql_filter
        ORDER BY file_time ASC;";
    $file_data = $wpdb->get_results($sql, 'ARRAY_A');
    
    if (count($file_data) == 0) {
        unc_tools_debug_trace('unc_day_images_list no images for date found!',  $sql); 
    }
    
    foreach ($file_data as $F) {
        $I = unc_image_info_read($F['file_path']);
        if (in_array($F['file_name'], $D['featured_image'])) {
            $I['featured'] = true;
            $featured_list[] = $I;
        } else {
            $I['featured'] = false;
            $files[] = $I;
        }
    }

    // TODO: Move this to the SQL string
    // random featured file
    if (in_array('random', $D['featured_image'])) {
        $new_featured_key = array_rand($files);
        $new_featured_arr = $files[$new_featured_key];
        $new_featured_arr['featured'] = true;
        $featured_list[] = $new_featured_arr;
        unset($files[$new_featured_key]);
    }

    // TODO: Move this to the SQL string
    if (in_array('latest', $D['featured_image'])) {
        reset($files);
        $first_key = key($files);
        $new_featured_arr = $files[$first_key];
        $new_featured_arr['featured'] = true;
        $featured_list[] = $new_featured_arr;
        unset($files[$first_key]);
    }

    foreach ($featured_list as $feat) {
        array_unshift($files, $feat);
    }

    if (count($files) == 0) {

    }

    return $files;
}

/**
 * Validate Datestr
 *
 * @param type $date_str
 * @return boolean
 */
function unc_day_validate_date($date_str) {
    global $UNC_GALLERY;
    
    $pattern = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
    if (preg_match($pattern, $date_str)) {
        return $date_str;
    }else{
        return false;
    }
}

/**
 * create a list of all dates between 2 dates
 *
 * @param string $date1 (date_str format)
 * @param string $date2
 * @return array
 */
function unc_day_date_span($date1, $date2) {
    global $UNC_GALLERY;
    
    // we try to sort the dates
    if ($date1 < $date2) {
        $early = $date1;
        $later = $date2;
    } else if ($date1 == $date2) {
        return array($date1);
    } else {
        $early = $date2;
        $later = $date1;
    }

    if (strlen($later) == 10) {
        $later .= " 23:59:59";
    }

    $dates_arr = new DatePeriod(
         new DateTime($early),
         new DateInterval('P1D'),
         new DateTime($later)
    );
    $date_str_arr = array();
    foreach($dates_arr as $date_obj) {
        $date_str_arr[] = $date_obj->format("Y-m-d");
    }
    return $date_str_arr;
}

/**
 * returns the latest date
 *
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_day_date_latest() {
    global $wpdb;

    $img_table_name = $wpdb->prefix . "unc_gallery_img";

    $sql ="SELECT SUBSTR(file_time, 1, 10) as date_str FROM `$img_table_name` ORDER BY file_time DESC LIMIT 1;";
    $file_data = $wpdb->get_results($sql, 'ARRAY_A');
    if (count($file_data) == 0) {
        unc_tools_debug_trace('unc_day_date_latest no images found!',  $sql);  
        return false;
    }
    $date_str = $file_data[0]['date_str'];

    return $date_str;
}

/**
 * returns a random date
 *
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_day_date_random() {
    global $UNC_GALLERY, $wpdb;
    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $sql = "SELECT SUBSTR(file_time, 1, 10) as date_str FROM `$img_table_name` GROUP BY SUBSTR(file_time, 1, 10) ORDER BY RAND() LIMIT 1";
    $file_data = $wpdb->get_results($sql, 'ARRAY_A');
    $date_str = $file_data[0]['date_str'];

    return $date_str;
}
