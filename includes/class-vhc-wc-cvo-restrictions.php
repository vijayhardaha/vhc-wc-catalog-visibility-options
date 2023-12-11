<?php
/**
 * VHC_WC_CVO_Restrictions class
 *
 * This class manages various catalog restrictions and transients related to WooCommerce.
 *
 * @package VHC_WC_CVO_Options
 */

if ( ! class_exists( 'VHC_WC_CVO_Restrictions' ) ) {

	/**
	 * VHC_WC_CVO_Restrictions class
	 */
	class VHC_WC_CVO_Restrictions {

		/**
		 * Instance of the class.
		 *
		 * @var VHC_WC_CVO_Restrictions|null Instance of the class.
		 */
		private static $instance;

		/**
		 * Counter for clearing transients.
		 *
		 * @var int Counter for clearing transients.
		 */
		private static $transient_clear_count = 0;

		/**
		 * Maximum count for transient clearing.
		 *
		 * @var int Maximum count for transient clearing.
		 */
		private static $max_transient_clear_count = 5;

		/**
		 * Get the instance of the class.
		 *
		 * @return VHC_WC_CVO_Restrictions|null Instance of the class.
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new VHC_WC_CVO_Restrictions();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Initializes actions based on certain conditions.
			if ( ! ( is_admin() && ! defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {
				add_action( 'woocommerce_init', array( $this, 'on_init' ), 0 );
			}

			// Sets up hooks to clear transients in various scenarios.
			add_action( 'save_post', array( $this, 'clear_transients' ) );

			// Clears session transients upon certain user actions.
			add_action( 'user_register', array( $this, 'clear_session_transients' ) );
			add_action( 'wp_login', array( $this, 'clear_session_transients' ) );
			add_action( 'wp_logout', array( $this, 'clear_session_transients' ) );
		}

		/**
		 * Clears transients based on specific patterns in the option_name.
		 *
		 * @global wpdb $wpdb WordPress database access abstraction object.
		 */
		public function clear_transients() {
			global $wpdb;

			// Checks if the transient clear count is less than the maximum allowed.
			if ( self::$transient_clear_count < self::$max_transient_clear_count ) {
				// Deletes options from the database matching specific patterns related to transients.
				// phpcs:disable WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_related%'" );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_loop%'" );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_product_loop%'" );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_product_query%'" );

				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_twccr%'" );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_twccr%'" );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery

				// Flushes the cache to ensure consistency after deleting transients.
				wp_cache_flush();
				self::$transient_clear_count++; // Increments the transient clear count.
			}
		}

		/**
		 * Clears session-related transients.
		 *
		 * @global wpdb $wpdb WordPress database access abstraction object.
		 */
		public function clear_session_transients() {
			global $wpdb;

			// Deletes session-related options from the database based on specific patterns in the option_name.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_loop%'" );
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_product_loop%'" );
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_product_query%'" );
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_twccr%'" );
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_twccr%'" );

			$session = WC()->session;

			// Checks and handles the session data if WooCommerce session is set.
			if ( isset( $session ) ) {
				$session_id = WC()->session->get_customer_id();

				// Deletes session-related options associated with the current session ID.
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_twccr_" . $session_id . "%'" );
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_twccr_" . $session_id . "%'" );

				wp_cache_flush(); // Flushes the cache to ensure consistency after deleting session-related transients.
			}
			// phpcs:enable WordPress.DB.DirectDatabaseQuery
		}

		/**
		 * Initializes catalog restrictions when WooCommerce is initialized.
		 */
		public function on_init() {
			// Checks if WooCommerce is not available.
			if ( ! WC() ) {
				return;
			}

			// Allows editors to bypass restrictions if the corresponding filter allows it and the user can edit posts.
			if ( apply_filters( 'woocommerce_catalog_restrictions_allow_editors', false ) && current_user_can( 'edit_posts' ) ) {
				return;
			}

			// Requires the class responsible for applying catalog restriction filters and initializes its instance.
			require 'class-vhc-wc-cvo-restrictions-filters.php';
			VHC_WC_CVO_Restrictions_Filters::instance();
		}

		/**
		 * Retrieves the URL of the plugin directory.
		 *
		 * @return string The URL of the plugin directory.
		 */
		public function plugin_url() {
			return plugin_dir_url( __FILE__ );
		}

		/**
		 * Retrieves the path of the plugin directory without a trailing slash.
		 *
		 * @return string The path of the plugin directory.
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Retrieves the value of a setting from the options table.
		 *
		 * @param string $key      The setting key.
		 * @param mixed  $default  Optional. The default value if the setting doesn't exist. Default is null.
		 *
		 * @return mixed The value of the setting, or the default value if the setting doesn't exist.
		 */
		public function get_setting( $key, $default = null ) {
			return get_option( $key, $default );
		}
	}
}

/**
 * Returns an instance of VHC_WC_CVO_Restrictions.
 *
 * @return VHC_WC_CVO_Restrictions An instance of the VHC_WC_CVO_Restrictions class.
 */
function vhc_wc_cvo_restrictions() {
	return VHC_WC_CVO_Restrictions::instance();
}

// Store an instance of VHC_WC_CVO_Restrictions in the global variable for accessibility.
$GLOBALS['vhc_wc_cvo_restrictions'] = VHC_WC_CVO_Restrictions();
