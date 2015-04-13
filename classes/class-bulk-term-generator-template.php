<?php

class Bulk_Term_Generator_Template {

    private $file_path;
    private $data;
    private $template;
    private $select_options = '';
    private $list_items = '';

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
        $this->pre_proccess_data();

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
     * Pre-process Data
     *
     * Loop through the data array and see if any of the values match the
     * function names in this class (in the approved list). If it does, run that function and
     * add the output to the data array.
     *
     * For example, if the data array has a value of 'taxonomy_select_list', we need to run that
     * function and add the resulting html to the template data.
     */
    private function pre_proccess_data() {

        $functions = array( 'taxonomy_select_list', 'term_select_list', 'html_list' );

        foreach ($this->data as $key => $value) {
            if (!is_array($value))
                continue;
            if ( in_array(key($value), $functions, true) ){
                $array = array_values($value);
                $this->data[$key] = $this->{key($value)}( array_shift($array) );
            }
        }

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

        ( is_array($this->data) && !empty($this->data) ) ? $this->data = array_merge( $data, $this->data ) : $this->data = $data;

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
        $html = '<select id="'.$options['id'].'" name="'.$options['id'].'" class="'.$options['class'].'">';

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

    public function term_select_list( $args = array() ) {

        // Setup default options
        $defaults = array(
            'taxonomy' => 'category',
            'id' => 'taxonomy-list',
            'class' => 'select',
            'value' => 'term_id'
        );

        // Combine default options with passed arguments
        $options = array_merge($defaults, $args);

        // Get all of the terms for the given taxonomy
        $terms = get_terms( $options['taxonomy'], array( 'hide_empty' => false, 'parent' => 0 ) );

        // Start building the select list HTML
        $html  = '<select id="'.$options['id'].'" name="'.$options['id'].'" class="'.$options['class'].'">';
        $html .= '<option></option>';

        // Reset the selection options variable
        $this->select_options = '';

        foreach ($terms as $term) {
            $this->get_select_options( $options['taxonomy'], $term );
        }

        $html .= $this->select_options;

        $html .= "</select>";

        return $html;

    }

    /**
     * HTML List
     *
     * Generates an unorderd or ordered HTML list
     *
     * @param     array    $args    List of options
     * @return    string            Unordered/ordered list (html)
     */
    public function html_list( $args = array() ) {

        // Setup default options
        $defaults = array(
            'id' => '',
            'class' => '',
            'taxonomy' => 'category',
            'list_type' => 'ul'
        );

        // Combine default options with passed arguments
        $options = array_merge($defaults, $args);

        // Get all of the terms for the given taxonomy
        $terms = get_terms( $options['taxonomy'], array( 'hide_empty' => false, 'parent' => 0 ) );

        $html  = '<'.$options['list_type'];
        $html .= ( $options['id'] != '' ) ? ' id="'.$options['id'].'"' : '';
        $html .= ( $options['class'] != '' ) ? ' class="'.$options['class'].'">' : '>';

        // Reset the selection options variable
        $this->select_options = '';

        foreach ($terms as $term) {
            $this->get_list_items( $options['taxonomy'], $term, $options['list_type'] );
        }

        $html .= $this->list_items.'</ul>';

        return $html;

    }

    /**
     * Get Seperators
     *
     * Will return seperators for each nested level the term is under
     *
     * @param  int       $term_id     The term's ID
     * @param  string    $taxonomy    The taxonomy the term belongs to
     * @return string                 A seperator for each level
     */
    private function get_seperators( $term_id, $taxonomy, $seperator  = '&#8212;' ){

        $seperators = '';
        $term = get_term( $term_id, $taxonomy );

        while( $term->parent != 0 ){
            $term = get_term( $term->parent, $taxonomy );
            $seperators .= $seperator;
        }

        return $seperators;
    }

    /**
     * Get Select Options
     *
     * Recursive function that generates the HTML options for each term. The HTML
     * is stored in the private variable "$select_options" so it can be accessed
     * after its done.
     *
     * @param  string    $taxonomy The taxonomy slug
     * @param  object    $term     The term object
     */
    private function get_select_options( $taxonomy, $term ){

        $this->select_options .= '<option value="'.$term->term_id.'" data-parent="'.$term->parent.'" data-name="'.$term->name.'">'.$this->get_seperators($term->term_id,$taxonomy).$term->name.'</option>';

        $children = get_terms( $taxonomy, array('parent' => $term->term_id, 'hide_empty' => '0'));

        if (!empty($children)) {
            foreach ($children as $child ) {
                $this->get_select_options( $taxonomy, $child);
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
     * @param  string    $taxonomy The taxonomy slug
     * @param  object    $term     The term object
     */
    private function get_list_items( $taxonomy, $term, $ul ){

        $children = get_terms( $taxonomy, array('parent' => $term->term_id, 'hide_empty' => '0'));

        if (!empty($children)) {

            $this->list_items .= '<li>'.$term->name.'<'.$ul.'>';

            foreach ($children as $child ) {
                $this->get_list_items( $taxonomy, $child, $ul);
            }

            $this->list_items .= '</'.$ul.'></li>';

        } else {

            $this->list_items .= '<li>'.$term->name.'</li>';

        }

    }

}