<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Main form for uploads in the admin screen
 * @return string
 */
function unc_uploads_form() {
    if (isset($_SESSION['uploads_iterate_files'])) {
        unset($_SESSION['uploads_iterate_files']);
    }

    ?>
    <h2>Upload Images</h2>
    <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <script type="text/javascript">
            jQuery(document).ready(function() {
                var max_files = <?php echo ini_get('max_file_uploads'); ?>;
                var max_size = <?php echo unc_tools_bytes_get(ini_get('post_max_size')); ?>;
                unc_uploadajax(max_files, max_size);
            });
        </script>
        <div class="image_upload_input">
            <table>
                <tr>
                    <td><label>Select files to upload:</label></td>
                    <td colspan="2"><input type="file" id="userImage" name="userImage[]" class="uploadInputBox" multiple required/></td>
                </tr>
                <tr>
                    <td rowspan="3">File handling:</td><td><label>Only write new files, don't overwrite existing</label></td>
                    <td><input type="radio" name="overwrite" value="new" checked></td>
                </tr>
                <tr>
                    <td><label>Upload all, overwrite existing files</label></td>
                    <td><input type="radio" name="overwrite" value="all"></td>
                </tr>
                <tr>
                    <td><label>Only write already existing files, ignore the rest</label></td>
                    <td><input type="radio" name="overwrite" value="existing"></td>
                </tr>

            </table>
        </div>
        <div class="progress-div">
            <div id="upload-progress-bar">Upload 0%</div>
            <div id="process-progress-bar">Import 0%</div>
        </div>
        <div id="targetLayer"></div>
        <div class="image_upload_submit">
            <?php submit_button("Upload", "primary", "btnSubmit", false); ?>
        </div>
    </form>
    <h2>Import Images</h2>
    <form id="importForm" method="POST" enctype="multipart/form-data">
        Current path: <?php echo getcwd(); ?><br>
        <table>
            <tr>
                <td><label>Import photos from path on this server:</label></td>
                <td colspan="2"><input type="text" id="import_path" name="import_path[]" class="import_path"></td>
            </tr>
            <tr>
                <td rowspan="3">File handling:</td><td><label>Only write new files, don't overwrite existing</label></td>
                <td><input id="overwrite_import1" type="radio" name="overwrite_import[]" value="new" checked></td>
            </tr>
            <tr>
                <td><label>Upload all, overwrite existing files</label></td>
                <td><input id="overwrite_import2" type="radio" name="overwrite_import[]" value="all"></td>
            </tr>
            <tr>
                <td><label>Only write already existing files, ignore the rest</label></td>
                <td><input id="overwrite_import3" type="radio" name="overwrite_import[]" value="existing"></td>
            </tr>
        </table>
        <button class="button button-primary" onclick="unc_gallery_import_images(); return false;">
            Import
        </button>
        <div class="progress-div">
            <div id="import-process-progress-bar">Import 0%</div>
        </div>
        <div id="import_targetLayer"></div>
    </form>
    <?php
}

/**
 * Main iterator for uploads handling after form was submitted. Is called through
 * AJAX button and JS Function unc_gallery_import_images()
 *
 * @return boolean
 */
function unc_uploads_iterate_files() {
    global $UNC_GALLERY;

    // get the amount of files
    // do we have an upload or an import?
    $F = false;
    $import_path = filter_input(INPUT_POST, 'import_path');
    $process_id = filter_input(INPUT_POST, 'process_id');
    if (!is_null($import_path)) {
        if (is_dir($import_path)) {
            // iterate files in the path
            $check = unc_tools_import_enumerate($import_path);
            if (!$check) {
                unc_tools_progress_update($process_id, "Could not enumerate files in folder $import_path", 0);
                wp_die();
            }
            $F = $UNC_GALLERY['import'];
            $count = count($F["name"]);
            unc_tools_progress_update($process_id, "Found $count files in folder $import_path", 0);
        } else {
            $check = unc_tools_folder_access_check($import_path);
            $msg = '<p style=\"color: #F00;\">' . $import_path . " cannot be accessed or does not exist! Make sure its readable by the apache user: $check</p>";
            unc_tools_progress_update($process_id, $msg, 0);
            wp_die();
        }
    } else if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post'){ //catch file overload error...
            $postMax = ini_get('post_max_size'); //grab the size limits...
            $msg = "<p style=\"color: #F00;\">\nPlease note files larger than {$postMax} will result in this error!<br>Please be advised this is not a limitation in the CMS, This is a limitation of the hosting server.<br>For various reasons they limit the max size of uploaded files, if you have access to the php ini file you can fix this by changing the post_max_size setting.<br> If you can't then please ask your host to increase the size limits, or use the FTP uploaded form</p>"; // echo out error and solutions...
            unc_tools_progress_update($process_id, $msg, 0);
            wp_die(); //bounce back to the just filled out form.
    } else {
        $UNC_GALLERY['upload_files'] = $_FILES["userImage"];
        $F = $_FILES["userImage"];
        $count = count($F["name"]);
        $ini_max = ini_get('max_file_uploads');
        if ($count > $ini_max) {
            unc_tools_progress_update($process_id, "Your server does not allow you to upload more than $ini_max files, you picked $count!");
            wp_die();
        }
    }

    if ($count < 1) {
        unc_tools_progress_update($process_id, var_export($_FILES["userImage"], true));
        unc_tools_progress_update($process_id, "No images found to upload");
        wp_die();
    }

    $valid_options = array('new' => ", only using new files", 'all' => ', using new, overwriting existing', 'existing' => ', ignoring new files only overwriting existing');
    // filte_input is null when the vaiable is not in POST
    if (isset($UNC_GALLERY['import'])) {
        $status = "Importing $count images";
        $import_option_raw = $_POST['overwrite_import'];
        $imp_stats = $import_option_raw[0];
        $imp_vals = $import_option_raw[1];
        foreach ($imp_stats as $id => $stat) {
            if ($stat == 'true') {
                $import_option = $imp_vals[$id];
                break;
            }
        }
    } else {
        $status = "Uploading $count images";
        $import_option = filter_input(INPUT_POST, 'overwrite');
    }
    if (is_null($import_option) || !isset($valid_options[$import_option])) {
        echo "Bad import option: $import_option!";
        wp_die();
    }

    $overwrite = $import_option;
    unc_tools_progress_update($process_id, $status . $valid_options[$import_option], 0);

    // count up
    $date_str_arr = array();

    $one_file_percent = 100 / $count;

    $percentage = 0;
    $updated = 0;
    for ($i=0; $i < $count; $i++){
        // process one file
        $result_arr = unc_uploads_process_file($i, $overwrite, $process_id);
        $date_str = $result_arr['date'];
        $action = $result_arr['action'];
        if (!$date_str) {
            $string = unc_display_errormsg($action);
        } else {
            $updated++;
            $keywords = $result_arr['keywords'];
            $string = "$date_str: image $action\n";
            $string .= "Keywords: $keywords";
            $date_str_arr[] = $date_str;
        }
        $percentage += $one_file_percent;
        unc_tools_progress_update($process_id, "File " . ($i + 1) . ": " . $string, $percentage);
    }

    if ($updated > 0) {
        $string = "$count images processed, $updated successfully! <br><br>" . '
            Sample Shortcode for this upload:<br>
            <input
                style="width:100%;"
                id="upload_short_code_sample"
                onClick="SelectAll(\'upload_short_code_sample\');"
                type="text"
                value="[unc_gallery start_time=&quot;' . min($date_str_arr) . '&quot; end_time=&quot;' . max($date_str_arr) . '&quot;]"
            ><br>';
    } else {
        $string = "No files uploaded<br>";
    }

    unc_tools_progress_update($process_id, $string, 100);
    // this signals to the JS function that we can terminate the process_get loop
    unc_tools_progress_update($process_id, false);
    wp_die();
}

/**
 * processes one uploaded file. Creates folders, moves files
 *
 * @global type $UNC_GALLERY
 * @param type $i
 * @param type $overwrite
 * @return boolean
 */
function unc_uploads_process_file($i, $overwrite, $process_id) {
    global $UNC_GALLERY;
    $action = false;

    if (isset($UNC_GALLERY['import'])) {
        $type = 'import';
        $F = $UNC_GALLERY['import'];
    } else {
        $type = 'upload';
        $F = $UNC_GALLERY['upload_files'];
    }

    $source_name = $F['name'][$i];
    $source_tmp_path = $F['tmp_name'][$i];

    // get the current path of the temp name
    if ($type == 'upload' && !is_uploaded_file($source_tmp_path)) {
        return array('date'=> false, 'action' => "Cannot find uploaded file $source_tmp_path!");
    } else if ($type == 'import' && !is_file($source_tmp_path)) {
        return array('date'=> false, 'action' => "Cannot find imported file $source_tmp_path!");
    }

    // is there an error with the file?
    if ($F["error"][$i] !== 0){
        $errormsg = $F["error"][$i];
        return array('date'=> false, 'action' => "Unable to process file ($errormsg), upload cancelled: $source_name");
    }

    // if the image has height & width, we have a valid image
    $image_check = getimagesize($source_tmp_path);
    if (!$image_check) {
        return array('date'=> false, 'action' => "Not image file, upload cancelled of file $source_name");
    }
    $original_width = $image_check[0];
    $original_height = $image_check[1];

    // let's set variables for the currently uploaded file so we do not have to get the same data twice.
    $UNC_GALLERY['upload_file_info'] = array(
        'image_size' => $image_check,
        'temp_name' => $source_tmp_path,
        'type' => $type,
    );

    // let's make sure the image is not 0-size
    if ($original_width == 0 || $original_height == 0) {
        echo unc_display_errormsg("Image size $source_name = 0");
        return false;
    }

    // let's shrink only if we need to
    if ($original_width == $UNC_GALLERY['thumbnail_height'] && $original_height == $UNC_GALLERY['thumbnail_height']) {
        return array('date'=> false, 'action' => "Image size $source_name is smaller than thumbnail!");
    }

    // get imagetype
    $exif_imagetype = $image_check[2];
    if (!$exif_imagetype) {
        return array('date'=> false, 'action' => "Could not determine image type of file $source_name, upload cancelled!");
    }
    $UNC_GALLERY['upload_file_info']['exif_imagetype'] = $exif_imagetype;

    // get mime-type and check if it's in the list of valid ones
    $mime_type = image_type_to_mime_type($exif_imagetype);
    if (!isset($mime_type, $UNC_GALLERY['valid_filetypes'])){
        return array('date'=> false, 'action' => "Invalid file type for $source_name:" . $F["type"][$i]);
    }

    // get extension for optional resize
    $extension = $UNC_GALLERY['valid_filetypes'][$mime_type];

    // we set the new filename of the image including extension so there is no guessing
    $file_no_ext = pathinfo($source_name, PATHINFO_FILENAME);

//    // in case we want to convert the file to another format, give the extension now
//    if ($UNC_GALLERY['image_filetype_convert'] != 'none') {
//        $new_extension = $UNC_GALLERY['image_filetype_convert'];
//
//       //  unc_image_convert($source_tmp_path, $target_path);
//    }

    $UNC_GALLERY['upload_file_info']['extension'] = $extension;

    $target_filename = $file_no_ext . "." . $extension;
    $UNC_GALLERY['upload_file_info']['target_filename'] = $target_filename;

    // we need the exif date to know when the image was taken
    $date_str = unc_image_date($source_tmp_path);
    if (!$date_str) {
        return array('date'=> false, 'action' => "Cannot read EXIF or IPTC date of file $source_tmp_path");
    }
    $UNC_GALLERY['upload_file_info']['date_str'] = $date_str;

    $date_check = date_create($date_str);
    if (!$date_check) {
        return array('date'=> false, 'action' => "'$date_str' is invalid date in EXIF or IPTC");
    }
    // echo "File date is $date_str";

    // check if there is another image with the same date and same filename
    global $wpdb;
    $day_string = substr($date_str, 0, 8);
    $check_sql = "SELECT * FROM `wp_unc_gallery_img`
        WHERE (file_time >= '$day_string 00:00:00' AND file_time <= '$day_string 23:59:59') AND file_name='$target_filename' AND file_time <> '$date_str'";
    $check_files = $wpdb->get_results($check_sql);
    if (count($check_files) > 0) {
        return array('date'=> false, 'action' => "File $target_filename cannot be uploaded since there is already a file with the sane name but a different time on that date");
    }

    // create all the by-day folders
    $date_obj = unc_date_folder_create($date_str);
    // if it failed return back
    if (!$date_obj) {
        return array('date'=> false, 'action' => "Could not create date folders!");
    }

    // get the upload directory
    $dirPath = $UNC_GALLERY['upload_path'];

    // let's make the path with system-specific dir. separators
    $format = implode("/", array('Y', 'm', 'd'));

    $date_str_folder = $date_obj->format($format);
    // echo "Folder date is $date_str_folder<br>";

    $target_subfolder = $dirPath . "/" . $UNC_GALLERY['photos'] . "/" . $date_str_folder;
    $thumb_subfolder = $dirPath . "/" . $UNC_GALLERY['thumbnails'] . "/" . $date_str_folder;
    $new_path =  $target_subfolder . "/" . $target_filename;
    $new_thumb_path =  $thumb_subfolder . "/" . $target_filename;

    // act on overwrite options
    if ($overwrite == 'new' && file_exists($new_path)) {
        return array('date'=> false, 'action' => "skipped file $target_filename, already exists<br>");
    } else if ($overwrite == 'existing' && !file_exists($new_path)) {
        return array('date'=> false, 'action' => "skipped file $target_filename, is new<br>");
    } else if ($overwrite == 'existing' && file_exists($new_path)) {
        unlink($new_path);
        $action = 'overwritten';
    } else if ($overwrite == 'all' && file_exists($new_path)) {
        unlink($new_path);
        $action = 'overwritten';
    }

    // finally, move the file. either we resize in case this is a setting
    if ($UNC_GALLERY['picture_long_edge'] > 0) {
        $resize_check = unc_import_image_resize(
            $source_tmp_path,
            $new_path,
            $UNC_GALLERY['picture_long_edge'],
            $extension,
            $UNC_GALLERY['image_quality'],
            $process_id,
            'max_height'
        );
        if (!$resize_check) {
            return array('date'=> false, 'action' => "Could not resize $source_name from $source_tmp_path to $new_path");
        }
    } else { // otherwise we take the file as-is
        if ($type == 'upload') {
            $rename_chk = move_uploaded_file($source_tmp_path, $new_path);
        } else { // import
            $rename_chk = copy($source_tmp_path, $new_path);
        }
        if (!$rename_chk) {
            return array('date'=> false, 'action' => "Could not move $source_name from $source_tmp_path to $new_path");
        }
    }

    // chmod file to make sure it cannot be executed
    $check_chmod = chmod($new_path, 0644);
    if (!$check_chmod) {
        return array('date'=> false, 'action' => "Could not chmod 644 file $new_path");
    }

    // now make the thumbnail
    $thumb_format = $UNC_GALLERY['thumbnail_format'];
    $check = unc_import_image_resize(
        $new_path, // the imported/uploded main image
        $new_thumb_path, // the thumb target that will be created
        $UNC_GALLERY['thumbnail_height'],
        $extension, // we use the same image extension as the main file
        $UNC_GALLERY['thumbnail_quality'],
        $process_id,
        $thumb_format
    );

    if (!$check) {
        return array('date'=> false, 'action' => "Could not create the thumbnail for $source_tmp_path / $new_thumb_path!");
    } else if (!$action) {
        $action = 'written';
    }

    $xmp_status = unc_image_info_write($new_path);
    if (!$xmp_status) {
        return array('date' => false, 'action' => "Could not write XMP/IPTC/EXIF data to database $target_filename");
    }

    return array('date'=> $date_str, 'action' => $target_filename . ": " . $action, 'keywords' => $xmp_status['keywords'], 'location' => $xmp_status['location']);
}

/**
 * Resize an image so the long edge becomes a given value
 *
 * @global array $UNC_GALLERY
 * @param string $image_file_path
 * @param string $target_file_path
 * @param int $size target size of the image
 * @param string $extension the file extension
 * @param int @quality quality from 1 (worst) to 100 (best)
 * @param string $format
 * @param int @process_id
 * @return boolean
 */
function unc_import_image_resize($image_file_path, $target_file_path, $size, $extension, $quality, $process_id, $format = false) {
    global $UNC_GALLERY;
    $img_types = array(1 => 'GIF', 2 => 'JPEG', 3 => 'PNG');

    //
    if (!isset($UNC_GALLERY['upload_file_info'])) {
        $image_data = unc_image_info_read($image_file_path);
        $original_width = $image_data['exif']['file_width'];
        $original_height = $image_data['exif']['file_height'];
        $image_ext = $ext = pathinfo($image_file_path, PATHINFO_EXTENSION);
        $file_date = $image_data['date_str'];
    } else {
        // let's get the image size from the last check
        $arr_image_details = $UNC_GALLERY['upload_file_info']['image_size'];
        $original_width = $arr_image_details[0];
        $original_height = $arr_image_details[1];
        $image_ext = $img_types[$arr_image_details[2]];
        $file_date = $UNC_GALLERY['upload_file_info']['date_str'];
    }

    // for long-edge fitting, check which one is longer
    if ($original_height > $original_width) {
        $long_edge = 'height';
        $short_edge = 'width';
    } else {
        $long_edge = 'width';
        $short_edge = 'height';
    }

    if ($original_height > $original_width) {
        $square_x = 0;
        $square_y = ceil(($original_height - $original_width) / 2);
    } else {
        $square_x = ceil(($original_width - $original_height) / 2);
        $square_y = 0;
    }

    // if we go for square, the target edge is the short one
    if ($format == 'square') {
        $new_height = $size;
        $new_width = $size;
    } else { // if ($format == 'max_height') {
        $new_height = $size;
        $new_width = intval($original_width * ($size / $original_height));
    }

    // set the function names for processing
    $imgcreatefrom = "imagecreatefrom" . strtolower($image_ext);

    $new_image = imagecreatetruecolor($new_width, $new_height); // create a blank canvas
    // unc_tools_progress_update($process_id, "imagecreatetruecolor($new_width, $new_height);");
    $old_image = $imgcreatefrom($image_file_path); // take the old image to memory
    // unc_tools_progress_update($process_id, "$imgcreatefrom($image_file_path);");
    $source_width = $original_width;
    $source_height = $original_height;

    if ($format == 'square') {
        $source_x = $square_x;
        $source_y = $square_y;
        if ($long_edge == 'height') {
            $source_height = $original_width;
        } else {
            $source_width = $original_height;
        }
    } else {
        $source_x = 0;
        $source_y = 0;
    }

    $resize_check = imagecopyresized($new_image, $old_image, 0, 0, $source_x, $source_y, $new_width, $new_height, $source_width, $source_height); // resize it
    $val_array = array('nw'=> $new_width, 'nh'=>$new_height, 'ow'=>$original_width, 'oh'=> $original_height);
    foreach ($val_array as $name => $value) {
        if (!is_string($value) && !is_int($value)) {
            unc_tools_progress_update($process_id, "checking $name! $value");
            wp_die("Value $name is invalid!");
        }
    }
    // unc_tools_progress_update($process_id, "imagecopyresized(new_image, old_image 0, 0, $source_x, $source_y, $new_width, $new_height, $source_width, $source_height)");
    if (!$resize_check) { // let's check if the file was resized
        echo "Could not resize image to dimensions $new_width, $new_height, $original_width, $original_height!";
        wp_die();
    }

    $img_generator = "image" . strtolower($extension);
    $image_check = $img_generator($new_image, $target_file_path, $quality);

    // unc_tools_progress_update($process_id, "$img_generator($target_file_path, $quality)");
    // unc_tools_progress_update($process_id, "renaming & compressing...");
    if (!$image_check || !file_exists($target_file_path)) { // let's check if the file was created
        echo "File $target_file_path was not created through $img_generator at quality $quality!";
        wp_die();
    }

    // write iptc date to new thumbnail file
    unc_iptc_date_write($target_file_path, $file_date);
    //$new_file_date = unc_image_date($target_file_path);
    imagedestroy($new_image); // free up the memory
    imagedestroy($old_image);
    return true;
}