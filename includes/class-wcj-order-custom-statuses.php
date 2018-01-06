<?php
/**
 * Booster for WooCommerce - Module - Order Custom Statuses
 *
 * @version 3.2.2
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Order_Custom_Statuses' ) ) :

class WCJ_Order_Custom_Statuses extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.2
	 * @todo    check all changes from Custom Order Status plugin
	 * @todo    `wcj_orders_custom_statuses_processing_and_completed_actions` to Custom Order Status plugin
	 * @todo    (maybe) add options to change icon and icon's color for all statuses (i.e. not only custom)
	 */
	function __construct() {

		$this->id         = 'order_custom_statuses';
		$this->short_desc = __( 'Order Custom Statuses', 'woocommerce-jetpack' );
		$this->desc       = __( 'Custom statuses for WooCommerce orders.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-order-custom-statuses';
		parent::__construct();

		$this->add_tools( array(
			'custom_statuses' => array(
				'title' => __( 'Custom Statuses', 'woocommerce-jetpack' ),
				'desc'  => __( 'Tool lets you add, edit or delete any custom status for WooCommerce orders.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			add_filter( 'wc_order_statuses',                  array( $this, 'add_custom_statuses_to_filter' ), PHP_INT_MAX );
			add_action( 'init',                               array( $this, 'register_custom_post_statuses' ) );
			add_action( 'admin_head',                         array( $this, 'hook_statuses_icons_css' ) );

			add_filter( 'woocommerce_default_order_status',   array( $this, 'set_default_order_status' ), PHP_INT_MAX );

			if ( 'yes' === get_option( 'wcj_orders_custom_statuses_add_to_reports' ) ) {
				add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_custom_order_statuses_to_reports' ), PHP_INT_MAX );
			}

			if ( 'yes' === get_option( 'wcj_orders_custom_statuses_add_to_bulk_actions' ) ) {
				add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ), 11 );
			}

			if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_orders_custom_statuses_add_to_order_list_actions', 'no' ) ) ) {
				add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_status_actions_buttons' ), PHP_INT_MAX, 2 );
				add_action( 'admin_head',                      array( $this, 'add_custom_status_actions_buttons_css' ) );
			}

			if ( 'hide' != apply_filters( 'booster_option', 'hide', get_option( 'wcj_orders_custom_statuses_processing_and_completed_actions', 'hide' ) ) ) {
				add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_status_to_processing_and_completed_actions' ), PHP_INT_MAX, 2 );
			}

			// Is order editable
			if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_orders_custom_statuses_is_order_editable', 'no' ) ) ) {
				add_filter( 'wc_order_is_editable', array( $this, 'add_custom_order_statuses_to_order_editable' ), PHP_INT_MAX, 2 );
			}

			// "Order Statuses" tool
			include_once( 'tools/class-wcj-order-statuses-tool.php' );
			$this->custom_statuses_tool = new WCJ_Order_Statuses_Tool( 'custom_statuses', $this );

		}
	}

	/**
	 * get_custom_order_statuses.
	 *
	 * @version 3.1.2
	 * @since   3.1.2
	 */
	function get_custom_order_statuses( $cut_prefix = false ) {
		$orders_custom_statuses = get_option( 'wcj_orders_custom_statuses_array', '' );
		if ( empty( $orders_custom_statuses ) ) {
			return array();
		} else {
			if ( $cut_prefix ) {
				$orders_custom_statuses_no_prefix = array();
				foreach( $orders_custom_statuses as $status => $status_name ) {
					$orders_custom_statuses_no_prefix[ substr( $status, 3 ) ] = $status_name;
				}
				return $orders_custom_statuses_no_prefix;
			} else {
				return $orders_custom_statuses;
			}
		}
	}

	/**
	 * add_custom_order_statuses_to_order_editable.
	 *
	 * @version 3.1.2
	 * @since   3.1.2
	 */
	function add_custom_order_statuses_to_order_editable( $is_editable, $_order ) {
		return ( in_array( $_order->get_status(), array_keys( $this->get_custom_order_statuses( true ) ) ) ? true : $is_editable );
	}

	/**
	 * add_custom_status_to_processing_and_completed_actions.
	 *
	 * @version 3.2.2
	 * @since   2.8.0
	 */
	function add_custom_status_to_processing_and_completed_actions( $actions, $_order ) {
		$custom_order_statuses = $this->get_custom_order_statuses();
		if ( ! empty( $custom_order_statuses ) && is_array( $custom_order_statuses ) ) {
			$custom_order_statuses_without_wc_prefix = array();
			foreach ( $custom_order_statuses as $slug => $label ) {
				$custom_order_statuses_without_wc_prefix[] = substr( $slug, 3 );
			}
			global $post;
			$default_actions = array();
			$show = apply_filters( 'booster_option', 'hide', get_option( 'wcj_orders_custom_statuses_processing_and_completed_actions', 'hide' ) );
			if (
				( 'show_both' === $show || 'show_processing' === $show ) &&
				$_order->has_status( array_merge( array( 'pending', 'on-hold' ), $custom_order_statuses_without_wc_prefix ) )
			) {
				$default_actions['processing'] = array(
					'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $post->ID ),
						'woocommerce-mark-order-status' ),
					'name'      => __( 'Processing', 'woocommerce' ),
					'action'    => "processing",
				);
			}
			if (
				( 'show_both' === $show || 'show_complete' === $show ) &&
				$_order->has_status( array_merge( array( 'pending', 'on-hold', 'processing' ), $custom_order_statuses_without_wc_prefix ) )
			) {
				$default_actions['complete'] = array(
					'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $post->ID ),
						'woocommerce-mark-order-status' ),
					'name'      => __( 'Complete', 'woocommerce' ),
					'action'    => "complete",
				);
			}
			$actions = array_merge( $default_actions, $actions );
		}
		return $actions;
	}

	/**
	 * add_custom_status_actions_buttons.
	 *
	 * @version 3.2.2
	 * @since   2.6.0
	 */
	function add_custom_status_actions_buttons( $actions, $_order ) {
		$custom_order_statuses = $this->get_custom_order_statuses();
		if ( ! empty( $custom_order_statuses ) && is_array( $custom_order_statuses ) ) {
			foreach ( $custom_order_statuses as $slug => $label ) {
				$custom_order_status = substr( $slug, 3 );
				if ( ! $_order->has_status( array( $custom_order_status ) ) ) { // if order status is not $custom_order_status
					$actions[ $custom_order_status ] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . $custom_order_status . '&order_id=' .
							wcj_get_order_id( $_order ) ), 'woocommerce-mark-order-status' ),
						'name'      => $label,
						'action'    => "view " . $custom_order_status, // setting "view" for proper button CSS
					);
				}
			}
		}
		return $actions;
	}

	/**
	 * get_status_icon_data.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function get_status_icon_data( $status_slug_without_wc_prefix ) {
		$return = array(
			'content' => 'e011',
			'color'   => '#999999',
		);
		if ( '' != ( $icon_data = get_option( 'wcj_orders_custom_status_icon_data_' . $status_slug_without_wc_prefix, '' ) ) ) {
			$return['content'] = $icon_data['content'];
			$return['color']   = $icon_data['color'];
		}
		return $return;
	}

	/**
	 * add_custom_status_actions_buttons_css.
	 *
	 * @version 3.2.2
	 * @since   2.6.0
	 */
	function add_custom_status_actions_buttons_css() {
		$custom_order_statuses = $this->get_custom_order_statuses( true );
		foreach ( $custom_order_statuses as $slug => $label ) {
			$icon_data   = $this->get_status_icon_data( $slug );
			$color_style = ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_orders_custom_statuses_add_to_order_list_actions_colored', 'no' ) ) ) ?
				' color: ' . $icon_data['color'] . ' !important;' : '';
			echo '<style>.view.' . $slug . '::after { font-family: WooCommerce !important;' . $color_style .
				' content: "\\' . $icon_data['content'] . '" !important; }</style>';
		}
	}

	/**
	 * add_custom_order_statuses_to_reports.
	 *
	 * @version 3.2.2
	 */
	function add_custom_order_statuses_to_reports( $order_statuses ) {
		if ( is_array( $order_statuses ) && in_array( 'completed', $order_statuses ) ) {
			return array_merge( $order_statuses, array_keys( $this->get_custom_order_statuses( true ) ) );
		} else {
			return $order_statuses;
		}
	}

	/**
	 * set_default_order_status.
	 *
	 * @version 3.2.2
	 */
	function set_default_order_status( $status ) {
		return ( 'wcj_no_changes' != ( $default_status = get_option( 'wcj_orders_custom_statuses_default_status', 'pending' ) ) ? $default_status : $status );
	}

	/**
	 * register_custom_post_statuses.
	 *
	 * @version 3.2.2
	 */
	function register_custom_post_statuses() {
		$custom_statuses = $this->get_custom_order_statuses();
		foreach ( $custom_statuses as $slug => $label )
			register_post_status( $slug, array(
					'label'                     => $label,
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ),
				)
			);
	}

	/**
	 * add_custom_statuses_to_filter.
	 *
	 * @version 3.2.2
	 */
	function add_custom_statuses_to_filter( $order_statuses ) {
		return array_merge( ( '' == $order_statuses ? array() : $order_statuses ), $this->get_custom_order_statuses() );
	}

	/**
	 * hook_statuses_icons_css.
	 *
	 * @verison 3.2.2
	 */
	function hook_statuses_icons_css() {
		$output   = '';
		$statuses = $this->get_custom_order_statuses( true );
		foreach( $statuses as $status => $status_name ) {
			$icon_data = $this->get_status_icon_data( $status );
			$output .= 'mark.' . $status . '::after { content: "\\' . $icon_data['content'] . '"; color: ' . $icon_data['color'] . '; }';
			$output .= 'mark.' . $status . ':after {font-family:WooCommerce;speak:none;font-weight:400;font-variant:normal;text-transform:none;' .
				'line-height:1;-webkit-font-smoothing:antialiased;margin:0;text-indent:0;position:absolute;top:0;left:0;width:100%;height:100%;text-align:center}';
		}
		if ( '' != $output ) {
			echo '<style>' . $output . '</style>';
		}
	}

	/**
	 * create_custom_statuses_tool.
	 *
	 * @version 3.2.2
	 */
	function create_custom_statuses_tool() {
		return $this->custom_statuses_tool->create_tool();
	}

	/**
	 * Add extra bulk action options to mark orders as complete or processing
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @version 3.2.2
	 * @since   2.2.7
	 */
	function bulk_admin_footer() {
		global $post_type;
		if ( 'shop_order' == $post_type ) {
			?><script type="text/javascript"><?php
			foreach( wcj_get_order_statuses() as $key => $order_status ) {
				if ( in_array( $key, array( 'processing', 'on-hold', 'completed', ) ) ) continue;
				?>jQuery(function() {
					jQuery('<option>').val('mark_<?php echo $key; ?>').text('<?php echo __( 'Mark', 'woocommerce-jetpack' ) . ' ' .
						$order_status; ?>').appendTo('select[name="action"]');
					jQuery('<option>').val('mark_<?php echo $key; ?>').text('<?php echo __( 'Mark', 'woocommerce-jetpack' ) . ' ' .
						$order_status; ?>').appendTo('select[name="action2"]');
				});<?php
			}
			?></script><?php
		}
	}

}

endif;

return new WCJ_Order_Custom_Statuses();
