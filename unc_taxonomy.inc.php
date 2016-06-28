<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Compare existing post tags with the image and fix missing ones.
 *
 * @global type $UNC_GALLERY
 * @param type $F
 * @return boolean
 */
function unc_tags_apply($F) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "file_data");}
    
    // do we havea post? If so get the id, otherwise bail
    $post_id = get_the_ID();
    if (!$post_id) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "No post ID available");}
        return;
    }

    // we assume first we append tags
    $append_tags = true;
    // get the system setting
    $setting = $UNC_GALLERY['post_keywords'];
    // it's a string a_b_c, split it
    $set_split = explode("_", $setting);
    // 
    $selected_tags = $set_split[0];
    if (isset($set_split[1])) {
        $append_tags = false;
    }

    // let's create an array that will hold a list of unique tags of this post
    $photo_tags = array();
    // lets iterate all files
    foreach ($F as $FD) {
        // if the file has no keywords, continue to next one
        if (!isset($FD[$selected_tags]['keywords'])) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "No $selected_tags Keywords set");}
            continue;
        }
        // otherwise, the field is set, check if we have keywords in it
        $image_tags = $FD[$selected_tags]['keywords'];
        if (!is_array($image_tags)) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "Keyword set is not an array (i.e. no keywords)");}
            continue;
        }
        // now, we hae tags, go through them
        foreach ($image_tags as $tag) {
            $photo_tags[] = ucwords($tag);
        }
    }
    if (count($photo_tags) == 0) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("unc_display_tags_compare", "collected zero keywords from array");}
        return false;
    }
    $photo_tags_unique = array_unique($photo_tags);

    asort($photo_tags_unique);
    
    // in case there are no tags in the photos, we won't do anything
    if (count($photo_tags_unique) == 0) {
        return;
    }

    // get all post tags
    $post_tags = array();
    $posttags_obj = get_the_tags();
    if ($posttags_obj) {
        foreach($posttags_obj as $tag) {
            $post_tags[] = ucwords($tag->name);
        }
    }
    asort($post_tags);
    
    $post_tags_unique = array_unique($post_tags);
    $comp_result = unc_array_analyse($photo_tags_unique, $post_tags_unique);
    $complete_set = $comp_result['complete_set'];
    asort($complete_set);
    $missing_tags = $comp_result['only_in_1'];
        
    $retval = false;
    // if we append tags, we only look for the missing ones.
    if ($append_tags) {
        if (count($missing_tags) > 0) {
            $retval = true;
            wp_set_post_tags($post_id, $missing_tags, $append_tags);
        }
    } else if ($complete_set != $post_tags_unique) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, array('post' => $post_tags_unique, 'set' => $complete_set));}
        // if we replace tags, we overwrite only if the tags are not identical
        wp_set_post_tags($post_id, $photo_tags_unique, $append_tags);
        $retval = true;
    }
    return $retval;
}


/**
 * Compare existing post categories with the image and fixing the missing
 *
 * @global type $UNC_GALLERY
 * @param type $file_data
 * @return type
 */
function unc_categories_apply($file_data) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "File_data");}

    $post_id = get_the_ID();
    if (!$post_id) {
        return;
    }

    // need to include taxonomy to use category stuff
    $admin_directory = ABSPATH . '/wp-admin/';
    require_once($admin_directory . 'includes/taxonomy.php');

    $curr_cats = array();
    // re-format the currnet categories so we can compare them

    foreach (get_the_category($post_id) as $c_cat) {
        $cat_name_id = strtolower($c_cat->name);
        $curr_cats[$cat_name_id]['name'] = $c_cat->name;
        $curr_cats[$cat_name_id]['id'] = $c_cat->cat_ID;
    }
    //XMPP_ERROR_trace("got existing post categories:", $curr_cats);

    // get all cats in nthe system
    $wp_all_cats = get_categories();
    $all_cat_index = array();
    // reformat them so we can search easier
    foreach ($wp_all_cats as $C) {
        $lower_name = strtolower($C->name);
        $all_cat_index[$lower_name]['id'] = $C->cat_ID;
        $all_cat_index[$lower_name]['parent'] = $C->parent;
    }
    //XMPP_ERROR_trace("got all categories:", $all_cat_index);

    // find out what the current setting is
    $setting = $UNC_GALLERY['post_categories'];
    // split into array:
    $setting_array = explode("_", $setting);
    $data_type = array_shift($setting_array); // remove the XPM/EXIF from the front of the array
    // iterate all files and get all the different levels of categories
    $cat_sets = array();

    $has_cats = false;
    // we go through all files in the post and get all categories for this post uniquely
    foreach ($file_data as $F) {
        // we go through the wanted fields from the setting
        $file_cats = array();
        foreach ($setting_array as $exif_code) {
            $cat_sets[$exif_code] = false; // with this we also catch empty levels
            if (!isset($F[$data_type][$exif_code])) {
                $value = '%%none%%';
            } else {
                $has_cats = true;
                $value = $F[$data_type][$exif_code];
            }
            $file_cats[] = $value;
        }
        // we try to create a code to make sure we do not make duplicates
        $cats_id = implode("-", $file_cats);
        $cat_sets[$cats_id] = $file_cats;
    }
    if (!$has_cats) {
        return;
    }

    //XMPP_ERROR_trace("iterated all files, got category sets:", $cat_sets);

    $post_categories = array();

    // now we go through the collected categories and apply them to the poat
    foreach ($cat_sets as $cat_set) {
        //XMPP_ERROR_trace("Checking cat set:", $cat_set);
        // iterate each level
        $depth = 1; // depth of the hierarchical cats
        $next_parent = 0;
        if (!$cat_set) {
            continue;
        }
        foreach ($cat_set as $cat) {
            //XMPP_ERROR_trace("Checking cat:", $cat);
            // check if the post has a category of that name already
            $cat_id = strtolower($cat);
            if ($cat == '%%none%%') {
                //XMPP_ERROR_trace("cat is emtpy, continue");
                continue;
            } else if (isset($curr_cats[$cat_id])) {
                // get the existing cat ID and add it to the post
                $post_categories[] = $curr_cats[$cat_id]['id'];
                //XMPP_ERROR_trace("cat is set already get ID for final assignment", $curr_cats[$cat_id]['id']);
                $next_parent = $curr_cats[$cat_id]['id'];
                continue;
            }
            // check if the current cat already exists in wordpress
            if (!isset($all_cat_index[$cat_id])) {
                $this_id = wp_create_category($cat, $next_parent);
                //XMPP_ERROR_trace("Creating category $cat, ID: $this_id, Parent $next_parent");
            } else {
                //XMPP_ERROR_trace("Cat exists already, get parent for next level", $all_cat_index[$cat_id]['parent']);
                $this_id = $all_cat_index[$cat_id]['id'];

            }
            $post_categories[] = $this_id; // collect the categories to add them to the post
            $next_parent = $this_id;
            $depth++;
        }
    }

    // TODO only update if we need to!
    // we need to check if the categories we added have the right hierarchy, so let's get the whole list first
    //XMPP_ERROR_trace("assign final list of cats", $post_categories);
    wp_set_post_categories($post_id, $post_categories, false); // true means cats will be added, not replaced
    //XMPP_ERROR_trigger("test");
}
