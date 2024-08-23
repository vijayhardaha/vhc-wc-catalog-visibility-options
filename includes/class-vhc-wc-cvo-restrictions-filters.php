<?php
/**
 * Class VHC_WC_CVO_Restrictions_Filters
 *
 * Manages filters for catalog restrictions in WooCommerce.
 *
 * @package VHC_WC_CVO_Options
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class VHC_WC_CVO_Restrictions_Filters
 */
class VHC_WC_CVO_Restrictions_Filters {

	/**
	 * Singleton instance of this class.
	 *
	 * @var VHC_WC_CVO_Restrictions_Filters|null $instance Singleton instance of this class.
	 */
	private static $instance;

	/**
	 * Get an instance of the VHC_WC_CVO_Restrictions_Filters class.
	 *
	 * @return VHC_WC_CVO_Restrictions_Filters Singleton instance.
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new VHC_WC_CVO_Restrictions_Filters();
		}

		return self::$instance;
	}

	/**
	 * Cache for whether a user can purchase products.
	 *
	 * @var array $cache_can_purchase Cache for whether a user can purchase products.
	 */
	private $cache_can_purchase = array();

	/**
	 * Cache for whether a user can view product prices.
	 *
	 * @var array $cache_can_view_prices Cache for whether a user can view product prices.
	 */
	private $cache_can_view_prices = array();

	/**
	 * Flag to indicate buffer state.
	 *
	 * @var bool $buffer_on Flag to indicate buffer state.
	 */
	public $buffer_on = false;

	/**
	 * Flag to indicate after cart button action.
	 *
	 * @var bool $did_after_cart_button Flag to indicate after cart button action.
	 */
	public $did_after_cart_button = false;

	/**
	 * VHC_WC_CVO_Restrictions_Filters constructor.
	 *
	 * Initializes and configures various filters and actions related to catalog restrictions in WooCommerce.
	 * Sets up filters for product prices, orders, single product actions, late bindings, compatibility, and version-specific adjustments.
	 */
	public function __construct() {
		// Product Price Filters.
		add_filter( 'woocommerce_get_price_html', array( $this, 'on_price_html' ), 99, 2 );
		add_filter( 'woocommerce_variable_subscription_price_html', array( $this, 'on_price_html' ), 100, 2 );
		add_filter( 'woocommerce_sale_flash', array( $this, 'on_sale_flash' ), 99, 3 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'on_cart_item_price' ), 999, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'on_cart_item_subtotal' ), 999, 2 );
		add_filter( 'woocommerce_cart_subtotal', array( $this, 'on_cart_subtotal' ), 9999, 2 );
		add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'on_cart_total' ), 9999 );

		// Order Filters.
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'on_order_formatted_line_subtotal' ), 10, 2 );

		// Actions on Single Product.
		add_action( 'woocommerce_after_single_product', array( $this, 'on_woocommerce_after_single_product_bind' ), 9 );
		add_action( 'woocommerce_after_single_product', array( $this, 'on_woocommerce_after_single_product_unbind' ), 11 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'handle_product_paypal_button' ), 0 );

		// Late Binding and Compatibility.
		add_action( 'woocommerce_init', array( $this, 'bind_filters_late' ), 99 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'on_woocommerce_add_to_cart_validation' ), 10, 2 );
		add_filter( 'woocommerce_bv_render_form', array( $this, 'on_woocommerce_bv_render_form' ), 99, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'on_woocommerce_product_add_to_cart_url' ), 99, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'on_loop_add_to_cart_link' ), 99, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'on_woocommerce_product_add_to_cart_text' ), 99, 2 );
		add_action( 'template_redirect', array( $this, 'plugin_compatibility_filters' ), 11 );

		// Compatibility for Different Versions.
		add_filter( 'woocommerce_available_variation', array( $this, 'on_get_woocommerce_available_variation' ), 10, 3 );
		add_filter( 'wc_get_template', array( $this, 'on_get_variation_template' ), 99, 2 );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'on_get_woocommerce_structured_data_product' ), 10, 2 );
		add_action( 'woocommerce_email_order_details', array( $this, 'on_email_order_details' ), 10, 1 );
	}

	/**
	 * Binds specific filters late in the WooCommerce lifecycle.
	 */
	public function bind_filters_late() {
		add_action( 'woocommerce_before_single_variation', array( $this, 'on_before_single_variation' ), 0 );
		add_action( 'woocommerce_after_single_variation', array( $this, 'on_after_single_variation' ), 998 );

		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'on_before_add_to_cart_form' ), 0 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'on_after_add_to_cart_form' ), 998 );
	}

	/**
	 * Removes specific filters from email order details.
	 */
	public function on_email_order_details() {
		remove_filter( 'woocommerce_get_price_html', array( $this, 'on_price_html' ), 99 );
		remove_filter( 'woocommerce_variable_subscription_price_html', array( $this, 'on_price_html' ), 100 );
		remove_filter( 'woocommerce_sale_flash', array( $this, 'on_sale_flash' ), 99 );
		remove_filter( 'woocommerce_cart_item_price', array( $this, 'on_cart_item_price' ), 999 );
		remove_filter( 'woocommerce_cart_item_subtotal', array( $this, 'on_cart_item_subtotal' ), 999 );
		remove_filter( 'woocommerce_cart_subtotal', array( $this, 'on_cart_subtotal' ), 9999 );
		remove_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'on_cart_total' ), 9999 );

		remove_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'on_order_formatted_line_subtotal' ), 10 );
	}

	/**
	 * Function to handle actions after the add to cart form.
	 */
	public function handle_product_paypal_button() {
		global $product;

		// Paypal Express Handling.
		if ( defined( 'WC_GATEWAY_PPEC_VERSION' ) ) {
			// Check if product exists and user can't purchase it.
			if ( $product && ! $this->user_can_purchase( $product ) ) {
				// Remove the display of the PayPal button for the product.
				remove_action( 'woocommerce_after_add_to_cart_form', array( wc_gateway_ppec()->cart, 'display_paypal_button_product' ), 1 );
			}
		}
	}

	/**
	 * Binds filters after a single product is displayed.
	 * Filters regular variation price and regular product price.
	 */
	public function on_woocommerce_after_single_product_bind() {
		// Filters the regular variation price.
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'on_get_price' ), 10, 2 );

		// Filters the regular product get price.
		add_filter( 'woocommerce_product_get_price', array( $this, 'on_get_price' ), 10, 2 );
	}

	/**
	 * Unbinds filters after a single product is displayed.
	 * Removes filters for regular variation price and regular product price.
	 */
	public function on_woocommerce_after_single_product_unbind() {
		// Removes the filter for regular variation price.
		remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'on_get_price' ), 10 );

		// Removes the filter for regular product get price.
		remove_filter( 'woocommerce_product_get_price', array( $this, 'on_get_price' ), 10 );
	}

	/**
	 * Handles plugin compatibility filters.
	 * Disables rendering form if the user cannot purchase the product.
	 */
	public function plugin_compatibility_filters() {
		// Check if the current page is a product.
		if ( is_product() ) {
			// Check if the user can't purchase the current product.
			if ( ! $this->user_can_purchase( wc_get_product( get_the_ID() ) ) ) {
				// Disable rendering form if user can't purchase the product.
				add_filter( 'woocommerce_bv_render_form', '__return_false' );
			}
		}
	}

	/**
	 * Resets the availability_html to hide stock information in WC 2.6+ if user can't view the price.
	 *
	 * This function modifies the variation data to remove availability HTML, display price, and regular price
	 * if the user cannot view the price of the variable product.
	 *
	 * @param array               $variation_data The data for the product variation.
	 * @param WC_Product_Variable $variable       The variable product object.
	 * @param WC_Product          $variation      The product variation object.
	 * @return array Modified variation data.
	 */
	public function on_get_woocommerce_available_variation( $variation_data, $variable, $variation ) {
		if ( $variable && ( ! $this->user_can_view_price( $variable ) ) ) {
			// Resetting availability_html and price display if user can't view price.
			$variation_data['availability_html']     = '';
			$variation_data['display_price']         = '';
			$variation_data['display_regular_price'] = '';
		}

		return $variation_data;
	}

	/**
	 * Checks and filters the product price.
	 *
	 * @param string|float|int $price   The product price.
	 * @param WC_Product       $product The product object.
	 * @return string|float|int Modified price if user can't view, otherwise original price.
	 */
	public function on_get_price( $price, $product ) {
		global $wc_cvo;

		// Check if the product exists and the user can't view the price.
		if ( $product && ! $this->user_can_view_price( $product ) ) {
			return ''; // If user can't view price, return empty string.
		}

		return $price; // Return original price if user can view.
	}

	/**
	 * Filters the price HTML output.
	 *
	 * @param string     $html      The price HTML.
	 * @param WC_Product $_product  The product object.
	 * @return string Modified HTML if user can't view, otherwise original HTML.
	 */
	public function on_price_html( $html, $_product ) {
		global $wc_cvo;

		// If the product is a variation, get the parent product.
		if ( $_product && $_product->get_type() === 'variation' ) {
			$_product = wc_get_product( $_product->get_parent_id() );
		}

		// Check if the product exists and the user can't view the price.
		if ( $_product && ! $this->user_can_view_price( $_product ) ) {
			// Return alternate price HTML if user can't view price.
			return apply_filters(
				'catalog_visibility_alternate_price_html',
				do_shortcode( wptexturize( $wc_cvo->setting( 'wc_cvo_c_price_text' ) ) ),
				$_product
			);
		}

		return $html; // Return original HTML if user can view.
	}

	/**
	 * Filters the price of an item in the cart.
	 *
	 * @param string $price      The price of the cart item.
	 * @param array  $cart_item  Cart item data.
	 * @return string Modified price if user can't view, otherwise original price.
	 */
	public function on_cart_item_price( $price, $cart_item ) {
		global $wc_cvo;
		$product = $cart_item['data'];

		// Check if the product exists and the user can't view the price.
		if ( $product && ! $this->user_can_view_price( $product ) ) {
			// Return alternate price HTML if user can't view price.
			return apply_filters(
				'catalog_visibility_alternate_cart_item_price_html',
				do_shortcode( wptexturize( $wc_cvo->setting( 'wc_cvo_c_price_text' ) ) ),
				$cart_item
			);
		}

		return $price; // Return original price if user can view.
	}

	/**
	 * Filters the subtotal of an item in the cart.
	 *
	 * @param string $price      The subtotal of the cart item.
	 * @param array  $cart_item  Cart item data.
	 * @return string Modified subtotal if user can't view, otherwise original subtotal.
	 */
	public function on_cart_item_subtotal( $price, $cart_item ) {
		$product = $cart_item['data'];

		// Check if the product exists and the user can't view the price.
		if ( $product && ! $this->user_can_view_price( $product ) ) {
			return apply_filters(
				'catalog_visibility_alternate_cart_item_subtotal_html',
				'', // Return empty string for subtotal if user can't view price.
				$cart_item
			);
		}

		return $price; // Return original subtotal if user can view.
	}

	/**
	 * Filters the formatted line subtotal in an order.
	 *
	 * @param float         $subtotal   The subtotal of the order item.
	 * @param WC_Order_Item $item       Order item data.
	 * @return mixed|void   Modified subtotal if user can't view, otherwise original subtotal.
	 */
	public function on_order_formatted_line_subtotal( $subtotal, $item ) {
		global $wc_cvo;
		try {
			$product = $item->get_product();

			// Check if the product exists and the user can't view the price.
			if ( $product && ! $this->user_can_view_price( $product ) ) {
				return apply_filters(
					'catalog_visibility_alternate_order_formatted_line_subtotal',
					'', // Return empty string for formatted line subtotal if user can't view price.
					$item
				);
			}
		} catch ( Exception $exception ) {
			return $subtotal;
		}

		return $subtotal; // Return original subtotal if user can view.
	}

	/**
	 * Filters the cart subtotal.
	 *
	 * @param float $subtotal   The subtotal of the cart.
	 * @return mixed|void   Modified cart subtotal if user can't view, otherwise original subtotal.
	 */
	public function on_cart_subtotal( $subtotal ) {
		global $wc_cvo;

		// Iterate through each cart item.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! $this->user_can_view_price( $cart_item['data'] ) ) {
				return apply_filters(
					'catalog_visibility_alternate_cart_subtotal',
					do_shortcode( wptexturize( $wc_cvo->setting( 'wc_cvo_c_price_text' ) ) ),
					$cart_item
				);
			}
		}

		return $subtotal; // Return original cart subtotal if user can view.
	}

	/**
	 * Filters the cart total displayed on the cart page.
	 *
	 * @param float $total   The cart total.
	 * @return mixed|void   Modified cart total if user can't view, otherwise original total.
	 */
	public function on_cart_total( $total ) {
		global $wc_cvo;

		// Iterate through each cart item.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! $this->user_can_view_price( $cart_item['data'] ) ) {
				return apply_filters(
					'catalog_visibility_alternate_cart_total',
					do_shortcode( wptexturize( $wc_cvo->setting( 'wc_cvo_c_price_text' ) ) ),
					$cart_item
				);
			}
		}

		return $total; // Return original cart total if user can view.
	}

	/**
	 * Filters the sale flash displayed for products.
	 *
	 * @param string       $html       The sale flash HTML.
	 * @param WP_Post|null $post       The post object.
	 * @param WC_Product   $product    The product object.
	 * @return string       Modified sale flash HTML if user can't view, otherwise original HTML.
	 */
	public function on_sale_flash( $html, $post, $product ) {
		if ( empty( $product ) ) {
			return $html;
		}

		// If the product is a variation, get its parent product.
		if ( $product->get_type() === 'variation' ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		// Check if the user can't view the price for the product.
		if ( ! $this->user_can_view_price( $product ) ) {
			return ''; // Return empty string for sale flash if user can't view price.
		}

		return $html; // Return original sale flash HTML if user can view.
	}

	/**
	 * Removes actions and calls `on_before_add_to_cart_form` before displaying a single variation.
	 */
	public function on_before_single_variation() {
		remove_action( 'woocommerce_before_add_to_cart_form', array( $this, 'on_before_add_to_cart_form' ), 0 );
		remove_action( 'woocommerce_after_add_to_cart_form', array( $this, 'on_after_add_to_cart_form' ), 998 );
		$this->on_before_add_to_cart_form();
	}

	/**
	 * Handles actions before displaying the add to cart button.
	 */
	public function on_before_add_to_cart_form() {
		global $product;

		// Check if the product can't be purchased by the user.
		if ( $product && ! $this->user_can_purchase( $product ) ) {
			if ( ! $this->buffer_on ) {
				$this->buffer_on = ob_start(); // Start output buffering if not already buffering.
			}
		}
	}

	/**
	 * Calls `on_after_add_to_cart_form` after displaying a single variation.
	 */
	public function on_after_single_variation() {
		$this->on_after_add_to_cart_form();
	}

	/**
	 * Handles actions after displaying the add to cart button.
	 */
	public function on_after_add_to_cart_form() {
		global $wc_cvo, $product;

		// Check if the action was already executed.
		if ( $this->did_after_cart_button ) {
			return; // Stop if the action was already executed.
		} else {
			$this->did_after_cart_button = true;
		}

		// Check if the product cannot be purchased by the user.
		if ( $product && ! $this->user_can_purchase( $product ) ) {
			if ( $this->buffer_on ) {
				ob_end_clean(); // Clear output buffer if buffering was initiated.
			}
		} else {
			return; // Stop if the product can be purchased.
		}

		// Action before displaying alternate add to cart button.
		do_action( 'catalog_visibility_before_alternate_add_to_cart_button' );

		// Get HTML content for the alternate add to cart button.
		$html = apply_filters( 'catalog_visibility_alternate_add_to_cart_button', do_shortcode( wpautop( wptexturize( $wc_cvo->setting( 'wc_cvo_s_price_text' ) ) ) ), $product );

		// Variable product price handling.
		if ( $product->is_type( 'variable' ) ) {
			?>
			<div class="single_variation woocommerce-variation"></div>
			<div class="variations_button">
				<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>"/>
				<input type="hidden" name="variation_id" class="variation_id" value="0"/>
			</div>
			<?php do_action( 'wc_cvo_after_single_variation', $product ); ?>

			<?php
		} else {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Action after displaying alternate add to cart button.
		do_action( 'catalog_visibility_after_alternate_add_to_cart_button' );
	}

	/**
	 * Modifies the template file used for product variations.
	 *
	 * @param string $located       The currently located template file.
	 * @param string $template_name The name of the template file being located.
	 *
	 * @return string The modified or default template file location.
	 */
	public function on_get_variation_template( $located, $template_name ) {
		global $wc_cvo;

		$_product = wc_get_product();

		// Check if the product exists and if the template name matches the variation template.
		if ( $_product && 'single-product/add-to-cart/variation.php' === $template_name ) {

			// If the product is a variation, get its parent product.
			if ( $_product->get_type() === 'variation' ) {
				$_product = wc_get_product( $_product->get_parent_id() );
			}

			// Check if the user cannot view the price of the product.
			if ( ! $this->user_can_view_price( $_product ) ) {
				$located = $wc_cvo->plugin_dir() . '/templates/variation.php'; // Set a different template location.
			}
		}

		return $located; // Return the modified or default template location.
	}

	/**
	 * Controls the rendering of the form for WooCommerce Bulk Variations based on user's purchase permissions.
	 *
	 * @param bool   $render  The default render state.
	 * @param object $product The WooCommerce product object.
	 *
	 * @return bool The modified render state based on user's purchase permissions.
	 */
	public function on_woocommerce_bv_render_form( $render, $product ) {
		return $this->user_can_purchase( $product ); // Returns a boolean indicating if the user can purchase the product.
	}

	/**
	 * Handles the validation process when adding a product to the cart in WooCommerce.
	 *
	 * @param bool $result      The default validation result.
	 * @param int  $product_id  The ID of the product being added to the cart.
	 *
	 * @return bool The modified validation result based on the user's purchase permissions and wishlist functionality.
	 */
	public function on_woocommerce_add_to_cart_validation( $result, $product_id ) {
		$product           = wc_get_product( $product_id ); // Retrieves the product based on the ID.
		$user_can_purchase = $product && self::instance()->user_can_purchase( $product ); // Checks if the user can purchase the product.

		// Adjust the validation result for wishlist functionality when adding an item to the wishlist.
		if ( $result && ! $user_can_purchase ) {
			// If the result was OK but the user can't purchase the product, adjust the validation for wishlist.
			add_filter( 'woocommerce_add_to_wishlist_validation', array( $this, 'on_woocommerce_add_to_wishlist_validation' ), 10, 1 );
		}

		// Return the result based on the user's purchase permissions and wishlist adjustments.
		return $result && $user_can_purchase;
	}

	/**
	 * Overrides catalog visibility disallowing items from being added to a wishlist.
	 *
	 * @param bool $result The default validation result.
	 *
	 * @return bool The modified validation result to allow adding items to a wishlist.
	 */
	public function on_woocommerce_add_to_wishlist_validation( $result ) {
		// Removes the filter to avoid potential infinite loops.
		remove_filter( 'woocommerce_add_to_wishlist_validation', array( $this, 'on_woocommerce_add_to_wishlist_validation' ), 10, 1 );

		// Overrides the result to allow adding the item to a wishlist regardless of catalog visibility restrictions.
		$result = true;

		return $result;
	}

	/**
	 * Modifies the add-to-cart link in the product loop based on catalog visibility settings.
	 *
	 * @param string $markup The default add-to-cart link HTML markup.
	 * @param object $product The WooCommerce product object.
	 *
	 * @return string The modified add-to-cart link markup or the default markup based on catalog visibility.
	 */
	public function on_loop_add_to_cart_link( $markup, $product ) {
		global $wc_cvo;

		// Check if the user can't purchase the product due to catalog visibility settings.
		if ( $product && ! $this->user_can_purchase( $product ) ) {
			$label = wptexturize( $wc_cvo->setting( 'wc_cvo_atc_text' ) );

			// If the label is empty, return an empty string.
			if ( empty( $label ) ) {
				return '';
			}

			// Generate the alternate add-to-cart link based on catalog visibility settings.
			$link           = get_permalink( $product->get_id() );
			$alternate_link = sprintf(
				'<a href="%s" data-product_id="%s" class="button product_type_%s">%s</a>',
				$link,
				$product->get_id(),
				$product->get_type(),
				$label
			);

			// Apply filter to the alternate add-to-cart link.
			return apply_filters( 'catalog_visibility_alternate_add_to_cart_link', $alternate_link );
		} else {
			// If the user can purchase the product, return the default add-to-cart link.
			return $markup;
		}
	}

	/**
	 * Modifies the add-to-cart button text based on catalog visibility settings.
	 *
	 * @param string $text The default add-to-cart button text.
	 * @param object $product The WooCommerce product object.
	 *
	 * @return string The modified add-to-cart button text or the default text based on catalog visibility.
	 */
	public function on_woocommerce_product_add_to_cart_text( $text, $product ) {
		global $wc_cvo;

		// Check if the user can't purchase the product due to catalog visibility settings.
		if ( $product && ! $this->user_can_purchase( $product ) ) {
			$label = wptexturize( $wc_cvo->setting( 'wc_cvo_atc_text' ) );

			// If the label is empty, return an empty string.
			if ( empty( $label ) ) {
				return '';
			}

			// Apply filter to the alternate add-to-cart button text.
			return apply_filters( 'catalog_visibility_alternate_product_add_to_cart_text', $label, $product );
		} else {
			// If the user can purchase the product, return the default add-to-cart button text.
			return $text;
		}
	}

	/**
	 * Modifies the add-to-cart button URL based on catalog visibility settings.
	 *
	 * @param string $url The default add-to-cart button URL.
	 * @param object $product The WooCommerce product object.
	 *
	 * @return string The modified add-to-cart button URL or the default URL based on catalog visibility.
	 */
	public function on_woocommerce_product_add_to_cart_url( $url, $product ) {
		if ( $product && ! $this->user_can_purchase( $product ) ) {
			$link = get_permalink( $product->get_id() );

			// Apply filter to the alternate add-to-cart button URL.
			return apply_filters( 'catalog_visibility_alternate_add_to_cart_link_url', $link, $product );
		} else {
			// If the user can purchase the product, return the default add-to-cart button URL.
			return $url;
		}
	}

	/**
	 * Modifies the structured data (JSON-LD markup) for a product based on catalog visibility settings.
	 *
	 * @param array  $markup The default structured data for the product.
	 * @param object $product The WooCommerce product object.
	 *
	 * @return array The modified structured data or the default data based on catalog visibility.
	 */
	public function on_get_woocommerce_structured_data_product( $markup, $product ) {
		// Check if the user can't view the price of the product.
		if ( ! $this->user_can_view_price( $product ) ) {
			// Remove the 'offers' data to hide pricing information.
			$markup['offers'] = array();
		}

		return $markup;
	}

	/**
	 * Checks if a user can purchase a product.
	 *
	 * @param object $product The WooCommerce product object.
	 *
	 * @return bool Returns true if the user can purchase the product, otherwise false.
	 */
	public function user_can_purchase( $product ) {
		if ( empty( $product ) ) {
			return false;
		}

		// If the product is a variation, retrieve its parent product.
		if ( $product->get_type() === 'variation' ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		// If the result for this product is cached, return the cached result.
		if ( isset( $this->cache_can_purchase[ $product->get_id() ] ) ) {
			return $this->cache_can_purchase[ $product->get_id() ];
		}

		// Check if the user is logged in, defaults to false if not logged in.
		$result = is_user_logged_in() ? true : false;

		// Apply filters to allow modification of the result.
		$result = apply_filters( 'catalog_visibility_user_can_purchase', $result, $product );

		// Cache the result for future use.
		$this->cache_can_purchase[ $product->get_id() ] = $result;

		return $result;
	}

	/**
	 * Checks if a user can view the price of a product.
	 *
	 * @param object $product The WooCommerce product object.
	 *
	 * @return bool Returns true if the user can view the price of the product, otherwise false.
	 */
	public function user_can_view_price( $product ) {
		if ( empty( $product ) ) {
			return false;
		}

		// If the product is a variation, retrieve its parent product.
		if ( $product->get_type() === 'variation' ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		// If the result for this product is cached, return the cached result.
		if ( isset( $this->cache_can_view_prices[ $product->get_id() ] ) ) {
			return $this->cache_can_view_prices[ $product->get_id() ];
		}

		// Check if the user is logged in, defaults to false if not logged in.
		$result = is_user_logged_in() ? true : false;

		// Apply filters to allow modification of the result.
		$result = apply_filters( 'catalog_visibility_user_can_view_price', $result, $product );

		// Cache the result for future use.
		$this->cache_can_view_prices[ $product->get_id() ] = $result;

		return $result;
	}
}
