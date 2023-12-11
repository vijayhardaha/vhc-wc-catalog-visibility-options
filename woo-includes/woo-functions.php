<?php
/**
 * Functions used by plugins
 *
 * @package VHC_WC_CVO_Options
 */

if ( ! class_exists( 'WC_Dependencies' ) ) {
	require_once 'class-wc-dependencies.php'; // Include the class file if it doesn't exist.
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool Whether WooCommerce is active or not.
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool Whether WooCommerce is active or not.
	 */
	function is_woocommerce_active() {
		return WC_Dependencies::woocommerce_active_check(); // Check if WooCommerce is active.
	}
}
