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
 * Version:           1.4.0
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
define( 'BULK_TERM_GENERATOR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Use an autoloader to load classes automatically
 */
spl_autoload_register( 'bulk_term_generator_autoloader' );

/**
 * Autoloader for the BulkTermGenerator namespace.
 *
 * This function is registered with spl_autoload_register and automatically
 * loads class files when a class with the BulkTermGenerator namespace is used.
 * The class name is converted to lowercase and backslashes are replaced with
 * slashes to match the file path structure.
 *
 * @param string $class_name The fully-qualified name of the class to load.
 * @return void
 */
function bulk_term_generator_autoloader( $class_name ) {
	// If the namespace isn't BulkTermGenerator, return
	if ( strpos( $class_name, 'BulkTermGenerator\\' ) !== 0 ) {
		return;
	}

	// Remove the namespace from the class name and replace backslashes with slashes
	$class_name = str_replace( array( 'BulkTermGenerator\\', '\\' ), array( '', '/' ), $class_name );

	// Construct the file path
	$file_path = BULK_TERM_GENERATOR_PATH . 'classes/' . strtolower( $class_name ) . '.php';

	// Check if the file exists, and if it does, include it
	if ( file_exists( $file_path ) ) {
		include $file_path;
	}
}

/**
 * Begins execution of the plugin.
 */
function run_bulk_term_generator() {
	( new \BulkTermGenerator\Plugin() )->initialize();
}
add_action( 'plugins_loaded', 'run_bulk_term_generator' );
