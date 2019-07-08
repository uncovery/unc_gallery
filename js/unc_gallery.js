var unc_interval;

/**
 * function that selects the content of an input field when the user clicks on it
 * used in the admin screen
 * @param {type} id
 * @returns {undefined}
 */
function SelectAll(id) {
    document.getElementById(id).focus();
    document.getElementById(id).select();
}

/**
 * The file upload form AJAX script
 *
 * @param {type} max_files
 * @param {type} max_size
 * @returns {undefined}
 */
function unc_uploadajax(max_files, max_size) {
    var process_id;
    jQuery('#uploadForm').submit(function() { // once the form us submitted
        process_id = unc_gallery_timestamp();
        var options = {
            url: ajaxurl, // this is pre-filled by WP to the ajac-response url
            // this needs to match the add_action add_action('wp_ajax_unc_gallery_uploads', 'unc_uploads_iterate_files');
            data: {action: 'unc_gallery_uploads', process_id: process_id},
            success: success, // the function we run on success
            uploadProgress: uploadProgress, // the function tracking the upload progress
            beforeSubmit: beforeSubmit, // what happens before we start submitting
            complete: complete
        };
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
        // set a timer to retrieve updates of the progress
        jQuery('#targetLayer').html("");
        jQuery('#process-progress-bar').html("Import 0%");
        clearInterval(unc_interval);
        unc_interval = setInterval(function() {unc_gallery_progress_get(process_id, 'targetLayer', 'process-progress-bar', 'Import', unc_interval);}, 1000);
        jQuery(this).ajaxSubmit(options);  // do ajaxSubmit with the obtions above
        return false; // needs to be false so that the HTML is not actually submitted & reloaded
    });
    function success(response){
        // refresh the imagelist via AJAX after upload was successful
        unc_gallery_generic_ajax_progress('unc_gallery_images_refresh', 'datepicker_target', false);
    }
    function uploadProgress(event, position, total, percentComplete) {
        jQuery("#upload-progress-bar").width(percentComplete + '%');
        jQuery("#upload-progress-bar").html('<div id="progress-status">Upload ' + percentComplete +'%</div>');
    }
    function beforeSubmit(formData, jqForm, options) {
        jQuery("#progress-bar").width('0%');
        jQuery('#targetLayer').html(''); // empty the div from the last submit
        jQuery('#progress_targetLayer').html('');
        jQuery('#process-progress-bar').width('0%');
        return true;
    }
    function complete(jqXHR, status) {
        unc_gallery_progress_get(process_id, 'targetLayer', 'process-progress-bar', 'Import', unc_interval);
    }
}


function datepicker_available(date) {
    off = date.getTimezoneOffset();
    // adjust for timezone
    off_inv = off * -1;
    date.addMinutes(off_inv);
    iso = date.toISOString();
    ymd = iso.substring(0, 10);
    if (jQuery.inArray(ymd, availableDates) !== -1) {
        return [true, formatCurrentDate(ymd), ymd + " has images"];
    } else {
        return [false, "dateunavailable", "No images on " + ymd];
    }
}

/**
 * controls what happens when you select a date in teh datepicker
 * @param {type} dateText
 * @param {type} inst
 * @returns {undefined}
 */
function datepicker_select(dateText, inst) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'unc_gallery_datepicker', date: dateText},
        complete: function (response) {
            jQuery('#selector_target').html(response.responseText);
            jQuery('#photodate').html("Showing " + dateText);
        },
        error: function () {

        }
    });
}

function datepicker_ready(defaultdate) {
    jQuery('#datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        defaultDate: defaultdate,
        beforeShowDay: datepicker_available,
        onSelect: datepicker_select
    });
}

/**
 * action when the datelist dropdown is updated
 * @param {type} inst
 * @returns {undefined}
 */
function datelist_change(inst) {
    var datelist_value = jQuery('#datepicker').val();
    datepicker_select(datelist_value, inst);
}


function filter_select(filter_key, filter_value, filter_group, filter_name, options, page, inst) {
    calling_function = arguments.callee.caller.toString();
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'unc_filter_update', filter_key: filter_key, filter_value: filter_value, filter_group: filter_group, filter_name: filter_name, page: page, options: options},
        complete: function (response) {
            jQuery('#selector_target').html(response.responseText);
            jQuery('html, body').animate({
                scrollTop: jQuery("#selector_target").offset().top
            }, 2000);
        },
        error: function () {

        }
    });
}

/**
 * bridge between the dropdown filter and the list filter
 *
 * @param {type} filter_key
 * @param {type} filter_group
 * @param {type} filter_name
 * @param {type} options
 * @param {type} page
 * @param {type} inst
 * @returns {undefined}
 */
function filter_change(filter_key, filter_group, filter_name, options, page, inst) {
    var filter_value = jQuery('#filter').val();
    filter_select(filter_key, filter_value, filter_group, filter_name, options, page, inst);
}

function map_filter(position, inst) {
    // calling_function = arguments.callee.caller.toString();
    filter_select('att_value', position, 'exif', 'gps', 'map', inst);
}

function show_category(category_id, inst) {
    // calling_function = arguments.callee.caller.toString();
    filter_select('category_id', category_id, 'n/a', 'n/a', 'map', inst);
}


function chrono_select(page, inst) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'unc_chrono_update', page: page},
        complete: function (response) {
            jQuery('#unc_gallery').html(response.responseText);
            window.scrollTo(0,document.body.scrollHeight);
        },
        error: function () {

        }
    });
}

/**
 * action for the delete image link
 *
 * @param {type} file_name
 * @param {type} rel_date
 * @returns {undefined}
 */
function delete_image(file_name, rel_date) {
    var c = confirm("Are you sure you want to delete " + file_name + "?");
    if (c) {
        jQuery.ajax({
            url: ajaxurl,
            method: 'GET',
            dataType: 'text',
            data: {action: 'unc_gallery_image_delete', date: rel_date, file_name: file_name},
            complete: function (response) {
                jQuery('#selector_target').html(response.responseText);
            },
            error: function () {

            }
        });
    }
}

function ranking_submit(rank_up, rank_dn, rank_year) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'unc_ranking_new_image', rank_up: rank_up, rank_dn: rank_dn, rank_year: rank_year},
        complete: function (response) {
            jQuery('#ranking_imagebox').html(response.responseText);
        },
        error: function () {

        }
    });  
}

/**
 * generic AJAX to return results provided that not more data is needed
 *
 * This will return any ECHO from the function in progress
 * as well as get a status report from the process ID that was passed
 *
 * @param {type} action this is the name of the action (function on the PHP side)
 * @param {type} target_div this DIV will be replaced by the outcome
 * @param {type} confirmation_message check for this before submitting
 * @param {type} post is the form submitted via POST or GET?
 * @param {type} progress_div this div will be updated with a progress update
 * @param {type} progress_text this is the text of the progresss
 * @returns {undefined}
 */
function unc_gallery_generic_ajax_progress(action, target_div, confirmation_message, post, progress_div, progress_text) {
    jQuery('#' + target_div).html('');
    if (confirmation_message) {
        var c = confirm(confirmation_message);
    }
    if (post) {
        method = 'POST';
    } else {
        method = 'GET';
    };
    var generic_unc_interval;
    var process_id = unc_gallery_timestamp();
    if (c) {
        clearInterval(generic_unc_interval);
        generic_unc_interval = setInterval(function() {unc_gallery_progress_get(process_id, target_div, progress_div, progress_text, generic_unc_interval);}, 1000);
        jQuery.ajax({
            url: ajaxurl,
            method: method,
            dataType: 'text',
            data: {action: action, process_id: process_id},
            complete: function (response) {
                unc_gallery_progress_get(process_id, target_div, progress_div, progress_text, generic_unc_interval);
            },
            error: function () {

            }
        });
    } else {
        jQuery('#' + target_div).html('Action cancelled!');
    }
}

/**
 * generic AJAX to return results provided that not more data is needed
 *
 * This will return any ECHO from the function in progress
 * as well as get a status report from the process ID that was passed
 *
 * @param {type} action this is the name of the action (function on the PHP side)
 * @param {type} target_div this DIV will be replaced by the outcome
 * @param {type} confirmation_message check for this before submitting
 * @param {type} post is the form submitted via POST or GET?
 * @returns {undefined}
 */
function unc_gallery_generic_ajax(action, target_div, confirmation_message, post, fieldname, append) {
    jQuery('#' + target_div).html('');
    var c = false;
    if (confirmation_message) {
        c = confirm(confirmation_message);
    }
    if (post) {
        method = 'POST';
    } else {
        method = 'GET';
    };
    var fieldvalue = jQuery('#' + fieldname).val();
    if (c === confirmation_message) {
        jQuery.ajax({
            url: ajaxurl,
            method: method,
            dataType: 'text',
            data: {action: action, fieldname: fieldname, fieldvalue: fieldvalue},
            complete: function (response) {
                var newval = response.responseText;
                if (append) {
                    var target_contents = jQuery('#' + target_div).val();
                    var newval = target_contents + response.responseText;
                }
                jQuery('#' + target_div).html(newval);
            },
            error: function (request, status, error) {
                confirm(status + ": " + error);
            }
        });
    } else {
        jQuery('#' + target_div).html('Action cancelled!');
    }
}

function unc_gallery_filter_ajax(action, target_div, fieldname, append) {
    jQuery('#' + target_div).html('');

    var fieldvalue = jQuery('#' + fieldname).val();
    
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'text',
        data: {action: action, fieldname: fieldname, fieldvalue: fieldvalue},
        complete: function (response) {
            var newval = response.responseText;
            if (append) {
                var target_contents = jQuery('#' + target_div).val();
                var newval = target_contents + response.responseText;
            }
            jQuery('#' + target_div).html(newval);
        },
        error: function (request, status, error) {
            confirm(status + ": " + error);
        }
    });
}


// this parses the current iterated date and checks if it's the current displayed
function formatCurrentDate(dateYmd) {
    var query = window.location.search.substring(1);
    if (query.search(dateYmd) > 0) {
        return "dateavailable dateShown";
    } else {
        return "dateavailable";
    }
}

Date.prototype.addMinutes= function(m){
    this.setMinutes(this.getMinutes()+m);
    return this;
};

/**
 * importing images in the admin screen
 * calls PHP function unc_uploads_iterate_files()
 *
 * @returns {undefined}
 */
function unc_gallery_import_images() {
    var path = jQuery('#import_path').val();
    var overwrite_import_stats = [
        jQuery('#overwrite_import1').prop("checked"),
        jQuery('#overwrite_import2').prop("checked"),
        jQuery('#overwrite_import3').prop("checked")
    ];
    var overwrite_import_vals = [
        jQuery('#overwrite_import1').val(),
        jQuery('#overwrite_import2').val(),
        jQuery('#overwrite_import3').val()
    ];
    var process_id = unc_gallery_timestamp();
    clearInterval(unc_interval);
    unc_interval = setInterval(function() {unc_gallery_progress_get(process_id, 'import_targetLayer', 'import-process-progress-bar', 'Import', unc_interval);}, 1000);
    jQuery('#import_targetLayer').html('');
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'text',
        data: {action: 'unc_gallery_import_images', import_path: path, overwrite_import: [overwrite_import_stats, overwrite_import_vals], process_id: process_id},
        complete: function (jqXHR, textstatus) {
            unc_gallery_progress_get(process_id, 'import_targetLayer', 'import-process-progress-bar', 'Import', unc_interval);
        },
        error: function (request, status, error) {

        },
        susccss: function (data, textstatus, jqXHR ) {

        },
    });
}

/**
 * generic function to get the progress of a background process via AJAX
 * This should be called via a timer script
 * See: http://stackoverflow.com/questions/32890420/jquery-php-returning-processing-status-with-ajax-post
 *
 * @param {type} process_id
 * @param {type} targetlayer
 * @param {type} progressbar
 * @param {type} progressbar_text
 * @param {type} interval_id
 * @returns {undefined}
 */
function unc_gallery_progress_get(process_id, targetlayer, progressbar, progressbar_text, interval_id) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'text',
        data: {action: 'unc_tools_progress_get', process_id: process_id},
        complete: function (response) {
            var return_data = JSON.parse(response.responseText);

            if (return_data === false) {
                return;
            }
            var arrayLength = return_data.text.length;
            var outputText = '';
            for (var i = 0; i < arrayLength; i++) {
                if (return_data.text[i] === false) {
                    clearInterval(interval_id);
                } else {
                    outputText = outputText + "<br>" + return_data.text[i];
                }
            }
            jQuery('#' + targetlayer).html(outputText);
            jQuery("#" + progressbar).width(return_data.percent + '%');
            jQuery("#" + progressbar).html('<div id="progress-status">' + progressbar_text + ": " + return_data.percent +'%</div>');
        },
        error: function () {

        }
    });
}

/**
 * Get a UNIX timestamp
 *
 * @returns {String}
 */
function unc_gallery_timestamp() {
    // IE does not know the Date.now function, so we create one
    if (!Date.now) {
        Date.now = function() {
            return new Date().getTime();
        };
    }
    var timestamp = Date.now();
    // we need to add a string here because $_SESSION variables in PHP cannot
    // have a pure numeric index.
    return "ts_" + timestamp;
}

/*
 * source: http://stackoverflow.com/questions/25981512/markerwithlabel-mouseover-issue
 */
function MarkerWithLabelAndHover(marker){
    if (marker.get('hoverContent')){
        marker.set('defaultContent',marker.get('labelContent'));
        var fx=function(e,m){
            var r=e.relatedTarget;
            if(!r){
                return true;
            }
            while(r.parentNode){
                if(r.className === m.labelClass){
                    return false;
                }
                r=r.parentNode;
            }
            return true;
        };
        marker.set('defaultContent',marker.get('labelContent'));
        google.maps.event.addListener(marker,'mouseout',function(e){
            var that=this;
            if(fx(e,this)){
                this.set('labelContent', this.get('defaultContent'));
                this.set('labelClass', this.get('defaultClass'));
            }
        });
        google.maps.event.addListener(marker,'mouseover',function(e){
            var that=this;
            if(fx(e,this)){
                this.set('labelContent', this.get('hoverContent'));
                this.set('labelClass', this.get('hoverClass'));
            }
        });
    }
    return marker;
}