<?php

/**
 * Convert an array to a printable text and save to file
 *
 * @param type $data
 * @param type $array_name
 * @param type $file
 * @return string
 */
function unc_array2file($data, $array_name, $file, $global = false) {
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

function unc_array2file_line($array, $layer, $val_change_func = false) {
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

function unc_array2file_indent($layer) {
    $text = '    ';
    $out = '';
    for ($i=0; $i<=$layer; $i++) {
        $out .= $text;
    }
    return $out;
}