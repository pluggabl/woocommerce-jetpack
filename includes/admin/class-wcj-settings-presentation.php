<?php
/**
 * Presentation metadata for four existing native module forms.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * No option reads, settings evaluation, rendering or save behavior.
 */
class WCJ_Settings_Presentation {
	/**
	 * Whether this module has an explicit presentation map.
	 *
	 * @param string $section Native module identifier.
	 * @return bool
	 */
	public static function supports( $section ) {
		return in_array( $section, array( 'add_to_cart', 'more_button_labels', 'price_labels', 'related_products' ), true );
	}

	/**
	 * Enqueue only on the supported modern admin page, before the head is printed.
	 *
	 * @param string $page Native admin page.
	 * @param string $section Native module identifier.
	 */
	public static function enqueue( $page, $section ) {
		if ( 'wcj-plugins' !== $page || ! self::supports( $section ) ) {
			return;
		}
		wp_enqueue_style( 'wcj-settings-presentation', wcj_plugin_url() . '/assets/css/admin/wcj-settings-presentation.css', array( 'wcj-admin-new-style' ), '1.2' );
		wp_enqueue_script( 'wcj-settings-presentation', wcj_plugin_url() . '/assets/js/admin/wcj-settings-presentation.js', array( 'wcj-admin-new-script' ), '1.2', true );
	}

	/**
	 * Attach metadata from the already resolved native definitions.
	 *
	 * @param string $section Native module identifier.
	 * @param array  $settings Existing filtered definitions, evaluated exactly once by the caller.
	 */
	public static function configure( $section, $settings ) {
		$config = self::get_config( $section, $settings );
		if ( $config && wp_script_is( 'wcj-settings-presentation', 'enqueued' ) ) {
			wp_localize_script( 'wcj-settings-presentation', 'wcjSettingsPresentation', $config );
		}
	}

	/**
	 * Produce a bounded map. Missing, duplicate or altered shapes fail open.
	 *
	 * @param string $section Native module identifier.
	 * @param array  $settings Already resolved native definitions.
	 * @return array
	 */
	public static function get_config( $section, $settings ) {
		if ( ! self::supports( $section ) || ! is_array( $settings ) ) {
			return array();
		}
		$map    = self::get_map( $section );
		$fields = array();
		$tab    = '';
		$index  = 0;
		foreach ( $settings as $setting ) {
			++$index;
			if ( ! is_array( $setting ) || ! isset( $setting['id'], $setting['type'] ) || ! is_string( $setting['id'] ) || ! is_string( $setting['type'] ) ) {
				continue;
			}
			if ( 'tab_start' === $setting['type'] ) {
				$tab = $setting['id'];
			} elseif ( 'tab_end' === $setting['type'] ) {
				$tab = '';
			}
			$id = $setting['id'];
			if ( isset( $fields[ $id ] ) ) {
				$fields[ $id ] = false;
				continue;
			}
			$label         = ! empty( $setting['title'] ) ? $setting['title'] : ( isset( $setting['desc'] ) ? $setting['desc'] : '' );
			$fields[ $id ] = array(
				'type'    => $setting['type'],
				'label'   => is_scalar( $label ) ? wp_strip_all_tags( (string) $label ) : '',
				'default' => isset( $setting['default'] ) ? $setting['default'] : '',
				'tab'     => $tab,
				'index'   => $index,
			);
		}
		$groups = array();
		foreach ( $map['groups'] as $group ) {
			$descriptors = array();
			$last_index  = null;
			foreach ( $group['fields'] as $id => $types ) {
				if ( empty( $fields[ $id ] ) || ! in_array( $fields[ $id ]['type'], $types, true ) || $group['tab'] !== $fields[ $id ]['tab'] || ( null !== $last_index && $last_index + 1 !== $fields[ $id ]['index'] ) ) {
					continue 2;
				}
				$descriptors[ $id ] = $fields[ $id ];
				$last_index         = $fields[ $id ]['index'];
			}
			if ( isset( $group['controller'] ) ) {
				$id = $group['controller'];
				if ( empty( $fields[ $id ] ) || 'checkbox' !== $fields[ $id ]['type'] || $group['tab'] !== $fields[ $id ]['tab'] ) {
					continue;
				}
				$group['controller_field'] = $fields[ $id ];
			}
			$group['fields'] = $descriptors;
			$groups[]        = $group;
		}
		return array(
			'section'          => $section,
			'tabs'             => $map['tabs'],
			'groups'           => $groups,
			'labels'           => array(
				'essentials' => __( 'Essentials', 'woocommerce-jetpack' ),
				'help'       => __( 'Use the existing tabs and save buttons. Active or changed advanced options stay expanded. Edition restrictions still apply.', 'woocommerce-jetpack' ),
				'showAll'    => __( 'Show all settings', 'woocommerce-jetpack' ),
				'allShown'   => __( 'All settings are visible. Your unsaved edits are unchanged. Select a tab to return to the tabbed view.', 'woocommerce-jetpack' ),
			),
			'visible_advanced' => 'more_button_labels' === $section ? array(
				'id'    => 'wcj_checkout_place_order_button_override',
				'title' => __( 'Advanced: Payment gateway text override', 'woocommerce-jetpack' ),
				'help'  => __( 'Keep this visible option off unless a payment gateway replaces your checkout button text.', 'woocommerce-jetpack' ),
			) : null,
		);
	}

	/**
	 * Explicit cohort, not a general settings renderer or schema.
	 *
	 * @param string $section Native module identifier.
	 * @return array
	 */
	private static function get_map( $section ) {
		$groups = array();
		$tabs   = array();
		if ( 'add_to_cart' === $section ) {
			$tabs   = array(
				'add_to_cart_per_category_tab'     => __( 'Manage category groups below. Save after changing the number of groups.', 'woocommerce-jetpack' ),
				'add_to_cart_per_product_tab'      => __( 'Enable per-product labels here, then edit individual labels on the product edit screen.', 'woocommerce-jetpack' ),
				'add_to_cart_per_product_type_tab' => __( 'Start with single-product and archive labels. Conditional labels apply when this section is enabled.', 'woocommerce-jetpack' ),
			);
			$titles = array(
				'simple'   => __( 'Advanced: Simple product conditions', 'woocommerce-jetpack' ),
				'variable' => __( 'Advanced: Variable product conditions', 'woocommerce-jetpack' ),
				'external' => __( 'Advanced: External product conditions', 'woocommerce-jetpack' ),
				'grouped'  => __( 'Advanced: Grouped product conditions', 'woocommerce-jetpack' ),
				'other'    => __( 'Advanced: Other product conditions', 'woocommerce-jetpack' ),
			);
			foreach ( $titles as $product_type => $title ) {
				$ids = array();
				if ( 'variable' !== $product_type ) {
					foreach ( array( 'archives_not_in_stock', 'single_sale', 'archives_sale', 'single_zero_price', 'archives_zero_price', 'archives_no_price' ) as $condition ) {
						$ids[ 'wcj_add_to_cart_text_on_' . $condition . '_' . $product_type ] = array( 'text' );
					}
				}
				if ( 'external' !== $product_type ) {
					foreach ( array( 'single_in_cart', 'archives_in_cart' ) as $condition ) {
						$ids[ 'wcj_add_to_cart_text_on_' . $condition . '_' . $product_type ] = array( 'text' );
					}
				}
				$groups[] = array(
					'tab'        => 'add_to_cart_per_product_type_tab',
					'title'      => $title,
					'fields'     => $ids,
					'controller' => 'wcj_add_to_cart_text_enabled',
				);
			}
		} elseif ( 'more_button_labels' === $section ) {
			$tabs = array( 'more_button_labels_order_button_tab' => __( 'Set checkout button text, or leave it blank to keep the WooCommerce default. Both settings remain visible.', 'woocommerce-jetpack' ) );
		} elseif ( 'price_labels' === $section ) {
			$tabs = array(
				'price_labels_per_product_tab' => __( 'Enable per-product editing below, then set labels on each product edit screen.', 'woocommerce-jetpack' ),
				'price_labels_globally_tab'    => __( 'Start with text before or after the price. HTML and blank values keep their existing meaning.', 'woocommerce-jetpack' ),
			);
			$sets = array(
				array( __( 'Advanced: Sale price text', 'woocommerce-jetpack' ), array( 'between_regular_and_sale_text' => array( 'textarea' ) ) ),
				array( __( 'Advanced: Remove or replace price text', 'woocommerce-jetpack' ), array_fill_keys( array( 'remove_text', 'replace_text', 'replace_with_text', 'instead_text' ), array( 'textarea' ) ) ),
				array( __( 'Advanced: Limit which products receive labels', 'woocommerce-jetpack' ), array_fill_keys( array( 'products_incl', 'products_excl', 'product_cats_incl', 'product_cats_excl', 'product_types_incl' ), array( 'multiselect' ) ) ),
			);
			foreach ( $sets as $set ) {
				$ids = array();
				foreach ( $set[1] as $suffix => $types ) {
					$ids[ 'wcj_global_price_labels_' . $suffix ] = $types;
				}
				$groups[] = array(
					'tab'    => 'price_labels_globally_tab',
					'title'  => $set[0],
					'fields' => $ids,
				);
			}
		} elseif ( 'related_products' === $section ) {
			$tabs = array(
				'wcj_related_product_genral_options_tab' => __( 'Choose the number of related products and grid columns.', 'woocommerce-jetpack' ),
				'wcj_related_product_order_tab'          => __( 'Choose how related products are sorted. Random sorting ignores the ascending or descending order.', 'woocommerce-jetpack' ),
				'wcj_related_product_relate_tab'         => __( 'Category and tag relationships are enabled by default. Review them before changing advanced matching.', 'woocommerce-jetpack' ),
				'wcj_related_product_hide_tab'           => __( 'Hide can remove the whole related-products section. Review its state before using the filters.', 'woocommerce-jetpack' ),
			);
			$sets = array(
				array(
					'wcj_related_product_relate_tab',
					__( 'Advanced: Match product attributes', 'woocommerce-jetpack' ),
					array(
						'by_attribute_enabled'         => array( 'checkbox' ),
						'by_attribute_attribute_type'  => array( 'select' ),
						'by_attribute_attribute_name'  => array( 'text' ),
						'by_attribute_attribute_value' => array( 'text' ),
					),
				),
				array(
					'wcj_related_product_relate_tab',
					__( 'Advanced: Select related products manually', 'woocommerce-jetpack' ),
					array(
						'per_product'             => array( 'checkbox' ),
						'per_product_box_type'    => array( 'select' ),
						'per_product_cmb_default' => array( 'select' ),
					),
				),
				array(
					'wcj_related_product_hide_tab',
					__( 'Advanced: Limit where related products are hidden', 'woocommerce-jetpack' ),
					array(
						'hide_cats_incl'     => array( 'multiselect' ),
						'hide_cats_excl'     => array( 'multiselect' ),
						'hide_tags_incl'     => array( 'multiselect' ),
						'hide_tags_excl'     => array( 'multiselect' ),
						'hide_products_incl' => array( 'multiselect', 'text' ),
						'hide_products_excl' => array( 'multiselect', 'text' ),
					),
				),
			);
			foreach ( $sets as $set ) {
				$ids = array();
				foreach ( $set[2] as $suffix => $types ) {
					$ids[ 'wcj_product_info_related_products_' . $suffix ] = $types;
				}
				$group = array(
					'tab'    => $set[0],
					'title'  => $set[1],
					'fields' => $ids,
				);
				if ( 'wcj_related_product_hide_tab' === $set[0] ) {
					$group['controller'] = 'wcj_product_info_related_products_hide';
				}
				$groups[] = $group;
			}
		}
		return array(
			'tabs'   => $tabs,
			'groups' => $groups,
		);
	}
}
