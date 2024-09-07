<?php

/**
 * Plugin Name: WP Blurhash
 * Plugin URI: https://kylehotchkiss.com/wp-blurhash
 * Description: Generates Blurhashes for uploaded media in WordPress.
 * Version: 1.0.0
 * Author: Kyle Hotchkiss
 * Author URI: https://kylehotchkiss.com
 * License: MIT
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Include the Composer autoload file.
if (is_readable(__DIR__ . '/vendor/autoload.php')) {
  require __DIR__ . '/vendor/autoload.php';
}

// Load Blurhash library
use kornrunner\Blurhash\Blurhash;

// Check if ACF is activated
function blurhash_acf_activated()
{
  return class_exists('ACF');
}

// Create ACF fields
function blurhash_acf_activate()
{
  if (!class_exists('ACF')) {
    return false;
  }

  // Check if there's a field group for Media. Create one if not.
  if (!acf_get_field_group('group_blurhash')) {
    acf_add_local_field_group(array(
      'key' => 'group_blurhash',
      'title' => 'Blurhash',
      'show_in_graphql' => true,
      'graphql_field_name' => 'acfBlurhash',
      'fields' => array(array(
        'key' => 'field_blurhash',
        'name' => 'blurhash',
        'label' => 'Blurhash',
        'type' => 'text',
      )),
      'location' => array(array(array(
        'param' => 'attachment',
        'operator' => '==',
        'value' => 'all',
      ))),
    ));
  }
}

// Resize image to 32x32 so that blurhash can be processed pixel by pixel
function resize_image_before_blurhash($image_path)
{
  // Load the image
  $image = wp_get_image_editor($image_path);

  if (!is_wp_error($image)) {
    // Resize the image to 32x32px
    $image->resize(32, 32, true);

    // Save the resized image to memory
    $saved_image = $image->save('php://memory');

    return $saved_image['path'];
  }

  // Return original image path in case of error
  return $image_path;
}

// Generate blurhash for an image
function generate_blurhash($image_path)
{
  //error_log("Generating blurhash for $image_path");
  //error_log('Image: ' . file_get_contents($image_path));
  $pixels = [];
  $image = imagecreatefromstring(file_get_contents($image_path));
  $width = imagesx($image);
  $height = imagesy($image);

  for ($y = 0; $y < $height; ++$y) {
    $row = [];

    for ($x = 0; $x < $width; ++$x) {
      $index = imagecolorat($image, $x, $y);
      $colors = imagecolorsforindex($image, $index);

      $row[] = [$colors['red'], $colors['green'], $colors['blue']];
    }
    $pixels[] = $row;
  }

  $components_x = 4;
  $components_y = 3;

  $blurhash = Blurhash::encode($pixels, $components_x, $components_y);

  return $blurhash;
}

// Save blurhash when a media is uploaded
function save_blurhash_on_upload($attachment_id)
{
  $file_path = get_attached_file($attachment_id);

  if ($file_path) {
    // Resize the image
    $resized_image_path = resize_image_before_blurhash($file_path);

    // Generate the blurhash
    $blurhash = generate_blurhash($resized_image_path);

    // Cleanup the temporary resized image
    if ($file_path !== $resized_image_path) {
      unlink($resized_image_path);
    }

    if (blurhash_acf_activated()) {
      // Save blurhash to ACF field
      update_field('blurhash', $blurhash, $attachment_id);
    } else {
      // Save blurhash to post meta
      update_post_meta($attachment_id, 'blurhash', $blurhash);
    }
  }
}


// WP Cron task to generate 10 blurhashes
function cron_generate_blurhashes()
{
  error_log('Generating blurhashes for 50 uploaded images via wp-cron');

  // Get 10 attachments without blurhash
  $args = array(
    'post_type' => 'attachment',
    'posts_per_page' => 50,
    'meta_query' => array(
      array(
        'key' => 'blurhash',
        'compare' => 'NOT EXISTS',
      ),
    ),
  );

  $attachments = get_posts($args);

  foreach ($attachments as $attachment) {
    save_blurhash_on_upload($attachment->ID);
  }
}

if (!wp_next_scheduled('cron_generate_blurhashes_hook')) {
  wp_schedule_event(time(), 'hourly', 'cron_generate_blurhashes_hook');
}

// Admin page to display blurhash statistics
function blurhash_admin_page()
{
  // Get total attachments count
  $total_attachments = wp_count_posts('attachment')->inherit;

  // Get attachments with blurhash
  $args_with_blurhash = array(
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => 'blurhash',
  );

  // Calculate counts
  $count_with_blurhash = count(get_posts($args_with_blurhash));
  $count_without_blurhash = $total_attachments - $count_with_blurhash;

  echo "<h2>Blurhash Statistics</h2>";
  echo '<p>Images with Blurhash: <span id="js-blurhash-count-completed">' . $count_with_blurhash . '</p>';
  echo '<p>Images without Blurhash: <span id="js-blurhash-count-pending">' . $count_without_blurhash . '</p>';
  echo '<button id="js-blurhash-run-cron" class="button button-primary">Process 50 more images <span id="blurhash-loader" style="display: none;"><img src="' . admin_url('images/loading.gif') . '" alt="Loading..."></span></button>';
}

function blurhash_enqueue_admin_js($hook)
{
  // Check if we're on our plugin's admin page
  if ('media_page_blurhash_stats' == $hook) {
    wp_enqueue_script('blurhash-admin', plugins_url('assets/blurhash-admin.js', __FILE__), array('jquery'), '1.0.0', true);

    // Add localized variables to the script
    wp_localize_script('blurhash-admin', 'blurhash_vars', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('blurhash_nonce')
    ));
  }
}

function run_blurhash_cron_callback()
{
  check_ajax_referer('blurhash_nonce', 'nonce');

  // Run your cron task here
  cron_generate_blurhashes();

  // Get total attachments count
  $total_attachments = wp_count_posts('attachment')->inherit;

  // Get attachments with blurhash
  $args_with_blurhash = array(
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => 'blurhash',
  );

  // Calculate counts
  $count_with_blurhash = count(get_posts($args_with_blurhash));
  $count_without_blurhash = $total_attachments - $count_with_blurhash;


  // Return a success response
  wp_send_json_success(array(
    'completed' => $count_with_blurhash,
    'pending' => $count_without_blurhash
  ));
}

function blurhash_admin_menu()
{
  add_media_page('Blurhash Statistics', 'Blurhash', 'manage_options', 'blurhash_stats', 'blurhash_admin_page');
}

add_action('acf/init', 'blurhash_acf_activate');
add_action('admin_menu', 'blurhash_admin_menu');
add_action('admin_enqueue_scripts', 'blurhash_enqueue_admin_js');

add_action('wp_ajax_run_blurhash_cron', 'run_blurhash_cron_callback');
add_action('add_attachment', 'save_blurhash_on_upload');
add_action('cron_generate_blurhashes_hook', 'cron_generate_blurhashes');
