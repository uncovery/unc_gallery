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
            jQuery('#photobox').html(response.responseText);
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