<?php
class Series {
    public function __construct() {
        add_menu_page('CF Series Settings', 'CF Series', 'administrator',
                __FILE__, array($this, 'settings'));
        
        $hook = add_submenu_page(__FILE__, 'Series', 'Add New Series', 'administrator',
                __FILE__ . '_series', array($this,'edit_series'));  
    }
    
    /**
     * Handles displaying the edit series form as well as saving the data to the database
     */
    public function edit_series() {
        global $wpdb;
        $series = array();
        
        if($_POST) {
            $series = array(
              'title' => str_replace("\\", "",$_POST['title']),
              'description' => str_replace("\\", "", $_POST['description']),
              'start_date' => to_mysql_date($_POST['start']),
              'end_date' => to_mysql_date($_POST['end'])
            );

            if(trim($_POST['devo_start']) === '') {
                $series['devo_start'] = $series['start_date'];
            }

            //Check for required fields 
            $errors = array();
            if(trim($series['title']) === '') {
                array_push($errors, "Title is required.");
            }
            
            if(trim($series['start_date']) === '') {
                array_push($errors, "Start date is required.");
            }
            
            if(count($errors) > 0) {
                echo '<div id="message" class="error">';
                foreach($errors as $error) {
                    echo '<p>' . $error . '</p>';
                }
                echo '</div>';
            } else {
                if($_POST['series_id'] != '') {
                    $wpdb->update('cf_series', $series, array('series_id' => $_POST['series_id']));
                } else {
                    $wpdb->insert('cf_series', $series);
                }

                echo '<div id="message" class="updated"><p>Series information was saved.</p></div>';
            }
        } else if($_GET) {
            $results = $wpdb->get_results('SELECT * FROM cf_series where series_id = ' . $_GET["id"], ARRAY_A);
            if($wpdb->num_rows > 0) {
                $series = $results[0];
            }
        }
    ?>
    <div class="wrap">
        <?php screen_icon('edit') ?>
        <h2><?php echo count($series) == 0 ? "Add New Series" : 'Edit Settings - ' . $series['title'] ?></h2>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="title">Title<span class="required">*</span></label></th>
                    <td>
                        <input type="text" value="<?php echo $series['title'] ?>" 
                               maxlength="100" size="100" name="title" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="description">Description</label></th>
                    <td>
                        <textarea name="description" cols="89" rows="10"><?php echo $series['description'] ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="start">Start Date<span class="required">*</span></label></th>
                    <td>
                        <input type="text" name="start" class="date" value="<?php echo mysql2date('m/d/Y',$series['start_date']) ?>" />
                        <span class="description">The day this series should appear as the main series.</span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="end">End Date</label></th>
                    <td>
                        <input type="text" name="end" class="date" value="<?php echo mysql2date('m/d/Y', $series['end_date']) ?>" />
                        <span class="description">The day the series should be moved to archived.</span>
                    </td>
                </tr>
                <tr valign="top">
                    <td>
                        <input type="submit" name="save" value="Save Series" class="button-primary" />
                        <a class="button-secondary" href="admin.php?page=cf-wp-series/Series.php">Back to List</a>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="series_id" value="<?php echo $_GET['id'] ?>" />
        </form>
    </div>
    <?php
    }
    
    /**
     * Renders the settings page
     */
    public function settings() {
        global $wpdb;
        $series = $wpdb->get_results('SELECT * FROM cf_series');
    ?>
    <div class="wrap">
        <?php screen_icon('plugins') ?>
        <h2>CF Series Settings <a href="admin.php?page=cf-wp-series/Series.php_series" class="button">Add New Series</a></h2>
        <p>Select a series below to edit corresponding options.</p>
        <table class="widefat data">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody> 
                <?php if(count($series) == 0) { ?>
                <tr>
                    <td colspan="4">No series found.</td>
                </tr>
                <?php } else { ?>
                    <?php foreach($series as $item) { ?>
                        <tr>
                            <td>
                                <a href="admin.php?page=cf-wp-series/Series.php_series&id=<?php echo $item->series_id ?>">
                                    <?php echo $item->title ?>
                                </a>
                            </td>
                            <td><?php echo $item->description ?></td>
                            <td><?php echo mysql2date('m/d/Y', $item->start_date) ?></td>
                            <td><?php echo mysql2date('m/d/Y', $item->end_date) ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
    }
}
?>
