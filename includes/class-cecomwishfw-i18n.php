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
		// WordPress 4.6+ automatically loads plugin translations from the
		// WordPress.org language packs directory, so no manual call is needed.
		// Keeping the method stub for backwards compatibility with the loader.
	}
}
