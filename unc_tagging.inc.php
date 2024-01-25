<?php

/**
 * image tagging main process
 */
function unc_tagging_show() {

    $tag_edit = filter_input(INPUT_GET, 'tag_edit', FILTER_VALIDATE_INT);
    $update_tag = filter_input(INPUT_POST, 'tag_update', FILTER_VALIDATE_INT);
    
    if ($update_tag) {
        unc_tag_update();
        $out = unc_tagging_get_all();
    } else if ($tag_edit) {
        $out = unc_tag_edit($tag_edit);
    } else {
        $out = unc_tagging_get_all();
    }
    echo $out;
}

function unc_tag_update() {
    global $wpdb;
    $tag_id = filter_input(INPUT_POST, 'tag_update', FILTER_VALIDATE_INT);
    $tag = filter_input(INPUT_POST, 'tag', FILTER_SANITIZE_STRING);
    
    $tag_table = $wpdb->prefix . "unc_gallery_tags";
    $data = array('tag' => $tag, 'file_id' => $tag_id);
    $format = array('%s','%d');
    $wpdb->insert($tag_table, $data, $format);
    // $insert_id = $wpdb->insert_id;    
}

/**
 * show the form for the image tag update
 *
 * @param type $file_id
 */
function unc_tag_edit($file_id) {
    $path = umc_image_id2path($file_id);

    if (!$path) {
        return "Invalid file ID!";
    }

    $I = unc_image_info_read($path);

    if (is_array($I['xmp']['keywords'])) {
        $keywords = implode(", ", $I['xmp']['keywords']);
    } else {
        $keywords = $I['xmp']['keywords'];
    }

    $out = "<img src=\"{$I['file_url']}\">
        <form method=\"POST\">
            <input type=\"hidden\" name=\"tag_update\" value=\"$file_id\">
            <table>
                <tr><td>Current Tag:</td><td>$keywords</td></tr>
                <tr><td>Add new Tag:</td><td><input type=\"text\" name=\"tag\" value=\"\"></td><td><input type=\"submit\" value=\"Submit\"></td></tr>
            </table>
        </form>";

    echo $out;
}

/**
 * get all taggable images (those without parenthesis in the keyword
 *
 * @global type $wpdb
 * @return type
 */
function unc_tagging_get_all() {
    global $wpdb;
    $att_table = $wpdb->prefix . "unc_gallery_att";
    $img_table = $wpdb->prefix . "unc_gallery_img";
    $tag_table = $wpdb->prefix . "unc_gallery_tags";

    $sql = "SELECT id, file_time, att_value, file_path, file_name, tag
        FROM `$att_table` as att
        LEFT JOIN $img_table as img ON att.file_id=img.id
        LEFT JOIN $tag_table as tags ON att.file_id=tags.file_id
        WHERE `att_name` = 'keywords' AND att_group = 'xmp' AND `att_value` NOT LIKE '%(%'
        ORDER BY `att_value`, att.file_id, tags.time ASC";
   
    $I = $wpdb->get_results($sql, 'ARRAY_A');
    $out = "<table>
        <tr><th>Date</th><th>Name</th><th>ID</th><th>Original Tag</th><th>New Tag</th><th>Action</th></tr>\n";
    foreach ($I as $i) {
        // $F = unc_image_info_read($i['file_path']);

        $edit_link = '<a href="https://uncovery.net/image-tagging/?tag_edit=' . $i['id'] . '">Edit</a>';

        $out .= "<tr><td>{$i['file_time']}</td><td>{$i['file_name']}</td><td>{$i['id']}</td><td>{$i['att_value']}</td><td>{$i['tag']}</td><td>$edit_link</td></tr>";
    }
    $out .= "</table>";
    return $out;
}


function unc_tagging_fix() {
    global $wpdb;

    $sql = "SELECT * FROM `wp_terms` WHERE name Like '%(%'";
   
    $I = $wpdb->get_results($sql, 'ARRAY_A');
    foreach ($I as $i) {
        $id = $i['term_id'];
        $name = $i['name'];
        $new_name = ucwords($name,  " \t\r\n\f\v'(");
        $update = "UPDATE `wp_terms` SET name='$new_name' WHERE term_id=$id";
        echo $update . "\n";
    }
}
