<?php
/**
 * SLW Frontend Product Class
 *
 * @since 1.3.0
 */

namespace SLW\SRC\Classes\Frontend;

use SLW\SRC\Helpers\SlwFrontendHelper;

if ( !defined( 'WPINC' ) ) {
	die;
}

if( !class_exists('SlwFrontendProduct') ) {

	class SlwFrontendProduct
	{
		/**
		 * Construct.
		 *
		 * @since 1.3.0
		 */
		public function __construct()
		{
			// get settings
			$this->plugin_settings = get_option( 'slw_settings' );

			// check if show in cart is enabled
			if( isset( $this->plugin_settings['show_in_product_page'])  && $this->plugin_settings['show_in_product_page'] == 'yes' ) {
				add_action( 'woocommerce_before_add_to_cart_button', array($this, 'simple_location_select') );
				add_action( 'woocommerce_single_variation', array($this, 'variable_location_select') );
				add_filter( 'woocommerce_add_cart_item_data', array($this, 'add_to_cart_location_validation'), 10, 3 );
				add_action( 'wp_ajax_get_variation_locations', array($this, 'get_variation_locations') );
				add_action( 'wp_ajax_nopriv_get_variation_locations', array($this, 'get_variation_locations') );
			}
		}

		/**
		 * Add stock locations selection to simple product page.
		 *
		 * @since 1.3.0
		 */
		public function simple_location_select()
		{
			global $product;
			if( empty($product) || $product->get_type() != 'simple' ) return;

			$stock_locations  = SlwFrontendHelper::get_all_product_stock_locations_for_selection( $product->get_id() );
			$default_location = isset( $this->plugin_settings['default_location_in_frontend_selection'] ) ? get_post_meta( $product->get_id(), '_slw_default_location', true ) : 0;

			if( ! empty($stock_locations) ) {
				echo '<select id="slw_item_stock_location_simple_product" class="slw_item_stock_location" name="slw_add_to_cart_item_stock_location" style="display:block;" required>';
				if( ! empty( $default_location ) ) {
					echo '<option disabled>'.__('Select location...', 'stock-locations-for-woocommerce').'</option>';
				} else {
					echo '<option disabled selected>'.__('Select location...', 'stock-locations-for-woocommerce').'</option>';
				}
				foreach( $stock_locations as $id => $location ) {
					$disabled = '';
					if( $location['quantity'] < 1 && $location['allow_backorder'] != 1 ) {
						$disabled = 'disabled="disabled"';
					}
					if( ! empty( $default_location ) &&  $location['term_id'] == $default_location ) {
						echo '<option value="'.$location['term_id'].'" '.$disabled.' selected>'.$location['name'].'</option>';
					} else {
						echo '<option value="'.$location['term_id'].'" '.$disabled.'>'.$location['name'].'</option>';
					}
				}
				echo '</select>';
			}
		}

		/**
		 * Add stock locations selection to variable product page.
		 *
		 * @since 1.3.0
		 */
		public function variable_location_select()
		{
			global $product;
			if( empty($product) || $product->get_type() != 'variable' ) return;

			$default_location = isset( $this->plugin_settings['default_location_in_frontend_selection'] ) ? get_post_meta( $product->get_id(), '_slw_default_location', true ) : 0;
			
			echo '<select id="slw_item_stock_location_variable_product" class="slw_item_stock_location" name="slw_add_to_cart_item_stock_location">';
			if( ! empty( $default_location ) ) {
				echo '<option disabled>'.__('Select location...', 'stock-locations-for-woocommerce').'</option>';
			} else {
				echo '<option disabled selected>'.__('Select location...', 'stock-locations-for-woocommerce').'</option>';
			}
			echo '</select>';
		}

		/**
		 * Get variation locations.
		 *
		 * @since 1.3.0
		 */
		public function get_variation_locations()
		{
			if( isset( $_POST['variation_id'] ) && isset( $_POST['product_id'] ) && $_POST['action'] == 'get_variation_locations' ) {
				$variation_id = sanitize_text_field( $_POST['variation_id'] );
				$product_id   = sanitize_text_field( $_POST['product_id'] );

				$stock_locations  = SlwFrontendHelper::get_all_product_stock_locations_for_selection( $variation_id );
				$default_location = isset( $this->plugin_settings['default_location_in_frontend_selection'] ) ? get_post_meta( $product_id, '_slw_default_location', true ) : 0;

				if( !empty($stock_locations) ) {
					wp_send_json_success( compact( 'stock_locations', 'default_location' ) );
				} else {
					wp_send_json_error( array(
						'error' => __('No locations found for this product/variant!', 'stock-locations-for-woocommerce')
					) );
				}
			}
			die();
		}

		/**
		 * Validate cart item selected location.
		 *
		 * @since 1.3.0
		 */
		function add_to_cart_location_validation( $cart_item_data, $product_id, $variation_id ) {
			if( isset( $_POST['slw_add_to_cart_item_stock_location'] ) ) {
				$cart_item_data['stock_location'] = sanitize_text_field( $_POST['slw_add_to_cart_item_stock_location'] );
			}
			return $cart_item_data;
		}

	}

}
