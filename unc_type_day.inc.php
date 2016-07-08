<?php

function unc_day_var_init($a) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
        $UNC_GALLERY['display']['date_description'] = true;
        $UNC_GALLERY['display']['dates'] = array($date_str);
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!$D) {
        $D = $UNC_GALLERY['display'];
    }

    $dates = $D['dates'];

    $files = array();
    $featured_list = array();

    $att_table_name = $wpdb->prefix . "unc_gallery_att";


    // get all images for the selected dates
    $date_sql = implode($dates, "','");
    $sql = "SELECT path_table.file_id, path_table.att_value  FROM `$att_table_name`
        LEFT JOIN `$att_table_name` as path_table ON $att_table_name.file_id=path_table.file_id
        WHERE $att_table_name.att_name='date_str' AND $att_table_name.att_value IN('$date_sql') AND path_table.att_name='file_path';";
    $file_data = $wpdb->get_results($sql, 'ARRAY_A');
    XMPP_ERROR_trace("sql", $sql);
    XMPP_ERROR_trace("sql_dates", $file_data);
    // XMPP_ERROR_trigger("test");

    foreach ($dates as $date_str) {
        // translate date string to folder
        $date_path = str_replace("-", "/", $date_str);
        $photo_folder =  $UNC_GALLERY['upload_path'] . "/" . $UNC_GALLERY['photos'];
        $folder = $photo_folder . "/" . $date_path;
        foreach (glob($folder . "/*") as $file_path) {
            $F = unc_image_info_read($file_path);
            if (($D['range']['end_time'] && $D['range']['start_time']) && // only if both are set
                    ($D['range']['end_time'] < $D['range']['start_time'])) { // AND the end is before the start
                if (($D['range']['end_time'] < $F['time_stamp'])
                        && ($F['time_stamp'] < $D['range']['start_time'])) {  // then skip over the files inbetween end and start
                    continue;
                }
            } else if (($D['range']['start_time'] && ($F['time_stamp'] < $D['range']['start_time'])) || // if there is a start and the file is earlier
                ($D['range']['end_time'] && ($D['range']['end_time'] < $F['time_stamp']))) { // or if there is an end and the file is later then skip
                continue;
            }
            if (in_array($F['file_name'], $D['featured_image'])) {
                $F['featured'] = true;
                $featured_list[] = $F;
            } else {
                $F['featured'] = false;
                if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace('file_date', $F['file_date']);}
                $files[$F['file_date']] = $F;
            }
        }
    }
    ksort($files);

    // random featured file
    if (in_array('random', $D['featured_image'])) {
        $new_featured_key = array_rand($files);
        $new_featured_arr = $files[$new_featured_key];
        $new_featured_arr['featured'] = true;
        $featured_list[] = $new_featured_arr;
        unset($files[$new_featured_key]);
    }

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
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("Zero images found");}
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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

    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $img_table_name = $wpdb->prefix . "unc_gallery_img";

    $sql ="SELECT SUBSTR(file_time, 1, 10) as date_str FROM `$img_table_name` ORDER BY file_time DESC LIMIT 1;";
    $file_data = $wpdb->get_results($sql, 'ARRAY_A');
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $sql = "SELECT SUBSTR(file_time, 1, 10) as date_str FROM `$img_table_name` GROUP BY SUBSTR(file_time, 1, 10) ORDER BY RAND() LIMIT 1";
    $file_data = $wpdb->get_results($sql, 'ARRAY_A');
    $date_str = $file_data[0]['date_str'];

    return $date_str;
}
