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


function filter_select(filter_key, filter_value, filter_group, filter_name, options, inst) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'unc_filter_update', filter_key: filter_key, filter_value: filter_value, filter_group: filter_group, filter_name: filter_name, options: options},
        complete: function (response) {
            jQuery('#filter_selector').html(response.responseText);
            window.scrollTo(0,document.body.scrollHeight);
        },
        error: function () {

        }
    });
}

function filter_change(filter_key, filter_group, filter_name, options, inst) {
    var filter_value = jQuery('#filter').val();
    filter_select(filter_key, filter_value, filter_group, filter_name, options, inst);
}

function map_filter(position, inst) {
    filter_select('att_value', position, 'exif', 'gps', 'map', inst);
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

/**
 * do-all can-all generic ajax
 *
 * @param {type} action
 * @param {type} target_div
 * @param {type} confirmation_message
 * @returns {undefined}
 */
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

/**
 * importing images in the admin screen
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
    jQuery('#import_targetLayer').html('');
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'text',
        data: {action: 'unc_gallery_import_images', import_path: path, overwrite_import: [overwrite_import_stats, overwrite_import_vals]},
        complete: function (response) {
            jQuery('#import_targetLayer').html(response.responseText);
        },
        error: function () {

        }
    });
}

/*
 * source: http://stackoverflow.com/questions/25981512/markerwithlabel-mouseover-issue
 */
function MarkerWithLabelAndHover(marker){
    if (marker.get('hoverContent')){
        marker.set('defaultContent',marker.get('labelContent'))
        var fx=function(e,m){
            var r=e.relatedTarget;
            if(!r){
                return true;
            }
            while(r.parentNode){
                if(r.className==m.labelClass){
                    return false;
                }
                r=r.parentNode;
            }
            return true;
        }
        marker.set('defaultContent',marker.get('labelContent'))
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