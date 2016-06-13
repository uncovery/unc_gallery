# Uncovery Gallery
A Wordpress-plugin that displays photo galleries.

## General Info

This plugin specializes in displaying photos of a certain date or a certain time
span. It takes away the need to handle each photo individually. The
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

* Photo and Thumbnail resizing: The admin can choose the quality and size of thumbnails.
* Time Limits: Define a specific time span during the day to be displayed instead of
the whole day, several whole days or any photo between any two points in time
* Featured photo: Display one or several photos at the start and larger than the other photos
* One Photo only: Show only one photo of one day. Either a specific photo, random,
the or the latest
* Dynamic date: Instead of showing a specific date, show random or latest
* Descriptions: Whole days and individual photos can have specific description
set in the short code
* Re-Uploads: If you want to update a picture with a new version, simply re-upload it and it will be updated
in the posts where the photos is shown automatically.
* EXIF data: The photo description can show automatically generated photo data such as ISO, F-stop etc.
* XMP data: Display keywords added by Adobe Lightroom or other software

## Shortcodes

### Basic minimum

`[unc_gallery]` This will display the full, latest day. Add additional codes
inside the brackets to modify the display.

### Picking a date/time

#### `date="latest|random|2016-10-30"`

This will show all photos of one specific date.
You can use either `latest`, `random` or a date in the format `yyyy-mm-dd`.

#### `date="2016-10-15,2016-10-16"`

This will show all photos of the first date, the second date and all inbetween.

#### `start_time="2016-10-30 20:32:00" end_time="2016-10-30 21:40:29"`

This will show all photos between the two points in time. This needs to be in
the format `yyyy-mm-dd hh:mm:ss`, 24h notation. You can enter different dates. Usage:

* `start_time="2016-10-30 20:32:00`: hide everything before 20:32, show the rest of that day
* `end_time="2016-10-30 21:40:29"`: hide everything after 21:40:29, show the rest of that day
* `start_time="2016-10-30 20:00:00" end_time="2016-10-30 21:00:00"`: Show only the hour 20:00-21:20
* `end_time="2016-10-30 20:00:00" start_time="2016-10-30 21:00:00"`: Hide the hour 20:00-21:20

If you have multiple gaps during a day, you need to use several [unc_gallery ...] tags on the same page/post

### Other features

#### `type="day|image|thumb"`

You can user either `day`, `image` or `thumb` as type.
`day` will display all images of that day, `image` a single image in full size
and `thumb` the image with a link to the large version. Default is `day` in case
the type is not used.

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
not appear again among the other, smaller images. You can also add several of them
by separating them with commas (do not add spaces!): `featured="filename1.jpg,filename2.jpg"`
Sample screenshot of a featured image:

![Sample screenshot of a featured image](/images/screenshot-5.png)

#### `details="IMG_9401.jpg:Some text;IMG_9341.jpg:Some more text"`

This allows you to display a description for specific images. The format for one
image is `filename.jpg:Description;`. Make sure that the last description does
not have NOT have a colon `;` in the end. Also, no double quotes `"` or line breaks
can be used in the details.

#### `limit_rows="3"`

This will hide all photos except for the first 3 rows. Options are 2,3,4 or 5.
This is helpful for use in post excerpts. Please note that the rest of the photos
will still be loaded by the browser, but is simply hidden. Also, the user can still
swipe through the photos if the display method is photoswipe or lightbox.

#### `options="calendar|datelist"`

You can choose to show field on top of your gallery that allows the user to
display a different date's images. You can chose between a calendar and a date
list in a dropdown. You cannot display more than one of those per page!

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

## Featured Size

How many image rows tall should a featured image be? You can select 2,3,4 or 5 rows.
It's recommended to chose a smaller number if the majority of your images are landscape
format. You can set this to dynamic to enable orientation-specific scaling.

## Orientation specific scaling (Featured size for portrait/landscape)

Same as above, but the system will automatically detect if the featured image is landscape
or portrait dimension (wider than high or the opposite) and scale the picture according to that.

### Admin Date Selector

In the administration `Manage Images` page the system will give you an option to
select a date. You can choose to select a date either with a calendar or a
drop-down list of all available dates. Chose `Calendar` or `Datelist`
depending on your preference.

|Sample screenshot of the calendar|Sample screenshot of the datelist|
|---------------------------------|----------------------------------|
|![Calendar](/images/screenshot-7.png)|![Datelist](/images/screenshot-8.png)|

### Image View Method

You can chose between Photoswipe and Lightbox to show images when clicked on it.
Lightbox is a simple display overlay with keyboard navigation in a centered window.
Photoswipe is a more complex system with a full-screen display and is enabled
for touch devices such as mobile phones and tablets.

| Lightbox | Photoswipe|
|---------------------------------|----------------------------------|
|![Lightbox](/images/screenshot-13.png)|![Photoswipe](/images/screenshot-12.png)|

## Limitations

* The system cannot handle 2 files of the same filename taken on the same date
* The system can only handle files in JPG/JPEG format.
* The system cannot handle 2 files that are taken exactly at the same time (date/minute/second).

## Known Bugs, upcoming features

You can see known bugs and upcoming features on the project's [issue page on Github](https://github.com/uncovery/unc_gallery/issues)

## Credits

This plugin uses the following libraries:

* [Photoswipe](http://photoswipe.com/)
* [Lightbox](http://lokeshdhakar.com/projects/lightbox2/)
* [Parsedown](http://parsedown.org)
* [LightSlider](http://sachinchoolur.github.io/lightslider/)