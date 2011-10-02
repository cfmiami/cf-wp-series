<?php

/**
 * Register scripts and styles for the watch page
 */
function cf_series_scripts() {
    echo '<style>.ui-tabs-hide { display: none; }</style>';

}
add_action('wp_print_styles', 'cf_series_scripts');

/**
 * Gets the page template for the given series info
 * @param $type Determines whether it is a devotional or series session
 * @param $series The slug for the series
 * @param $area The area to display (i.e. cfkids, small-groups, etc.)
 * @param $slug The slug for the specific post in the session
 */
function get_series_content($type, $series, $area, $slug) {
    global $wpdb;

    //If there is no series given, default to the current series
    $results = empty($series) ?
        $wpdb->get_row("select * from cf_series where CURRENT_DATE between start_date and end_date or end_date <= CURRENT_DATE order by end_date DESC limit 1") :
        $wpdb->get_row($wpdb->prepare("select * from cf_series where slug = '%s' limit 1", $series));

    //If no post slug is given, default to the first on of the series/area
    if(empty($slug)) {
        $session = get_post_by_type($type,  $results->series_id, $area);

        //Select the current date's devotional. If none, just pick the last one
        foreach($session as $item) {
            if(strtotime(substr($item->post_date, 0, 10)) <= strtotime(date('Y-m-d'))) {
                $post = $item;
            }
        }

        //If none, just select the first one
        if(!$post) {
            $post = $session[0];
        }
    } else {
        //Otherwise, get the given slug
        $post = get_post_by_slug($slug);
    }

    return $post;
}

/**
 * Returns the page that matches the given slug
 * @param $page_slug
 * @return null
 */
function get_post_by_slug($page_slug) {
    if(!empty($page_slug))
    {
        $page_slug = str_replace("'", "''", $page_slug);
        $page_slug = str_replace("’", "''", $page_slug);

        global $wpdb;
        $page = $wpdb->get_row($wpdb->prepare("select * from wp_posts where post_name = %s", $page_slug));
        if ($page) {
            return $page;
        }
    }

    return null;
}

/**
 * Returns the devotionals for the given series and area
 * @param $series the id of the series the devotionals are in
 * @param $area the area slug the devotionals are in
 * @return WP_Query an object containing the results of the query
 */
function get_post_by_type($type, $series, $area, $count = -1) {
    global $wpdb;
    
    $sql = "
select p.* from wp_term_relationships tr
    inner join wp_term_taxonomy tt on tt.term_taxonomy_id = tr.term_taxonomy_id
    inner join wp_terms t on t.term_id = tt.term_id
    inner join wp_posts p on p.id = tr.object_id
    inner join wp_postmeta pm on pm.post_id = p.id and pm.meta_key = '_cf_series' and pm.meta_value = '%s'
where tt.taxonomy = 'series_area' and t.slug = '%s'
    and p.post_status <> 'trash'
    and p.post_type = '%s'
order by p.post_date
";


    if($count > -1) {
        $sql .= 'limit ' . $count;
    }

    return $wpdb->get_results($wpdb->prepare($sql, $series, $area, $type));
}

/**
 * Returns the meta data for the given series session post id
 * @param $id The id of the series session
 * @return array|string
 */
function get_series_session_meta($id) {
    $meta =  array(
        'video' => get_post_meta($id, '_cf_video_url', true),
        'small_group' => get_post_meta($id, '_cf_group_questions', true),
        'family_discussion' => get_post_meta($id, '_cf_family_questions', true),
        'audio_transcript' => get_post_meta($id, '_cf_audio_transcript', true),
        'series_id' => get_post_meta($id, '_cf_series', true),
        'post_id' => $id
    );

    //Get session areas
    $session_areas = array();
    foreach(wp_get_post_terms($id, 'series_area') as $term) {
        array_push($session_areas, $term->slug);
        $meta['area'] .= $term->name;
        $meta['areacode'] .= $term->slug; 
    }

    //Get series sessions
    $meta['posts'] = get_post_by_type('cf_series_session', $meta['series_id'], $session_areas[0]);

    //Get series information
    global $wpdb;
    $meta['series'] = $wpdb->get_row(
                $wpdb->prepare("select * from cf_series where series_id = %s", $meta['series_id']));

    return $meta;
}

/**
 * Returns the meta data for the given devotional post id
 * @param $id The id of the devotional
 * @return array|string
 */
function get_devotional_meta($id) {
    $meta = array(
        'verse' => get_post_meta($id, '_cf_daily_verses', true),
        'footer' => get_post_meta($id, '_cf_footer', true),
        'series_id' => get_post_meta($id, '_cf_series', true),
        'post_id' => $id
    );

    //Get session areas
    $session_areas = array();
    foreach(wp_get_post_terms($id, 'series_area') as $term) {
        array_push($session_areas, $term->slug);
        $meta['area'] .= $term->name;
        $meta['areacode'] .= $term->slug;
    }

    //Get devotionals
    $meta['posts'] = get_post_by_type('cf_devotional', $meta['series_id'], $session_areas[0]);

    //Get series information
    global $wpdb;
    $meta['series'] = $wpdb->get_row(
                $wpdb->prepare("select * from cf_series where series_id = %s", $meta['series_id']));

    return $meta;
}


/**
 * Converts the given php string to a format suitable for MySQL
 * @param type $date
 * @return type
 */
function to_mysql_date($date) {
    if($date == "") return;

    $php_date = new DateTime($date);
    return $php_date->format('Y-m-d');
}

/**
 * Adds extra query parameters to the watch series page
 */
function add_query_vars($vars) {
    $new_vars = array('series', 'area', 'post');
    $vars = $new_vars + $vars;
    return $vars;
}
add_filter('query_vars', 'add_query_vars');

/**
 * Populates the extra query parameters to the watch series page
 */
function add_rewrite_rules($aRules) {
    $aNewRules = array(
        'watch/([^/]+)/?$' => 'index.php?pagename=watch&series=$matches[1]',
        'watch/([^/]+)/([^/]+)/?$' => 'index.php?pagename=watch&series=$matches[1]&area=$matches[2]',
        'watch/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=watch&series=$matches[1]&area=$matches[2]&post=$matches[3]',
        'devotionals/([^/]+)/?$' => 'index.php?pagename=devotionals&series=$matches[1]',
        'devotionals/([^/]+)/([^/]+)/?$' => 'index.php?pagename=devotionals&series=$matches[1]&area=$matches[2]',
        'devotionals/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=devotionals&series=$matches[1]&area=$matches[2]&post=$matches[3]');
    $aRules = $aNewRules + $aRules;
    return $aRules;
}
add_filter('rewrite_rules_array', 'add_rewrite_rules');
