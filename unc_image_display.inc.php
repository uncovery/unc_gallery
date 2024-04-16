<?php

// show a single image with the correct information
function unc_image_display() {
    global $wpdb;

    $picture_id = filter_input(INPUT_GET, 'unc_gallery_id', FILTER_SANITIZE_NUMBER_INT);
    if (!$picture_id) {
        return "No image requested!";
    }

    $img_table = $wpdb->prefix . "unc_gallery_img";
    $att_table = $wpdb->prefix . "unc_gallery_att";

    $query = $wpdb->prepare("SELECT id, att_group, att_name, att_value FROM $img_table
        LEFT JOIN $att_table
        ON $img_table.id = $att_table.file_id
        WHERE $img_table.id=%d;", $picture_id);

    $I = $wpdb->get_results($query, 'ARRAY_A');

    if (count($I) == 0) {
        return "This image does not exist";
    }

    $F = array();
    foreach ($I as $D) {
        $group = $D['att_group'];
        $name = $D['att_name'];
        $value = $D['att_value'];
        if (isset($F[$group][$name]) && !is_array($F[$group][$name])) {
            $oldvalue = $F[$group][$name];
            unset($F[$group][$name]);
            $F[$group][$name][] = $oldvalue;
            $F[$group][$name][] = $value;
        } else {
            $F[$group][$name] = $value;
        }

    }

    // get keyword links
    $keywords = array();
    if (isset($F['xmp']['keywords'])) {
        $keywords = $F['xmp']['keywords'];
        if (!is_array($keywords)) {
            $keywords = array($keywords);
        }
    }
    $slug = '';
    $tag_links = array();
    foreach ($keywords as $keyword) {
        $slug = ucwords(strtolower($keyword));
        $tag_link = get_term_link($slug, 'post_tag');
        $tag_links[] = "<a href=\"$tag_link\">$slug</a>";
    }
    $tag_string = implode(", ", $tag_links);



    // get location strings
    $location = '';
    if (isset($F['iptc']['loc_str'])) {
        $location_string = $F['iptc']['loc_str'];
        $loc_string_nice = str_replace("|", ", ", $location_string);
        $loc_name_array = explode("|", $location_string);
        $loc_string = implode("-", $loc_name_array);
        $category_id = intval(unc_categories_link_read($loc_string));
        if ($category_id != 0) {
            $category_link = get_term_link($category_id, 'category');
            $location = "<li><b>Image Location: </b><a href=\"$category_link\">" . str_replace("|", ", ", $location_string). "</a></li>";
        }
    }

    // display the picture
    $out = "<img src=\"{$F['default']['file_url']}\" alt=\"$slug, photo taken in $loc_string_nice\">";

    $img_datasets = array(
        'Camera' => 'camera_model',
        'Lens' => 'lens',
        'Exposure' => 'exposure_time',
        'Aperture' => 'f',
        'ISO' => 'iso',
        'Focal Length' => 'focal_length',
    );

    $camera_info = '';
    foreach ($img_datasets as $title => $data) {
        if (isset($F['exif'][$data])) {
            $camera_info .= "<li><b>$title: </b>{$F['exif'][$data]}</li>";
        }
    }


    $out .= "<h3>Image details</h3>
        <ul class=\"post_list\">
            <li><b>Date taken: </b>{$F['default']['file_date']}</li>
            <li><b>In this image: </b>$tag_string</li>
            $location
        </ul>

    <h3>Camera details</h3>
        <ul class=\"post_list\">
            $camera_info
        </ul>";

    return $out;
}