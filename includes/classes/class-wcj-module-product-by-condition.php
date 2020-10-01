<?php
/**
 * Booster for WooCommerce - Module - Product by Condition
 *
 * @version 5.1.1
 * @since   3.6.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Module_Product_By_Condition' ) ) :

abstract class WCJ_Module_Product_By_Condition extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.7.1
	 * @since   3.6.0
	 */
	function __construct( $type = 'module' ) {

		parent::__construct( $type );

		if ( $this->is_enabled() ) {
			// Product meta box
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			// Core
			if ( wcj_is_frontend() ) {
				if ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'is_visible' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'is_purchasable' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'pre_get_posts' ) );
					if ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_query_widgets', 'no' ) ) {
						add_filter( 'woocommerce_products_widget_query_args', array( $this, 'products_widget_query' ), PHP_INT_MAX );
					}
				}
				$this->maybe_add_extra_frontend_filters();
			}
			// Quick and bulk edit
			if (
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_' . $this->id . '_admin_bulk_edit', 'no' ) ) ||
				'yes' === wcj_get_option( 'wcj_' . $this->id . '_admin_quick_edit', 'no' )
			) {
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_' . $this->id . '_admin_bulk_edit', 'no' ) ) ) {
					add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
				}
				if ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_admin_quick_edit', 'no' ) ) {
					add_action( 'woocommerce_product_quick_edit_end', array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
				}
				add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
			}
			// Admin products list
			if ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_admin_add_column', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),   PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
			}

			// Disable pre_get_posts query when exporting products
			add_filter( 'wcj_product_by_condition_pre_get_posts_validation', array( $this, 'disable_pre_get_posts_on_export' ) );

			// Regenerate invisible products transient
			add_action( 'save_meta_box_' . $this->id, array( $this, 'regenerate_invisible_products_transient' ) );
			add_action( 'save_bulk_and_quick_edit_fields_' . $this->id, array( $this, 'regenerate_invisible_products_transient' ) );
		}
	}

	/**
	 * get_invisible_products_ids.
	 *
	 * @version 4.7.1
	 * @since   4.7.1
	 *
	 * @param null $params
	 *
	 * @return array
	 */
	function get_invisible_products_ids( $params = null ) {
		$params             = wp_parse_args( $params, array(
			'option_to_check'               => $this->get_check_option(),
			'invisible_products_query_args' => $this->get_invisible_products_query_args()
		) );
		$option_to_check    = $params['option_to_check'];
		$args               = $params['invisible_products_query_args'];
		$transient_name     = 'wcj_' . $this->id . '_' . md5( serialize( $args ) . '_' . serialize( $option_to_check ) );
		$invisible_products = get_transient( $transient_name );
		if ( false === $invisible_products ) {
			$invisible_products = array();
			$loop               = new WP_Query( $args );
			foreach ( $loop->posts as $product_id ) {
				if ( ! $this->is_product_visible( $product_id, $option_to_check ) ) {
					$invisible_products[] = $product_id;
				}
			}
			set_transient( $transient_name, $invisible_products, YEAR_IN_SECONDS );
		}
		return $invisible_products;
	}

	/**
	 * get_invisible_products_query_args.
	 *
	 * @version 4.7.1
	 * @since   4.7.1
	 *
	 * @return array
	 */
	function get_invisible_products_query_args() {
		$meta_query = array();
		if ( 'invisible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			$meta_query[] = array(
				'key'     => '_' . 'wcj_' . $this->id . '_visible',
				'value'   => '',
				'compare' => '!=',
			);
		}
		if ( 'visible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			$meta_query[] = array(
				'key'     => '_' . 'wcj_' . $this->id . '_invisible',
				'value'   => '',
				'compare' => '!=',
			);
		}
		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'OR';
		}
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'meta_query'     => $meta_query,
		);
		return $args;
	}

	/**
	 * delete_invisible_products_transient.
	 *
	 * @version 5.1.1
	 * @since   4.7.1
	 *
	 * @param null $params
	 */
	function delete_invisible_products_transient( $params = null ) {
		$params = wp_parse_args( $params, array(
			'remove_method'                 => 'all_roles', // current_user_roles | all_roles
			'option_to_check'               => $this->get_check_option(),
			'invisible_products_query_args' => $this->get_invisible_products_query_args()
		) );
		if ( 'current_user_roles' === $params['remove_method'] ) {
			$option_to_check = $params['option_to_check'];
			$args            = $params['invisible_products_query_args'];
			$transient_name  = 'wcj_' . $this->id . '_' . md5( serialize( $args ) . '_' . serialize( $option_to_check ) );
			delete_transient( $transient_name );
		} elseif ( 'all_roles' === $params['remove_method'] ) {
			global $wpdb;
			$sql = "delete from {$wpdb->options} where option_name REGEXP %s";
			$wpdb->query( $wpdb->prepare( $sql, '^_transient_' . 'wcj_' . $this->id ) );
		}
	}

	/**
	 * regenerate_invisible_products_transient.
	 *
	 * @version 4.7.1
	 * @since   4.7.1
	 */
	function regenerate_invisible_products_transient() {
		$this->delete_invisible_products_transient();
		$this->get_invisible_products_ids();
	}

	/**
	 * save_meta_box.
	 *
	 * @version 4.7.1
	 * @since   4.7.1
	 *
	 * @param $post_id
	 * @param $__post
	 */
	function save_meta_box( $post_id, $__post ) {
		parent::save_meta_box( $post_id, $__post );
		do_action( 'save_meta_box_' . $this->id, $post_id, $__post );
	}

	/**
	 * Disables pre_get_posts query when exporting products.
	 *
	 * @version 4.7.0
	 * @since   4.7.0
	 *
	 */
	function disable_pre_get_posts_on_export( $validation ) {
		if (
			! isset( $_REQUEST['action'] ) ||
			'woocommerce_do_ajax_product_export' !== $_REQUEST['action']
		) {
			return $validation;
		}
		$validation = false;
		return $validation;
	}

	/**
	 * add_product_columns.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function add_product_columns( $columns ) {
		$columns[ 'wcj_' . $this->id ] = $this->title;
		return $columns;
	}

	/**
	 * render_product_column.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function render_product_column( $column ) {
		if ( 'wcj_' . $this->id === $column ) {
			$result = '';
			if ( 'invisible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
				if ( $visible = get_post_meta( wcj_maybe_get_product_id_wpml( get_the_ID() ), '_' . 'wcj_' . $this->id . '_visible', true ) ) {
					if ( is_array( $visible ) ) {
						$result .= '<span style="color:green;">' . implode( ', ', $visible ) . '</span>';
					}
				}
			}
			if ( 'visible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
				if ( $invisible = get_post_meta( wcj_maybe_get_product_id_wpml( get_the_ID() ), '_' . 'wcj_' . $this->id . '_invisible', true ) ) {
					if ( is_array( $invisible ) ) {
						if ( '' != $result ) {
							$result .= '<br>';
						}
						$result .= '<span style="color:red;">' . implode( ', ', $invisible ) . '</span>';
					}
				}
			}
			echo $result;
		}
	}

	/**
	 * add_bulk_and_quick_edit_fields.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function add_bulk_and_quick_edit_fields() {
		$all_options = '';
		$all_options .= '<option value="wcj_no_change" selected>' . __( '— No change —', 'woocommerce' ) . '</option>';
		foreach ( $this->get_options_list() as $id => $desc ) {
			$all_options .= '<option value="' . $id . '">' . $desc . '</option>';
		}
		if ( 'invisible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			?><br class="clear" />
			<label>
				<span class="title"><?php echo $this->title . ': ' . esc_html_e( 'Visible', 'woocommerce-jetpack' ); ?></span>
				<select multiple id="wcj_<?php echo $this->id; ?>_visible" name="wcj_<?php echo $this->id; ?>_visible[]">
					<?php echo $all_options; ?>
				</select>
			</label><?php
		}
		if ( 'visible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			?><br class="clear" />
			<label>
				<span class="title"><?php echo $this->title . ': ' . esc_html_e( 'Invisible', 'woocommerce-jetpack' ); ?></span>
				<select multiple id="wcj_<?php echo $this->id; ?>_invisible" name="wcj_<?php echo $this->id; ?>_invisible[]">
					<?php echo $all_options; ?>
				</select>
			</label><?php
		}
	}

	/**
	 * save_bulk_and_quick_edit_fields.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function save_bulk_and_quick_edit_fields( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		// Check nonce.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}
		// Check bulk or quick edit.
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) { // WPCS: input var ok.
			if ( 'no' === wcj_get_option( 'wcj_' . $this->id . '_admin_quick_edit', 'no' ) ) {
				return $post_id;
			}
		} else {
			if ( 'no' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_' . $this->id . '_admin_bulk_edit', 'no' ) ) ) {
				return $post_id;
			}
		}
		// Save.
		if ( 'invisible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			if ( ! isset( $_REQUEST[ 'wcj_' . $this->id . '_visible' ] ) ) {
				update_post_meta( $post_id, '_' . 'wcj_' . $this->id . '_visible', array() );
			} elseif ( is_array( $_REQUEST[ 'wcj_' . $this->id . '_visible' ] ) && ! in_array( 'wcj_no_change', $_REQUEST[ 'wcj_' . $this->id . '_visible' ] ) ) {
				update_post_meta( $post_id, '_' . 'wcj_' . $this->id . '_visible', $_REQUEST[ 'wcj_' . $this->id . '_visible' ] );
			}
		}
		if ( 'visible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			if ( ! isset( $_REQUEST[ 'wcj_' . $this->id . '_invisible' ] ) ) {
				update_post_meta( $post_id, '_' . 'wcj_' . $this->id . '_invisible', array() );
			} elseif ( is_array( $_REQUEST[ 'wcj_' . $this->id . '_invisible' ] ) && ! in_array( 'wcj_no_change', $_REQUEST[ 'wcj_' . $this->id . '_invisible' ] ) ) {
				update_post_meta( $post_id, '_' . 'wcj_' . $this->id . '_invisible', $_REQUEST[ 'wcj_' . $this->id . '_invisible' ] );
			}
		}
		do_action( 'save_bulk_and_quick_edit_fields_' . $this->id, $post_id, $post );
		return $post_id;
	}

	/**
	 * products_widget_query.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) check if pagination needs to be fixed (as in `$this->pre_get_posts()`)
	 */
	function products_widget_query( $query_args ) {
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		$option_to_check        = $this->get_check_option();
		$post__not_in           = ( isset( $query_args['post__not_in'] ) ? $query_args['post__not_in'] : array() );
		$args                   = $query_args;
		$args['fields']         = 'ids';
		$args['posts_per_page'] = -1;
		$loop                   = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			if ( ! $this->is_product_visible( $product_id, $option_to_check ) ) {
				$post__not_in[] = $product_id;
			}
		}
		$query_args['post__not_in'] = $post__not_in;
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		return $query_args;
	}

	/**
	 * pre_get_posts.
	 *
	 * @version 4.7.1
	 * @since   3.6.0
	 * @todo    [dev] maybe move `if ( ! function_exists( 'is_user_logged_in' ) ) {`
	 */
	function pre_get_posts( $query ) {
		if (
			( is_admin() && ! wp_doing_ajax() ) ||
			false === apply_filters( 'wcj_product_by_condition_pre_get_posts_validation', true, $this, $query )
		) {
			return;
		}
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			require_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		$invisible_products = $this->get_invisible_products_ids();
		$post__not_in       = $query->get( 'post__not_in' );
		$post__not_in       = empty( $post__not_in ) ? array() : $post__not_in;
		$query->set( 'post__not_in', array_unique( array_merge( $post__not_in, $invisible_products ) ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	/**
	 * is_purchasable.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function is_purchasable( $purchasable, $_product ) {
		return ( ! $this->is_product_visible( wcj_get_product_id_or_variation_parent_id( $_product ), $this->get_check_option() ) ? false : $purchasable );
	}

	/**
	 * is_visible.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function is_visible( $visible, $product_id ) {
		return ( ! $this->is_product_visible( $product_id, $this->get_check_option() ) ? false : $visible );
	}

	/**
	 * is_product_visible.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) replace with `abstract is_product_visible()`
	 */
	function is_product_visible( $product_id, $option_to_check ) {
		if ( 'invisible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			$visible = get_post_meta( wcj_maybe_get_product_id_wpml( $product_id ), '_' . 'wcj_' . $this->id . '_visible', true );
			if ( ! empty( $visible ) && is_array( $visible ) ) {
				if ( is_array( $option_to_check ) ) {
					$the_intersect = array_intersect( $visible, $option_to_check );
					if ( empty( $the_intersect ) ) {
						return false;
					}
				} else {
					if ( ! in_array( $option_to_check, $this->maybe_extra_options_process( $visible ) ) ) {
						return false;
					}
				}
			}
		}
		if ( 'visible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
			$invisible = get_post_meta( wcj_maybe_get_product_id_wpml( $product_id ), '_' . 'wcj_' . $this->id . '_invisible', true );
			if ( ! empty( $invisible ) && is_array( $invisible ) ) {
				if ( is_array( $option_to_check ) ) {
					$the_intersect = array_intersect( $invisible, $option_to_check );
					if ( ! empty( $the_intersect ) ) {
						return false;
					}
				} else {
					if ( in_array( $option_to_check, $this->maybe_extra_options_process( $invisible ) ) ) {
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * add_settings_from_file.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function add_settings_from_file( $settings ) {
		return $this->maybe_fix_settings( require( wcj_plugin_path() . '/includes/settings/wcj-settings-product-by-condition.php' ) );
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_meta_box_options() {
		$filename = wcj_plugin_path() . '/includes/settings/meta-box/wcj-settings-meta-box-product-by-condition.php';
		return ( file_exists ( $filename ) ? require( $filename ) : array() );
	}

	/**
	 * maybe_add_extra_frontend_filters.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) replace with action
	 */
	function maybe_add_extra_frontend_filters() {
		return false;
	}

	/**
	 * maybe_extra_options_process.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) replace with filter (or remove completely - i.e. `abstract is_product_visible()`)
	 */
	function maybe_extra_options_process( $options ) {
		return $options;
	}

	/**
	 * maybe_add_extra_settings.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) replace with filter
	 */
	function maybe_add_extra_settings() {
		return array();
	}

	/**
	 * get_options_list.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	abstract function get_options_list();

	/**
	 * get_check_option.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	abstract function get_check_option();

}

endif;
