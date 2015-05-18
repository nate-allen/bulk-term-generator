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
 * Plugin Name:       Bulk Term Generator
 * Plugin URI:        http://nateallen.com/wordpress-plugins/bulk-term-generator
 * Description:       Provides the ability to add terms to taxonomies in bulk
 * Version:           1.0.1
 * Author:            Nate Allen
 * Author URI:        http://nateallen.com/
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
define("BULK_TERM_GENERATOR_PATH", plugin_dir_path(__FILE__) );

/**
 * Use an autoloader to load classes automatically
 */
spl_autoload_register( 'bulk_term_generator_autoloader' );

function bulk_term_generator_autoloader( $class ) {

    $class = strtolower( str_replace('_', '-', $class) );

    // If it's not one of my classes, ignore it
    if ( substr( $class, 0, 19 ) != 'bulk-term-generator' )
        return false;

    // Check if the file exists, and if it does, include it
    if ( file_exists ( plugin_dir_path( __FILE__ ) . 'classes/class-' . $class . '.php' ) ){

        include( plugin_dir_path( __FILE__ ) . 'classes/class-' . $class . '.php');

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