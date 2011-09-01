<?php

/**
 * Register scripts and styles for the watch page
 */
function cf_series_scripts() {
    $svr_uri = $_SERVER['REQUEST_URI'];

    if ( strstr($svr_uri, 'watch')) {
        echo '<style>.ui-tabs-hide { display: none; }</style>';
        
        wp_enqueue_style('prettyphoto-css', JAVASCRIPTSPATH .'libs/prettyphoto/css/prettyPhoto.css', false, '1.0.0', 'screen' );
        wp_enqueue_script('prettyphoto-js', JAVASCRIPTSPATH .'libs/prettyphoto/js/jquery.prettyPhoto.js', array('jquery'));
        wp_enqueue_script('jquery-ui-tabs');
    }
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
    $data = array();

    wp_enqueue_script( 'jquery-ui-tabs' );
    
    //If there is no series given, default to the current series
    $data['series'] = empty($series) ?
        $wpdb->get_row("select * from cf_series where CURRENT_DATE between start_date and end_date limit 1") :
        $wpdb->get_row($wpdb->prepare("select * from cf_series where slug = %s", $series));

    //If no area given, default to the "Watch The Sermons" area
    if(empty($area)) {
        switch($type) {
            case "cf_series_session": $area = 'sermons'; break;
            case "cf_devotional": $area = 'small-groups'; break;
        }
    }

    //If no post slug is given, default to the first on of the series/area
    if(empty($slug)) {
        $session = get_post_by_type($type,  $data['series']->series_id, $area, 1);
        $data['post'] = $session[0];
    } else {
        //Otherwise, get the given slug
        $data['post'] = get_post_by_slug($slug);
    }

    if($data['post']) {
        switch($type) {
            case "cf_series_session": $data['meta'] = get_series_session_meta($data['post']->ID); break;
            case "cf_devotional": $data['meta'] = get_devotional_meta($data['post']->ID); break;
        }

    }

    $data['base_path'] = HOMEPATH . '/watch/'. $data['series']->slug;
    $data['devotionals_path'] = str_replace('/watch/', '/devotionals/', $data['base_path']);
    $data['area'] = $area;

    //Determine the content type to display. Each page type is dynamically created
    //based on the information in the series plugin.
    switch($type) {
        case "cf_series_session":
            if($area == 'sermons') {
                get_watch_main($data);
            } else if($area == "choose") {
                get_students_choice($data);
            } else {
                get_watch_session($data);
            }
            break;

        case "cf_devotional":
            get_devotional_template($data);
            break;
    }
}

/**
 * Displays the devotional
 * @param $data Contains data needed to render the template
 */
function get_devotional_template($data) {
    display_series_masthead($data);
?>
<article>
    <h1><?php echo $data['post']->post_title; ?></h1>
    <p>Today's Reading Passage: <?php echo $data['meta']['verse']; ?></p>

    <p><?php echo str_replace("\n", "</p><p>", $data['post']->post_content); ?></p>

    <?php if($data['area'] == "small-groups") { ?>
    <p><a target="_blank" href="http://eepurl.com/dBAcr">Receive Devotionals In Your Inbox</a></p>
    <?php } ?>
</article>
<?php 
}

/**
 * Allows a user to choose between high school and middle school series sessions
 * @param $data Contains data needed to render the template
 */
function get_students_choice($data) {
    display_series_masthead($data);
?>
<section class="series-description">
    <p>CF Students has programs for both high school &amp; middle school.</p>
</section>

<section class="options">
    <nav>
        <ul class="two-segments">
            <li>
                <a href="<?php echo $data['base_path'] ?>/cfstudents">
                    High School
                    <span>Desc Here</span>
                </a>
            </li>
            <li>

                <a href="<?php echo $data['base_path'] ?>/cfmiddle">
                    Middle School
                    <span>Desc Here</span>
                </a>
            </li>
        </ul>
    </nav>
</section>
<nav class="sub-menu watch">
    <ul class="three-segments">
        <li><a href="<?php echo $data['devotionals_path']; ?>/cf-students">CF Student Devotionals</a></li>
        <li><a href="<?php echo HOMEPATH; ?>/families/cf-students">CF Students Page</a></li>
        <li><a href="<?php echo HOMEPATH; ?>/contact/?ministry_area=CF Students">Contact CF Students</a></li>
    </ul>
</nav>
<?php
}

/**
 * Displays the template for all series sessions except "Watch The Series"
 * @param $data Contains data needed for the template
 */
function get_watch_session($data) {
    display_series_masthead($data);
?>
<div id="tabs">
    <aside class="sidebar">
        <ul>
            <li><a href="#information" title="Information">Information</a></li>

            <?php if(!empty($data['meta']['small_group'])) { ?>
                <li><a href="#small_group" title="Small Group Questions">Small Group Questions</a></li>
            <?php } ?>

            <?php if(!empty($data['meta']['family_discussion'])) { ?>
                <li><a href="#family_discussion" title="Family Discussions">Family Discussions</a></li>
            <?php } ?>
        </ul>
    </aside>
    <article>
        <div id="information"><p><?php echo str_replace("\n", "</p><p>",$data['post']->post_content); ?></p></div>

        <?php if(!empty($data['meta']['small_group'])) { ?>
            <div id="small_group"><p><?php echo str_replace("\n", "</p><p>", $data['meta']['small_group']); ?></p></div>
        <?php } ?>

        <?php if(!empty($data['meta']['family_discussion'])) { ?>
            <div id="family_discussion"><p><?php echo str_replace("\n", "</p><p>", $data['meta']['family_discussion']); ?></p></div>
        <?php } ?>
    </article>
</div>

<nav class="sub-menu watch">
    <ul class="three-segments">
        <?php switch($data['area']) {
            case 'cfstudents': ?>
                <li><a href="<?php echo $data['devotionals_path']; ?>/cf-students">CF Students Devotionals</a></li>
                <li><a href="<?php echo HOMEPATH; ?>/families/cf-students">CF Students Page</a></li>
                <li><a href="<?php echo HOMEPATH; ?>/contact/?ministry_area=CF Students">Contact CF Students</a></li>
            <?php break;
            case 'small-groups': ?>
                <li><a href="<?php echo $data['devotionals_path']; ?>/small-groups">Devotionals</a></li>
                <li><a href="<?php echo HOMEPATH; ?>/small-groups">Small Groups Page</a></li>
                <li><a href="<?php echo HOMEPATH; ?>/contact/?ministry_area=Small Groups">Contact Small Groups</a></li>
            <?php break;
            case 'cfkids': ?>
                <li><a href="<?php echo $data['devotionals_path']; ?>/cfkids">CF Kids Devotionals</a></li>
                <li><a href="<?php echo HOMEPATH; ?>/families/cf-kids">CF Kids Page</a></li>
                <li><a href="<?php echo HOMEPATH; ?>/contact/?ministry_area=CF Kids">Contact CF Kids</a></li>
            <?php break;
        } ?>
    </ul>
</nav>
<?php
}

/**
 * Displays the template for the main "Watch The Series" page
 * @param $data Contains data needed for the template
 */
function get_watch_main($data) {
    display_series_masthead($data);
?>

<section class="series-description">
    <p><?php echo $data['series']->description ?></p>
</section>

<h3 class="series-resources">Series Resources for Small Groups, Students, &amp; Kids</h3>

<section class="options">
    <nav>
        <ul class="three-segments">
            <li>
                <a href="<?php echo $data['base_path'] ?>/choose">
                    CF Students
                    <span>Desc Here</span>
                </a>
            </li>
            <li>

                <a href="<?php echo $data['base_path'] ?>/cfkids">
                    CF Kids
                    <span>Desc Here</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $data['base_path'] ?>/small-groups">
                    Small Groups
                    <span>Desc Here</span>

                </a>
            </li>
        </ul>
    </nav>
</section>
<nav class="sub-menu watch">
    <ul class="three-segments">
        <li><a target="_blank" href="http://itunes.apple.com/us/podcast/christ-fellowship-miami/id399037659">Subscribe To Our Podcast</a></li>
        <li><a href="<?php echo $data['devotionals_path']; ?>">Devotionals</a></li>
        <li><a href="#">Previous Series</a></li>
    </ul>
</nav>
<?php
}

/**
 * Displays the top portion of a series session
 * @param $data Contains data needed to display the masthead
 */
function display_series_masthead($data) {
    $meta = $data["meta"];
    $i = 1;
    switch($data['post']->post_type) {
        case "cf_series_session": $base_path = $data['base_path']; break;
        case "cf_devotional": $base_path = $data['devotionals_path']; break;
    }
?>
<?php if($data['post']->post_type == 'cf_series_session') { ?>
<h2 class="featured">
    <img width="940" height="290" src="<?php echo $data['series']->main_image_url; ?>" class="attachment-post-thumbnail wp-post-image" alt="" title="" />

    <?php if(!empty($meta['video'])) { ?>
        <a class="play" href="<?php echo $meta['video']; ?>" rel="prettyPhoto" title="<?php echo $data['post']->post_title; ?>">
            <span class="play-video"><span><?php echo $data['post']->post_title; ?></span></span>
        </a>
    <?php } ?>
</h2>
<?php } ?>
        
<div class="sessions">
<?php if(isset($meta['posts'])) : ?>
    <?php foreach($meta['posts'] as $session) : ?>
        <a <?php echo $meta['post_id'] == $session->id ? 'class="current"' : '' ?> href="<?php echo $base_path ?>/<?php echo $data['area'] ?>/<?php echo $session->post_name ?>"><?php echo $i++; ?></a>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<?php
}

/**
 * Returns the page that matches the given slug
 * @param $page_slug
 * @return null
 */
function get_post_by_slug($page_slug) {
    if(!empty($page_slug))
    {
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
    inner join wp_postmeta pm on pm.post_id = p.id and pm.meta_key = '_cf_series' and pm.meta_value = %s
where tt.taxonomy = 'series_area' and t.slug = %s
    and p.post_status = 'publish'
    and p.post_type = %s
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
        'series_id' => get_post_meta($id, '_cf_series', true),
        'post_id' => $id
    );

    //Get session areas
    $session_areas = array();
    foreach(wp_get_post_terms($id, 'series_area') as $term) {
        array_push($session_areas, $term->slug);
        $meta['area'] .= $term->name;
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