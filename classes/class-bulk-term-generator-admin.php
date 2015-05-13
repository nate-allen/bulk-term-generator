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
    private $settings_page_template;
    private $generate_terms_page_template;

    /**
     * The ID of this plugin.
     *
     * @var    string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var    string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Array to hold data used in templates
     *
     * @var    array    $data    Associative array. Key is variable name, value is its value
     */
    private $data = array();

    /**
     * Template file
     *
     * @var    string    File path to the template file that should be loaded
     */
    private $template;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->settings_page_template       = BULK_TERM_GENERATOR_PATH.'views/admin/templates/settings_page_default.php';
        $this->generate_terms_page_template = BULK_TERM_GENERATOR_PATH.'views/admin/templates/generate_terms_page.php';

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {

        wp_register_style( $this->plugin_name.'-admin', plugin_dir_url( dirname(__FILE__) ) . 'views/admin/css/bulk-term-generator-admin.css', array($this->plugin_name.'-jquery-ui-css', 'font-awesome'), $this->version, 'all' );
        wp_register_style( $this->plugin_name.'-jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css', array(), '1.11.3', 'all' );
        wp_register_style( 'font-awesome', plugin_dir_url( dirname(__FILE__) ) . 'views/admin/css/font-awesome.min.css', array(), '4.3.0', 'all'  );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {

        wp_register_script( $this->plugin_name.'-admin', plugin_dir_url( dirname(__FILE__) ) . 'views/admin/js/bulk-term-generator-admin.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Add Bulk Term Generator to the Admin Menu under Tools
     */
    public function add_to_menu() {

        add_submenu_page( 'tools.php', 'Bulk Term Generator', 'Bulk Term Generator', 'manage_options', 'bulk_term_generator_options', array($this, 'options_page') );

    }

    public function options_page() {

        // Normal page load, not a form submit. Load the default page
        if ( !isset( $_POST['action'] ) && !isset($_GET['taxonomy']) ) {

            $this->data['taxonomy_select_list'] = array( 'taxonomy_select_list' => array( 'id' => 'chosen_taxonomy' ) );
            $this->load_default_page();

            return;

        }

        if ( isset( $_POST['action'] ) && $_POST['action'] == 'taxonomy_selected' && empty( $_POST['chosen_taxonomy'] ) ){

            $this->data['error'] = 'Please choose a taxonomy';
            $this->data['taxonomy_select_list'] = array( 'taxonomy_select_list' => array( 'id' => 'chosen_taxonomy' ) );
            $this->load_default_page();

        }

        // If the taxonomy is in the URL parameter
        if ( isset($_GET['taxonomy']) ) {

            $taxonomy = get_taxonomy( $_GET['taxonomy'] );
            $taxonomy_slug = $_GET['taxonomy'];
            $taxonomy_name = $taxonomy->labels->name;
            $taxonomy_terms = get_terms($_GET['taxonomy'], array('hide_empty' => false));

            $this->data['is_hierarchical'] = $taxonomy->hierarchical;
            $this->data['taxonomy_slug'] = $taxonomy_slug;
            $this->data['taxonomy_name'] = $taxonomy_name;
            $this->data['terms'] = $taxonomy_terms;
            $this->data['term_list'] = array( 'html_list' => array( 'taxonomy' => $taxonomy_slug, 'id' => 'term-list' ) );
            $this->data['term_select_list'] = array( 'term_select_list' => array( 'taxonomy' => $taxonomy_slug, 'id' => 'parent_term' ) );

            $this->load_generate_terms_page($taxonomy->name);

        }

    }

    public function taxonomy_select() {

        // If the user submitted the "Choose a Taxonomy" form
        if ( isset( $_POST['action'] ) && $_POST['action'] == 'taxonomy_selected' && !empty( $_POST['chosen_taxonomy'] ) ) {

            wp_redirect( add_query_arg( array('taxonomy' => $_POST['chosen_taxonomy']), esc_url_raw($_SERVER['REQUEST_URI']) ) );exit;

        }

    }

    public function add_term() {

        $term_name = $_POST['term_name'];
        $taxonomy = $_POST['taxonomy'];
        $parent = $_POST['parent'];
        $slug = $_POST['slug'];
        $desc = $_POST['desc'];
        $data = new stdClass;

        // Check that the submitted nonce is one that was generated earlier.
        // If not, return an error message
        if ( !wp_verify_nonce( $_POST['_ajax_nonce'] , 'btg_add_term_to_'.$taxonomy) ){
            $data->success = false;
            $data->error = 'Security check failed.';
            echo json_encode($data);
            wp_die();
        }

        $args = array();

        // Build the optional arguments
        if ( isset($parent) && $parent != 0 ){
            $args['parent'] = intval($parent);
        }
        if ( isset($slug) && $slug != '' ){
            $args['slug'] = $slug;
        }
        if ( isset($desc) && $desc != '' ){
            $args['description'] = $desc;
        }

        $term_object = wp_insert_term( $term_name, $taxonomy, $args );

        if ( is_wp_error( $term_object ) ) {

            $data->success = false;
            $data->error = $term_object->get_error_code();

            // If the term exists, get its ID and parent
            if ( $term_object->get_error_code() == 'term_exists' ) {
                $existing_term = term_exists( $term_name, $taxonomy );
                $data->new_id = $existing_term['term_id'];

                $term_info = get_term_by('id', $existing_term['term_id'], $taxonomy);
                $data->parent_id = $term_info->parent;
            }

        } else {

            $data->success = true;
            $data->new_id = $term_object['term_id'];
            $data->parent_id = intval($parent);

        }

        $data->new_nonce = wp_create_nonce( 'btg_add_term_to_'.$taxonomy);

        echo json_encode($data);
        wp_die();

    }

    /**
     * Private Functions
     */

    private function load_default_page() {

        wp_enqueue_style($this->plugin_name.'-admin');
        $template_path = $this->settings_page_template;
        $template = new Bulk_Term_Generator_Template( $template_path, $this->data );

        echo $template->render();

    }

    private function load_generate_terms_page( $taxonomy ) {

        wp_enqueue_style(array($this->plugin_name.'-admin', $this->plugin_name.'-jquery-ui-css', 'font-awesome'));
        wp_enqueue_script(array('jquery-ui-progressbar', 'jquery-ui-dialog', $this->plugin_name.'-admin'));

        $template_path = $this->generate_terms_page_template;

        $template = new Bulk_Term_Generator_Template( $template_path, $this->data );

        $json_list = $template->json_list($taxonomy);

        wp_localize_script(
            'bulk-term-generator-admin',
            'btg_object',
            array(
                'btg_terms_list' => $json_list,
                'admin_url' => admin_url( 'admin-ajax.php' ),
                'plugin_dir' => plugins_url('', dirname(__FILE__)),
                'taxonomy' => $taxonomy
            )
        );

        echo $template->render();

    }

}