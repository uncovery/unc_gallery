=== Uncovery Gallery ===
Author: uncovery
Contributors: uncovery
Author URI: https://uncovery.net
Plugin Name: Uncovery Gallery
Plugin URI: https://github.com/uncovery/unc_gallery
Tags: gallery, photos, photo, gallery, photoblog, date, photographer, photography, EXIF, IPTC, XMP
Requires at least: 4.5.2
Tested up to: 4.5.2
Version: 4.1.3
Stable tag: 4.1.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple, self-generating, auto-tagging, auto-categorizing date-based gallery with bulk uploading,
perfect for photo blogging and large image collections.

== Description ==

This plugin specializes in displaying all photos of a certain date or between any two points in time.
It takes away the need to handle each photo individually. The admin can simply
upload all images in a batch and the system will recognise the date and time the pictures were taken.

The galleries are then displayed by inserting a short code into any wordpress element
such as posts or pages in the basic format [unc_gallery date="2006-03-30"].

This plugin is specially made for photo bloggers who often often post several images of a certain event
such as a hike, a trip, a dive or concert without having to touch every single image individually.

Keywords added in Adobe Lightroom etc. can be directly used on the website to tag photos and posts.
The locations stored in the photo metadata can be used to automatically categorize the posts.

Additional features include:

* featured photos: show one or more photos larger than others
* dynamic dates: show the latest, first or random dates
* date picker: let the user pick the date themselves
* seamless re-uploading: any photo can be re-uploaded anytime with a better/different version
* EXIF data: The photo description can show automatically generated photo data such as ISO, F-stop etc.
* automatic post assignments of tags based on EXIF/IPTC keywords.
* automatic post assignments of hierarchical categories based on EXIF/IPTC location info
* optional square or rectangular thumbnails

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/unc_gallery` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Uncovery Gallery screen to configure it and upload photos

= Uninstall =

Please note that while deactivating the plugin has no bad consequences, deleting it will also delete all
uploaded photos from the server if you don't set the proper admin option!

== Screenshots ==

1. A sample gallery without featured images
2. A sample gallery with featured images
3. The admin options screen

== Frequently Asked Questions ==

1. The system cannot handle 2 files of the same filename taken on the same date
1. The system can only handle files in JPG/JPEG format.
1. The system cannot handle 2 files that are taken exactly at the same time (date, minute & second).
1. The system works only on images which have valid EXIF or IPTC creation dates

== Changelog ==

= 4.2 =

1. Fix an issue with replacement of existing tags
1. Show sample shortcode after upload that includes all uploaded images
1. Allow uploads to ignore new files and only overwrite existing ones.
1. Allow links in single images to point to the posts' permalink (good for excerpt post)
1. Change to use MySQL instead of files for faster data access

= 4.1.3 =

1. Bug fixes

= 4.0 =

1. featured images that do not fit on the screen with the fixed height are now shrunken to maintain the aspect ratio
1. better image delete-link in image manager
1. made the location of the admin menu optional between settings or sidebar
1. refresh the admin image manager after uploads
1. better formatting for the admin settings help text
1. made deletion of images on uninstall optional
1. auto-select sample shortcode in admin screen
1. EXIF/IPTC/XMP data can be chosen individually
1. automatic post assignments of tags based on EXIF/IPTC keywords
1. automatic post assignments of hierarchical categories based on EXIF/IPTC location info
1. featured images can now be latest and random images of the given set
1. optional square or rectangular thumbnails

= 3.2.2 =

Plugin description update, version name fix

= 3.2.1 =

Failed upload, 2nd try (no changes to code)

= 3.2 =

1. You can now have several featured images
1. You can now have several galleries on one page without photoswipe breaking

= 3.1 =

1. no more date needed if start / end time given
1. rss feed display fixed
1. added EXIF photo data reader
1. featured image row height option
1. bug fixes

= 2.0 =

Add Photoswipe as a display option
Bug fixes

= 1.0 =

Initial Release