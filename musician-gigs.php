<?php
/*
Plugin Name: Musician Gigs
Plugin URI: https://charleshood.net/musician-gigs
Description: A plugin for musicians to display their upcoming gigs. Use the [musician_gigs] shortcode to display the gigs on any page or post.
Version: 2.0
Author: Charles Hood
Author URI: https://charleshood.net/
*/

// Create the database table on plugin activation
function musician_gigs_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'musician_gigs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text NOT NULL,
        event_date date NOT NULL,
        start_time time NOT NULL,
        end_time time NOT NULL,
        image_url varchar(255) NOT NULL,
        address varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add option to store the Google Maps API key
    add_option('musician_gigs_google_maps_api_key', '');
}
register_activation_hook(__FILE__, 'musician_gigs_activate');

// Create the admin menu
function musician_gigs_menu() {
    add_menu_page('Musician Gigs', 'Musician Gigs', 'manage_options', 'musician-gigs', 'musician_gigs_admin_page');
    add_submenu_page('musician-gigs', 'Settings', 'Settings', 'manage_options', 'musician-gigs-settings', 'musician_gigs_settings_page');
}
add_action('admin_menu', 'musician_gigs_menu');

// Display the settings page
function musician_gigs_settings_page() {
    // Retrieve the Google Maps API key
    $google_maps_api_key = get_option('musician_gigs_google_maps_api_key');

    // Handle form submission
    if (isset($_POST['submit'])) {
        // Update the Google Maps API key
        update_option('musician_gigs_google_maps_api_key', sanitize_text_field($_POST['google_maps_api_key']));

        // Display a success message
        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }

    // Display the settings form
    ?>
    <div class="wrap">
        <h1>Musician Gigs Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="google_maps_api_key">Google Maps API Key</label></th>
                    <td><input type="text" name="google_maps_api_key" id="google_maps_api_key" class="regular-text" value="<?php echo esc_attr($google_maps_api_key); ?>"></td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// Display the admin page
function musician_gigs_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'musician_gigs';

    // Handle form submission
    if (isset($_POST['submit'])) {
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $event_date = sanitize_text_field($_POST['event_date']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $address = sanitize_text_field($_POST['address']);

        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['image']['name'])) {
            $uploaded_file = $_FILES['image'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $image_url = $movefile['url'];
            }
        }

        if (isset($_POST['gig_id'])) {
            // Update existing gig
            $gig_id = absint($_POST['gig_id']);
            $wpdb->update(
                $table_name,
                array(
                    'title' => $title,
                    'description' => $description,
                    'event_date' => $event_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'image_url' => $image_url,
                    'address' => $address
                ),
                array('id' => $gig_id)
            );
        } else {
            // Add new gig
            $wpdb->insert(
                $table_name,
                array(
                    'title' => $title,
                    'description' => $description,
                    'event_date' => $event_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'image_url' => $image_url,
                    'address' => $address
                )
            );
        }
    }

    // Handle event deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['gig_id'])) {
        $gig_id = absint($_GET['gig_id']);
        $wpdb->delete($table_name, array('id' => $gig_id));
    }

    // Retrieve existing gigs
    $gigs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY event_date ASC");

    // Display the form and existing gigs
    ?>
    <div class="wrap">
        <h1>Musician Gigs</h1>

        <table class="widefat">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gigs as $gig) : ?>
                    <tr>
                        <td><?php echo esc_html($gig->title); ?></td>
                        <td><?php echo esc_html(date('F j, Y', strtotime($gig->event_date))); ?></td>
                        <td><?php echo esc_html(date('g:i a', strtotime($gig->start_time))); ?></td>
                        <td><?php echo esc_html(date('g:i a', strtotime($gig->end_time))); ?></td>
                        <td>
                            <a href="?page=musician-gigs&action=edit&gig_id=<?php echo $gig->id; ?>">Edit</a> |
                            <a href="?page=musician-gigs&action=delete&gig_id=<?php echo $gig->id; ?>" onclick="return confirm('Are you sure you want to delete this gig?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>
            <?php
            if (isset($_GET['action']) && $_GET['action'] === 'edit') {
                echo 'Edit Gig';
            } else {
                echo 'Add New Gig';
            }
            ?>
        </h2>

        <form method="post" enctype="multipart/form-data">
            <?php
            $gig_id = isset($_GET['gig_id']) ? absint($_GET['gig_id']) : 0;
            if ($gig_id) {
                $gig = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $gig_id));
            }
            ?>
            <input type="hidden" name="gig_id" value="<?php echo $gig_id; ?>">

            <table class="form-table">
                <tr>
                    <th><label for="title">Event Title</label></th>
                    <td><input type="text" name="title" id="title" class="regular-text" value="<?php echo isset($gig) ? esc_attr($gig->title) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="event_date">Event Date</label></th>
                    <td><input type="date" name="event_date" id="event_date" value="<?php echo isset($gig) ? esc_attr($gig->event_date) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="start_time">Start Time</label></th>
                    <td><input type="time" name="start_time" id="start_time" value="<?php echo isset($gig) ? esc_attr($gig->start_time) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="end_time">End Time</label></th>
                    <td><input type="time" name="end_time" id="end_time" value="<?php echo isset($gig) ? esc_attr($gig->end_time) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="address">Venue Address</label></th>
                    <td><input type="text" name="address" id="address" class="regular-text" value="<?php echo isset($gig) ? esc_attr($gig->address) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="image">Event Image</label></th>
                    <td>
                        <input type="file" name="image" id="image">
                        <?php if (isset($gig) && !empty($gig->image_url)) : ?>
                            <br>
                            <img src="<?php echo esc_url($gig->image_url); ?>" alt="Event Image" width="200">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="description">Event Description</label></th>
                    <td><textarea name="description" id="description" rows="5" cols="50"><?php echo isset($gig) ? esc_textarea($gig->description) : ''; ?></textarea></td>
                </tr>
            </table>

            <?php submit_button(isset($gig_id) && $gig_id ? 'Update Gig' : 'Add Gig'); ?>
        </form>
    </div>
    <?php
}

// Display the gigs using a shortcode
function musician_gigs_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'musician_gigs';

    $gigs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY event_date ASC");

    // Retrieve the Google Maps API key
    $google_maps_api_key = get_option('musician_gigs_google_maps_api_key');

    $output = '<div class="musician-gigs">';
    foreach ($gigs as $gig) {
        $output .= '<div class="musician-gig">';
        $output .= '<div class="musician-gig-header">';
        if (!empty($gig->image_url)) {
            $output .= '<div class="musician-gig-image">';
            $output .= '<img src="' . esc_url($gig->image_url) . '" alt="' . esc_attr($gig->title) . '">';
            $output .= '</div>';
        }
        $output .= '<h3>' . esc_html($gig->title) . '</h3>';
        $output .= '</div>';
        $output .= '<div class="musician-gig-details">';
        $output .= '<p class="musician-gig-date-time">';
        $output .= '<span class="musician-gig-date">' . esc_html(date('F j, Y', strtotime($gig->event_date))) . '</span>';
        $output .= '<span class="musician-gig-time">' . esc_html(date('g:i a', strtotime($gig->start_time))) . ' - ' . esc_html(date('g:i a', strtotime($gig->end_time))) . '</span>';
        $output .= '</p>';
        $address_parts = explode(', ', $gig->address);
        $street_address = $address_parts[0];
        $city_state_zip = implode(', ', array_slice($address_parts, 1));
        $google_maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($gig->address);
        $output .= '<p class="musician-gig-address">';
        $output .= '<a href="' . esc_url($google_maps_url) . '" target="_blank">';
        $output .= '<span class="musician-gig-street-address">' . esc_html($street_address) . '</span><br>';
        $output .= '<span class="musician-gig-city-state-zip">' . esc_html($city_state_zip) . '</span>';
        $output .= '</a>';
        $output .= '</p>';
        $output .= '<div class="musician-gig-description">' . wpautop(esc_html($gig->description)) . '</div>';
        $output .= '</div>';
        $output .= '<div class="musician-gig-map">';
        $output .= '<iframe width="100%" height="200" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?key=' . esc_attr($google_maps_api_key) . '&q=' . urlencode($gig->address) . '" allowfullscreen></iframe>';
        $output .= '</div>';
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode('musician_gigs', 'musician_gigs_shortcode');

// Enqueue scripts and styles
function musician_gigs_enqueue_scripts() {
    wp_enqueue_style('musician-gigs-style', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('musician-gigs-script', plugins_url('js/script.js', __FILE__), array('jquery'), '1.9', true);

    // Enqueue admin styles
    if (is_admin()) {
        wp_enqueue_style('musician-gigs-admin-style', plugins_url('css/admin-style.css', __FILE__));
    }
}
add_action('admin_enqueue_scripts', 'musician_gigs_enqueue_scripts');
add_action('wp_enqueue_scripts', 'musician_gigs_enqueue_scripts');