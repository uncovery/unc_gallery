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
case you changed them without going through complicated admin pages.

## Features

* Time shift: If photos are generally also taken after midnight, a time shift
can be applied to all photos to make sure they do not appear as photos of the next day.
* Photo and Thumbnail resizing: The admin can choose the quality and size of thumbnails.

There are several additional options beyond this basic format:

* Time Limits: Define a specific time span during the day to be displayed instead of
the whole day
* Featured photo: Display one photo at the start and larger than the other photos
* One Photo only: Show only one photo of one day. Either a specific photo, random,
the first or latest
* Dynamic date: Instead of showing a specific date, show random, first or latest
* Descriptions: Whole days or individual photos can have specific description
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