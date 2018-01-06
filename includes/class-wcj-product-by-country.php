<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by Country
 *
 * @version 3.2.4
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_Country' ) ) :

class WCJ_Product_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.0
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'product_by_country';
		$this->short_desc = __( 'Product Visibility by Country', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by customer\'s country.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-visibility-by-country';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by Country" meta box to each product\'s edit page.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Product meta box
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			// Core
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'wcj_product_by_country_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_country' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_country_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'product_by_country_purchasable' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_country_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'product_by_country_pre_get_posts' ) );
				}
				if ( 'manual' === apply_filters( 'booster_option', 'by_ip', get_option( 'wcj_product_by_country_selection_method', 'by_ip' ) ) ) {
					add_action( 'init',                           array( $this, 'save_country_in_session' ), PHP_INT_MAX ) ;
				}
			}
			// Admin products list
			if ( 'yes' === get_option( 'wcj_product_by_country_add_column_visible_countries', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),   PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_product_columns( $columns ) {
		$columns[ 'wcj_product_by_country_visible_countries' ] = __( 'Countries', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * render_product_column.
	 *
	 * @version 3.1.1
	 * @since   2.9.0
	 */
	function render_product_column( $column ) {
		if ( 'wcj_product_by_country_visible_countries' === $column ) {
			$result = '';
			if ( 'invisible' != apply_filters( 'booster_option', 'visible', get_option( 'wcj_product_by_country_visibility_method', 'visible' ) ) ) {
				if ( $countries = get_post_meta( get_the_ID(), '_' . 'wcj_product_by_country_visible', true ) ) {
					if ( is_array( $countries ) ) {
						$result .= '<span style="color:green;">' . implode( ', ', $countries ) . '</span>';
					}
				}
			}
			if ( 'visible' != apply_filters( 'booster_option', 'visible', get_option( 'wcj_product_by_country_visibility_method', 'visible' ) ) ) {
				if ( $countries = get_post_meta( get_the_ID(), '_' . 'wcj_product_by_country_invisible', true ) ) {
					if ( is_array( $countries ) ) {
						if ( '' != $result ) {
							$result .= '<br>';
						}
						$result .= '<span style="color:red;">' . implode( ', ', $countries ) . '</span>';
					}
				}
			}
			echo $result;
		}
	}

	/**
	 * product_by_country_pre_get_posts.
	 *
	 * @version 3.1.0
	 * @since   2.9.0
	 */
	function product_by_country_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );
		$country        = $this->get_country();
		$post__not_in   = $query->get( 'post__not_in' );
		$args           = $query->query;
		$args['fields'] = 'ids';
		$loop           = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			if ( ! $this->is_product_visible_in_country( $product_id, $country ) ) {
				$post__not_in[] = $product_id;
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );
	}

	/**
	 * product_by_country_purchasable.
	 *
	 * @version 3.1.0
	 * @since   2.9.0
	 */
	function product_by_country_purchasable( $purchasable, $_product ) {
		return ( ! $this->is_product_visible_in_country( wcj_get_product_id_or_variation_parent_id( $_product ), $this->get_country() ) ? false : $purchasable );
	}

	/**
	 * product_by_country.
	 *
	 * @version 3.1.0
	 * @since   2.5.0
	 */
	function product_by_country( $visible, $product_id ) {
		return ( ! $this->is_product_visible_in_country( $product_id, $this->get_country() ) ? false : $visible );
	}

	/**
	 * is_product_visible_in_country.
	 *
	 * @version 3.1.1
	 * @since   3.1.0
	 */
	function is_product_visible_in_country( $product_id, $country ) {
		if ( 'invisible' != apply_filters( 'booster_option', 'visible', get_option( 'wcj_product_by_country_visibility_method', 'visible' ) ) ) {
			$countries = get_post_meta( $product_id, '_' . 'wcj_product_by_country_visible', true );
			if ( ! empty( $countries ) && is_array( $countries ) ) {
				if ( in_array( 'EU', $countries ) ) {
					$countries = array_merge( $countries, wcj_get_european_union_countries() );
				}
				if ( ! in_array( $country, $countries ) ) {
					return false;
				}
			}
		}
		if ( 'visible' != apply_filters( 'booster_option', 'visible', get_option( 'wcj_product_by_country_visibility_method', 'visible' ) ) ) {
			$countries = get_post_meta( $product_id, '_' . 'wcj_product_by_country_invisible', true );
			if ( ! empty( $countries ) && is_array( $countries ) ) {
				if ( in_array( 'EU', $countries ) ) {
					$countries = array_merge( $countries, wcj_get_european_union_countries() );
				}
				if ( in_array( $country, $countries ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * save_country_in_session.
	 *
	 * @version 3.2.4
	 * @since   3.1.0
	 */
	function save_country_in_session() {
		wcj_session_maybe_start();
		if ( isset( $_REQUEST['wcj_country_selector'] ) ) {
			wcj_session_set( 'wcj_selected_country', $_REQUEST['wcj_country_selector'] );
		}
	}

	/**
	 * get_country.
	 *
	 * @version 3.2.4
	 * @since   3.1.0
	 */
	function get_country() {
		if ( 'manual' === apply_filters( 'booster_option', 'by_ip', get_option( 'wcj_product_by_country_selection_method', 'by_ip' ) ) ) {
			if ( '' == wcj_session_get( 'wcj_selected_country' ) ) {
				$country = wcj_get_country_by_ip();
				wcj_session_set( 'wcj_selected_country', $country );
				return $country;
			} else {
				return wcj_session_get( 'wcj_selected_country' );
			}
		} else {
			return wcj_get_country_by_ip();
		}
	}

}

endif;

return new WCJ_Product_By_Country();
