<?php

/**
 * The public-facing functionality of the plugin.
 */

class Bulk_Term_Generator_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {

        wp_register_style( $this->plugin_name.'-public', plugin_dir_url( dirname(__FILE__) ) . 'views/public/css/bulk-term-generator-public.css', array(), $this->version, 'all' );

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_scripts() {

        wp_register_script( $this->plugin_name.'-public', plugin_dir_url( dirname(__FILE__) ) . 'views/public/js/bulk-term-generator-public.js', array( 'jquery' ), $this->version, false );

    }

}