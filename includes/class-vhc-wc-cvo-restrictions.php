<?php
/**
 * VHC_WC_CVO_Restrictions class
 *
 * This class manages various catalog restrictions and transients related to WooCommerce.
 *
 * @package VHC_WC_CVO_Options
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

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
			require 'class-vhc-wc-cvo-restrictions-transient.php';

			// Initializes actions based on certain conditions.
			if ( ! ( is_admin() && ! defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {
				add_action( 'woocommerce_init', array( $this, 'on_init' ), 0 );
			}

			// Sets up hooks to clear transients in various scenarios.
			add_action( 'save_post', array( $this, 'clear_transients_on_save_product' ) );

			// Clears session transients upon certain user actions.
			add_action( 'user_register', array( $this, 'clear_transients' ) );
			add_action( 'wp_login', array( $this, 'clear_transients' ) );
			add_action( 'wp_logout', array( $this, 'clear_transients' ) );
		}

		/**
		 * Clear transients associated with products when a product is saved.
		 *
		 * This method checks the count of transient clears and clears transients
		 * associated with WooCommerce products if the count is within the defined limit.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function clear_transients_on_save_product( $post_id ) {
			// Check if the transient clear count is within the defined limit.
			if ( self::$transient_clear_count < self::$max_transient_clear_count ) {
				// Get the post type of the saved post.
				$post_type = get_post_type( $post_id );

				// Check if the post type is 'product' (WooCommerce product).
				if ( 'product' === $post_type ) {
					// Note - Calling this function will increment the transient clear count.
					$this->clear_transients();
					self::$transient_clear_count++; // Increments the transient clear count.
				}
			}
		}

		/**
		 * Clears transients based on specific patterns in the option_name.
		 */
		public function clear_transients() {
			VHC_WC_CVO_Restrictions_Transient::queue_delete_transients();
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
