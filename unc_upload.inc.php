<?php
/**
 * Main form for uploads in the admin screen
 * @return string
 */
function unc_gallery_admin_upload() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    ?>
    <h2>Upload Images</h2>
    <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <script type="text/javascript">
            jQuery(document).ready(function() {
                var options = {
                    url: ajaxurl, // this is pre-filled by WP to the ajac-response url
                    data: {action: 'unc_gallery_uploads'}, // this needs to match the add_action add_action('wp_ajax_unc_gallery_uploads', 'unc_uploads_iterate_files');
                    success: success, // the function we run on success
                    uploadProgress: uploadProgress, // the function tracking the upload progress
                    beforeSubmit: beforeSubmit // what happens before we start submitting
                };
                jQuery('#uploadForm').submit(function() { // once the form us submitted
                    // server limits:
                    var max_files = <?php echo ini_get('max_file_uploads'); ?>;
                    var max_size = <?php echo unc_tools_bytes_get(ini_get('post_max_size')); ?>;

                    var fileInput = jQuery("input[type='file']").get(0);
                    var actual_count = parseInt(fileInput.files.length);

                    var actual_size = 0;
                    for (var i = 0; i < fileInput.files.length; i++) {
                        var file = fileInput.files[i];
                        if ('size' in file) {
                            actual_size = actual_size + +file.size;
                        }
                    }

                    // check for max file number

                    if (actual_count > max_files) {
                        alert("Your webserver allows only a maximum of " + max_files + " files");
                        return false;
                    }

                    if (actual_size > max_size){
                        alert("Your webserver allows only a maximum of " + max_size + " Bytes, you tried " + actual_size);
                        return false;
                    }
                    jQuery(this).ajaxSubmit(options);  // do ajaxSubmit with the obtions above
                    return false; // needs to be false so that the HTML is not actually submitted & reloaded
                });
                function success(response){
                    jQuery('#targetLayer').html(response); // fill the right element with a response
                    // also refresh the image list
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'GET',
                        dataType: 'text',
                        data: {action: 'unc_gallery_images_refresh'},
                        complete: function (response2) {
                            jQuery('#datepicker_target').html(response2.responseText);
                        },
                        error: function () {

                        }
                    });
                }
                function uploadProgress(event, position, total, percentComplete) {
                    jQuery("#progress-bar").width(percentComplete + '%');
                    jQuery("#progress-bar").html('<div id="progress-status">' + percentComplete +' %</div>');
                }
                function beforeSubmit(formData, jqForm, options) {
                    jQuery("#progress-bar").width('0%');
                    jQuery('#targetLayer').html(''); // empty the div from the last submit
                    return true;
                }
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
        <div id="progress-div">
            <div id="progress-bar"></div>
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
        <div id="import_targetLayer"></div>
    </form>
    <?php
}

/**
 * Main iterator for uploads handling after form was submitted
 *
 * @return boolean
 */
function unc_uploads_iterate_files() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // get the amount of files
    // do we have an upload or an import?
    $F = false;
    $import_path = filter_input(INPUT_POST, 'import_path');
    if (!is_null($import_path)) {
        if (is_dir($import_path)) {
            // iterate files in the path
            unc_tools_import_enumerate($import_path);
            $F = $UNC_GALLERY['import'];
            $count = count($F["name"]);
            echo "Found $count files $import_path<br>";
        } else {
            echo $import_path . " cannot be accessed or does not exist! Make sure its readable by the apache user!";
            wp_die();
        }
    } else if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post'){ //catch file overload error...
            $postMax = ini_get('post_max_size'); //grab the size limits...
            echo "<p style=\"color: #F00;\">\nPlease note files larger than {$postMax} will result in this error!<br>Please be advised this is not a limitation in the CMS, This is a limitation of the hosting server.<br>For various reasons they limit the max size of uploaded files, if you have access to the php ini file you can fix this by changing the post_max_size setting.<br> If you can't then please ask your host to increase the size limits, or use the FTP uploaded form</p>"; // echo out error and solutions...
            wp_die(); //bounce back to the just filled out form.
    } else {
        $UNC_GALLERY['upload_files'] = $_FILES["userImage"];
        $F = $_FILES["userImage"];
        $count = count($F["name"]);
        $ini_max = ini_get('max_file_uploads');
        if ($count > $ini_max) {
            echo "Your server does not allow you to upload more than $ini_max files, you picked $count!";
            wp_die();
        }
    }

    if ($count < 1) {
        echo "No images found to upload";
        wp_die();
    }

    $valid_options = array('new' => ", only using new files", 'all' => ', using new, overwriting existing', 'existing' => ', ignoring new files only overwriting existing');
    // filte_input is null when the vaiable is not in POST
    if (isset($UNC_GALLERY['import'])) {
        echo "Importing $count images ";
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
        echo "Uploading $count images ";
        $import_option = filter_input(INPUT_POST, 'overwrite');
    }
    if (is_null($import_option) || !isset($valid_options[$import_option])) {
        echo "Bad import option: $import_option!";
        wp_die();
    }
    $overwrite = $import_option;
    echo $valid_options[$import_option];
    echo "<br>";

    // count up
    $date_str_arr = array();
    for ($i=0; $i < $count; $i++){
        // process one file
        $result_arr = unc_uploads_process_file($i, $overwrite);
        $date_str = $result_arr['date'];
        $date_str_arr[] = $date_str;
        $action = $result_arr['action'];
        echo ($i + 1) . ": ";
        if (!$date_str) {
            echo unc_display_errormsg($action);
        } else {
            echo "$date_str: image $action<br>\n";
        }
    }

    echo "<br>All images processed!";

    echo '<br> Sample Shortcode for this upload: [unc_gallery start_time="' . min($date_str_arr) . '" end_time="' . max($date_str_arr) . '"]<br>';

    // ob_clean();
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
function unc_uploads_process_file($i, $overwrite) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $action = false;
    //$_FILES(1) {
    //    ["userImage"]=> array(5) {
    //        ["name"]=> array(1) { [0]=> string(23) "2013-11-02 21.00.38.jpg" }
    //        ["type"]=> array(1) { [0]=> string(10) "image/jpeg" }
    //        ["tmp_name"]=> array(1) { [0]=> string(14) "/tmp/phptgNK2k" }
    //        ["error"]=> array(1) { [0]=> int(0) }
    //        ["size"]=> array(1) { [0]=> int(213485) }
    //    }
    //}

    // the FILE array from the server


    if (isset($UNC_GALLERY['import'])) {
        $type = 'import';
        $F = $UNC_GALLERY['import'];
    } else {
        $type = 'upload';
        $F = $UNC_GALLERY['upload_files'];
    }

    // get the current path of the temp name
    if ($type == 'upload' && is_uploaded_file($F['tmp_name'][$i])) {
        $sourcePath = $F['tmp_name'][$i];
    } else if ($type == 'import' && is_file($F['tmp_name'][$i])) {
        $sourcePath = $F['tmp_name'][$i];
    } else {
        return array('date'=> false, 'action' => "Cannot find uploaded file {$F['tmp_name'][$i]}!");
    }

    // is there an error with the file?
    if ($F["error"][$i] > 0){
        return array('date'=> false, 'action' => "Unable to read the file, upload cancelled of file " . $F['name'][$i]);
    }

    // if there is an imagesize, we have a valid image
    $image_check = getimagesize($F['tmp_name'][$i]);

    if (!$image_check) {
        return array('date'=> false, 'action' => "Not image file, upload cancelled of file " . $F['name'][$i]);
    }

    // let's set variables for the currently uploaded file so we do not have to get the same data twice.
    $UNC_GALLERY['upload_file_info'] = array(
        'image_size' => $image_check,
        'temp_name' => $F['tmp_name'][$i],
        'type' => $type,
    );

    $original_width = $image_check[0];
    $original_height = $image_check[1];

    // let's make sure the image is not 0-size
    if ($original_width == 0 || $original_height == 0) {
        echo unc_display_errormsg("Image size {$F['name'][$i]} = 0");
        return false;
    }

    // let's shrink only if we need to
    if ($original_width == $UNC_GALLERY['thumbnail_height'] && $original_height == $UNC_GALLERY['thumbnail_height']) {
        return array('date'=> false, 'action' => "Image size {$F['name'][$i]} is smaller than thumbnail!");
    }

    // get imagetype
    $exif_imagetype = $image_check[2];
    if (!$exif_imagetype) {
        return array('date'=> false, 'action' => "Could not determine image type of file " . $F['name'][$i] . ", upload cancelled!");
    }
    $UNC_GALLERY['upload_file_info']['exif_imagetype'] = $exif_imagetype;

    // get mime-type and check if it's in the list of valid ones
    $mime_type = image_type_to_mime_type($exif_imagetype);
    if (!isset($mime_type, $UNC_GALLERY['valid_filetypes'])){
        return array('date'=> false, 'action' => "Invalid file type :" . $F["type"][$i]);
    } else { // get extension for optional resize
        $extension = $UNC_GALLERY['valid_filetypes'][$mime_type];
    }
    $UNC_GALLERY['upload_file_info']['extension'] = $extension;

    // we set the new filename of the image including extension so there is no guessing
    $file_no_ext = pathinfo($F['name'][$i], PATHINFO_FILENAME);
    $target_filename = $file_no_ext . "." . $extension;
    $UNC_GALLERY['upload_file_info']['target_filename'] = $target_filename;

    // we need the exif date to know when the image was taken
    $date_str = unc_image_date($sourcePath);
    if (!$date_str) {
        return array('date'=> false, 'action' => "Cannot read EXIF or IPCT of file $sourcePath");
    }
    $UNC_GALLERY['upload_file_info']['date_str'] = $date_str;

    $date_check = date_create($date_str);
    if (!$date_check) {
        return array('date'=> false, 'action' => "'$date_str' is invalid date in EXIF or IPCT");
    }
    // echo "File date is $date_str";

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

    // finally, move the file
    if ($UNC_GALLERY['picture_long_edge'] > 0) {
        $resize_check = unc_import_image_resize(
            $F['tmp_name'][$i],
            $new_path,
            $UNC_GALLERY['picture_long_edge'],
            $extension,
            $UNC_GALLERY['image_quality'],
            'max_height'
        );
        if (!$resize_check) {
            return array('date'=> false, 'action' => "Could not resize {$F['name'][$i]} from {$F['tmp_name'][$i]} to $new_path");
        }
    } else {
        if ($type == 'upload') {
            $rename_chk = move_uploaded_file($F['tmp_name'][$i], $new_path);
        } else { // import
            $rename_chk = copy($F['tmp_name'][$i], $new_path);
        }
        if (!$rename_chk) {
            return array('date'=> false, 'action' => "Could not move {$F['name'][$i]} from {$F['tmp_name'][$i]} to $new_path");
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
        $new_path,
        $new_thumb_path,
        $UNC_GALLERY['thumbnail_height'],
        $UNC_GALLERY['thumbnail_ext'],
        $UNC_GALLERY['thumbnail_quality'],
        $thumb_format
    );
    if (!$check) {
        return array('date'=> false, 'action' => "Could not create the thumbnail for {$F['tmp_name'][$i]} / $new_thumb_path!");
    } else if (!$action) {
        $action = 'written';
    }

    $check_xmp = unc_image_info_write($new_path);
    if (!$check_xmp) {
        return array('date' => false, 'action' => "Could not write XMP/IPCT/EXIF data to file");
    }

    return array('date'=> $date_str, 'action' => $target_filename . ": " . $action);
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
 * @return boolean
 */
function unc_import_image_resize($image_file_path, $target_file_path, $size, $extension, $quality, $format = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $img_types = array(1 => 'GIF', 2 => 'JPEG', 3 => 'PNG');

    //
    if (!isset($UNC_GALLERY['upload_file_info'])) {
        $image_data = unc_image_info_read($image_file_path);
        $original_width = $image_data['exif']['file_width'];
        $original_height = $image_data['exif']['file_height'];
        $image_ext = $img_types[2]; // TODO this should not be hardcoded, but currently we only accept JPG
        $file_date = $image_data['date_str'];
    } else {
        // let's get the image size from the last check
        $arr_image_details = $UNC_GALLERY['upload_file_info']['image_size'];
        $original_width = $arr_image_details[0];
        $original_height = $arr_image_details[1];
        $image_ext = $img_types[$arr_image_details[2]];
        $file_date = $UNC_GALLERY['upload_file_info']['date_str'];
    }

    //XMPP_ERROR_trace("width / height", "$original_width / $original_height");

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("Read image date result", $file_date);}

    // for long-edge fitting, check which one is longer
    if ($original_height > $original_width) {
        $long_edge = 'height';
        $short_edge = 'width';
    } else {
        $long_edge = 'width';
        $short_edge = 'height';
    }

    //XMPP_ERROR_trace("Long edge", $long_edge);

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

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("New image dims", "$original_width x $original_height ==> $new_width x $new_height");}
    // get image extension from MIME type


    // set the function names for processing
    $img_generator = "Image" . $extension;
    $imgcreatefrom = "ImageCreateFrom" . $image_ext;

    $new_image = imagecreatetruecolor($new_width, $new_height); // create a blank canvas
    $old_image = $imgcreatefrom($image_file_path); // take the old image to memort

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
    if (!$resize_check) { // ;et's check if the file was resized
        echo "Could not resize image to dimensions $new_width, $new_height, $original_width, $original_height!";
        wp_die();
    }

    $image_check = $img_generator($new_image, $target_file_path, $quality);
    if (!$image_check || !file_exists($target_file_path)) { // let's check if the file was created
        echo "File $target_file_path was not created through $img_generator at quality $quality!";
        wp_die();
    }

    // write ipct date to new thumbnail file
    unc_ipct_date_write($target_file_path, $file_date);
    $new_file_date = unc_image_date($target_file_path);
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("check IPCT result", $new_file_date);}
    //XMPP_ERROR_trigger("test");
    imagedestroy($new_image); // free up the memory
    return true;
}