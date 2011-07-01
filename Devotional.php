<?php

class Devotional {
    public $post_type = 'cf_devotional';
    
    /**
     * Default constructor
     */
    public function __construct() {
        register_post_type($this->post_type, array(
            'public' => true,
            'query_var' => 'devotional',
            'rewrite' => array(
                'slug' => 'devotionals',
                'with_front' => false
            ),
            'supports' => array(
                'title', 'editor'
            ),
            'labels' => array(
                'name' => 'Devotionals',
                'singular_name' => 'Devotional',
                'add_new' => 'Add New Devotional',
                'add_new_item' => 'Add New Devotional',
                'edit_item' => 'Edit Devotional',
                'new_item' => 'New Devotional',
                'view_items' => 'View Devotional',
                'search_items' => 'Search Devotionals',
                'not_found' => 'No Devotionals Found',
                'not_found_in_trash' => 'No Devotionals Found In Trash'
            ),
            'register_meta_box_cb' => array($this, '_devotional_meta_boxes')
        ));
    }
    
    /**
     * Registers the meta boxes for devotionals
     */
    function _devotional_meta_boxes() {
        add_meta_box('devo_info', 'Additional Information', array($this, '_devotional_information'), 
                $this->post_type, 'normal', 'high');
    }
    
    /**
     * Displays meta information for devotionals
     * @global type $wpdb
     * @param type $devotional 
     */
    function _devotional_information($devotional) {
        global $wpdb;
        $series_list = $wpdb->get_results('SELECT * FROM cf_series ORDER BY start_date DESC');
        
        $display_date = get_post_meta($devotional->ID, '_cf_display_date', true);
        $series = get_post_meta($devotional->ID, '_cf_series', true);
        $verses = get_post_meta($devotional->ID, '_cf_daily_verses', true);
        $footer = get_post_meta($devotional->ID, '_cf_footer', true);
        
        if($display_date == '') {
            $display_date = date('m/d/Y');
        }
        
        ?>
        <h4>Display Date</h4>
        <p>This is the date the devotional will appear on the site. All devotionals
            are numbered in order of display date.</p>
        
        <input type="text" name="_cf_display_date" class="date" value="<?php echo $display_date ?>" />
        
        <h4>Series</h4>
        <p>Please select the series this devotional should be located in.</p>
        
        <select name="_cf_series">
        <?php foreach($series_list as $item) { ?>
            <option value="<?php echo $item->series_id ?>" <?php selected($series, $item->series_id) ?>>
                    <?php echo $item->title ?>
            </option>
        <?php } ?>
        </select>
        
        <h4>Today's Reading Passage</h4>
        <p>Select the verses that go with this devotional. Separate the verses with commas.</p>
        <input type="text" name="_cf_daily_verses" style="width: 100%;" value="<?php echo $verses ?>" />
        
        <h4>Footer</h4>
        <p>This text will appear in its own section below the main content of the devotional.</p>
        <div class="customEditor">
            <textarea name="_cf_footer" style="width: 100%;" rows="20"><?php echo $footer ?></textarea>
        </div>
        
        <input type="hidden" name="post_type" value="<?php echo $this->post_type ?>" />
        <?php 
    }
}
?>
