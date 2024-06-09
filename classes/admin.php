<?php
/**
 * The admin-specific functionality of the plugin.
 */

namespace BulkTermGenerator;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {

	/**
	 * Template Paths
	 */
	private string $admin_page_template;

	/**
	 * The ID of this plugin.
	 *
	 * @var string $plugin_name The name of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version The current version of this plugin.
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->admin_page_template = BULK_TERM_GENERATOR_PATH . 'views/admin-page.php';

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
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
				'load_page',
			)
		);
	}

	/**
	 * Changes the title of the plugin on the plugins page
	 *
	 * @param array $plugins Array of plugins
	 *
	 * @return array Modified array of plugins
	 */
	public function modify_plugin_title( $plugins ) {
		if ( isset( $plugins['bulk-term-generator/bulk-term-generator.php'] ) ) {
			$plugins['bulk-term-generator/bulk-term-generator.php']['Name'] = esc_html__( 'Bulk Term Generator', 'bulk-term-generator' );
		}

		return $plugins;
	}

	public function add_term() {
		$term_name = $_POST['term_name']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$taxonomy  = $_POST['taxonomy']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parent    = $_POST['parent']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$slug      = $_POST['slug']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$desc      = $_POST['desc']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data      = new \stdClass();

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

	public function load_page() {
		wp_enqueue_style(
			$this->plugin_name,
			BULK_TERM_GENERATOR_URL . 'build/index.css',
			array( 'wp-components' ),
			$this->version
		);
		wp_enqueue_script(
			$this->plugin_name,
			BULK_TERM_GENERATOR_URL . 'build/index.js',
			array(
				'wp-api-fetch',
				'wp-components',
				'wp-element',
				'wp-i18n',
				'react',
				'react-dom',
				'wp-data-controls',
				'wp-data',
			),
			$this->version,
			true
		);
		$template_path = $this->admin_page_template;
		$template      = new Template( $template_path, array( 'version' => $this->version ) );

		echo $template->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
