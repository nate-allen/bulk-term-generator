<?php

class Bulk_Term_Generator_Template {

    private $file_path;
    private $data;
    private $template;

    /**
     * Constructor
     *
     * This runs when the object is instantiated.
     *
     * @param    string    $file_path    File path to the template file
     * @param    array     $data         Optional. Associative array. Key is variable name,
     *                                   value is its value
     */
    public function __construct( $file_path, $data = null ) {

        $this->file_path = $file_path;
        $this->data      = $data;

    }

    /**
     * Render
     *
     * Combines the HTML template with the PHP data.
     *
     * @return    string    The rendered template HTML
     *
     */
    public function render() {

        // Check if any data was passed
        ( $this->data ) ? extract( $this->data ) : null;

        ob_start();
        include ( $this->file_path );
        $this->template = ob_get_contents();
        ob_end_clean();

        return $this->template;

    }

    /**
     * Get Template
     *
     * Returns the rendered HTML template.
     *
     * @return    string    HTML template
     */
    public function get_template() {

        return $this->template;

    }

    /**
     * Add data
     *
     * If data needs to be added after instantiation, it can be passed here before
     * rendering the template.
     *
     * @param    array    $data    The data to be added. Key is variable name, value is its value
     */
    public function add_data( $data ) {

        ( is_array($this->data) ) ? array_merge( $this->data, $data ) : $this->data = $data;

    }

    /**
     * Taxonomy Select List
     *
     * Generates and returns HTML for a select list of taxonomies.
     *
     * @return   string    HTML select list of taxonomies
     */
    public function taxonomy_select_list( $args = array() ) {

        // Setup default options
        $defaults = array(
            'id' => 'taxonomy-list',
            'class' => 'select'
        );

        // Combine default options with passed arguments
        $options = array_merge($defaults, $args);

        // Get all of the taxonomies
        $all_taxonomies = get_taxonomies( array(), 'objects');

        // List the taxonmies we don't add terms to
        $ignored_taxonomies = array( 'nav_menu', 'link_category', 'post_format' );

        // Filter the terms to remove the ignored ones
        $taxonomies = array_diff_key( $all_taxonomies, array_flip( $ignored_taxonomies ) );

        // Start building the select list HTML
        $html = '<select id="'.$options['id'].'" class="'.$options['class'].'">';

        // If there are no taxonomies (which is rare), return an empty select list.
        if ( empty($taxonomies) ) {
            return $html .= '<option> -- No Taxonomies Available -- </option></select>';
        } else {
            $html .= '<option></option>';
        }

        // Loop over taxonomies and create an option for each
        foreach ($taxonomies as $taxonomy) {
            $html .= '<option value="'.$taxonomy->name.'">'.$taxonomy->labels->name.'</option>';
        }

        $html .= '</select>';

        return $html;

    }


}