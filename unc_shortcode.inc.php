<?php

/**
 * Show the shortcode creation
 *
 * @global type $UNC_GALLERY
 * @param type $return
 * @return type
 */
function unc_gallery_shortcode_form($return = true) {
    global $UNC_GALLERY;


    $fields = array(
        'group', 'field', 'operator', 'value'
    );

    foreach ($fields as $field) {
        $$field = filter_input(INPUT_POST, $field, FILTER_SANITIZE_STRING);
    }

    // $out = var_export($_POST, true) . "<br>";

    $out = unc_gallery_shortcode_filter_line();

    if ($return) {
        return $out;
    } else {
        ob_clean();
        echo $out;
        wp_die();
    }
}

/**
 * Create one new line of filter fields
 *
 * @global type $UNC_GALLERY
 * @param type $line_no
 * @param type $group
 * @param type $field
 * @param type $operator
 * @param type $value
 * @return string
 */
function unc_gallery_shortcode_filter_line($line_no = 0, $group = false, $field = false, $operator = false, $value = false) {
    global $UNC_GALLERY;

    $out = '';

    $defaults = array(
        'group' => 'exif',
        'field' => 'created',
        'operator' => 'l', // is larger
        'value' => '2019-01-01 00:00:00', // this needs to be calculated
    );

    // set the default values
    foreach ($defaults as $field => $default) {
        if (!$$field) {
            $$field = $default;
        }
    }

    // display groups dropdown
    $code_groups = $UNC_GALLERY['codes'];
    $groups_array = array();

    foreach (array_keys($code_groups) as $group) {
        $groups_array[$group] = strtoupper($group);
    }

    $ajax = '';

    // this needs an onsubmit to update the fields
    $out .= unc_tools_dropdown($groups_array, "group[$line_no]", $group, false, false, 'group', false, false, $ajax);

    $fields = $UNC_GALLERY['codes'][$group];
    $fields_array = array();
    foreach ($fields as $id => $data) {
        $fields_array[$id] = $data['description'];
    }

    $out .= unc_tools_dropdown($fields_array, "field[$line_no]", $field, false, false, 'field');

    $out .= unc_tools_dropdown($UNC_GALLERY['operators'], "operator[$line_no]", $operator, false, false, 'oeprator');

    $out .= "<input name=\"value[$line_no]\" value=\"$value\" id=\"id\">
        <button class=\"button button-primary\" onclick=\"unc_gallery_filter_ajax('unc_gallery_shortcode_form', 'add_shortcode_form', false, true, 'group')\">
            Submit
        </button>";
    return $out;
}

/**
 *
 * @global array $UNC_GALLERY
 * @param array $atts
 * @return type
 */
function unc_gallery_shortcode_translate($atts = array()) {
    global $UNC_GALLERY;

    if (!is_array($atts)) {
        // if we return the shortcode without arguments, we need to re-set this here.
        $atts = array();
    }

    // first we set defauls for all variables that are not declared in the shortcode
    // we also validate given variables
    $shortcode_array = unc_gallery_shortcode_analyse($atts);

    // if there are no problems, we display a page
    if ($shortcode_array) {
        return unc_gallery_images_filter($shortcode_array);
    } else {
        // otherwise, throw an error
        $err_text = implode("<br>", $UNC_GALLERY['errors']);
        $UNC_GALLERY['errors'] = array();
        return $err_text;
    }
}

/**
 * convert the short code string into an array
 *
 * @param type $atts
 */
function unc_gallery_shortcode_analyse($atts) {
    global $UNC_GALLERY;
    // format

    if (!isset($atts['data'])) {
        $UNC_GALLERY['errors'][] = "data variable not found in shortcode!";
        return false;
    }

    $code = $atts['data'];
    $atts_pieces = var_export($code, true);

    return $atts_pieces;
}

/**
 * find all the photos that match the filter
 *
 * @param type $shortcode_array
 */
function unc_gallery_images_filter($shortcode_array) {


    return "$shortcode_array <br> done!";
}
