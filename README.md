# unc_gallery
Wordpress-plugin a simple for self-generating, date-based gallery

The goal of this plugin is to allow wordpress sites to list photos purely based on the date they were taken.

The plugin will accept files uploaded via the backend or copied from a folder on the system and automatically
move them into a date-based folder structure that is derived form the EXIF Dates in the photos.

The plugin will automatically create thumbnails for photos.

No photo editing or other tools will be provided. The only options will be:

- adjusting when a day starts.
For photos of events that last beyond midnight, photos after 00:00 should not be in the next day folder.
The admin can decide once for all photos when the day stops (and therefore the next one starts).

- removing created folders with all included photos

- removing individual photos

- setting the size of the thumbnails

- re-creating all thumbnails

There will be several display options:

- individual days with a tag such as [unc_gallery 2016-20-10]

- indivdual photos [unc_gallery 2016-20-10 filename.jpg]

- the latest day [unc_gallery latest_day]

- the latest photo [unc_gallery latest_photo]

- a random modifier to show only one random photo of a folder [unc_gallery latest_day random]
