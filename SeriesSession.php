<?php

class SeriesSession {
    public $post_type = 'cf_series_session';
  
    /**
     * Default constructor
     */
    public function __construct() {
        register_post_type($this->post_type, array(
            'public' => true,
            'query_var' => 'session',
            'rewrite' => array(
                'slug' => 'sessions',
                'with_front' => false
            ),
            'supports' => array(
                'title', 'editor'
            ),
            'labels' => array(
                'name' => 'Series Session',
                'singular_name' => 'Series Session',
                'add_new' => 'Add New Series Session',
                'add_new_item' => 'Add New Series Session',
                'edit_item' => 'Edit Series Session',
                'new_item' => 'New Series Session',
                'view_items' => 'View Series Session',
                'search_items' => 'Search Series Sessions',
                'not_found' => 'No Series Sessions Found',
                'not_found_in_trash' => 'No Series Sessions Found In Trash'
            ),
            'register_meta_box_cb' => array($this, 'meta_boxes')
        ));
        
        add_action('wp_ajax_cf_edit_question', array($this, 'edit_question'));
        add_action('wp_ajax_cf_delete_question', array($this, 'delete_question'));
    }
   
    /**
     * Registers the meta data boxes for this custom post type
     */
    function meta_boxes() {
        add_meta_box('session_info', 'Additional Information', array($this, 'additional_information'), 
                $this->post_type, 'normal', 'high');

        add_meta_box('small_group', 'Small Group Questions', array($this, 'small_group_questions'),
                $this->post_type, 'normal', 'high');

        add_meta_box('family', 'Family Discussion Questions', array($this, 'family_questions'),
                $this->post_type, 'normal', 'high');

        add_meta_box('audio_transcript', 'Audio Transcript', array($this, 'audio_transcript'),
                $this->post_type, 'normal', 'high');
    }

    function audio_transcript($session) {
        $transcript = get_post_meta($session->ID, '_cf_audio_transcript', true);

        ?>
        <div class="customEditor">
            <textarea name="_cf_audio_transcript" style="width: 100%;" rows="5"><?php echo $transcript ?></textarea>
        </div>
        <?php
    }

    function small_group_questions($session) {
        $group_questions = get_post_meta($session->ID, '_cf_group_questions', true);

        ?>
        <div class="customEditor">
            <textarea name="_cf_group_questions" style="width: 100%;" rows="5"><?php echo $group_questions ?></textarea>
        </div>
        <?php
    }

    function family_questions($session) {
        $family_questions = get_post_meta($session->ID, '_cf_family_questions', true);
        ?>
        <div class="customEditor">
            <textarea name="_cf_family_questions" style="width: 100%;" rows="20"><?php echo $family_questions ?></textarea>
        </div>
        <?php
    }

    /**
     * Displays meta information for series session
     * @global type $wpdb
     * @param type $session 
     */
    function additional_information($session) {
        global $wpdb;
        $series_list = $wpdb->get_results('SELECT * FROM cf_series ORDER BY start_date DESC');

        $series = get_post_meta($session->ID, '_cf_series', true);
        $video_url = get_post_meta($session->ID, '_cf_video_url', true);
        
        ?>
       
        <h4>Series</h4>
        <p>Please select the series this session should be located in.</p>
        
        <select name="_cf_series">
        <?php foreach($series_list as $item) { ?>
            <option value="<?php echo $item->series_id ?>" <?php selected($series, $item->series_id) ?>>
                    <?php echo $item->title ?>
            </option>
        <?php } ?>
        </select>
        <h4>Video Url</h4>
        <p>Insert the web address of a video to display.</p>
        <input type="text" size="100" name="_cf_video_url" value="<?php echo $video_url ?>" />
        
        <input type="hidden" name="post_type" value="<?php echo $this->post_type ?>" />
        <?php 
    }
}
?>
