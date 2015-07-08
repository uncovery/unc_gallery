
function available(date) {
    iso = date.toISOString();
    ymd = iso.substring(0, 10);
    if ($.inArray(ymd, availableDates) != -1) {
        return [true, "dateavailable",ymd + " has images"];
    } else {
        return [false,"dateunavailable","No images on " + ymd];
    }
}

function openlink(dateText, inst) {
    window.location = "'. $WPG_CONFIG['base_url'] . '?date=" + dateText;
}
