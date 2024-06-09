<?php
/**
 * Loads and defines the internationalization files for this plugin
 */

namespace BulkTermGenerator;

class I18n {

	/**
	 * The domain specified for this plugin.
	 */
	private string $domain;

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->domain,
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);
	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @param string $domain The domain that represents the locale of this plugin.
	 */
	public function set_domain( string $domain ) {
		$this->domain = $domain;
	}
}
