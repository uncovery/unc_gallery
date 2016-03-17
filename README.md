# Uncovery Gallery
A Wordpress-plugin that displays photo galleries.

## General Info

This plugin specializes in displaying photos of a certain date or a certain time
of a single day. It takes away the need to handle each photo individually. The
admin can simply upload all images in a batch and they will be automatically
sorted by date.

The galleries are then displayed by inserting a short code into any wordpress element
such as posts or pages in the basic format `[unc_gallery date="2006-03-30"]`.

One key advantage of this system is that you can remove individual photos and
they will disappear without errors from the whole page (unless they have been
featured somewhere) and you can also re-upload photos and overwrite old ones in
case you changed them without going through delete & replace processes.

## Example gallery

![Example of a gallery](/images/screenshot-11.png)

## Features

* Time shift: If photos are generally also taken after midnight, a time shift
can be applied to all photos to make sure they do not appear as photos of the next day.
* Photo and Thumbnail resizing: The admin can choose the quality and size of thumbnails.

There are several additional options beyond this basic format:

* Time Limits: Define a specific time span during the day to be displayed instead of
the whole day
* Featured photo: Display one photo at the start and larger than the other photos
* One Photo only: Show only one photo of one day. Either a specific photo, random,
the or the latest
* Dynamic date: Instead of showing a specific date, show random or latest
* Descriptions: Whole days and individual photos can have specific description
set in the short code

## Shortcodes

### Basic minimum

`[unc_gallery]` This will display the full, latest day. Add additional codes
inside the brackets to modify the display.

### Additional items

#### `type="day|image|thumb"`

You can user either `day`, `image` or `thumb` as type.
`day` will display all images of that day, `image` a single image in full size
and `thumb` the image with a link to the large version. Default is `day` in case
the type is not used.

#### `date="latest|random|2016-10-30"`

You can use either `latest`, `random` or a date in the format `yyyy-mm-dd`. If
the `date` is not used, the latest date will be displayed.

#### `file="latest|random|2016-10-30"`

The file setting is only active if you use `image` or `thumb` as a `type`. You
can use `latest`, `random` or the filename such as `img_1233.jpg` here. If you
use `image` or `thumb` as a `type` but omit the `file` tag, it will display the
latest by default.

#### `description="some text"`

This will show a description for every image that does not have a `details` tag
set. No double quotes `"` or line breaks can be used in the details. All images
automatically have the description of the filename and the date. The above set
description is added to it if set. Example of a description set to "Don't panic":

![Sample screenshot of a description](/images/screenshot-6.png)

#### `featured="filename.jpg"`

This will, in case you did not use `image` or `thumb` as a `type`, show the image
used here as a featured image for the date, at the left edge and larger. It will
not appear again among the other, smaller images.
Sample screenshot of a featured image:

![Sample screenshot of a featured image](/images/screenshot-5.png)

#### `details="IMG_9401.jpg:Some text;IMG_9341.jpg:Some more text"`

This allows you to display a description for specific images. The format for one
image is `filename.jpg:Description;`. Make sure that the last description does
not have NOT have a colon `;` in the end. Also, no double quotes `"` or line breaks
can be used in the details.

#### `start_time="2016-10-30 20:32:00" end_time="2016-10-30 21:40:29"`

This allows you to show only images from a certain time span. It needs to be in
the format `yyyy-mm-dd hh:mm:ss`, 24h notation. Usage:

* `start_time="2016-10-30 20:32:00`: hide everything before 20:32
* `end_time="2016-10-30 21:40:29"`: hide everything after 21:40:29
* `start_time="2016-10-30 20:00:00" end_time="2016-10-30 21:00:00"`: Show only the hour 20:00-21:20
* `end_time="2016-10-30 20:00:00" start_time="2016-10-30 21:00:00"`: Hide the hour 20:00-21:20

#### `options="calendar|datelist"`

You can choose to show field on top of your gallery that allows the user to
display a different date's images. You can chose between a calendar and a date
list in a dropdown.

|Sample screenshot of the calendar|Sample screenshot of the datelist:|
|---------------------------------|----------------------------------|
|![Calendar](/images/screenshot-9.png)|![Datelist](/images/screenshot-10.png)|

## Configuration

### Thumbnail Height

This determines the pixel height of the thumbnails that are generated during the
upload of new images. If this value is changed, it will only affect newly uploaded
images. However, you can re-build all existing thumbnails in the Maintenance menu.

### Thumbnail Quality

This determines the JPEG quality of your thumbnails and their file size. The lowest
value is `1` with the lowest quality and the smallest files. The highest number is
`100` with the largest file size and the highest quality.

### Picture Long Edge

If this is not `0`, the images will be resized on upload so that their longest edge
(the vertical for portrait style images and the horizontal for landscape style images)
will be shrunken to that length. Images will not be enlarged.

### Picture Quality

If the images are resized because `Picture Long Edge` is larger than `0`, they will
be compressed with the quality set for this value.  The lowest value is `1` with
the lowest quality and the smallest files. The highest number is `100` with the
largest file size and the highest quality.

### Time Offset

If you take photos until after midnight, but do not want those photos to show as
a different date, set this to a negative value (e.g. `-6 hours`). With this
example, all images between 6:00 in the morning of Sunday until 5:59 Monday morning
will be considered to be taken on Sunday. This applies for photos on upload.
If you want to change this for individual days, change the setting before uploading
images and then change it back after the upload.

### Admin Date Selector

In the administration `Manage Images` page the system will give you an option to
select a date. You can choose to select a date either with a calendar or a
drop-down list of all available dates. Chose `Calendar` or `Datelist`
depending on your preference.

|Sample screenshot of the calendar|Sample screenshot of the datelist|
|---------------------------------|----------------------------------|
|![Calendar](/images/screenshot-7.png)|![Datelist](/images/screenshot-8.png)|

## Known Bugs, upcoming features

You can see known bugs and upcoming features on the project's
![Issue page on Github](https://github.com/uncovery/unc_gallery/issues)