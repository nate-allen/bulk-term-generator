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

        wp_register_style( $this->plugin_name.'-admin', plugin_dir_url( dirname(__FILE__) ) . 'views/admin/css/bulk-term-generator-admin.css', array(), $this->version, 'all' );

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

        // If the user submitted the "Choose a Taxonomy" form
        if ( isset( $_POST['action'] ) && $_POST['action'] == 'taxonomy_selected' ) {

            if ( empty( $_POST['chosen_taxonomy'] ) ){

                $this->data['error'] = 'Please choose a taxonomy';
                $this->data['taxonomy_select_list'] = array( 'taxonomy_select_list' => array( 'id' => 'chosen_taxonomy' ) );
                $this->load_default_page();

            }
            // If the user did choose a taxonomy, add it to the URL parameter
            else {

                wp_redirect( add_query_arg( 'taxonomy', $_POST['chosen_taxonomy'] ) );exit;

            }

        }

    }

    private function load_default_page() {

        wp_enqueue_style('bulk-term-generator-admin');
        $template_path = $this->settings_page_template;
        $template = new Bulk_Term_Generator_Template( $template_path, $this->data );

        echo $template->render();

    }

    private function load_generate_terms_page( $taxonomy ) {

        wp_enqueue_style('bulk-term-generator-admin');
        wp_enqueue_script('bulk-term-generator-admin');

        $template_path = $this->generate_terms_page_template;

        $template = new Bulk_Term_Generator_Template( $template_path, $this->data );

        $json_list = $template->json_list($taxonomy);

        wp_localize_script( 'bulk-term-generator-admin', 'btg_terms_list',  $json_list);

        echo $template->render();

    }

}
