<?php
/**
 * Main form for uploads in the admin screen
 * @return string
 */
function unc_uploads_form() {
    ?>
    <div class="wrap">
        <h2>Upload Images</h2>
    </div>
    <form id="uploadForm"  action="?page=unc_gallery_admin_upload" method="POST" enctype="multipart/form-data">
        <script type="text/javascript">
            jQuery(document).ready(function() {
                var options = {
                    url: ajaxurl,
                    data: {action: 'unc_uploads'},
                    success: success,
                    uploadProgress: uploadProgress,
                    beforeSubmit: beforeSubmit
                };                
                jQuery('#uploadForm').submit(function() {
                    jQuery(this).ajaxSubmit(options);
                    return false;
                });
                function success(response){
                    jQuery('#targetLayer').html(response);
                }                
                function uploadProgress(event, position, total, percentComplete) {
                    jQuery("#progress-bar").width(percentComplete + '%');
                    jQuery("#progress-bar").html('<div id="progress-status">' + percentComplete +' %</div>');
                }
                function beforeSubmit(formData, jqForm, options) {
                    jQuery("#progress-bar").width('0%');
                    jQuery('#targetLayer').html('');
                    return true;
                }               
            });
        </script>
        <div class="image_upload_input">
            <label>Select files to upload:</label>
            <input type="file" id="userImage" name="userImage[]" class="demoInputBox" multiple required/>
        </div>
        <div class="image_upload_submit"><input type="submit" id="btnSubmit" value="submit" class="btnSubmit" /></div>
        <div id="progress-div"><div id="progress-bar"></div></div>
        <div id="targetLayer"></div>
    </form>
    <?php
}

/**
 * Main iterator for uploads handling after form was submitted
 *
 * @return boolean
 */
function unc_uploads_handler() { 
    $count = count($_FILES["userImage"]["name"]);
    if ($count < 1) {
        $out = "No images found to upload";
        return $out;
    }
    $out = "Processing $count image(s)....<br>";

    for ($i=0; $i<$count; $i++){
        $date_str = unc_uploads_process($i);
        if (!$date_str) {
            continue;
        }
    }
    $out .= "All images processed!";
    echo $out;
}

/**
 * Checks uploaded files, returns the datestring from EXIF
 *
 * @global type $WPG_CONFIG
 * @param type $i
 * @return boolean
 */
function unc_uploads_process($i) {
    global $WPG_CONFIG;

    //array(1) {
    //    ["userImage"]=> array(5) {
    //        ["name"]=> array(1) { [0]=> string(23) "2013-11-02 21.00.38.jpg" }
    //        ["type"]=> array(1) { [0]=> string(10) "image/jpeg" }
    //        ["tmp_name"]=> array(1) { [0]=> string(14) "/tmp/phptgNK2k" }
    //        ["error"]=> array(1) { [0]=> int(0) }
    //        ["size"]=> array(1) { [0]=> int(213485) }
    //    }
    //}

    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    $F = $_FILES["userImage"];

    if ($F["error"][$i] > 0){
        echo "Unable to read the file, upload cancelled of file " . $F['name'][$i] . "<br />";
        return false;
    }
    $image_check = getimagesize($F['tmp_name'][$i]);
    if (!$image_check) {
        echo "Not image file, upload cancelled of file " . $F['name'][$i] . "<br />";
        return false;
    }
    // get imagetype
    $exif_imagetype = exif_imagetype($F['tmp_name'][$i]);
    if (!$exif_imagetype) {
        echo "Could not determine image type of file " . $F['name'][$i] . ", upload cancelled<br />";
        return false;
    }

    $mime_type = image_type_to_mime_type($exif_imagetype);
    if (!isset($mime_type, $WPG_CONFIG['valid_filetypes'])){
        echo "Invalid file type :" . $F["type"][$i];
        return false;
    }

    $extension = $WPG_CONFIG['valid_filetypes'][$mime_type];
    $file_no_ext = pathinfo($F['name'][$i], PATHINFO_FILENAME);
    $target_filename = $file_no_ext . "." . $extension;

    if (is_uploaded_file($F['tmp_name'][$i])) {
        $sourcePath = $F['tmp_name'][$i];
    } else {
        echo "Error finding uploaded file {$F['tmp_name'][$i]}!";
        return false;
    }

    $exif_data = exif_read_data($sourcePath);
    if (!$exif_data || !isset($exif_data['DateTimeOriginal'])) {
        echo "Error reading EXIF of file $sourcePath <br>\n";
        return false;
    }

    $date_str = $exif_data['DateTimeOriginal']; // format: 2011:06:04 08:56:22
    $date_check = date_create($date_str);
    if (!$date_check) {
        echo "ERROR: '$date_str' is invalid date in EXIF<br>";
        return false;
    }

    $date_obj = unc_date_folder_create($date_str);
    if (!$date_obj) {
        return false;
    }

    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];

    $target_subfolder = $dirPath . $WPG_CONFIG['photos'] . $date_obj->format("/Y/m/d");
    $thumb_subfolder = $dirPath . $WPG_CONFIG['thumbnails'] . $date_obj->format("/Y/m/d");
    $new_path =  $target_subfolder . DIRECTORY_SEPARATOR . $target_filename;

    if (file_exists($new_path)) {
        echo "$new_path Filename already exists!<br>";
        return false;
    }

    $rename_chk = move_uploaded_file($F['tmp_name'][$i], $new_path);
    if (!$rename_chk) {
        echo "Error (move_uploaded_file): Could not move {$F['name'][$i]} from {$F['tmp_name'][$i]} to $new_path<br>";
        return false;
    } else {
        echo "Moving file from {$F['tmp_name'][$i]} to $new_path<br>";
    }

    // chmod file to make sure it cannot be executed
    $check_chmod = chmod($new_path, 0644);
    if (!$check_chmod) {
        echo "Error (chmod): Could chmod 644 file $new_path<br>";
        return false;
    } else {
        echo "Chmod successful!<br>";
    }

    $check = unc_import_make_thumbnail($new_path, $thumb_subfolder);
    if ($check) {
        echo "Done!<br>";
    }
    return true;
}

function unc_import_make_thumbnail($image_filename, $target_subfolder) {
    global $WPG_CONFIG;

    $out = $image_filename;
    $thumbnail_size = $WPG_CONFIG['thumbnail_size'];
    $filename = basename($image_filename);

    $img_types = array(1 => 'GIF', 2 => 'JPEG', 3 => 'PNG');

    $arr_image_details = getimagesize($image_filename); // pass id to thumb name
    if (!$arr_image_details) {
        echo "ERROR: Failed to get image size of $image_filename<br>";
        return false;
    }
    $original_width = $arr_image_details[0];
    $original_height = $arr_image_details[1];
    $out .= " | Size: $original_width / $original_height | ";

    if ($original_width == 0 || $original_height == 0) {
        echo "ERROR: Image size $image_filename == 0<br>";
        return false;
    }
    // landscape image
    if ($original_width > $original_height) {
        $new_height = intval($original_height * ($thumbnail_size / $original_width));
        $new_width = $thumbnail_size;
    } else { // portrait image
        $new_width = intval($original_width * ($thumbnail_size / $original_height));
        $new_height = $thumbnail_size;
    }
    // get image extension
    $image_ext = $img_types[$arr_image_details[2]];
    $img_generator = "Image" . $WPG_CONFIG['thumbnail_ext'];
    $imgcreatefrom = "ImageCreateFrom" . $image_ext;

    $old_image = $imgcreatefrom($image_filename);
    $new_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresized($new_image, $old_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
    $img_generator($new_image, $target_subfolder . "/" . $filename);
    echo "Thumbnail created!<br>";
    return true;
}