<?php
/**
 * Plugin Name: Contact Form 7 MadMimi Integration
 * Plugin URI: https://dennisridder.com
 * Description: Adds a Madmimi service to Contact Form 7. Easily enables your form(s) to subscribe to Madmimi audience lists.
 * Author: Dennis Ridder
 * Author URI: https://dennisridder.com
 * Version: 1.0
 *
 * @package DennisRidder\Integrations\WPCF7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'src/Init.php';
