<?php

class SeriesSession {
    public $post_type = 'cf_series_session';
    public $edit_question_link =
        'http://localhost/wordpress/wp-admin/admin-ajax.php?action=cf_edit_question&id=%s&post=%s&height=685&width=550';
    
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
        
        add_meta_box('discussion_questions', 'Discussion Questions', array($this, 'discussion_questions'),
                $this->post_type, 'normal', 'high');
        
        add_meta_box('information', 'Information', array($this, 'information'),
                $this->post_type, 'normal', 'high');
    }
    
    function information($session) {
        $blurb = get_post_meta($session->ID, '_cf_blurb', true);
        $information = get_post_meta($session->ID, '_cf_information', true);
        ?>
        <h4>Session Blurb</h4>
        <p>Contains introductory information for the session such as title, speaker, verses, etc.</p>
        <textarea name="_cf_blurb" style="width: 100%;" rows="5"><?php echo $blurb ?></textarea>
        
        <h4>Summary</h4>
        <p>Summary information for the session. This will appear under the blurb in the opening tab.</p>
        <div class="customEditor">
            <textarea name="_cf_information" style="width: 100%;" rows="20"><?php echo $information ?></textarea>
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
        
        $display_date = get_post_meta($session->ID, '_cf_display_date', true);
        $series = get_post_meta($session->ID, '_cf_series', true);
        $video_url = get_post_meta($session->ID, '_cf_video_url', true);
        
        ?>
        <h4>Display Date</h4>
        <p>This is the date the session will appear on the site. All sessions
            are numbered in order of display date.</p>
        
        <input type="text" name="_cf_display_date" class="date" value="<?php echo $display_date ?>" />
        
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
        add_action('admin_print_footer_scripts',array($this,'tinymce'),99);
    }
    
    /**
     * Displays meta information for the discussion questions
     * @global type $wpdb
     * @param type $session 
     */
    function discussion_questions($session) {
        global $wpdb;
        $questions = $wpdb->get_results('SELECT * FROM cf_series_session_questions where post_id = ' . $session->ID);
        
        ?>
        <table class="widefat questions" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Question</th>
                    <th>Comments</th>
                    <th></th>
                </tr>
            </thead>
            <tbody> 
                <?php foreach($questions as $question) { ?>
                    <tr>
                        <td><?php echo $question->number ?></td>
                        <td><?php echo $question->question ?></td>
                        <td><?php echo $question->comments ?></td>
                        <td>
                            <a href="<?php echo printf($this->edit_question_link, $question->question_id, $session->ID) ?>" class="thickbox"
                               title="Edit Question - #<?php echo $question->number ?>">
                                <img src="<?php echo plugin_dir_url(__FILE__) ?>img/icon_edit.png" />
                            </a>
                            <a href="#" class="delete" data-id="<?php echo $question->question_id ?>">
                                <img src="<?php echo plugin_dir_url(__FILE__) ?>img/icon_delete.png" />
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>  
        <a class="button thickbox" value="Add Question" style="float: right;"
           title="Add Discussion Question"
           href="<?php echo printf($this->edit_question_link, '', $session->ID) ?>">Add Question</a>
        <div style="clear: both;"></div>        
        
        <?php
    }
    
    /**
     * Renders the form that handles adding questions to series session post types
     */
    function edit_question() {
        global $wpdb;
        
        //If this is a post, add or update the question
        if($_POST)
        {
            $question = array(
                'number' => $_POST['number'],
                'question' => $_POST['question'],
                'comments' => $_POST['comments'],
                'post_id' => $_POST['post']
            );

            if($_POST['question_id'] != '') {
                $wpdb->update('cf_series_session_questions', $question, array('question_id' => $_POST['question_id']));
            } else {
                $wpdb->insert('cf_series_session_questions', $question);
            }

            //Return the question table. 
            $session -> {'ID'} = $question['post_id'];
            $this->discussion_questions($session);
        } else {
            //If an id was passed in, retrieve the question for display in the form
            if($_GET['id'] != '') {
                $question = $wpdb->get_results('SELECT * FROM cf_series_session_questions where question_id = ' . esc_sql($_GET['id']));
                if($wpdb->num_rows > 0) {
                    $question = $question[0];
                }
            }
        ?>
            <div id="question_form">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Number</th>
                        <td><input type="text" name="number" id="cf_number" value="<?php echo $question->number ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Question</th>
                        <td><textarea cols="55" rows="4" name="question" id="cf_question"><?php echo $question->question ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Comments</th>
                        <td><textarea cols="55" rows="11" name="comments" id="cf_comments"><?php echo $question->comments ?></textarea></td>
                    </tr>
                </table>
                <p>
                    <input type="hidden" value="<?php echo $_GET['post'] ?>" id="cf_post" name="cf_post" />
                    <input type="hidden" value="<?php echo $question->question_id ?>" id="cf_question_id" name="cf_question_id" />
                    <a href="javascript:editQuestion()" class="button" value="Save Question">Save Question</a>
                </p>
            </div>
        <?php
        }
        die();
    }
    
     /**
     * Removes a question from a series session
     * @global type $wpdb 
     */
    function delete_question() {
        global $wpdb;
        
        $wpdb->query('DELETE FROM cf_series_session_questions WHERE question_id = ' 
                . esc_sql($_POST['id']));
    }
    
    function tinymce() {
         ?>
        <script type="text/javascript">
            /* <![CDATA[ */
             jQuery(function($) {
                 var i=1;
                 $('.customEditor textarea').each(function(e) {
                     var id = $(this).attr('id');
                     if (!id) {
                         id = 'customEditor-' + i++;
                         $(this).attr('id',id);
                     }
                     tinyMCE.execCommand('mceAddControl', false, id);
                 });
             });
             /* ]]> */
        </script><?php
    }
}
?>
