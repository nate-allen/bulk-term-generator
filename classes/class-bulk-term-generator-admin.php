<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Bulk_Term_Generator_Admin {

	/**
	 * Template Paths
	 *
	 * All of the possible templates that can be loaded
	 */
	private string $settings_page_template;
	private string $generate_terms_page_template;

	/**
	 * The ID of this plugin.
	 *
	 * @var    string $plugin_name The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var    string $version The current version of this plugin.
	 */
	private string $version;

	/**
	 * Array to hold data used in templates
	 *
	 * @var    array $data Associative array. Key is variable name, value is its value
	 */
	private array $data = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->settings_page_template       = BULK_TERM_GENERATOR_PATH . 'views/admin/templates/settings-page-default.php';
		$this->generate_terms_page_template = BULK_TERM_GENERATOR_PATH . 'views/admin/templates/generate-terms-page.php';

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_register_style(
			$this->plugin_name . '-admin',
			plugin_dir_url( dirname( __FILE__ ) ) . 'views/admin/css/bulk-term-generator-admin.css',
			array(
				$this->plugin_name . '-jquery-ui-css',
				'font-awesome',
			),
			$this->version
		);
		wp_register_style( $this->plugin_name . '-jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css', array(), '1.13.2' );
		wp_register_style( 'font-awesome', plugin_dir_url( dirname( __FILE__ ) ) . 'views/admin/css/font-awesome.min.css', array(), '4.3.0' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_register_script( $this->plugin_name . '-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'views/admin/js/bulk-term-generator-admin.js', array( 'jquery' ), $this->version, true );
	}

	/**
	 * Add Bulk Term Generator to the Admin Menu under Tools
	 */
	public function add_to_menu() {
		add_submenu_page(
			'tools.php',
			esc_html__( 'Bulk Term Generator', 'bulk-term-generator' ),
			esc_html__( 'Bulk Term Generator', 'bulk-term-generator' ),
			'manage_options',
			'bulk_term_generator_options',
			array(
				$this,
				'options_page',
			)
		);
	}

	public function options_page() {

		// Normal page load, not a form submit. Load the default page
		if ( ! isset( $_POST['action'] ) && ! isset( $_GET['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->data['taxonomy_select_list'] = array( 'taxonomy_select_list' => array( 'id' => 'chosen_taxonomy' ) );
			$this->load_default_page();

			return;
		}

		if ( isset( $_POST['action'] ) && 'taxonomy_selected' === $_POST['action'] && empty( $_POST['chosen_taxonomy'] ) ) { // phpcs:ignore
			$this->data['error']                = esc_html__( 'Please choose a taxonomy', 'bulk-term-generator' );
			$this->data['taxonomy_select_list'] = array( 'taxonomy_select_list' => array( 'id' => 'chosen_taxonomy' ) );
			$this->load_default_page();
		}

		// If the taxonomy is in the URL parameter
		if ( isset( $_GET['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$taxonomy       = get_taxonomy( $_GET['taxonomy'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$taxonomy_slug  = $_GET['taxonomy']; // phpcs:ignore WordPress.Security.NonceVerification
			$taxonomy_name  = $taxonomy->labels->name;
			$taxonomy_terms = get_terms(
				array(
					'hide_empty' => false,
					'taxonomy'   => $_GET['taxonomy'], // phpcs:ignore WordPress.Security.NonceVerification
				)
			);

			$this->data['is_hierarchical']  = $taxonomy->hierarchical;
			$this->data['taxonomy_slug']    = $taxonomy_slug;
			$this->data['taxonomy_name']    = $taxonomy_name;
			$this->data['terms']            = $taxonomy_terms;
			$this->data['term_list']        = array(
				'html_list' => array(
					'taxonomy' => $taxonomy_slug,
					'id'       => 'term-list',
				),
			);
			$this->data['term_select_list'] = array(
				'term_select_list' => array(
					'taxonomy' => $taxonomy_slug,
					'id'       => 'parent_term',
				),
			);

			$this->load_generate_terms_page( $taxonomy->name );
		}

	}

	public function taxonomy_select() {

		// If the user submitted the "Choose a Taxonomy" form
		if ( isset( $_POST['action'] ) && 'taxonomy_selected' === $_POST['action'] && ! empty( $_POST['chosen_taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect(
				add_query_arg(
					array(
						'taxonomy' => $_POST['chosen_taxonomy'], // phpcs:ignore WordPress.Security.NonceVerification
					),
					esc_url_raw( $_SERVER['REQUEST_URI'] ),
				)
			);
			exit;
		}

	}

	/**
	 * Changes the title of the plugin on the plugins page
	 *
	 * @param array $plugins Array of plugins
	 *
	 * @return array Modified array of plugins
	 */
	public function modify_plugin_title( $plugins ) {
		if ( isset( $plugins[ 'bulk-term-generator/bulk-term-generator.php' ] ) ) {
			$plugins[ 'bulk-term-generator/bulk-term-generator.php' ]['Name'] = esc_html__( 'Bulk Term Generator', 'bulk-term-generator' );
		}

		return $plugins;
	}

	public function add_term() {

		$term_name = $_POST['term_name']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$taxonomy  = $_POST['taxonomy']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parent    = $_POST['parent']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$slug      = $_POST['slug']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$desc      = $_POST['desc']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data      = new stdClass();

		// Check that the submitted nonce is one that was generated earlier.
		// If not, return an error message.
		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'btg_add_term_to_' . $taxonomy ) ) {
			$data->success = false;
			$data->error   = esc_html__( 'Security check failed.', 'bulk-term-generator' );
			echo wp_json_encode( $data );
			wp_die();
		}

		$args = array();

		// Build the optional arguments
		if ( isset( $parent ) && 0 !== intval( $parent ) ) {
			$args['parent'] = intval( $parent );
		}
		if ( isset( $slug ) && '' !== $slug ) {
			$args['slug'] = $slug;
		}
		if ( isset( $desc ) && '' !== $desc ) {
			$args['description'] = $desc;
		}

		$term_object = wp_insert_term( $term_name, $taxonomy, $args );

		if ( is_wp_error( $term_object ) ) {
			$data->success = false;
			$data->error   = $term_object->get_error_code();

			// If the term exists, get its ID and parent
			if ( $term_object->get_error_code() === 'term_exists' ) {
				$existing_term   = term_exists( $term_name, $taxonomy );
				$data->new_id    = $existing_term['term_id'];
				$term_info       = get_term_by( 'id', $existing_term['term_id'], $taxonomy );
				$data->parent_id = $term_info->parent;
			}
		} else {
			$data->success   = true;
			$data->new_id    = $term_object['term_id'];
			$data->parent_id = intval( $parent );
		}

		$data->new_nonce = wp_create_nonce( "btg_add_term_to_$taxonomy" );

		echo wp_json_encode( $data );
		wp_die();
	}

	public function taxonomy_table( $taxonomy ) {
		$tax_object = get_taxonomy( $taxonomy );

		printf(
		/* translators: %1$s is the URL to the bulk term generator options page, %2$s is the taxonomy slug, %3$s is the taxonomy name */
			__( '<p><strong>Hint: You can save time by <a href="%1$s?page=bulk_term_generator_options&taxonomy=%2$s">adding %3$s in bulk</a></strong></p>', 'bulk-term-generator' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_url( admin_url( 'tools.php' ) ),
			esc_attr( $taxonomy ),
			esc_attr( $tax_object->labels->name )
		);
	}

	/**
	 * Private Functions
	 */

	private function load_default_page() {
		wp_enqueue_style( $this->plugin_name . '-admin' );
		$template_path = $this->settings_page_template;
		$template      = new Bulk_Term_Generator_Template( $template_path, $this->data );

		echo $template->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function load_generate_terms_page( $taxonomy ) {
		$styles = array(
			$this->plugin_name . '-admin',
			$this->plugin_name . '-jquery-ui-css',
			'font-awesome',
		);

		$scripts = array(
			'jquery-ui-progressbar',
			'jquery-ui-dialog',
			$this->plugin_name . '-admin',
		);

		foreach ( $styles as $style ) {
			wp_enqueue_style( $style );
		}

		foreach ( $scripts as $script ) {
			wp_enqueue_script( $script );
		}

		$template_path = $this->generate_terms_page_template;

		$template = new Bulk_Term_Generator_Template( $template_path, $this->data );

		$json_list = $template->term_list( $taxonomy );

		wp_localize_script(
			'bulk-term-generator-admin',
			'btg_object',
			array(
				'btg_terms_list' => $json_list,
				'admin_url'      => admin_url( 'admin-ajax.php' ),
				'plugin_dir'     => plugins_url( '', __DIR__ ),
				'taxonomy'       => $taxonomy,
				'i18n'           => array(
					'creating'              => __( 'Creating', 'bulk-term-generator' ),
					'done'                  => __( 'Done!', 'bulk-term-generator' ),
					'name'                  => __( 'Name', 'bulk-term-generator' ),
					'slug'                  => __( 'Slug', 'bulk-term-generator' ),
					'description'           => __( 'Description' ),
					'warning_line_1'        => __( "Your terms haven't been created yet!", 'bulk-term-generator' ),
					'warning_line_2'        => __( "Click the 'Generate Terms' button at the bottom of the page before you leave.", 'bulk-term-generator' ),
					'edit_term'             => __( 'Edit Term', 'bulk-term-generator' ),
					'save'                  => __( 'Save', 'bulk-term-generator' ),
					'generating_terms'      => __( 'Generating Terms...', 'bulk-term-generator' ),
					'stop'                  => __( 'Stop', 'bulk-term-generator' ),
					'pause'                 => __( 'Pause', 'bulk-term-generator' ),
					'continue'              => __( 'Continue', 'bulk-term-generator' ),
					'no_terms_yet'          => __( 'No terms yet. Add some below!', 'bulk-term-generator' ),
					'term_added'            => __( '{0} term has been added', 'bulk-term-generator' ),
					'terms_added'           => __( '{0} terms have been added', 'bulk-term-generator' ),
					'finished_adding_terms' => __( 'Finished adding terms!', 'bulk-term-generator' ),
					'close'                 => __( 'Close', 'bulk-term-generator' ),
				),
			)
		);

		echo $template->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
