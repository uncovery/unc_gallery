<?php

function photos_feed() {
    global $wpdb;
    header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

    // configure appropriately - pontikis.net is used as an example
    $a_channel = array(
      "title" => get_bloginfo('name'),
      "link" => get_bloginfo('url'),
      "description" => get_bloginfo('description'),
      "language" => get_bloginfo('language'),
      "feed_url" => get_bloginfo('url') . "/photos_feed",
    //  "image_title" => get_bloginfo('name'),
    //  "image_link" => get_bloginfo('url'),
    //  "image_url" => "http://www.pontikis.net/feed/rss.png",
    );

    $rss = new rss_feed($a_channel);

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";

    $file_sql = "SELECT id, file_time FROM `$img_table_name` ORDER BY file_time DESC LIMIT 10;";
    $file_data = $wpdb->get_results($file_sql);

    $data_arr = array(
        'city' => array('att_group' => 'xmp', 'att_name' => 'city'),
        'location' => array('att_group' => 'xmp', 'att_name' => 'location'),
        'country' => array('att_group' => 'xmp', 'att_name' => 'country'),
        'keywords' => array('att_group' => 'xmp', 'att_name' => 'keywords'),
        'gps' => array('att_group' => 'exif', 'att_name' => 'gps'),
        'focal_length' => array('att_group' => 'exif', 'att_name' => 'focal_length'),
        'iso' => array('att_group' => 'exif', 'att_name' => 'iso'),
        'fstop' => array('att_group' => 'exif', 'att_name' => 'f'),
        'exposure_time' => array('att_group' => 'exif', 'att_name' => 'exposure_time'),
        'camera_model' => array('att_group' => 'exif', 'att_name' => 'camera_model'),
        'date' => array('att_group' => 'default', 'att_name' => 'date_str'),
        'file_url' => array('att_group' => 'default', 'att_name' => 'file_url'),
        'map_link_html' => array('att_group' => 'exif', 'att_name' => 'gps_link'),
        'width' => array('att_group' => 'exif', 'att_name' => 'file_width'),
        'height' => array('att_group' => 'exif', 'att_name' => 'file_height'),
    );

    foreach ($file_data as $F) {
        $file_id = $F->id;
        $file_time = $F->file_time;

        foreach ($data_arr as $value_name => $fields) {
            $error = '';
            $filter = '';
            foreach ($fields as $column => $value) {
                $filter .= " AND `$column` LIKE \"$value\"";
            }
            $value_data_sql = "SELECT att_value as $value_name FROM `$att_table_name` WHERE `file_id`=$file_id $filter;";
            $value_data = $wpdb->get_results($value_data_sql, 'ARRAY_A');

            if (!isset($value_data[0])) {
                $error .= "ERROR, file $file_id data not found for RSS feed";
                $$value_name = '';
            } else if (!isset($value_data[0][$value_name])) {
                $error .= "ERROR, $value_name data not found for mysql ($value_data_sql)";
                $$value_name = false;
            } else {
                $$value_name = $value_data[0][$value_name];
            }
        }

        if (!$keywords) {
            continue;
        }
        
        $title = "$keywords, $location ($city), taken $date";

        // $description = "<![CDATA[<img width=\"1280\" height=\"960\" src=\"$file_url\" alt=\"$keywords\"><p>$keywords. Photo taken in $location, $city ($country).</p>]]>";

        $description = "<![CDATA[
            <img width=\"$width\" height=\"$height\" src=\"$file_url\" alt=\"$keywords\">
            <p>$keywords</p>
            <p>This photo was taken $date at $location, $city, $country (Gmaps $map_link_html).</p>
            <p>Photo details: ISO $iso, f/$fstop, $exposure_time, $focal_length, $camera_model</p>
            $error]]>";
        $rss->feed_add_item($title, $file_url, $description, date("r"), get_bloginfo('name'), get_bloginfo('description'), false);
    }

    echo $rss->create_feed();
}


/**
 * rss_feed (simple rss 2.0 feed creator php class)
 *
 * https://www.pontikis.net/blog/simple-rss-class-create-rss-feed
 *
 * @author     Christos Pontikis http://pontikis.net
 * @copyright  Christos Pontikis
 * @license    MIT http://opensource.org/licenses/MIT
 * @version    0.1.0 (28 July 2013)
 *
 */
class rss_feed {
    public $content;
    /**
     * Constructor
     * @param array $a_channel channel properties
     * @param string $feed_name feed name
     */
    public function __construct($a_channel) {
        // initialize
        $this->channel = $a_channel;
    }

    public function create_feed() {
        global $content;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:atom="http://www.w3.org/2005/Atom"
        xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
        xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
        <channel>
            <title>' . $this->channel["title"] . '</title>
            <link>' . $this->channel["link"] . '</link>
            <description>' . $this->channel["description"] . '</description>
            <language>' . $this->channel["language"] . '</language>
            <atom:link href="' . $this->channel["feed_url"] . '" rel="self" type="application/rss+xml" />';
        $xml .= $content;
        $xml .= "</channel>\n</rss>";
        return $xml;
    }

    public function feed_add_item($title, $link, $description, $date, $source, $category = false, $text = false) {
        global $content;
        $content .= "
            <item>
                <title>$title</title>
                <link>$link</link>
                <guid>$link</guid>
                <description>$description</description>
                <pubDate>$date</pubDate>
                <source url=\"$link\">$source</source>\n";
        if ($content) {
            $content .= "<content:encoded>$text</content:encoded>\n";
        }
        if ($category) {
            $content .= "<category>$category</category>\n";
        }

        $content .= "</item>\n";
        // return $this->content;
    }
}

