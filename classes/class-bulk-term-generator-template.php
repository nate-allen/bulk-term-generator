<?php

class Bulk_Term_Generator_Template {

	/**
	 * The path to the template file
	 *
	 * @var string
	 */
	private string $file_path;

	/**
	 * The data to be passed to the template
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * The template HTML
	 *
	 * @var string
	 */
	private string $template;

	/**
	 * The HTML for the select list of taxonomies
	 *
	 * @var string
	 */
	private string $select_options = '';

	/**
	 * The HTML for the select list of terms
	 *
	 * @var string
	 */
	private string $list_items = '';

	/**
	 * An array of terms
	 *
	 * @var array
	 */
	private array $terms_array = array();

	/**
	 * Constructor
	 *
	 * This runs when the object is instantiated.
	 *
	 * @param string $file_path File path to the template file
	 * @param array  $data      Optional. Associative array. Key is variable name, value is its value
	 */
	public function __construct( string $file_path, array $data = array() ) {
		$this->file_path = $file_path;
		$this->data      = $data;

		$this->pre_process_data();
	}

	/**
	 * Render
	 *
	 * Combines the HTML template with the PHP data.
	 *
	 * @return string The rendered template HTML
	 */
	public function render(): string {
		if ( $this->data ) {
			extract( $this->data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		ob_start();
		include $this->file_path;
		$this->template = ob_get_contents();
		ob_end_clean();

		return $this->template;
	}

	/**
	 * Get Template
	 *
	 * Returns the rendered HTML template.
	 *
	 * @return string HTML template
	 */
	public function get_template(): string {
		return $this->template;
	}

	/**
	 * Pre-process Data
	 *
	 * Loop through the data array and see if any of the values match the
	 * function names in this class (in the approved list). If it does, run that function and
	 * add the output to the data array.
	 *
	 * For example, if the data array has a value of 'taxonomy_select_list', we need to run that
	 * function and add the resulting html to the template data.
	 */
	private function pre_process_data() {
		$functions = array( 'taxonomy_select_list', 'term_select_list', 'html_list' );

		foreach ( $this->data as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}
			if ( in_array( key( $value ), $functions, true ) ) {
				$array              = array_values( $value );
				$this->data[ $key ] = $this->{key( $value )}( array_shift( $array ) );
			}
		}
	}

	/**
	 * Add data
	 *
	 * If data needs to be added after instantiation, it can be passed here before
	 * rendering the template.
	 *
	 * @param array $data The data to be added. Key is variable name, value is its value
	 */
	public function add_data( array $data ) {
		! empty( $this->data ) ? $this->data = array_merge( $data, $this->data ) : $this->data = $data;
	}

	/**
	 * Taxonomy Select List
	 *
	 * Generates and returns HTML for a select list of taxonomies.
	 *
	 * @return string HTML select list of taxonomies
	 */
	public function taxonomy_select_list( $args = array() ): string {
		// Setup default options.
		$defaults = array(
			'id'    => 'taxonomy-list',
			'class' => 'select',
		);

		// Combine default options with passed arguments.
		$options = array_merge( $defaults, $args );

		// Get all of the taxonomies.
		$all_taxonomies = get_taxonomies( array(), 'objects' );

		// List the taxonomies we don't add terms to.
		$ignored_taxonomies = array( 'nav_menu', 'link_category', 'post_format' );

		// Filter the terms to remove the ignored ones.
		$taxonomies = array_diff_key( $all_taxonomies, array_flip( $ignored_taxonomies ) );

		// Start building the select list HTML.
		$html = '<select id="' . $options['id'] . '" name="' . $options['id'] . '" class="' . $options['class'] . '">';

		$label = empty( $taxonomies ) ? esc_html__( '-- No Taxonomies Available --', 'bulk-term-generator' ) : '';

		$html .= '<option value="">' . $label . '</option>';

		// Loop over taxonomies and create an option for each
		foreach ( $taxonomies as $taxonomy ) {
			$html .= '<option value="' . $taxonomy->name . '">' . $taxonomy->labels->name . '</option>';
		}

		$html .= '</select>';

		return $html;
	}

	/**
	 * Term Select List
	 *
	 * Generates and returns HTML for a select list of terms.
	 *
	 * @return string HTML select list of terms
	 */
	public function term_select_list( $args = array() ): string {
		// Setup default options.
		$defaults = array(
			'taxonomy' => 'category',
			'id'       => 'taxonomy-list',
			'class'    => 'select',
			'value'    => 'term_id',
		);

		// Combine default options with passed arguments.
		$options = array_merge( $defaults, $args );

		// Get all of the terms for the given taxonomy
		$terms = get_terms(
			array(
				'hide_empty' => false,
				'parent'     => 0,
				'taxonomy'   => $options['taxonomy'],
			)
		);

		// Start building the select list HTML.
		$html = '<select id="' . $options['id'] . '" name="' . $options['id'] . '" class="' . $options['class'] . '">';

		// Reset the selection options variable
		$this->select_options = '<option value=""></option>';

		foreach ( $terms as $term ) {
			$this->get_select_options( $options['taxonomy'], $term );
		}


		$html .= $this->select_options;

		$html .= '</select>';

		return $html;
	}

	/**
	 * HTML List
	 *
	 * Generates an unordered or ordered HTML list
	 *
	 * @param array $args List of options
	 *
	 * @return string Unordered/ordered list (html)
	 */
	public function html_list( array $args = array() ): string {
		// Setup default options.
		$defaults = array(
			'id'        => '',
			'class'     => '',
			'taxonomy'  => 'category',
			'list_type' => 'ul',
		);

		// Combine default options with passed arguments.
		$options = array_merge( $defaults, $args );

		// Get all of the terms for the given taxonomy.
		$terms = get_terms(
			array(
				'hide_empty' => false,
				'parent'     => 0,
				'taxonomy'   => $options['taxonomy'],
			)
		);

		$html  = '<' . $options['list_type'];
		$html .= ( '' !== $options['id'] ) ? ' id="' . $options['id'] . '"' : '';
		$html .= ( '' !== $options['class'] ) ? ' class="' . $options['class'] . '">' : '>';

		// Reset the selection options variable.
		$this->select_options = '';

		foreach ( $terms as $term ) {
			$this->get_list_items( $options['taxonomy'], $term, $options['list_type'] );
		}

		$html .= $this->list_items . '</ul>';

		return $html;
	}

	/**
	 * Term List
	 *
	 * Returns a list of terms
	 *
	 * @param string $taxonomy The taxonomy to get terms from
	 *
	 * @return array An array of terms
	 */
	public function term_list( string $taxonomy ): array {
		// Get all of the terms for the given taxonomy.
		$terms = get_terms(
			array(
				'hide_empty' => false,
				'parent'     => 0,
				'taxonomy'   => $taxonomy,
			)
		);

		foreach ( $terms as $term ) {
			$this->get_terms_array( $taxonomy, $term );
		}

		return $this->terms_array;
	}

	/**
	 * Get Separators
	 *
	 * Will return separators for each nested level the term is under
	 *
	 * @param int    $term_id   The term's ID
	 * @param string $taxonomy  The taxonomy the term belongs to
	 * @param string $seperator The separator to use
	 *
	 * @return string A separator for each level
	 */
	private function get_separators( int $term_id, string $taxonomy, string $seperator = '&#8212;' ): string {
		$separators = '';
		$term       = get_term( $term_id, $taxonomy );

		while ( 0 !== $term->parent ) {
			$term        = get_term( $term->parent, $taxonomy );
			$separators .= $seperator;
		}

		return $separators;
	}

	/**
	 * Get Select Options
	 *
	 * Recursive function that generates the HTML options for each term. The HTML
	 * is stored in the private variable "$select_options" so it can be accessed
	 * after its done.
	 *
	 * @param string $taxonomy The taxonomy slug
	 * @param object $term The term object
	 */
	private function get_select_options( string $taxonomy, object $term ) {
		$this->select_options .= '<option value="' . $term->term_id . '" data-parent="' . $term->parent . '" data-name="' . $term->name . '">' . $this->get_separators( $term->term_id, $taxonomy ) . $term->name . '</option>';

		$children = get_terms(
			array(
				'parent'     => $term->term_id,
				'hide_empty' => '0',
				'taxonomy'   => $taxonomy,
			)
		);

		if ( ! empty( $children ) ) {
			foreach ( $children as $child ) {
				$this->get_select_options( $taxonomy, $child );
			}
		}
	}

	/**
	 * Get List Items
	 *
	 * Recursive function that generates the HTML list items for each term. The HTML
	 * is stored in the private variable "$list_items" so it can be accessed
	 * after its done.
	 *
	 * @param string $taxonomy The taxonomy slug
	 * @param object $term The term object
	 */
	private function get_list_items( string $taxonomy, object $term, $ul ) {
		$children = get_terms(
			array(
				'parent'     => $term->term_id,
				'hide_empty' => '0',
				'taxonomy'   => $taxonomy,
			)
		);

		if ( ! empty( $children ) ) {
			$this->list_items .= '<li>' . $term->name . '<' . $ul . '>';

			foreach ( $children as $child ) {
				$this->get_list_items( $taxonomy, $child, $ul );
			}

			$this->list_items .= '</' . $ul . '></li>';
		} else {
			$this->list_items .= '<li>' . $term->name . '</li>';
		}
	}

	/**
	 * Get Terms Array
	 *
	 * Get an array of term objects containing their id, name, and parent
	 *
	 * @param string $taxonomy The taxonomy you want to get terms for
	 * @param object $term     The term object
	 *
	 * @return void An array of term objects
	 */
	private function get_terms_array( string $taxonomy, object $term ): void {
		// Get all of the terms for the given taxonomy.
		$children = get_terms(
			array(
				'hide_empty' => false,
				'parent'     => $term->term_id,
				'taxonomy'   => $taxonomy,
			)
		);

		$term_object         = new stdClass();
		$term_object->id     = $term->term_id;
		$term_object->name   = $term->name;
		$term_object->parent = $term->parent;

		$this->terms_array[] = $term_object;

		if ( ! empty( $children ) ) {
			foreach ( $children as $child ) {
				$this->get_terms_array( $taxonomy, $child );
			}
		}
	}
}
