<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Bulk_Term_Generator_Admin {

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

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bulk-term-generator-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bulk-term-generator-admin.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Add Bulk Term Generator to the Admin Menu under Tools
     */
    public function add_to_menu() {

        add_submenu_page( 'tools.php', 'Bulk Term Generator', 'Bulk Term Generator', 'manage_options', 'bulk_term_generator_options', array($this, 'options_page') );

    }

    public function options_page() {

        $html  = '<div class="wrap">';
        $html .=    '<h2>Bulk Term Generator</h2>';
        $html .=    '<form action="options.php" method="post">';
        $html .=    '</form>';
        $html .= '</div>';

        echo $html;

    }

}
