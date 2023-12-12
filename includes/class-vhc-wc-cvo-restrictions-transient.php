<?php
/**
 * Class VHC_WC_CVO_Restrictions_Transient
 *
 * This class manages the deletion of transients associated with catalog restrictions.
 * It provides methods to queue and delete transients on WordPress shutdown.
 *
 * @package VHC_WC_CVO_Options
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class VHC_WC_CVO_Restrictions_Transient
 */
class VHC_WC_CVO_Restrictions_Transient {

	/**
	 * Flag to check if transients are already queued for deletion.
	 *
	 * @var bool $queued Tracks whether transients are queued for deletion or not.
	 */
	private static $queued = false;

	/**
	 * Queue delete transients action.
	 */
	public static function queue_delete_transients() {
		if ( self::$queued ) {
			return;
		}
		add_action( 'shutdown', array( __CLASS__, 'delete_transients_on_shutdown' ), 9999 );
		self::$queued = true;
	}

	/**
	 * Delete transients on shutdown.
	 */
	public static function delete_transients_on_shutdown() {
		global $wpdb;

		// Delete transients from the options table.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_related%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_loop%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_product_loop%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_product_query%'" );

		// Delete custom transients.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_twccr%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_twccr%'" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		// Flush the WordPress object cache.
		wp_cache_flush();
	}
}
