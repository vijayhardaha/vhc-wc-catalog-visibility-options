<?php
/**
 * Single variation display
 *
 * This is a javascript-based template for single variations (see https://codex.wordpress.org/Javascript_Reference/wp.template).
 * The values will be dynamically replaced after selecting attributes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package VHC_WC_CVO_Options
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<script type="text/template" id="tmpl-variation-template">
	<div class="woocommerce-variation-description wc-catalog-visibility">
		{{{ data.variation.variation_description }}}
	</div>

	<div class="woocommerce-variation-availability wc-catalog-visiblity">
		{{{ data.variation.availability_html }}}
	</div>
</script>
<script type="text/template" id="tmpl-unavailable-variation-template">
	<p><?php esc_html_e( 'Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce' ); ?></p>
</script>
