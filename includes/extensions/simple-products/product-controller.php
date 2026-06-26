<?php

namespace MyListing\Ext\Simple_Products;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Product_Controller extends \WC_REST_Products_Controller {
	public function c27_create_product( $data ) {
		if ( ! class_exists( '\\WC_Product' ) ) {
			return false;
		}

		$product   = null;
		$is_update = ! empty( $data['id'] ) && is_numeric( $data['id'] );
		if ( $is_update ) {
			$product = wc_get_product( absint( $data['id'] ) );
			if ( ! $product ) {
				return false;
			}
		} else {
			$product = new \WC_Product_Simple();
		}

		// Core fields.
		if ( isset( $data['name'] ) ) {
			$product->set_name( wp_unslash( $data['name'] ) );
		}
		if ( isset( $data['description'] ) ) {
			$product->set_description( wp_unslash( $data['description'] ) );
		}
		if ( isset( $data['short_description'] ) ) {
			$product->set_short_description( wp_unslash( $data['short_description'] ) );
		}
		if ( isset( $data['status'] ) ) {
			$product->set_status( sanitize_key( $data['status'] ) );
		}

		// Pricing.
		if ( isset( $data['regular_price'] ) ) {
			$product->set_regular_price( wc_format_decimal( $data['regular_price'] ) );
		}
		if ( array_key_exists( 'sale_price', $data ) ) {
			if ( $data['sale_price'] !== '' ) {
				$product->set_sale_price( wc_format_decimal( $data['sale_price'] ) );
			} else {
				$product->set_sale_price( '' );
			}
		}
		if ( ! empty( $data['date_on_sale_from'] ) ) {
			$from = strtotime( $data['date_on_sale_from'] );
			if ( $from ) {
				$product->set_date_on_sale_from( $from );
			}
		} else {
			$product->set_date_on_sale_from( null );
		}
		if ( ! empty( $data['date_on_sale_to'] ) ) {
			$to = strtotime( $data['date_on_sale_to'] );
			if ( $to ) {
				$product->set_date_on_sale_to( $to );
			}
		} else {
			$product->set_date_on_sale_to( null );
		}

		// Inventory.
		$manage_stock = ! empty( $data['manage_stock'] );
		$product->set_manage_stock( $manage_stock );
		if ( $manage_stock ) {
			$qty = isset( $data['stock_quantity'] ) && is_numeric( $data['stock_quantity'] ) ? (int) $data['stock_quantity'] : 0;
			$product->set_stock_quantity( max( 0, $qty ) );
			$product->set_backorders( isset( $data['backorders'] ) ? sanitize_text_field( $data['backorders'] ) : 'no' );
			// Allow explicit override if provided.
			if ( ! empty( $data['stock_status'] ) ) {
				$product->set_stock_status( $data['stock_status'] === 'outofstock' ? 'outofstock' : 'instock' );
			}
		} else {
			$product->set_backorders( isset( $data['backorders'] ) ? sanitize_text_field( $data['backorders'] ) : 'no' );
			$stock_status = isset( $data['stock_status'] ) ? sanitize_text_field( $data['stock_status'] ) : ( ! empty( $data['in_stock'] ) ? 'instock' : 'outofstock' );
			$product->set_stock_status( $stock_status === 'outofstock' ? 'outofstock' : 'instock' );
			$product->set_stock_quantity( null );
		}

		// Virtual products.
		$product->set_virtual( ! empty( $data['virtual'] ) );

		// Optional: SKU and dimensions/weight.
		if ( isset( $data['sku'] ) ) {
			$sku = sanitize_text_field( wp_unslash( $data['sku'] ) );
			if ( $sku !== '' ) {
				$product->set_sku( $sku );
			}
		}
		if ( isset( $data['weight'] ) ) {
			$product->set_weight( wc_format_decimal( $data['weight'] ) );
		}
		if ( isset( $data['length'] ) ) {
			$product->set_length( wc_format_decimal( $data['length'] ) );
		}
		if ( isset( $data['width'] ) ) {
			$product->set_width( wc_format_decimal( $data['width'] ) );
		}
		if ( isset( $data['height'] ) ) {
			$product->set_height( wc_format_decimal( $data['height'] ) );
		}

		// Meta data.
		if ( ! empty( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $meta ) {
				if ( is_array( $meta ) && isset( $meta['key'], $meta['value'] ) ) {
					$product->update_meta_data( $meta['key'], $meta['value'] );
				}
			}
		}

		$product->save();
		return $product;
	}
}