<?php
/**
 * Plugin Name: VHC WooCommerce Catalog Visibility Options
 * Plugin URI: https://github.com/vijayhardaha/vhc-wc-catalog-visibility-options
 * Description: Provides the ability to hide prices, or show prices only to authenticated users. Provides the ability to disable e-commerce functionality by disabling the cart.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha/
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vhc-wc-cvo
 * Domain Path: /languages/
 * Requires at least: 5.8
 * Requires PHP: 7.0
 * Tested up to: 6.0
 *
 * @package VHC_WC_CVO_Options
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

require_once 'woo-includes/woo-functions.php';

if ( is_woocommerce_active() ) {
	load_plugin_textdomain( 'vhc-wc-cvo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Initialize the Catalog Restrictions included plugin.
	require 'includes/class-vhc-wc-cvo-restrictions.php';

	define( 'VHC_WC_CVO_OPTIONS_VERSION', '1.0.0' );

	/**
	 * Class handling WooCommerce Catalog Visibility Options.
	 */
	class VHC_WC_CVO_Options {

		/**
		 * Constructor.
		 */
		public function __construct() {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->current_tab   = ( isset( $_GET['tab'] ) ) ? sanitize_key( $_GET['tab'] ) : 'general';
			$this->settings_tabs = array(
				'visibility_options' => __( 'Visibility Options', 'vhc-wc-cvo' ),
			);

			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'on_add_tab_array' ), 50 );

			// Run actions when generating the settings tabs.
			foreach ( $this->settings_tabs as $name => $label ) {
				add_action( 'woocommerce_settings_tabs_' . $name, array( $this, 'settings_tab_action' ), 10 );
				add_action( 'woocommerce_update_options_' . $name, array( $this, 'save_settings' ), 10 );
			}

			// Add the settings fields to each tab.
			add_action( 'woocommerce_visibility_options_settings', array( $this, 'add_settings_fields' ), 10 );
			add_action( 'woocommerce_admin_field_tinyeditor', array( $this, 'on_editor_field' ) );
		}

		/**
		 * Add a tab to WooCommerce settings.
		 *
		 * @param array $settings_tabs Array of WooCommerce settings tabs.
		 * @return array
		 */
		public function on_add_tab_array( $settings_tabs ) {
			$settings_tabs['visibility_options'] = __( 'Visibility Options', 'vhc-wc-cvo' );
			return $settings_tabs;
		}

		/**
		 * Execute actions when viewing the custom settings tab.
		 *
		 * @return void
		 */
		public function settings_tab_action() {
			global $woocommerce_settings;

			// Determine the current tab being viewed.
			$current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_settings_tabs_' );

			// Trigger an action for visibility options settings.
			do_action( 'woocommerce_visibility_options_settings' );

			// Display settings specific to the current tab.
			woocommerce_admin_fields( $woocommerce_settings[ $current_tab ] );
		}

		/**
		 * Add settings fields for each tab.
		 *
		 * @return void
		 */
		public function add_settings_fields() {
			global $woocommerce_settings;

			// Load the prepared form fields.
			$this->init_form_fields();

			// Check if fields are an array and assign them to WooCommerce settings.
			if ( is_array( $this->fields ) ) {
				foreach ( $this->fields as $key => $value ) {
					$woocommerce_settings[ $key ] = $value;
				}
			}
		}

		/**
		 * Get the tab currently being viewed/processed.
		 *
		 * @param string $current_filter The current filter being processed.
		 * @param string $filter_base    The base of the filter to extract the tab.
		 *
		 * @return string               The current tab in view.
		 */
		private function get_tab_in_view( $current_filter, $filter_base ) {
			return str_replace( $filter_base, '', $current_filter );
		}

		/**
		 * Prepare form fields to be used in the various tabs.
		 *
		 * @return void
		 */
		private function init_form_fields() {
			// Define form fields for visibility options.
			$v1 = apply_filters(
				'woocommerce_visibility_options_settings_fields',
				array(
					array(
						'name' => __( 'Shopping', 'vhc-wc-cvo' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'visibility_options_add-to-cart',
					),
					array(
						'name'    => __( 'Purchases', 'vhc-wc-cvo' ),
						'desc'    => '',
						'id'      => 'wc_cvo_atc',
						'type'    => 'select',
						'std'     => 'enabled',
						'class'   => 'chosen_select',
						'options' => array(
							'enabled'  => 'Enabled',
							'disabled' => 'Disabled',
							'secured'  => 'Enabled for Logged In Users',
						),
					),
					array(
						'name'    => __( 'Prices', 'vhc-wc-cvo' ),
						'desc'    => '',
						'id'      => 'wc_cvo_prices',
						'type'    => 'select',
						'std'     => 'enabled',
						'class'   => 'chosen_select',
						'options' => array(
							'enabled'  => 'Enabled',
							'disabled' => 'Disabled',
							'secured'  => 'Enabled for Logged In Users',
						),
					),
					array(
						'name' => __( 'Catalog Add to Cart Button Text', 'vhc-wc-cvo' ),
						'type' => 'text',
						'desc' => '',
						'css'  => 'min-width:500px;',
						'desc' => '',
						'id'   => 'wc_cvo_atc_text',
					),
					array(
						'name' => __( 'Catalog Price Text', 'vhc-wc-cvo' ),
						'type' => 'text',
						'desc' => '',
						'css'  => 'min-width:500px;',
						'std'  => '',
						'id'   => 'wc_cvo_c_price_text',
					),
					array(
						'name' => __( 'Alternate Content', 'vhc-wc-cvo' ),
						'type' => 'tinyeditor',
						'desc' => '',
						'id'   => 'wc_cvo_s_price_text',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'visibility_options_prices',
					),
				)
			);

			$this->fields['visibility_options'] = $v1;
		}

		/**
		 * Save settings in a single field in the database for each tab's fields (one field per tab).
		 *
		 * @return void
		 */
		public function save_settings() {
			global $woocommerce_settings;

			// Ensure recognition of settings fields.
			$this->add_settings_fields();

			// Get the current tab being processed.
			$current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_update_options_' );

			// Update options based on the current tab's settings.
			woocommerce_update_options( $woocommerce_settings[ $current_tab ] );

			// Prevent HTML from being stripped until the WC settings API supports custom field saving.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['wc_cvo_s_price_text'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data = wp_kses_post( wp_unslash( $_POST['wc_cvo_s_price_text'] ) );
				update_option( 'wc_cvo_s_price_text', $data );
			}
		}

		/**
		 * Gets a setting based on the provided key.
		 *
		 * @param string $key     The key for the setting.
		 * @param mixed  $default (Optional) The default value if the setting is not found.
		 *
		 * @return mixed The value of the setting if found, otherwise the provided default value.
		 */
		public function setting( $key, $default = null ) {
			return get_option( $key, $default );
		}

		/**
		 * Generate a custom admin field: editor
		 *
		 * @param array $value The value containing information about the field.
		 *
		 * @return void
		 */
		public function on_editor_field( $value ) {
			$content = get_option( $value['id'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc"><?php echo esc_html( $value['name'] ); ?></th>
				<td class="forminp">
					<?php wp_editor( $content, $value['id'] ); ?>
				</td>
			</tr>
			<?php
		}

		/**
		 * Retrieve the path to this plugin's directory.
		 *
		 * @return string The directory path to this plugin.
		 */
		public function plugin_dir() {
			return plugin_dir_path( __FILE__ );
		}
	}

	// Declare $wc_cvo as a global variable to be accessible outside the current scope.
	global $wc_cvo;

	// Create an instance of the VHC_WC_CVO_Options class and assign it to $wc_cvo.
	$wc_cvo = new VHC_WC_CVO_Options();
}

/**
 * Configure default options on plugin activation.
 */
function vhc_wc_cvo_activate() {
	// Set default option for 'wc_cvo_atc' if not already set.
	if ( ! get_option( 'wc_cvo_atc' ) ) {
		update_option( 'wc_cvo_atc', 'enabled' );
	}

	// Set default option for 'wc_cvo_prices' if not already set.
	if ( ! get_option( 'wc_cvo_prices' ) ) {
		update_option( 'wc_cvo_prices', 'enabled' );
	}
}
register_activation_hook( __FILE__, 'vhc_wc_cvo_activate' );

/**
 * Check if a user has access (logged in).
 *
 * @return bool True if the user is logged in, otherwise false.
 */
function vhc_wc_cvo_user_has_access() {
	return apply_filters( 'vhc_wc_cvo_user_has_access', is_user_logged_in() );
}
