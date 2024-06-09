<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */

namespace BulkTermGenerator;

class Plugin {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected string $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {
		$this->plugin_name = 'bulk-term-generator';
		$this->version     = BULK_TERM_GENERATOR_METADATA['Version'];

		//$this->set_locale();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Bulk_Term_Generator_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 */
	private function set_locale() {
		$plugin_i18n = new I18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all the hooks related to the admin area functionality of the plugin.
	 */
	public function initialize() {
		$plugin_admin = new Admin( $this->get_plugin_name(), $this->get_version() );

		add_action( 'admin_menu', array( $plugin_admin, 'add_to_menu' ) );
		add_action( 'wp_ajax_btg_add_term', array( $plugin_admin, 'add_term' ) );
		add_filter( 'all_plugins', array( $plugin_admin, 'modify_plugin_title' ), 10, 4 );

		$rest_endpoints = new Endpoints();

		add_action( 'rest_api_init', array( $rest_endpoints, 'register_rest_routes' ) );

		$taxonomies = get_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_add_form", array( $plugin_admin, 'taxonomy_table' ) );
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
