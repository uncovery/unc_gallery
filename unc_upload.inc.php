<?php
/**
 * Main form for uploads in the admin screen
 * @return string
 */
function unc_gallery_admin_upload() {
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
                    var fileUpload = jQuery("input[type='file']");
                    // check for max file number
                    var max_files = <?php echo ini_get('max_file_uploads'); ?>;
                    if (parseInt(fileUpload.get(0).files.length) > max_files){
                        alert("Your webserver allows only a maximum of " + max_files + " files");
                        return false;
                    }
                    // check for max filesize
                    var max_size = <?php echo ini_get('post_max_size'); ?>;
                    if (parseInt(fileUpload.get(0).files.size) > max_size){
                        alert("Your webserver allows only a maximum of " + max_size + " MB");
                        return false;
                    }
                    jQuery(this).ajaxSubmit(options);  // do ajaxSubmit with the obtions above
                    return false; // needs to be false so that the HTML is not actually submitted & reloaded
                });
                function success(response){
                    jQuery('#targetLayer').html(response); // fill the right element with a response
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
                    <td><input type="file" id="userImage" name="userImage[]" class="uploadInputBox" multiple required/></td>
                </tr>
                <tr>
                    <td><label>Overwrite existing files?</label></td>
                    <td><input type="checkbox" name="overwrite"></td>
                </tr>
            </table>
        </div>
        <div id="progress-div">
            <div id="progress-bar"></div>
        </div>
        <div class="image_upload_submit">
            <?php submit_button("Upload", "primary", "btnSubmit", false); ?>
        </div>
        <div id="targetLayer"></div>
    </form>
    <?php
}

/**
 * Main iterator for uploads handling after form was submitted
 *
 * @return boolean
 */
function unc_uploads_iterate_files() {
    // get the amount of files
    $count = count($_FILES["userImage"]["name"]);
    
    $ini_max = ini_get('max_file_uploads');
    
    if ($count < 1) {
        $out = "No images found to upload";
        return $out;
    }
    
    if ($count >= $ini_max) {
        $out = "Your server does not allow you to upload more than $ini_max files, you picked $count!";
        return $out;
    }
    
    $out = "Processing $count image(s)....<br>";

    // overwrite files?
    $overwrite = false;
    // filter_input is null when the vaiable is not in POST
    if (!is_null(filter_input(INPUT_POST, 'overwrite'))) {
        $overwrite = true;
    }

    // count up
    $out_arr = array();
    for ($i=0; $i<$count; $i++){
        // process one file
        $date_str = unc_uploads_process_file($i, $overwrite);
        $out_arr[$date_str]++;
        // did we get a valid result?
        if (!$date_str) {
            // something went wrong with this file, take the next
            // this is a bit redundant since nothing else would happen anyway
            continue;
        }
    }
    $out .= "All images processed!";
    ob_clean();
    echo $out;
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

    //array(1) {
    //    ["userImage"]=> array(5) {
    //        ["name"]=> array(1) { [0]=> string(23) "2013-11-02 21.00.38.jpg" }
    //        ["type"]=> array(1) { [0]=> string(10) "image/jpeg" }
    //        ["tmp_name"]=> array(1) { [0]=> string(14) "/tmp/phptgNK2k" }
    //        ["error"]=> array(1) { [0]=> int(0) }
    //        ["size"]=> array(1) { [0]=> int(213485) }
    //    }
    //}

    // the FILE array from the server
    $F = $_FILES["userImage"];

    // is there an error with the file?
    if ($F["error"][$i] > 0){
        echo unc_tools_errormsg("Unable to read the file, upload cancelled of file " . $F['name'][$i]);
        return false;
    }

    // if there is an imagesize, we have a valid image
    $image_check = getimagesize($F['tmp_name'][$i]);
    if (!$image_check) {
        echo unc_tools_errormsg("Not image file, upload cancelled of file " . $F['name'][$i]);
        return false;
    }

    $original_width = $image_check[0];
    $original_height = $image_check[1];

    // let's make sure the image is not 0-size
    if ($original_width == 0 || $original_height == 0) {
        echo unc_tools_errormsg("Image size {$F['name'][$i]} = 0");
        return false;
    }

    // let's shrink only if we need to
    if ($original_width == $UNC_GALLERY['thumbnail_size'] && $original_height == $UNC_GALLERY['thumbnail_size']) {
        echo unc_tools_errormsg("Image size {$F['name'][$i]} is smaller than thumbnail!");
        return false;
    }

    // get imagetype
    $exif_imagetype = exif_imagetype($F['tmp_name'][$i]);
    if (!$exif_imagetype) {
        echo unc_tools_errormsg("Could not determine image type of file " . $F['name'][$i] . ", upload cancelled!");
        return false;
    }

    // get mime-type and check if it's in the list of valid ones
    $mime_type = image_type_to_mime_type($exif_imagetype);
    if (!isset($mime_type, $UNC_GALLERY['valid_filetypes'])){
        echo unc_tools_errormsg("Invalid file type :" . $F["type"][$i]);
        return false;
    }

    // we set the new filename of the image including extension so there is no guessing
    $extension = $UNC_GALLERY['valid_filetypes'][$mime_type];
    $file_no_ext = pathinfo($F['name'][$i], PATHINFO_FILENAME);
    $target_filename = $file_no_ext . "." . $extension;

    // get the current path of the temp name
    if (is_uploaded_file($F['tmp_name'][$i])) {
        $sourcePath = $F['tmp_name'][$i];
    } else {
        echo unc_tools_errormsg("Cannot find uploaded file {$F['tmp_name'][$i]}!");
        return false;
    }

    // we need the exif date to know when the image was taken
    $exif_data = exif_read_data($sourcePath);
    if (!$exif_data || !isset($exif_data['DateTimeOriginal'])) {
        echo unc_tools_errormsg("Cannot read EXIF of file $sourcePath");
        return false;
    }

    // is that EXIF date a valid date?
    $date_str = $exif_data['DateTimeOriginal']; // format: 2011:06:04 08:56:22
    $date_check = date_create($date_str);
    if (!$date_check) {
        echo unc_tools_errormsg("'$date_str' is invalid date in EXIF");
        return false;
    }
    echo "File date is $date_str";

    // create all the by-day folders
    $date_obj = unc_date_folder_create($date_str);
    // if it failed return back
    if (!$date_obj) {
        return false;
    }

    // get the upload directory
    $dirPath =  WP_CONTENT_DIR . $UNC_GALLERY['upload'];

    // let's make the path with system-specific dir. separators
    $format = implode(DIRECTORY_SEPARATOR, array('Y', 'm', 'd'));
    
    $date_str_folder = $date_obj->format($format);
    echo "Folder date is $date_str_folder<br>";

    $target_subfolder = $dirPath . $UNC_GALLERY['photos'] . DIRECTORY_SEPARATOR . $date_str_folder;
    $thumb_subfolder = $dirPath . $UNC_GALLERY['thumbnails'] . DIRECTORY_SEPARATOR . $date_str_folder;
    $new_path =  $target_subfolder . DIRECTORY_SEPARATOR . $target_filename;
    $new_thumb_path =  $thumb_subfolder . DIRECTORY_SEPARATOR . $target_filename;

    // let's check that file already exists
    if (!$overwrite && file_exists($new_path)) {
        echo "$new_path Filename already exists, skipping!<br>";
        return false;
    } else {
        unlink($new_path);
        unlink($new_thumb_path);
        echo "$new_path Filename already exists, overwriting!<br>";
    }

    // finally, move the file
    $rename_chk = move_uploaded_file($F['tmp_name'][$i], $new_path);
    if (!$rename_chk) {
        echo unc_tools_errormsg("Could not move {$F['name'][$i]} from {$F['tmp_name'][$i]} to $new_path");
        return false;
    } else {
        echo "Moving file from {$F['tmp_name'][$i]} to $new_path<br>";
    }

    // chmod file to make sure it cannot be executed
    $check_chmod = chmod($new_path, 0644);
    if (!$check_chmod) {
        echo unc_tools_errormsg("Could not chmod 644 file $new_path");
        return false;
    } else {
        echo "Chmod successful!<br>";
    }

    // now make the thumbnail
    $check = unc_import_make_thumbnail($new_path, $new_thumb_path);
    if ($check) {
        echo "Done!<br>";
    }
    return true;
}

/**
 * Creates image thumbnails
 *
 * @global type $UNC_GALLERY
 * @param type $image_file_path
 * @param type $target_file_path
 * @return boolean
 */
function unc_import_make_thumbnail($image_file_path, $target_file_path) {
    global $UNC_GALLERY;

    $out = $image_file_path;
    $thumbnail_height = $UNC_GALLERY['thumbnail_height'];

    $img_types = array(1 => 'GIF', 2 => 'JPEG', 3 => 'PNG');

    // let's get the image size
    // this exit check is a bit redundant since we did that before with the uploaded file
    $arr_image_details = getimagesize($image_file_path); // pass id to thumb name
    if (!$arr_image_details) {
        echo unc_tools_errormsg("Failed to get image size of $image_file_path");
        return false;
    }
    $original_width = $arr_image_details[0];
    $original_height = $arr_image_details[1];
    $out .= " | Size: $original_width / $original_height | ";

    // we try to get the same height for all images
    $new_height = $thumbnail_height;
    $new_width = intval($original_width * ($thumbnail_height / $original_height));

    // get image extension
    $image_ext = $img_types[$arr_image_details[2]];

    // set the function names for processing
    $img_generator = "Image" . $UNC_GALLERY['thumbnail_ext'];
    $imgcreatefrom = "ImageCreateFrom" . $image_ext;

    $old_image = $imgcreatefrom($image_file_path);
    $new_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresized($new_image, $old_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
    $img_generator($new_image, $target_file_path);
    echo "Thumbnail created!<br>";
    return true;
}