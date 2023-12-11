<?php
/**
 * WC Dependency Checker
 *
 * Checks if WooCommerce is enabled.
 *
 * @package VHC_WC_CVO_Options
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * WC Dependency Checker
 */
class WC_Dependencies {

	/**
	 * Holds an array of active plugins.
	 *
	 * @var array $active_plugins An array containing active plugin paths.
	 */
	private static $active_plugins;

	/**
	 * Initializes the class by fetching active plugins.
	 *
	 * @return void
	 */
	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @return bool Returns true if WooCommerce is active, otherwise false.
	 */
	public static function woocommerce_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'woocommerce/woocommerce.php', self::$active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins );
	}
}
