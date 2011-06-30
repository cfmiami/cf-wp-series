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
                `description` VARCHAR( 4000 ) NULL,
                `start_date` DATE,
                `end_date` DATE NULL,
                `main_image_url` VARCHAR( 200 ) NULL,
                `style_url` VARCHAR( 200 ) NULL,
                PRIMARY KEY (`series_id`)
                );";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        //Add the series question table
        $tablename = 'cf_series_session_questions';
        if($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
            
            $sql = "CREATE TABLE `$tablename` (
                `question_id` INT( 11 ) NOT NULL AUTO_INCREMENT,
                `post_id` INT( 11 ) NOT NULL,
                `number` INT( 11 ) NOT NULL,
                `question` VARCHAR( 400 ) NOT NULL,
                `comments` VARCHAR( 4000 ) NULL,
                PRIMARY KEY (`question_id`)
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
        $wpdb->query("DROP TABLE IF EXISTS cf_series_session_questions");
    }
    
    /**
     * Initializes the plugin by creating custom post types and taxonomies
     * needed by the series resource
     */
    public function init() {
        include 'Devotional.php';
        include 'SeriesSession.php';
        
        //Intialize the devotional post type. This will create a new post type
        //for devotionals as well as create the meta data required.
        new Devotional();
        new SeriesSession();
        
        add_filter('manage_posts_columns', array($this, 'add_columns'), 10, 2);
        add_action('manage_posts_custom_column', array($this, 'display_columns'), 10, 2);
        add_action('save_post', array($this, 'save'));
        
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
    }
    
    /**
     * Saves the given property (retrieved from $_POST) as meta data 
     * for the given post
     * @param type $post_id
     * @param type $property 
     */
    function save_meta_data($post_id, $property) {
        $value = $_POST[$property];
        if($value != '') {

            update_post_meta($post_id, $property, strip_tags($value));
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
                $this->save_meta_data($post_id, '_cf_display_date');
                $this->save_meta_data($post_id, '_cf_series');
                $this->save_meta_data($post_id, '_cf_daily_verses');
                $this->save_meta_data($post_id, '_cf_footer');
                break;
            
            case 'cf_series_session':
                $this->save_meta_data($post_id, '_cf_display_date');
                $this->save_meta_data($post_id, '_cf_series');
                $this->save_meta_data($post_id, '_cf_video_url');
                $this->save_meta_data($post_id, '_cf_blurb');
                $this->save_meta_data($post_id, '_cf_information');
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
                $columns['cf_date'] = 'Display Date';
                $columns['date'] = 'Status';
                break;
        }
        
        return $columns;
    }
    
    function display_columns($column_name, $post_id) {
        global $wpdb;
        
        switch($column_name) {
            case 'cf_series':
                $series_id = get_post_meta($post_id, '_cf_series', true);
                $sql = "SELECT title FROM cf_series where series_id = $series_id";

                echo $wpdb->get_var($wpdb->prepare($sql));
            break;
        
            case 'cf_date':
                echo get_post_meta($post_id, '_cf_display_date', true);
                break;
            
            case 'cf_series_area':
                echo the_terms($post_id, 'series_area');
                break;
        }
    }
}

$cfseries = new CFSeries();

function to_mysql_date($date) {
    if($date == "") return;

    $php_date = new DateTime($date);
    return $php_date->format('Y-m-d');
}
?>
