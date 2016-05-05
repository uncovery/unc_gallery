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

function datepicker_select(dateText, inst) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'unc_gallery_datepicker', date: dateText},
        complete: function (response) {
            jQuery('#datepicker_target').html(response.responseText);
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

function datelist_change() {
    var datelist_value = jQuery('#datepicker').val();
    datepicker_select(datelist_value, inst);
}

function delete_image(file_name, rel_date) {
    var c = confirm("Are you sure you want to delete " + file_name + "?");
    if (c) {
        jQuery.ajax({
            url: ajaxurl,
            method: 'GET',
            dataType: 'text',
            data: {action: 'unc_gallery_image_delete', date: rel_date, file_name: file_name},
            complete: function (response) {
                jQuery('#datepicker_target').html(response.responseText);
            },
            error: function () {

            }
        });
    }
}

function unc_gallery_generic_ajax(action, target_div, confirmation_message) {
    jQuery('#' + target_div).html('');
    if (confirmation_message) {
        var c = confirm(confirmation_message);
    }
    if (c) {
        jQuery.ajax({
            url: ajaxurl,
            method: 'GET',
            dataType: 'text',
            data: {action: action},
            complete: function (response) {
                jQuery('#' + target_div).html(response.responseText);
            },
            error: function () {

            }
        });
    } else {
        jQuery('#' + target_div).html('Action cancelled!');
    }
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

function unc_gallery_import_images() {
    var path = jQuery('#import_path').val();
    var overwrite = jQuery('#import_overwrite').val();
    jQuery('#import_targetLayer').html('');
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'text',
        data: {action: 'unc_gallery_import_images', import_path: path, overwrite: overwrite},
        complete: function (response) {
            jQuery('#import_targetLayer').html(response.responseText);
        },
        error: function () {

        }
    });
}