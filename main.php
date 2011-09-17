<?php
/**
 * @package CFSeries
 */
/*
Plugin Name: CF Series
Description: Allows you to manage series information in WordPress.
Version: 1.0
Author: CF Web team
Author URI: http://www.cfmiami.org
License: GPLv2 or later
*/

class CFSeries {
    /**
     * Default constructor
     */
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'register_activation_hook'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'include_resources'));
    }

    /**
     * Adds css and javascript files needed for this plugins to the admin pages
     */
    public function include_resources() {
        $plugin_url = plugin_dir_url(__FILE__);
        wp_enqueue_style('jqueryui', $plugin_url . 'css/smoothness/jquery-ui-1.8.13.custom.css');
        wp_enqueue_style('data-tables-style', $plugin_url . 'css/dataTables.css');
        wp_enqueue_script('data-tables', $plugin_url . 'js/jquery.dataTables.min.js', array('jquery'));
        wp_enqueue_script('jquery-core-ui', $plugin_url . 'js/jquery-ui-1.8.13.custom.min.js');
        wp_enqueue_script('forms', $plugin_url . 'js/jquery.form.js');
        wp_enqueue_script('series', $plugin_url . 'js/series.js', array('jquery', 'jquery-ui-core'));
    }
    
    /**
     * Adds an options page for the series resource settings page
     */
    public function admin_menu() {
        include 'Series.php';
        new Series();
    }
    
    /**
     * Performs operations required only during the activation of this plugin
     * @global type $wp_version 
     */
    public function register_activation_hook() {
        global $wp_version;
        global $wpdb;
        
        if(version_compare($wp_version, "3.1", "<")) {
            deactivate_plugins(basename(__FILE__));
            wp_die("This plugin requires WordPress version 2.9 or higher.");
        }
        
        //Add the series table
        $tablename = 'cf_series';
        if($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
            
            $sql = "CREATE TABLE `$tablename` (
                `series_id` INT( 11 ) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR( 100 ) NOT NULL,
                `slug` VARCHAR( 100 ) NOT NULL,
                `description` VARCHAR( 4000 ) NULL,
                `start_date` DATE,
                `end_date` DATE NULL,
                `main_image_url` VARCHAR( 200 ) NULL,
                `kids_image_url` VARCHAR( 200 ) NULL,
                `style_url` VARCHAR( 200 ) NULL,
                `book_description` VARCHAR( 3000 ) NULL,
                `book_image_url` VARCHAR( 200 ) NULL
                PRIMARY KEY (`series_id`)
                );";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        register_uninstall_hook(__FILE__, array($this, 'uninstall'));
    }
    
    /**
     * Completely uninstalls the plugin from the system, including removing
     * any stored options and/or database tables
     * @global type $wpdb 
     */
    function uninstall() {
        global $wpdb;
        
        //Remove the database table from the system.
        $wpdb->query("DROP TABLE IF EXISTS cf_series");
    }
    
    /**
     * Initializes the plugin by creating custom post types and taxonomies
     * needed by the series resource
     */
    public function init() {
        include 'Devotional.php';
        include 'SeriesSession.php';

        //Create the taxomonies used by the new post types
        register_taxonomy('series_area', array('cf_series_session', 'cf_devotional'), array(
            'hierarchical' => true,
            'query_var' => 'area',
            'show_tagcloud' => false,
            'rewrite' => array(
                'slug' => 'areas',
                'with_front' => true
            ),
            'labels' => array(
                'name' => 'Series Areas',
                'singular_name' => 'Series Area',
                'edit_item' => 'Edit Series Area',
                'update_item' => 'Update Series Area',
                'add_new_item' => 'Add New Series Area',
                'new_item_name' => 'New Series Area Name',
                'all_items' => 'All Series Areas',
                'search_items' => 'Search Series Areas',
                'parent_item' => 'Parent Series Area',
                'parent_item_colon' => 'Parent Series Area:'
            )
        ));

        //Initialize the devotional post type. This will create a new post type
        //for devotionals as well as create the meta data required.
        new SeriesSession();
        new Devotional();


        add_filter('manage_posts_columns', array($this, 'add_columns'), 10, 2);
        add_filter('parse_query', array($this, 'parse_query'));

        add_action('restrict_manage_posts', array($this, 'restrict_manage_posts'));
        add_action('manage_posts_custom_column', array($this, 'display_columns'), 10, 2);
        add_action('save_post', array($this, 'save'));
    }

    function parse_query($query) {
        global $pagenow;
        if ( is_admin() && $pagenow=='edit.php' && isset($_GET['series']) && $_GET['series'] != '') {
            $query->query_vars['meta_key'] = '_cf_series';
            $query->query_vars['meta_value'] = $_GET['series'];
        }
    }

    function restrict_manage_posts($query)
    {
        if($_GET['post_type'] == 'cf_devotional' || $_GET['post_type'] == 'cf_series_session') {
            global $wpdb;

            //Series dropdown
            $series =  $wpdb->get_results("select * from cf_series order by title");

            echo '<select name="series" id="series">';
            echo '<option value="">Show All Series</option>';

            foreach($series as $item) {
                echo sprintf('<option %s value="%s">%s</option>',
                              $_GET['series'] == $item->series_id ? 'selected="selected"' : '', $item->series_id, $item->title);
            }

            echo '</select>';

            //Area dropdown
            $areas = get_terms('series_area', 'orderby=name&hide_empty=0');

            echo '<select name="area" id="area">';
            echo '<option value="">Show All Areas</option>';

            foreach($areas as $item) {
                echo sprintf('<option %s value="%s">%s</option>',
                             $_GET['area'] == $item->slug ? 'selected="selected"' : '', $item->slug, $item->name);
            }

            echo '</select>';
        }
    }
    
    /**
     * Saves the given property (retrieved from $_POST) as meta data 
     * for the given post
     * @param type $post_id
     * @param type $property 
     */
    function save_meta_data($post_id, $property, $value) {
        if($value != '') {
            update_post_meta($post_id, $property, $value);
        }
    }
    
    /**
     * Saves custom data when saving a post
     * @param type $post_id 
     */
    function save($post_id) {
        
        //Custom meta data for devotionals post type
        switch($_POST['post_type']) 
        {
            case 'cf_devotional':
                $this->save_meta_data($post_id, '_cf_series', $_POST['_cf_series']);
                $this->save_meta_data($post_id, '_cf_daily_verses', $_POST['_cf_daily_verses']);
                $this->save_meta_data($post_id, '_cf_footer', wpautop($_POST['_cf_footer']));
                break;
            
            case 'cf_series_session':
                $this->save_meta_data($post_id, '_cf_series', $_POST['_cf_series']);
                $this->save_meta_data($post_id, '_cf_video_url', $_POST['_cf_video_url']);
                $this->save_meta_data($post_id, '_cf_group_questions', wpautop($_POST['_cf_group_questions']));
                $this->save_meta_data($post_id, '_cf_family_questions', wpautop($_POST['_cf_family_questions']));
                $this->save_meta_data($post_id, '_cf_audio_transcript', wpautop($_POST['_cf_audio_transcript']));
                break;
        }
    }
    
    /**
     * Adds additional columns to various post types
     * @param string $columns
     * @param type $post_type
     * @return string 
     */
    function add_columns($columns, $post_type) {
        switch($post_type) {
            case 'cf_devotional':
            case 'cf_series_session':
                unset($columns['date']);
                $columns['author'] = 'Author';
                $columns['cf_series_area'] = 'Area(s)';
                $columns['cf_series'] = 'Series';
                $columns['date'] = 'Status';
                break;
        }
        
        return $columns;
    }
    
    /**
     * Displays the data for the new custom columns
     * @global type $wpdb
     * @param type $column_name
     * @param type $post_id 
     */
    function display_columns($column_name, $post_id) {
        global $wpdb;
        
        switch($column_name) {
            case 'cf_series':
                $series_id = get_post_meta($post_id, '_cf_series', true);
                $sql = "SELECT title FROM cf_series where series_id = $series_id";

                echo $wpdb->get_var($wpdb->prepare($sql));
            break;
        
            case 'cf_series_area':
                echo the_terms($post_id, 'series_area');
                break;
        }
    }
}

$cfseries = new CFSeries();

include_once('functions.php');
?>
