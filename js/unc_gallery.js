
function available(date) {
    off = date.getTimezoneOffset();
    // adjust for timezone
    off_inv = off * -1;
    date.addMinutes(off_inv);
    iso = date.toISOString();
    ymd = iso.substring(0, 10);
    if (jQuery.inArray(ymd, availableDates) !== -1) {
        return [true, "dateavailable",ymd + " has images"];
    } else {
        return [false,"dateunavailable","No images on " + ymd];
    }
}

function openlink(dateText, inst) {
    window.location = "?date=" + dateText;
}

Date.prototype.addMinutes= function(m){
    this.setMinutes(this.getMinutes()+m);
    return this;
};