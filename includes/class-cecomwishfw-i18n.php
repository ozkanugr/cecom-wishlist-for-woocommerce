<?php
/**
 * Defines internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so
 * it is ready for translation.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_I18n
 */
class Cecomwishfw_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'cecom-wishlist-for-woocommerce',
			false,
			dirname( plugin_basename( CECOMWISHFW_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
