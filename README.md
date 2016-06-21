# Uncovery Gallery
A Wordpress-plugin that displays photo galleries.

## General Info

This plugin specializes in displaying photos of a certain date or a certain time
span. It takes away the need to handle each photo individually. The
admin can simply upload all images in a batch and they will be automatically
sorted by date.

The galleries are then displayed by inserting a short code into any wordpress element
such as posts or pages in the basic format `[unc_gallery date="2006-03-30"]`.

The main target of this plugin is to reduce duplication of work. You can now manage
all the information about your photos in lightroom or similar software and Uncovery Gallery
will take all this information and display it on your images. You can auto-tag your posts
with keywords, auto-categorize with the location info, show all EXIF/IPCT/XMP information
on each image etc.

One key advantage of this system is that you can remove individual photos and
they will disappear without errors from the whole page and you can also re-upload
photos and overwrite old ones in case you changed them without going through
delete & replace processes.

## Example gallery

![Example of a gallery](/images/screenshot-11.png)

## Features

* Photo and Thumbnail resizing: The admin can choose the quality and size of thumbnails.
* Square thumbnails: Optionally render square thumbnails for a cleaner look
* Time Limits: Define a specific time span during the day to be displayed instead of
the whole day, several whole days or any photo between any two points in time
* Featured photo: Display one or several photos at the start and larger than the other photos
* One Photo only: Show only one photo of one day. Either a specific photo, random,
the or the latest
* Dynamic date: Instead of showing a specific date, show random or latest
* EXIF / XMP / IPCT data: Chose which information will be shown on your images
* Auto-tagging of posts with XMP/IPCT keywords
* Auto-categorizing posts with XMP/IPCT location info (hierarchically, eg. Country -> City -> Location)
* Descriptions: Whole days and individual photos can have additional description
set in the short code
* Re-Uploads: If you want to update a picture with a new version, simply re-upload it and it will be updated
in the posts where the photos is shown automatically.
* Hassle-free image deletion: If you delete one ore more images from your library,
you do not need to update the posts or pages where it was displayed.

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

#### `featured="filename.jpg|latest|random"`

This will, in case you did not use `image` or `thumb` as a `type`, show the image
used here as a featured image for the date, at the left edge and larger. It will
not appear again among the other, smaller images. You can also add several of them
by separating them with commas (do not add spaces!): `featured="filename1.jpg,filename2.jpg"`.
You can also add `latest` or `random` to the list and it will feature the latest or any random image.
Please be aware that if you have both `latest` and `random` the same image might be shown twice!
Sample screenshot of a featured image:

![Sample screenshot of a featured image](/images/screenshot-5.png)

#### `details="IMG_9401.jpg:Some text;IMG_9341.jpg:Some more text"`

This allows you to display a description for specific images. The format for one
image is `filename.jpg:Description;`. Make sure that the last description does
not have NOT have a colon `;` in the end. Also, no double quotes `"` or line breaks
can be used in the details. It is highly recommended to use your catalogue software
(lightroom et al.) instead, add the description there and then chose the fields in the
settings to display it automatically in the gallery.

### `limit_images="12"`

This will limit the amount of images that will be displayed. Users will still be
able to swipe beyond the last displayed image with photoswipe. This feature is specially
handy when using square thumbnail icons (see settings). The last image will have an
overlay indicating the amount of images not shown (similar to facebook galleries).

![Sample screenshot of a limited images with square thumbnails](/images/screenshot-16.png)

#### `limit_rows="3"`

This will hide all photos except for the first 3 rows. Options are 2,3,4 or 5.
This is helpful for use in post excerpts. Please note that the rest of the photos
will still be loaded by the browser, but is simply hidden. Also, the user can still
swipe through the photos if the display method is photoswipe.

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

### Thumbnail Format

Instead of creating thumbnails in the same shape as the original image, you can
crop them so that they are always square, similar to how galleries are presented
on facebook or images show in your profile on Instagram. This allows for cleaner
layouts. If you change this function, make sure to re-build all existing thumbnails
in the "Maintenance" menu.

### Picture Long Edge

If this is not `0`, the images will be resized on upload so that their longest edge
(the vertical for portrait style images and the horizontal for landscape style images)
will be shrunken to that length. Images will not be enlarged.

### Picture Quality

If the images are resized because `Picture Long Edge` is larger than `0`, they will
be compressed with the quality set for this value.  The lowest value is `1` with
the lowest quality and the smallest files. The highest number is `100` with the
largest file size and the highest quality.

### No image found alert

What kind of message should appear on the web site in case no image can be found for the given date?
This is useful if you have a calendar and always insert a default gallery for that day
just in case you add images later. Then, instead of seeing a big red alert, it will just show
a friendly "No images available" or even nothing.

### Featured Size

How many image rows tall should a featured image be? You can select 2,3,4 or 5 rows.
It's recommended to chose a smaller number if the majority of your images are landscape
format. You can set this to dynamic to enable orientation-specific scaling.

### Orientation specific scaling (Featured size for portrait/landscape)

Same as above, but the system will automatically detect if the featured image is landscape
or portrait dimension (wider than high or the opposite) and scale the picture according to that.
This will be ignored if you have several images of different orientations featured and the
setting below is not set to "Dynamic".

### Featured image scaling for several featured images

In case you feature several images of the same orientation in one gallery,
how many image rows high should they appear? Chose "Dynamic" to use the setting above instead.

### Description EXIF / XMP / IPCS Data choices

Chose here what information is shown for images when clicked on them. Please
be aware that many of them show the same content, depending on your photo catalogue software.

### Auto-Tag posts with Keywords

You can have the Gallery automatically tag posts with the keywords found in
your image. New tags will be created as needed.
Chose which keywords to use and if they should remove existing keywords
in case they are different. If an image does not have any keywords, existing
keywords will not be removed, no matter what setting.

### Auto-Categorize posts with Location info

You can have the Gallery automatically categorize posts hierarchically with the location
found in the image XMP/IPCT information. There are several patterns available.
New categories will be created as needed. If there is no location info in an image
nothing will happen, no matter what setting. If you need different patterns, let us know
we can add them very quickly.

### Admin menu location

You can chose to display the Plugin menu in the side bar of the dashboard or
as a submenu of the "Settings" menu.

|Side bar Menu|Settings submenu|
|---------------------------------|----------------------------------|
|![Calendar](/images/screenshot-14.jpg)|![Datelist](/images/screenshot-15.jpg)|

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
|![Lightbox](/images/screenshot-13.jpg)|![Photoswipe](/images/screenshot-12.jpg)|

## Limitations

* The system cannot handle 2 files of the same filename taken on the same date
* The system can only handle files in JPG/JPEG format.
* The system cannot handle 2 files that are taken exactly at the same time (date/minute/second).
* The system works only on images which have valid EXIF or IPCT creation dates

## Known Bugs, upcoming features

You can see known bugs and upcoming features on the project's [issue page on Github](https://github.com/uncovery/unc_gallery/issues)

## Credits

This plugin uses the following libraries:

* [Photoswipe](http://photoswipe.com/)
* [Lightbox](http://lokeshdhakar.com/projects/lightbox2/)
* [Parsedown](http://parsedown.org)
* [LightSlider](http://sachinchoolur.github.io/lightslider/)