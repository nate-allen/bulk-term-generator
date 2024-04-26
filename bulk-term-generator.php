<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       Bulk Term Generator - Import multiple tags, categories, and taxonomies easily
 * Plugin URI:        http://nateallen.com/wordpress-plugins/bulk-term-generator
 * Description:       Streamline taxonomy management in WordPress with Bulk Term Generator, your free tool for easy, bulk term importing.
 * Version:           1.3.3
 * Requires at least: 3.1
 * Tested up to:      6.5.2
 * Requires PHP:      7.4
 * Author:            Nate Allen
 * Author URI:        https://nateallen.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bulk-term-generator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define a constant for the plugin path
 */
function_exists( 'get_plugin_data' ) || require_once ABSPATH . 'wp-admin/includes/plugin.php';
define( 'BULK_TERM_GENERATOR_METADATA', get_plugin_data( __FILE__, false, false ) );
define( 'BULK_TERM_GENERATOR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Use an autoloader to load classes automatically
 */
spl_autoload_register( 'bulk_term_generator_autoloader' );

function bulk_term_generator_autoloader( $class_name ) {
	$class_name = strtolower( str_replace( '_', '-', $class_name ) );

	// If it's not one of my classes, ignore it
	if ( substr( $class_name, 0, 19 ) !== 'bulk-term-generator' ) {
		return false;
	}

	// Check if the file exists, and if it does, include it
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'classes/class-' . $class_name . '.php' ) ) {
		include plugin_dir_path( __FILE__ ) . 'classes/class-' . $class_name . '.php';
	}
}

/**
 * The code that runs during plugin activation.
 */
function activate_bulk_term_generator() {
	Bulk_Term_Generator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bulk_term_generator() {
	Bulk_Term_Generator_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bulk_term_generator' );
register_deactivation_hook( __FILE__, 'deactivate_bulk_term_generator' );

/**
 * Begins execution of the plugin.
 */
function run_bulk_term_generator() {
	$plugin = new Bulk_Term_Generator();
	$plugin->run();
}

run_bulk_term_generator();
