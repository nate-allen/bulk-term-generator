<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Bulk_Term_Generator_Admin {

    /**
     * Template Paths
     */
    private $settings_page_template;

    /**
     * The ID of this plugin.
     *
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->settings_page_template = 'views/admin/templates/settings_page_default.php';
        $this->plugin_name            = $plugin_name;
        $this->version                = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( dirname(__FILE__) ) . 'views/admin/css/bulk-term-generator-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( dirname(__FILE__) ) . 'views/admin/css/bulk-term-generator-admin.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Add Bulk Term Generator to the Admin Menu under Tools
     */
    public function add_to_menu() {

        add_submenu_page( 'tools.php', 'Bulk Term Generator', 'Bulk Term Generator', 'manage_options', 'bulk_term_generator_options', array($this, 'options_page') );

    }

    public function options_page() {

        $template_path = BULK_TERM_GENERATOR_PATH . $this->settings_page_template;

        $template = new Bulk_Term_Generator_Template( $template_path );

        // Generate taxonomy select list
        $taxonomy_select_list = array(
            'taxonomy_select_list' => $template->taxonomy_select_list()
        );

        // Add the taxonomy select list to the template
        $template->add_data( $taxonomy_select_list );

        echo $template->render();

    }

}
