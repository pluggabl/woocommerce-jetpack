<?php
/**
 * WooCommerce Jetpack Price Labels
 *
 * The WooCommerce Jetpack Price Labels class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Price_Labels' ) ) :

class WCJ_Price_Labels extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->id         = 'price_labels';
		$this->short_desc = __( 'Custom Price Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'Create any custom price label for any WooCommerce product.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-custom-price-labels/';
		parent::__construct();

		$this->add_tools( array(
			'migrate_from_custom_price_labels' => array(
				'title' => __( 'Migrate from Custom Price Labels (Pro)', 'woocommerce-jetpack' ),
				'desc'  => __( 'Tool lets you copy all the data (that is labels) from Custom Price labels (Pro) plugin to Booster.', 'woocommerce-jetpack' ),
//				'tab_title' => __( 'Migrate from Custom Price Labels (Pro)', 'woocommerce-jetpack' ),
				'depreciated' => true,
			),
		), array( 'tools_dashboard_hook_priority' => PHP_INT_MAX ) );

		// Custom Price Labels - fields array
		$this->custom_tab_group_name = 'wcj_price_labels';// for compatibility with Custom Price Label Pro plugin should use 'simple_is_custom_pricing_label'
		$this->custom_tab_sections = array( '_instead', '_before', '_between', '_after', );
		$this->custom_tab_sections_titles = array(
			'_instead' => __( 'Instead of the price', 'woocommerce-jetpack' ),// for compatibility with Custom Price Label Pro plugin should use ''
			'_before'  => __( 'Before the price', 'woocommerce-jetpack' ),
			'_between' => __( 'Between regular and sale prices', 'woocommerce-jetpack' ),
			'_after'   => __( 'After the price', 'woocommerce-jetpack' ),
		);
		$this->custom_tab_section_variations = array( '_text', '_enabled', '_home', '_products', '_single', '_page', '_cart', /*'_simple',*/ '_variable', '_variation', /*'_grouped',*/ );
		$this->custom_tab_section_variations_titles = array(
			'_text'      => '',//'The label',
			'_enabled'   => __( 'Enable', 'woocommerce-jetpack' ),// for compatibility with Custom Price Label Pro plugin should use ''
			'_home'      => __( 'Hide on home page', 'woocommerce-jetpack' ),
			'_products'  => __( 'Hide on products page', 'woocommerce-jetpack' ),
			'_single'    => __( 'Hide on single', 'woocommerce-jetpack' ),
			'_page'      => __( 'Hide on all pages', 'woocommerce-jetpack' ),
			'_cart'      => __( 'Hide on cart page only', 'woocommerce-jetpack' ),
//			'_simple'    => __( 'Hide for simple product', 'woocommerce-jetpack' ),
			'_variable'  => __( 'Hide for main price', 'woocommerce-jetpack' ),
			'_variation' => __( 'Hide for all variations', 'woocommerce-jetpack' ),
//			'_grouped'   => __( 'Hide for grouped product', 'woocommerce-jetpack' ),
		);

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_local_price_labels_enabled', 'yes' ) ) {
				// Meta box (admin)
				add_action( 'add_meta_boxes', array( $this, 'add_price_label_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_custom_price_labels' ), 999, 2 );
			}

			// Prices Hooks
			$this->prices_filters = array(
				// Cart
				'woocommerce_cart_item_price',
				// Composite Products
				'woocommerce_composite_sale_price_html',
				'woocommerce_composite_price_html',
				'woocommerce_composite_empty_price_html',
				'woocommerce_composite_free_sale_price_html',
				'woocommerce_composite_free_price_html',
				// Booking Products
				'woocommerce_get_price_html',
				// Simple Products
				'woocommerce_empty_price_html',
				'woocommerce_free_price_html',
				'woocommerce_free_sale_price_html',
				'woocommerce_price_html',
				'woocommerce_sale_price_html',
				// Grouped Products
				'woocommerce_grouped_price_html',
				// Variable Products
				'woocommerce_variable_empty_price_html',
				'woocommerce_variable_free_price_html',
				'woocommerce_variable_free_sale_price_html',
				'woocommerce_variable_price_html',
				'woocommerce_variable_sale_price_html',
				// Variable Products - Variations
				'woocommerce_variation_empty_price_html',
				'woocommerce_variation_free_price_html',
				'woocommerce_variation_price_html',
				'woocommerce_variation_sale_price_html',
				// WooCommerce Subscription
				'woocommerce_subscriptions_product_price_string',
				'woocommerce_variable_subscription_price_html',
			);
			foreach ( $this->prices_filters as $the_filter )
				add_filter( $the_filter, array( $this, 'custom_price' ), 100, 2 );
		}
	}

	/**
	 * get_migration_new_meta_name.
	 */
	/* public function get_migration_new_meta_name( $old_meta_name ) {
		$new_meta_name = str_replace( 'simple_is_custom_pricing_label', 'wcj_price_labels', $old_meta_name );
		return $new_meta_name;
	} */

	/**
	 * create_migrate_from_custom_price_labels_tool.
	 *
	 * @version 2.5.0
	 */
	public function create_migrate_from_custom_price_labels_tool() {

		echo '<h2>' . __( 'Booster - Migrate from Custom Price Labels (Pro)', 'woocommerce-jetpack' ) . '</h2>';

		$migrate = isset( $_POST['migrate'] ) ? true : false;

		$migration_data = array(
			'_simple_is_custom_pricing_label'                  => '_wcj_price_labels_instead_enabled',
			'_simple_is_custom_pricing_label_home'             => '_wcj_price_labels_instead_home',
			'_simple_is_custom_pricing_label_products'         => '_wcj_price_labels_instead_products',
			'_simple_is_custom_pricing_label_single'           => '_wcj_price_labels_instead_single',
			'_simple_is_custom_pricing_label_text'             => '_wcj_price_labels_instead_text',

			'_simple_is_custom_pricing_label_before'           => '_wcj_price_labels_before_enabled',
			'_simple_is_custom_pricing_label_before_home'      => '_wcj_price_labels_before_home',
			'_simple_is_custom_pricing_label_before_products'  => '_wcj_price_labels_before_products',
			'_simple_is_custom_pricing_label_before_single'    => '_wcj_price_labels_before_single',
			'_simple_is_custom_pricing_label_text_before'      => '_wcj_price_labels_before_text',

			'_simple_is_custom_pricing_label_between'          => '_wcj_price_labels_between_enabled',
			'_simple_is_custom_pricing_label_between_home'     => '_wcj_price_labels_between_home',
			'_simple_is_custom_pricing_label_between_products' => '_wcj_price_labels_between_products',
			'_simple_is_custom_pricing_label_between_single'   => '_wcj_price_labels_between_single',
			'_simple_is_custom_pricing_label_text_between'     => '_wcj_price_labels_between_text',

			'_simple_is_custom_pricing_label_after'            => '_wcj_price_labels_after_enabled',
			'_simple_is_custom_pricing_label_after_home'       => '_wcj_price_labels_after_home',
			'_simple_is_custom_pricing_label_after_products'   => '_wcj_price_labels_after_products',
			'_simple_is_custom_pricing_label_after_single'     => '_wcj_price_labels_after_single',
			'_simple_is_custom_pricing_label_text_after'       => '_wcj_price_labels_after_text',
		);
		$offset = 0;
		$block_size = 96;
		$html = '<pre><ul>';
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$the_product_id = get_the_ID();
				foreach ( $migration_data as $old_meta_name => $new_meta_name ) {
					$old_meta_value = get_post_meta( $the_product_id, $old_meta_name, true );
					if ( '' != $old_meta_value ) {
						// Found Old (Custom Price Labels Plugin) meta to migrate from
						$new_meta_value = get_post_meta( $the_product_id, $new_meta_name, true );
						if ( $new_meta_value !== $old_meta_value ) {
							// Found Old (Custom Price Labels Plugin) meta to migrate to
							if ( true === $migrate ) {
								// Migrating
								$html .= '<li>' . __( 'Migrating (product ID ', 'woocommerce-jetpack' ) . $the_product_id . '): ' .  $old_meta_name . '[' . $old_meta_value . ']' . ' -> ' . $new_meta_name . '[' . $new_meta_value . ']. ';// . '</li>';
								$html .= __( 'Result: ', 'woocommerce-jetpack' ) . update_post_meta( $the_product_id, $new_meta_name, $old_meta_value );
								$html .= '</li>';

								//wp_update_post( array( 'ID' => $the_product_id ) );

								// Fill all data with defaults
								foreach ( $this->custom_tab_sections as $custom_tab_section ) {
									foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {
										$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;
										if ( '' == get_post_meta( $the_product_id, '_' . $option_name, true ) ) {
											if ( '_text' === $custom_tab_section_variation )
												update_post_meta( $the_product_id, '_' . $option_name, '' );
											else
												update_post_meta( $the_product_id, '_' . $option_name, 'off' );

											//$html .= '<li>' . __( 'Setting defaults for: ', 'woocommerce-jetpack' ) . '_' . $option_name . '</li>';
										}
									}
								}
							}
							else {
								// Info only
								$html .= '<li>' . __( 'Found data to migrate (product ID ', 'woocommerce-jetpack' ) . $the_product_id . '): ' . $old_meta_name . '[' . $old_meta_value . ']' . ' -> ' . $new_meta_name . '[' . $new_meta_value . ']' . '</li>';
							}
							/*if ( true === $do_delete_old ) {
								// Delete Old (Custom Price Labels Plugin) meta
								$html .= '<li>' . __( 'Deleting: ', 'woocommerce-jetpack' ) . $old_meta_name . '[' . $old_meta_value . ']. ';// . '</li>';
								$html .= __( ' Result: ', 'woocommerce-jetpack' ) . delete_post_meta( $the_product_id, $old_meta_name, $old_meta_value );
								$html .= '</li>';
							}*/
						}
						//wp_update_post( array( 'ID' => $the_product_id ) );
					}
				}
			endwhile;
			$offset += $block_size;
		}
		if ( '<pre><ul>' == $html )
			$html = __( 'No data to migrate found', 'woocommerce-jetpack' );
		else
			$html .= '</ul></pre>';
		wp_reset_postdata();

		$form_html =  '<form method="post" action="">';
		$form_html .= '<p>' . __( 'Press button below to copy all labels from Custom Price Labels (Pro) plugin. Old labels will NOT be deleted. New labels will be overwritten.', 'woocommerce-jetpack' ) . '</p>';
		$form_html .= '<p><input type="submit" class="button-primary" name="migrate" value="' . __( 'Migrate data', 'woocommerce-jetpack' ) . '" /></p>';
		$form_html .= '</form>';

		echo $form_html . $html;
	}

	/**
	 * save_custom_price_labels.
	 */
	public function save_custom_price_labels( $post_id, $post ) {

//		$product = get_product( $post );TODO - do I need it?

		if ( ! isset( $_POST['woojetpack_price_labels_save_post'] ) )
			return;

		foreach ( $this->custom_tab_sections as $custom_tab_section ) {

			foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {

//				$option_name = $this->custom_tab_group_name;
				$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;

//				if ( isset( $_POST[ $option_name ] ) ) update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );

				if ( $custom_tab_section_variation == '_text' ) {
					//$option_name .= $custom_tab_section_variation . $custom_tab_section;
					if ( isset( $_POST[ $option_name ] ) ) update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
				}
				else {
					//$option_name .= $custom_tab_section . $custom_tab_section_variation;
					if ( isset( $_POST[ $option_name ] ) ) update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
					else update_post_meta( $post_id, '_' . $option_name, 'off' );
				}
			}
		}
	}

	/**
	 * add_price_label_meta_box.
	 *
	 * @version 2.4.8
	 */
	public function add_price_label_meta_box() {
		add_meta_box(
			'wc-jetpack-price-labels',
			__( 'Booster: Custom Price Labels', 'woocommerce-jetpack' ),
			array( $this, 'create_price_label_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/*
	 * create_price_label_meta_box - back end.
	 *
	 * @version 2.4.8
	 */
	public function create_price_label_meta_box() {

		$current_post_id = get_the_ID();
		echo '<table style="width:100%;">';

		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			echo '<td style="width:25%;"><h4>' . $this->custom_tab_sections_titles[ $custom_tab_section ] . '</h4></td>';
		}
		echo '</tr>';

		/*
		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			if ( '_instead' != $custom_tab_section )
				$disabled_if_no_plus = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_below' );
			else
				$disabled_if_no_plus = '';
			echo '<td style="width:25%;">' . $disabled_if_no_plus . '</td>';
		}
		echo '</tr>';
		*/

		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {

			echo '<td style="width:25%;">';

			/*if ( $custom_tab_section == '_before' ) $disabled_if_no_plus = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_below' );
			else $disabled_if_no_plus = '';
			echo '<p>' . $disabled_if_no_plus . '<ul><h4>' . $this->custom_tab_sections_titles[ $custom_tab_section ] . '</h4>';*/

			echo '<ul>';//<li><h4>' . $this->custom_tab_sections_titles[ $custom_tab_section ] . '</h4></li>';

			foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {

				//$option_name = $this->custom_tab_group_name;
				$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;

				if ( $custom_tab_section_variation == '_text' ) {

					//$option_name .= $custom_tab_section_variation . $custom_tab_section;

					if ( $custom_tab_section != '_instead' ) $disabled_if_no_plus = apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly_string' );
					else $disabled_if_no_plus = '';
					//if ( $disabled_if_no_plus != '' ) $disabled_if_no_plus = 'readonly';

					$label_text = get_post_meta( $current_post_id, '_' . $option_name, true );
					$label_text = str_replace ( '"', '&quot;', $label_text );

					//echo '<li>' . $this->custom_tab_section_variations_titles[ $custom_tab_section_variation ] . ' <input style="width:50%;min-width:300px;" type="text" ' . $disabled_if_no_plus . ' name="' . $option_name . '" id="' . $option_name . '" value="' . $label_text . '" /></li>';
					echo '<li>' . $this->custom_tab_section_variations_titles[ $custom_tab_section_variation ] . '<textarea style="width:95%;min-width:100px;height:100px;" ' . $disabled_if_no_plus . ' name="' . $option_name . '">' . $label_text . '</textarea></li>';

				}
				else {

					if ( '_home' === $custom_tab_section_variation )
						echo '<li><h5>Hide by page type</h5></li>';

					if ( '_variable' === $custom_tab_section_variation )
						echo '<li><h5>Variable products</h5></li>';

					//$option_name .= $custom_tab_section . $custom_tab_section_variation;

					if ( '_instead' != $custom_tab_section ) $disabled_if_no_plus = apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled_string' );
					else $disabled_if_no_plus = '';
					//if ( $disabled_if_no_plus != '' ) $disabled_if_no_plus = 'disabled';

					echo '<li><input class="checkbox" type="checkbox" ' . $disabled_if_no_plus . ' name="' . $option_name . '" id="' . $option_name . '" ' .
						checked( get_post_meta( $current_post_id, '_' . $option_name, true ), 'on', false ) . ' /> ' . $this->custom_tab_section_variations_titles[ $custom_tab_section_variation ] . '</li>';
				}
			}

			echo '</ul>';//</p>';

			echo '</td>';

		}
		echo '</tr>';
		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			if ( '_instead' != $custom_tab_section )
				$disabled_if_no_plus = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_above' );
			else
				$disabled_if_no_plus = '';
			echo '<td style="width:25%;">' . $disabled_if_no_plus . '</td>';
		}
		echo '</tr>';
		echo '</table>';
		echo '<input type="hidden" name="woojetpack_price_labels_save_post" value="woojetpack_price_labels_save_post">';
	}

	/*
	 * customize_price
	 */
	public function customize_price( $price, $custom_tab_section, $custom_label ) {

		switch ( $custom_tab_section ) {

			case '_instead':
				$price = $custom_label;
				break;

			case '_before':
				$price = apply_filters( 'wcj_get_option_filter', $price, $custom_label . $price );
				break;

			case '_between':
				$price = apply_filters( 'wcj_get_option_filter', $price, str_replace( '</del> <ins>', '</del>' . $custom_label . '<ins>', $price ) );
				break;

			case '_after':
				$price = apply_filters( 'wcj_get_option_filter', $price, $price . $custom_label );
				break;
		}

		return str_replace( 'From: ', '', $price ); // TODO: recheck if this is necessary? Also this works only for English WP installs.
	}

	/*
	 * custom_price - front end.
	 *
	 * @version 2.4.8
	 */
	public function custom_price( $price, $product ) {

		if ( ! wcj_is_frontend() ) return $price;

		$current_filter_name = current_filter();

		if ( 'woocommerce_cart_item_price' === $current_filter_name ) {
			$product = $product['data'];
		}

		if ( 'woocommerce_get_price_html' === $current_filter_name && 'booking' !== $product->product_type ) {
			return $price;
		}

		if ( 'subscription' === $product->product_type && 'woocommerce_subscriptions_product_price_string' !== $current_filter_name ) {
			return $price;
		}

		if ( 'variable-subscription' === $product->product_type && 'woocommerce_variable_subscription_price_html' !== $current_filter_name ) {
			return $price;
		}

		if ( 'subscription_variation' === $product->product_type && 'woocommerce_subscriptions_product_price_string' !== $current_filter_name ) {
			return $price;
		}
		if ( 'subscription_variation' === $product->product_type && 'woocommerce_subscriptions_product_price_string' === $current_filter_name ) {
			$current_filter_name = 'woocommerce_variation_subscription_price_html';
		}

		$do_apply_global = true;
		$products_incl = get_option( 'wcj_global_price_labels_products_incl', array() );
		if ( ! empty( $products_incl ) ) $do_apply_global = ( in_array( $product->id, $products_incl ) ) ? true : false;
		$products_excl = get_option( 'wcj_global_price_labels_products_excl', array() );
		if ( ! empty( $products_excl ) ) $do_apply_global = ( in_array( $product->id, $products_excl ) ) ? false : true;
		$product_categories = get_the_terms( $product->id, 'product_cat' );
		$product_categories_incl = get_option( 'wcj_global_price_labels_product_cats_incl', array() );
		if ( ! empty( $product_categories_incl ) ) {
			$do_apply_global = false;
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					if ( in_array( $product_category->term_id, $product_categories_incl ) ) {
						$do_apply_global = true;
						break;
					}
				}
			}
		}
		$product_categories_excl = get_option( 'wcj_global_price_labels_product_cats_excl', array() );
		if ( ! empty( $product_categories_excl ) ) {
			$do_apply_global = true;
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					if ( in_array( $product_category->term_id, $product_categories_excl ) ) {
						$do_apply_global = false;
						break;
					}
				}
			}
		}
		if ( $do_apply_global ) {
			// Global price labels - Add text before price
			$text_to_add_before = apply_filters( 'wcj_get_option_filter', '', get_option( 'wcj_global_price_labels_add_before_text' ) );
			if ( '' != $text_to_add_before )
				$price = $text_to_add_before . $price;
			// Global price labels - Add text after price
			$text_to_add_after = get_option( 'wcj_global_price_labels_add_after_text' );
			if ( '' != $text_to_add_after )
				$price = $price . $text_to_add_after;

			// Global price labels - Between regular and sale prices
			$text_to_add_between_regular_and_sale = get_option( 'wcj_global_price_labels_between_regular_and_sale_text' );
			if ( '' != $text_to_add_between_regular_and_sale )
				$price = apply_filters( 'wcj_get_option_filter', $price, str_replace( '</del> <ins>', '</del>' . $text_to_add_between_regular_and_sale . '<ins>', $price ) );

			// Global price labels - Remove text from price
			$text_to_remove = apply_filters( 'wcj_get_option_filter', '', get_option( 'wcj_global_price_labels_remove_text' ) );
			if ( '' != $text_to_remove )
				$price = str_replace( $text_to_remove, '', $price );
			// Global price labels - Replace in price
			$text_to_replace = apply_filters( 'wcj_get_option_filter', '', get_option( 'wcj_global_price_labels_replace_text' ) );
			$text_to_replace_with = apply_filters( 'wcj_get_option_filter', '', get_option( 'wcj_global_price_labels_replace_with_text' ) );
			if ( '' != $text_to_replace && '' != $text_to_replace_with )
				$price = str_replace( $text_to_replace, $text_to_replace_with, $price );
		}

		// Per product
		if ( 'yes' === get_option( 'wcj_local_price_labels_enabled', 'yes' ) ) {

			foreach ( $this->custom_tab_sections as $custom_tab_section ) {

				$labels_array = array();

				foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {

					//$option_name = $this->custom_tab_group_name;
					$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;
					$labels_array[ 'variation' . $custom_tab_section_variation ] = get_post_meta( $product->post->ID, '_' . $option_name, true );

					/* if ( $custom_tab_section_variation == '_text' ) {

						//$option_name .= $custom_tab_section_variation . $custom_tab_section;
						$labels_array[ 'variation' . $custom_tab_section_variation ] = get_post_meta($product->post->ID, '_' . $option_name, true );
					}
					else {

						//$option_name .= $custom_tab_section . $custom_tab_section_variation;
						$labels_array[ 'variation' . $custom_tab_section_variation ] = get_post_meta($product->post->ID, '_' . $option_name, true);
					} */

					//$price .= print_r( $labels_array );
				}

				if ( 'on' === $labels_array[ 'variation_enabled' ] ) {

					if (
						( ( 'off' === $labels_array['variation_home'] )     && ( is_front_page() ) ) ||
						( ( 'off' === $labels_array['variation_products'] ) && ( is_archive() ) ) ||
						( ( 'off' === $labels_array['variation_single'] )   && ( is_single() ) ) ||
						( ( 'off' === $labels_array['variation_page'] )     && ( is_page() ) )
					   )
						{
							//$current_filter_name = current_filter();
							if ( 'woocommerce_cart_item_price' === $current_filter_name && 'on' === $labels_array['variation_cart'] )
								continue;

							$variable_filters_array = array(
								'woocommerce_variable_empty_price_html',
								'woocommerce_variable_free_price_html',
								'woocommerce_variable_free_sale_price_html',
								'woocommerce_variable_price_html',
								'woocommerce_variable_sale_price_html',
								'woocommerce_variable_subscription_price_html',
							);

							$variation_filters_array = array(
								'woocommerce_variation_empty_price_html',
								'woocommerce_variation_free_price_html',
								//woocommerce_variation_option_name
								'woocommerce_variation_price_html',
								'woocommerce_variation_sale_price_html',
								'woocommerce_variation_subscription_price_html', // pseudo filter!
							);

							if (
								( in_array( $current_filter_name, $variable_filters_array ) && ( $labels_array['variation_variable'] == 'off' ) ) ||
								( in_array( $current_filter_name, $variation_filters_array ) && ( $labels_array['variation_variation'] == 'off' ) ) ||
								( ! in_array( $current_filter_name, $variable_filters_array ) && ! in_array( $current_filter_name, $variation_filters_array ) )
							   )
								$price = $this->customize_price( $price, $custom_tab_section, $labels_array['variation_text'] );
						}
				}

				//unset( $labels_array );
			}
		}

//		return do_shortcode( $price . $current_filter_name . $product->product_type . $labels_array['variation_variable'] . $labels_array['variation_variation'] );
		global $wcj_product_id_for_shortcode;
		$wcj_product_id_for_shortcode = ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id;
		$result = do_shortcode( $price );
		$wcj_product_id_for_shortcode = 0;
		return $result;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_price_labels_settings', $settings );
		return $this->add_standard_settings( $settings );
	}

	/*
	 * add_settings_hook.
	 *
	 * @version 2.3.7
	 * @since   2.3.7
	 */
	function add_settings_hook() {
		add_filter( 'wcj_price_labels_settings', array( $this, 'add_settings' ) );
	}

	/*
	 * add_settings.
	 *
	 * @version 2.5.0
	 * @since   2.3.7
	 */
	function add_settings() {

		$product_cats = array();
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		foreach ( $product_categories as $product_category ) {
			$product_cats[ $product_category->term_id ] = $product_category->name;
		}

		$products = wcj_get_products();

		$settings = array(
			array(
				'title'     => __( 'Custom Price Labels - Globally', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This section lets you set price labels for all products globally.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_options',
			),
			array(
				'title'     => __( 'Add before the price', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Enter text to add before all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_add_before_text',
				'default'   => '',
				'type'      => 'textarea',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => __( 'Add after the price', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Enter text to add after all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_add_after_text',
				'default'   => '',
				'type'      => 'textarea',
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => __( 'Add between regular and sale prices', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Enter text to add between regular and sale prices. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_between_regular_and_sale_text',
				'default'   => '',
				'type'      => 'textarea',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => __( 'Remove from price', 'woocommerce-jetpack' ),
//				'desc'      => __( 'Enable the Custom Price Labels feature', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Enter text to remove from all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_remove_text',
				'default'   => '',
				'type'      => 'textarea',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => __( 'Replace in price', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Enter text to replace in all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_replace_text',
				'default'   => '',
				'type'      => 'textarea',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
				            => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => '',
				'desc_tip'  => __( 'Enter text to replace with. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_replace_with_text',
				'default'   => '',
				'type'      => 'textarea',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => __( 'Products - Include', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Apply global price labels only for selected products. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_products_incl',
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $products,
			),
			array(
				'title'     => __( 'Products - Exclude', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Do not apply global price labels only for selected products. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_products_excl',
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $products,
			),
			array(
				'title'     => __( 'Product Categories - Include', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Apply global price labels only for selected product categories. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_product_cats_incl',
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $product_cats,
			),
			array(
				'title'     => __( 'Product Categories - Exclude', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Do not apply global price labels only for selected product categories. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_global_price_labels_product_cats_excl',
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $product_cats,
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_global_price_labels_options',
			),
			array(
				'title'     => __( 'Custom Price Labels - Per Product', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_local_price_labels_options'
			),
			array(
				'title'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc'      => __( 'This will add metaboxes to each product\'s admin edit page.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_local_price_labels_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_local_price_labels_options',
			),
		);
		return $settings;
	}
}

endif;

return new WCJ_Price_Labels();
