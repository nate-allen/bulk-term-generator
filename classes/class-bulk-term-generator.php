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
class Bulk_Term_Generator {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var Bulk_Term_Generator_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected Bulk_Term_Generator_Loader $loader;

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

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Bulk_Term_Generator_Loader. Orchestrates the hooks of the plugin.
	 * - Bulk_Term_Generator_I18n. Defines internationalization functionality.
	 * - Bulk_Term_Generator_Admin. Defines all hooks for the admin area.
	 * - Bulk_Term_Generator_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 */
	private function load_dependencies() {
		$this->loader = new Bulk_Term_Generator_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Bulk_Term_Generator_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 */
	private function set_locale() {
		$plugin_i18n = new Bulk_Term_Generator_I18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Bulk_Term_Generator_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_to_menu' );
		$this->loader->add_action( 'wp_ajax_btg_add_term', $plugin_admin, 'add_term' );
		$this->loader->add_action( 'init', $plugin_admin, 'taxonomy_select' );
		$this->loader->add_filter( 'all_plugins', $plugin_admin, 'modify_plugin_title', 10, 4 );

		$taxonomies = get_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			$this->loader->add_action( "{$taxonomy}_add_form", $plugin_admin, 'taxonomy_table' );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader(): Bulk_Term_Generator_Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
