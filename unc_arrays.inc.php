<?php

/**
 * analyses arrays for differences
 *
 * @param type $array1
 * @param type $array2
 * @return type
 */
function unc_array_analyse($array1, $array2) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $only_1 = array_diff($array1, $array2);
    $only_2 = array_diff($array2, $array1);
    $section = array_intersect($array1, $array2);
    $union = array_merge($only_1, $only_2, $section);

    $out = array(
        'only_in_1' => $only_1,
        'only_in_2' => $only_2,
        'common' => $section,
        'complete_set' => $union,
    );
    return $out;
}

/**
 * Convert an array to a printable text and save to file
 *
 * @param type $data
 * @param type $array_name
 * @param type $file
 * @return string
 */
function unc_array2file($data, $array_name, $file, $global = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    if ($global) {
        $out = '<?php' . "\n";
        $out .= "global $$array_name;\n";
        $out .= '$' . $array_name . "['$global'] = array(\n";
    } else {
        $out = '<?php' . "\n";
        $out .= '$' . $array_name . " = array(\n";
    }
    $out .= unc_array2file_line($data, 0) . ");";
    return file_put_contents($file, $out);
}

/**
 * Write one line of an array
 *
 * @global type $UNC_GALLERY
 * @param type $array
 * @param type $layer
 * @param type $val_change_func
 * @return type
 */
function unc_array2file_line($array, $layer, $val_change_func = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $in_text = unc_array2file_indent($layer);
    $out = "";
    foreach ($array as $key => $value) {
        if ($val_change_func) {
            $value = $val_change_func($key, $value);
        }
        $out .=  "$in_text'$key' => ";
        if (is_array($value)) {
            $layer++;
            $out .= "array(\n"
                . unc_array2file_line($value, $layer,  $val_change_func)
                . "$in_text),\n";
            $layer--;
        } else if(is_numeric($value)) {
            $out .= "$value,\n";
        } else {
            $safe_val = addslashes($value);
            $out .= "'$safe_val',\n";
        }
    }
    return $out;
}

/**
 * Calculate the indentation for the array written to file
 *
 * @global type $UNC_GALLERY
 * @param type $layer
 * @return string
 */
function unc_array2file_indent($layer) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $text = '    ';
    $out = '';
    for ($i=0; $i<=$layer; $i++) {
        $out .= $text;
    }
    return $out;
}