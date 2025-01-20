<?php
/*
Plugin Name:  Sauga Parks
Description:  This Wordpress plugin registers a custom post type, implement a custom taxonomy, and includes a short code to display and filter a list of those posts.
Version:      1.0.0
Author:       Andrea de Cerqueira
Author URI:   https://www.andreaamado.com
Text Domain:  sauga-parks
*/

namespace SaugaParks;

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

// Add Custom Post Type
add_action('init', function() {
  register_post_type('park', array(
      'public' => true,
      'labels' => array(
          'name' => 'Parks',
          'singular_name' => 'Park'
      ),
      'supports' => array('title', 'editor'),
      'menu_icon' => 'dashicons-location-alt'
  ));
});

// Add Facilities Taxonomy
add_action('init', function() {
  register_taxonomy('facility', 'park', array(
      'hierarchical' => true,
      'labels' => array(
          'name' => 'Facilities',
          'add_new_item' => 'Add New Facility'
      ),
      'show_admin_column' => true
  ));
});

// Prepopulate taxonomy terms on plugin activation
register_activation_hook(__FILE__, function() {
  // Make sure the taxonomy is registered
  register_taxonomy('facility', 'park');

  wp_insert_term('Ice Rink', 'facility');
  wp_insert_term('Lash-Free zone', 'facility');
  wp_insert_term('Water Fountain', 'facility');
});

// Add Meta Box
add_action('add_meta_boxes', function() {
  add_meta_box(
      'park_details',
      'Park Details',
      function($post) {
          ?>
          <p>
              <label>Location:</label>
              <input type="text" name="location" value="<?php echo esc_attr(get_post_meta($post->ID, '_location', true)); ?>">
          </p>
          <p>
              <label>Weekday Hours:</label>
              <input type="text" name="weekday_hours" value="<?php echo esc_attr(get_post_meta($post->ID, '_weekday_hours', true)); ?>" style="width: 100%">
              <small>Example: [6:00 AM - 9:00 PM]</small>
          </p>
          <p>
              <label>Weekend Hours:</label>
              <input type="text" name="weekend_hours" value="<?php echo esc_attr(get_post_meta($post->ID, '_weekend_hours', true)); ?>" style="width: 100%">
              <small>Example: [7:00 AM - 8:00 PM] or [Closed]</small>
          </p>
          <?php
      },
      'park'
  );
});

// Save Meta Box Data
add_action('save_post', function($post_id) {
    if (isset($_POST['location'])) {
        update_post_meta($post_id, '_location', sanitize_text_field($_POST['location']));
    }
    if (isset($_POST['weekday_hours'])) {
        update_post_meta($post_id, '_weekday_hours', sanitize_text_field($_POST['weekday_hours']));
    }
    if (isset($_POST['weekend_hours'])) {
        update_post_meta($post_id, '_weekend_hours', sanitize_text_field($_POST['weekend_hours']));
    }
});

// Add Shortcode
add_shortcode('park_list', function() {
  // Get all facilities
  $facilities = get_terms(array(
      'taxonomy' => 'facility',
      'hide_empty' => true
  ));

  // Mount facilities dropdown filter
  $output = '
  <div class="facility-filter-wrapper" role="search" aria-label="Filter parks by facility">
      <label id="facility-label" for="facility-filter" class="facility-label">
          Filter parks by facility
          <span class="screen-reader-text">(selecting a facility will automatically update the list below)</span>
      </label>
      <select
          id="facility-filter" 
          name="facility-filter"
          aria-labelledby="facility-label"
          aria-controls="parks-list"
      >
          <option value="">Show all facilities</option>';
          foreach($facilities as $facility) {
              $output .= '<option value="' . esc_attr($facility->slug) . '">' . 
                        esc_html($facility->name) . '</option>';
          }
  $output .= '
      </select>
  </div>';

  // Get parks
  $parks = get_posts(array(
      'post_type' => 'park',
      'posts_per_page' => -1
  ));

  // Mount parks list
  $output .= '<div id="parks-list" class="parks-list" aria-live="polite">';
  foreach($parks as $park) {
      $location = get_post_meta($park->ID, '_location', true);
      $weekday_hours = get_post_meta($park->ID, '_weekday_hours', true);
      $weekend_hours = get_post_meta($park->ID, '_weekend_hours', true);
      
      // Get facilities for this park
      $park_facilities = get_the_terms($park->ID, 'facility');
      $facility_classes = '';
      if ($park_facilities) {
          foreach($park_facilities as $facility) {
              $facility_classes .= ' facility-' . $facility->slug;
          }
      }

      $output .= '<div class="park-item' . esc_attr($facility_classes) . '">';
      $output .= '<h2>' . esc_html($park->post_title) . '</h2>';
      $output .= '<p class="park-description">' . wp_trim_words($park->post_content, 20) . '</p>';
      $output .= '<p><strong>Location:</strong> ' . esc_html($location) . '</p>';
      $output .= '<div class="hours">';
      $output .= '<p><strong>Hours:</strong></p>';
      $output .= '<p>&nbsp;&nbsp;&nbsp;Weekdays: ' . esc_html($weekday_hours) . '</p>';
      $output .= '<p>&nbsp;&nbsp;&nbsp;Weekends: ' . esc_html($weekend_hours) . '</p>';
      $output .= '</div>';

      // Display facilities for each park
      if ($park_facilities) {
          $output .= '<p class="facility-tag-wrapper"><strong>Facilities:</strong> ';
          foreach($park_facilities as $facility) {
              $output .= '<span>' . $facility->name . '</span>';
          }
          $output .= '</p>';
      }

      $output .= '</div>';
  }
  $output .= '</div>';

  return $output;
});

// Add jQuery for filtering
add_action('wp_enqueue_scripts', function() {
  wp_enqueue_script(
      'sauga-parks-script',
      plugins_url('js/main.js', __FILE__),
      array('jquery'),
      '1.0.0',
      true
  );
});

// Add CSS
add_action('wp_enqueue_scripts', function() {
  wp_enqueue_style(
      'sauga-parks-style',
      plugins_url('css/style.css', __FILE__),
      array(),
      '1.0.0'
  );
});
?>