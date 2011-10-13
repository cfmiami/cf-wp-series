<?php
class Series {
    public function __construct() {
        add_menu_page('CF Series Settings', 'CF Series', 'administrator',
                __FILE__, array($this, 'settings'), '', 25);

        $hook = add_submenu_page(__FILE__, 'Series', 'Add New Series', 'administrator',
                __FILE__ . '_series', array($this,'edit_series'));

        if (function_exists('wp_tiny_mce')) {

          add_filter('teeny_mce_before_init', create_function('$a', '
            $a["theme"] = "advanced";
            $a["skin"] = "wp_theme";
            $a["height"] = "200";
            $a["width"] = "800";
            $a["onpageload"] = "";
            $a["mode"] = "exact";
            $a["elements"] = "intro";
            $a["editor_selector"] = "customEditor";
            $a["plugins"] = "safari,inlinepopups,spellchecker";

            $a["forced_root_block"] = false;
            $a["force_br_newlines"] = true;
            $a["force_p_newlines"] = false;
            $a["convert_newlines_to_brs"] = true;

            return $a;'));

         wp_tiny_mce(true);
        }

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
              'slug' => str_replace("\\", "", $_POST['slug']),
              'start_date' => to_mysql_date($_POST['start']),
              'end_date' => to_mysql_date($_POST['end']),
              'main_image_url' => str_replace("\\", "", $_POST['main_image_url']),
              'kids_image_url' => str_replace("\\", "", $_POST['kids_image_url']),
              'book_description' => str_replace("\\", "",$_POST['book_description']),
              'book_image_url' => str_replace("\\", "", $_POST['book_image_url'])
            );
            
            //Check for required fields
            $errors = array();
            if(trim($series['title']) === '') {
                array_push($errors, "Title is required.");
            }

            if(trim($series['slug']) === '') {
                array_push($errors, "Slug is required.");
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
                    <th scope="row"><label for="slug">Slug<span class="required">*</span></label></th>
                    <td>
                        <input type="text" value="<?php echo $series['slug'] ?>"
                               maxlength="100" size="100" name="slug" />
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
                    <th scope="row"><label for="end">Main Image</label></th>
                    <td>
                        <input type="text" name="main_image_url" id="main_image_url" size="100" value="<?php echo $series['main_image_url'] ?>" />
                        <input type="button" value="Media Library Image" class="button-secondary upload"
                           data-control="main_image_url"/>
                        <br />
                        <span class="description">This appears as the featured image for the series</span>

                        <br />
                        <?php if(!empty($series['main_image_url'])) { ?>
                        <img style="width: 500px;" src="<?php echo $series['main_image_url'] ?>"/>
                        <?php } ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="end">Kids Main Image</label></th>
                    <td>
                        <input type="text" name="kids_image_url" id="kids_image_url" size="100" value="<?php echo $series['kids_image_url'] ?>" />
                        <input type="button" value="Media Library Image" class="button-secondary upload"
                           data-control="kids_image_url"/>
                        <br />
                        <span class="description">This appears as the featured image for the kids series. If no image is given, the image for the main series is used.</span>

                        <br />
                        <?php if(!empty($series['kids_image_url'])) { ?>
                        <img style="width: 500px;" src="<?php echo $series['kids_image_url'] ?>"/>
                        <?php } ?>
                    </td>
                </tr>
            </table>

            <h2>Recommended Book</h2>
             <table class="form-table" style="width: auto;">
                 <tr valign="top">
                    <th scope="row"><label for="book_description">Description</label></th>
                    <td>
                        <div class="customEditor">
                        <textarea id="book_description" name="book_description" rows="10"><?php echo $series['book_description'] ?></textarea>
                            </div>
                    </td>
                 </tr>
                <tr valign="top">
                    <th scope="row"><label for="book_image_url">Book Image</label></th>
                    <td>
                        <input type="text" id="book_image_url" value="<?php echo $series['book_image_url'] ?>"
                               maxlength="100" size="100" name="book_image_url" />
                        <input type="button" value="Media Library Image" class="button-secondary upload"
                           data-control="book_image_url"/>

                        <br />
                        <?php if(!empty($series['book_image_url'])) { ?>
                        <img src="<?php echo $series['book_image_url'] ?>"/>
                        <?php } ?>
                    </td>
                </tr>

             </table>

            <input type="hidden" name="series_id" value="<?php echo $_GET['id'] ?>" />
            <input type="submit" name="save" value="Save Series" class="button-primary" />
            <a class="button-secondary" href="admin.php?page=cf-wp-series/Series.php">Back to List</a>
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
                    <th>Image</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody> 
                <?php if(count($series) == 0) { ?>
                <tr>
                    <td colspan="5">No series found.</td>
                </tr>
                <?php } else { ?>
                    <?php foreach($series as $item) { ?>
                        <tr>
                            <td>
                                <?php if(!empty($item->main_image_url)) { ?>
                                <img style="width: 300px;" src="<?php echo $item->main_image_url ?>"/>
                                <?php } ?>
                            </td>
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
					<th>Image</th>
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