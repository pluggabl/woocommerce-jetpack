<?php
/**
 * Booster for WooCommerce - Module - Order Custom Statuses
 *
 * @version 3.1.2
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Order_Custom_Statuses' ) ) :

class WCJ_Order_Custom_Statuses extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.2
	 * @todo    copy all changes from Custom Order Status plugin
	 * @todo    `wcj_orders_custom_statuses_processing_and_completed_actions` to Custom Order Status plugin
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
				'desc'  => __( 'Tool lets you add or delete any custom status for WooCommerce orders.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			add_filter( 'wc_order_statuses',                  array( $this, 'add_custom_statuses_to_filter' ), 100 );
			add_action( 'init',                               array( $this, 'register_custom_post_statuses' ) );
			add_action( 'admin_head',                         array( $this, 'hook_statuses_icons_css' ) );

			add_filter( 'woocommerce_default_order_status',   array( $this, 'set_default_order_status' ), 100 );

			if ( 'yes' === get_option( 'wcj_orders_custom_statuses_add_to_reports' ) ) {
				add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_custom_order_statuses_to_reports' ), PHP_INT_MAX );
			}

			if ( 'yes' === get_option( 'wcj_orders_custom_statuses_add_to_bulk_actions' ) ) {
				add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ), 11 );
			}

			if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_orders_custom_statuses_add_to_order_list_actions', 'no' ) ) ) {
				add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_status_actions_buttons' ), PHP_INT_MAX, 2 );
				add_action( 'admin_head',                      array( $this, 'add_custom_status_actions_buttons_css' ) );
			}

			if ( 'hide' != apply_filters( 'booster_get_option', 'hide', get_option( 'wcj_orders_custom_statuses_processing_and_completed_actions', 'hide' ) ) ) {
				add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_status_to_processing_and_completed_actions' ), PHP_INT_MAX, 2 );
			}

			// Is order editable
			if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_orders_custom_statuses_is_order_editable', 'no' ) ) ) {
				add_filter( 'wc_order_is_editable', array( $this, 'add_custom_order_statuses_to_order_editable' ), PHP_INT_MAX, 2 );
			}

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
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function add_custom_status_to_processing_and_completed_actions( $actions, $_order ) {
		$custom_order_statuses = get_option( 'wcj_orders_custom_statuses_array' );
		if ( ! empty( $custom_order_statuses ) && is_array( $custom_order_statuses ) ) {
			$custom_order_statuses_without_wc_prefix = array();
			foreach ( $custom_order_statuses as $slug => $label ) {
				$custom_order_statuses_without_wc_prefix[] = substr( $slug, 3 );
			}
			global $post;
			$default_actions = array();
			$show = apply_filters( 'booster_get_option', 'hide', get_option( 'wcj_orders_custom_statuses_processing_and_completed_actions', 'hide' ) );
			if ( ( 'show_both' === $show || 'show_processing' === $show ) && $_order->has_status( array_merge( array( 'pending', 'on-hold' ), $custom_order_statuses_without_wc_prefix ) ) ) {
				$default_actions['processing'] = array(
					'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
					'name'      => __( 'Processing', 'woocommerce' ),
					'action'    => "processing",
				);
			}
			if ( ( 'show_both' === $show || 'show_complete' === $show ) && $_order->has_status( array_merge( array( 'pending', 'on-hold', 'processing' ), $custom_order_statuses_without_wc_prefix ) ) ) {
				$default_actions['complete'] = array(
					'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
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
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function add_custom_status_actions_buttons( $actions, $_order ) {
		$custom_order_statuses = get_option( 'wcj_orders_custom_statuses_array' );
		if ( ! empty( $custom_order_statuses ) && is_array( $custom_order_statuses ) ) {
			foreach ( $custom_order_statuses as $slug => $label ) {
				$custom_order_status = substr( $slug, 3 );
				if ( ! $_order->has_status( array( $custom_order_status ) ) ) { // if order status is not $custom_order_status
					$actions[ $custom_order_status ] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . $custom_order_status . '&order_id=' . wcj_get_order_id( $_order ) ), 'woocommerce-mark-order-status' ),
						'name'      => $label,
						'action'    => "view " . $custom_order_status, // setting "view" for proper button CSS
					);
				}
			}
		}
		return $actions;
	}

	/**
	 * add_custom_status_actions_buttons_css.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function add_custom_status_actions_buttons_css() {
		$custom_order_statuses = get_option( 'wcj_orders_custom_statuses_array' );
		if ( ! empty( $custom_order_statuses ) && is_array( $custom_order_statuses ) ) {
			foreach ( $custom_order_statuses as $slug => $label ) {
				$custom_order_status = substr( $slug, 3 );
				if ( '' != ( $icon_data = get_option( 'wcj_orders_custom_status_icon_data_' . $custom_order_status, '' ) ) ) {
					$content = $icon_data['content'];
					$color   = $icon_data['color'];
				} else {
					$content = 'e011';
					$color   = '#999999';
				}
				$color_style = ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_orders_custom_statuses_add_to_order_list_actions_colored', 'no' ) ) ) ? ' color: ' . $color . ' !important;' : '';
				echo '<style>.view.' . $custom_order_status . '::after { font-family: WooCommerce !important;' . $color_style . ' content: "\\' . $content . '" !important; }</style>';
			}
		}
	}

	/**
	 * get_default_order_statuses.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function get_default_order_statuses() {
		return array(
			'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
			'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
		);
	}

	/**
	 * add_custom_order_statuses_to_reports.
	 *
	 * @version 2.3.8
	 */
	function add_custom_order_statuses_to_reports( $order_statuses ) {
		if ( is_array( $order_statuses ) && in_array( 'completed', $order_statuses ) ) {
			$custom_order_statuses = get_option( 'wcj_orders_custom_statuses_array' );
			if ( ! empty( $custom_order_statuses ) && is_array( $custom_order_statuses ) ) {
				foreach ( $custom_order_statuses as $slug => $label ) {
					$order_statuses[] = substr( $slug, 3 );
				}
			}
		}
		return $order_statuses;
	}

	/**
	 * set_default_order_status.
	 */
	function set_default_order_status() {
		return get_option( 'wcj_orders_custom_statuses_default_status', 'pending' );
	}

	/**
	 * register_custom_post_statuses.
	 */
	function register_custom_post_statuses() {
		$wcj_orders_custom_statuses_array = ( '' == get_option( 'wcj_orders_custom_statuses_array' ) ) ? array() : get_option( 'wcj_orders_custom_statuses_array' );
		foreach ( $wcj_orders_custom_statuses_array as $slug => $label )
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
	 */
	function add_custom_statuses_to_filter( $order_statuses ) {
		$wcj_orders_custom_statuses_array = ( '' == get_option( 'wcj_orders_custom_statuses_array' ) ) ? array() : get_option( 'wcj_orders_custom_statuses_array' );
		$order_statuses = ( '' == $order_statuses ) ? array() : $order_statuses;
		return array_merge( $order_statuses, $wcj_orders_custom_statuses_array );
	}

	/**
	 * hook_statuses_icons_css.
	 *
	 * @verison 2.5.6
	 */
	function hook_statuses_icons_css() {
		$output = '<style>';
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		$default_statuses = $this->get_default_order_statuses();
		foreach( $statuses as $status => $status_name ) {
			if ( ! array_key_exists( $status, $default_statuses ) ) {
				if ( '' != ( $icon_data = get_option( 'wcj_orders_custom_status_icon_data_' . substr( $status, 3 ), '' ) ) ) {
					$content = $icon_data['content'];
					$color   = $icon_data['color'];
				} else {
					$content = 'e011';
					$color   = '#999999';
				}
				$output .= 'mark.' . substr( $status, 3 ) . '::after { content: "\\' . $content . '"; color: ' . $color . '; }';
				$output .= 'mark.' . substr( $status, 3 ) . ':after {font-family:WooCommerce;speak:none;font-weight:400;font-variant:normal;text-transform:none;line-height:1;-webkit-font-smoothing:antialiased;margin:0;text-indent:0;position:absolute;top:0;left:0;width:100%;height:100%;text-align:center}';
			}
		}
//		$output .= '.close:after { content: "\e011"; }';
		$output .= '</style>';
		echo $output;
	}

	/**
	 * Add new custom status to wcj_orders_custom_statuses_array.
	 *
	 * @version 2.6.0
	 */
	function add_custom_status( $new_status, $new_status_label, $new_status_icon_content, $new_status_icon_color ) {

		// Checking function arguments
		if ( ! isset( $new_status ) || '' == $new_status ) {
			return '<div class="error"><p>' . __( 'Status slug is empty. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
		}
		if ( strlen( $new_status ) > 17 ) {
			return '<div class="error"><p>' . __( 'The length of status slug must be 17 or less characters. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
		}
		if ( ! isset( $new_status_label ) || '' == $new_status_label ) {
			return '<div class="error"><p>' . __( 'Status label is empty. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
		}

		// Checking status
		$statuses_updated = ( '' == get_option( 'wcj_orders_custom_statuses_array' ) ) ? array() : get_option( 'wcj_orders_custom_statuses_array' );
		$new_key = 'wc-' . $_POST['new_status'];
		if ( isset( $statuses_updated[ $new_key ] ) ) {
			return '<div class="error"><p>' . __( 'Duplicate slug. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
		}
		$default_statuses = $this->get_default_order_statuses();
		if ( isset( $default_statuses[ $new_key ] ) ) {
			return '<div class="error"><p>' . __( 'Duplicate slug (default WooCommerce status). Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
		}
		$statuses_updated[ $new_key ] = $_POST['new_status_label'];

		// Adding custom status
		$result = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
		$result = update_option( 'wcj_orders_custom_status_icon_data_' . $new_status, array(
			'content' => $new_status_icon_content,
			'color'   => $new_status_icon_color,
		) );
		if ( true === $result ) {
			return '<div class="updated"><p>' . __( 'New status has been successfully added!', 'woocommerce-jetpack' ) . '</p></div>';
		} else {
			return '<div class="error"><p>' . __( 'Status was not added.', 'woocommerce-jetpack' ) . '</p></div>';
		}
	}

	/**
	 * create_custom_statuses_tool.
	 *
	 * @version 2.6.0
	 * @todo    (from Custom Order Status for WooCommerce plugin) delete: change all orders to fallback status
	 * @todo    (from Custom Order Status for WooCommerce plugin) delete: option to change fallback status from 'on-hold' to any other status
	 * @todo    (from Custom Order Status for WooCommerce plugin) delete: delete icon data
	 */
	function create_custom_statuses_tool() {
		$result_message = '';
		if ( isset( $_POST['add_custom_status'] ) ) {
			$result_message = $this->add_custom_status( $_POST['new_status'], $_POST['new_status_label'], $_POST['new_status_icon_content'], $_POST['new_status_icon_color'] );
		} elseif ( isset( $_POST['edit_custom_status'] ) ) {
			if ( ! isset( $_POST['new_status_label'] ) || '' == $_POST['new_status_label'] ) {
				$result_message = '<div class="error"><p>' . __( 'Status label is empty. Status was not edited!', 'woocommerce-jetpack' ) . '</p></div>';
			} else {
				$statuses_updated = ( '' == get_option( 'wcj_orders_custom_statuses_array' ) ) ? array() : get_option( 'wcj_orders_custom_statuses_array' );
				$statuses_updated[ 'wc-' . $_POST['new_status'] ] = $_POST['new_status_label'];
				$result = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
				$result_icon_data = update_option( 'wcj_orders_custom_status_icon_data_' . $_POST['new_status'], array(
					'content' => $_POST['new_status_icon_content'],
					'color'   => $_POST['new_status_icon_color'],
				) );
				if ( $result || $result_icon_data ) {
					$result_message = '<div class="updated"><p>' . __( 'Status has been successfully edited!', 'woocommerce-jetpack' ) . '</p></div>';
				} else {
					$result_message = '<div class="error"><p>' . __( 'Status was not edited.', 'woocommerce-jetpack' ) . '</p></div>';
				}
			}
		} elseif ( isset( $_GET['delete'] ) && ( '' != $_GET['delete'] ) ) {
			$statuses_updated = apply_filters( 'wc_order_statuses', array() );
			unset( $statuses_updated[ $_GET['delete'] ] );
			$result = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
			if ( true === $result ) {
				$result_message = '<div class="updated"><p>' . __( 'Status has been successfully deleted.', 'woocommerce-jetpack' ) . '</p></div>';
			} else {
				$result_message = '<div class="error"><p>' . __( 'Delete failed.', 'woocommerce-jetpack' ) . '</p></div>';
			}
		}
		echo '<p>' . $this->get_back_to_settings_link_html() . '</p>';
		?><div>
			<h2><?php echo __( 'Booster - Custom Statuses', 'woocommerce-jetpack' ); ?></h2>
			<p><?php echo __( 'The tool lets you add or delete any custom status for WooCommerce orders.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<h3><?php echo __( 'Statuses', 'woocommerce-jetpack' ); ?></h3>
			<table class="wc_status_table widefat"><?php
				echo '<tr>';
				echo '<th>' . __( 'Slug', 'woocommerce-jetpack' ) . '</th>';
				echo '<th>' . __( 'Label', 'woocommerce-jetpack' ) . '</th>';
				echo '<th>' . __( 'Icon Code', 'woocommerce-jetpack' ) . '</th>';
				echo '<th>' . __( 'Icon Color', 'woocommerce-jetpack' ) . '</th>';
				echo '<th>' . __( 'Actions', 'woocommerce-jetpack' ) . '</th>';
				echo '</tr>';
				$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
				$default_statuses = $this->get_default_order_statuses();
				foreach( $statuses as $status => $status_name ) {
					echo '<tr>';
					echo '<td>' . esc_attr( $status ) . '</td>';
					echo '<td>' . esc_html( $status_name ) . '</td>';
					if ( array_key_exists( $status, $default_statuses ) ) {
						echo '<td></td>';
						echo '<td></td>';
						echo '<td></td>';
					} else {
						if ( '' != ( $icon_data = get_option( 'wcj_orders_custom_status_icon_data_' . substr( $status, 3 ), '' ) ) ) {
							$content = $icon_data['content'];
							$color   = $icon_data['color'];
						} else {
							$content = 'e011';
							$color   = '#999999';
						}
						echo '<td>' . $content . '</td>';
						echo '<td>' . '<input disabled type="color" value="' . $color . '">' . '</td>';
						echo '<td>' . '<a class="button-primary" href="' . add_query_arg( 'delete', $status, remove_query_arg( 'edit' ) ) . '" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>';
						echo    ' ' . '<a class="button-primary"' . ( '' != apply_filters( 'booster_get_message', '', 'desc' ) ? ' disabled title="' . __( 'Get Booster Plus to enable.', 'woocommerce-jetpack' ) . '"' : ' href="' . add_query_arg( 'edit', $status, remove_query_arg( 'delete' ) ) . '"' ) . '>' . __( 'Edit', 'woocommerce-jetpack' ) . '</a>' . '</td>';
					}
					echo '</tr>';
				}
			?></table>
			<p></p>
		</div><?php
		$is_editing = ( isset( $_GET['edit'] ) ) ? true : false;
		if ( $is_editing ) {
			$edit_slug = $_GET['edit'];
			$custom_order_statuses = get_option( 'wcj_orders_custom_statuses_array' );
			$edit_label = isset( $custom_order_statuses[ $edit_slug ] ) ? $custom_order_statuses[ $edit_slug ] : '';
			if ( '' != ( $edit_icon_data = get_option( 'wcj_orders_custom_status_icon_data_' . substr( $edit_slug, 3 ), '' ) ) ) {
				$edit_content = $edit_icon_data['content'];
				$edit_color   = $edit_icon_data['color'];
			} else {
				$edit_content = 'e011';
				$edit_color   = '#999999';
			}
		}
		$icon_code_input_html  = '<input type="text" name="new_status_icon_content" value="' . ( $is_editing ? $edit_content : 'e011' )    . '">';
		$icon_color_input_html = '<input type="color" name="new_status_icon_color" value="'  . ( $is_editing ? $edit_color   : '#999999' ) . '">';
		?><div class="metabox-holder" style="width:300px;">
				<div class="postbox">
					<h3 class="hndle"><span><?php ( $is_editing ? _e( 'Edit', 'woocommerce-jetpack' ) : _e( 'Add', 'woocommerce-jetpack' ) ); ?></span></h3>
					<div class="inside">
						<form method="post" action="<?php echo remove_query_arg( 'delete' ); ?>">
							<ul>
								<li><?php _e( 'Slug (without wc- prefix)', 'woocommerce-jetpack' ); ?> <input type="text" name="new_status" style="width:100%;"<?php if ( $is_editing ) { echo ' value="' . substr( $edit_slug, 3 ) . '" readonly'; } ?>></li>
								<li><?php _e( 'Label', 'woocommerce-jetpack' ); ?> <input type="text" name="new_status_label" style="width:100%;"<?php if ( $is_editing ) { echo ' value="' . $edit_label . '"'; } ?>></li>
								<li><?php _e( 'Icon Code', 'woocommerce-jetpack' ); echo ' ' . $icon_code_input_html; ?><br><?php
									echo '<em>' . sprintf( __( 'You can check icon codes <a target="_blank" href="%s">here</a>.', 'woocommerce-jetpack' ), 'https://rawgit.com/woothemes/woocommerce-icons/master/demo.html' ) . '</em>'; ?></li>
								<li><?php _e( 'Icon Color', 'woocommerce-jetpack' ); echo ' ' . $icon_color_input_html; ?></li>
							</ul>
							<input class="button-primary" type="submit" name="<?php echo ( $is_editing ) ? 'edit_custom_status' : 'add_custom_status'; ?>" value="<?php ( $is_editing ? _e( 'Edit custom status', 'woocommerce-jetpack' ) : _e( 'Add new custom status', 'woocommerce-jetpack' ) ); ?>">
							<?php if ( $is_editing ) { echo ' <a class="button-primary" href="' . remove_query_arg( array( 'delete', 'edit' ) ) . '">' . __( 'Clear', 'woocommerce-jetpack' ) . '</a>'; } ?>
						</form>
					</div>
				</div>
		</div><?php
	}

	/**
	 * Add extra bulk action options to mark orders as complete or processing
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @version 2.2.7
	 * @since   2.2.7
	 */
	function bulk_admin_footer() {
		global $post_type;
		if ( 'shop_order' == $post_type ) {
			?><script type="text/javascript"><?php
			foreach( $this->get_order_statuses() as $key => $order_status ) {
				if ( in_array( $key, array( 'processing', 'on-hold', 'completed', ) ) ) continue;
				?>jQuery(function() {
					jQuery('<option>').val('mark_<?php echo $key; ?>').text('<?php echo __( 'Mark', 'woocommerce-jetpack' ) . ' ' . $order_status; ?>').appendTo('select[name="action"]');
					jQuery('<option>').val('mark_<?php echo $key; ?>').text('<?php echo __( 'Mark', 'woocommerce-jetpack' ) . ' ' . $order_status; ?>').appendTo('select[name="action2"]');
				});<?php
			}
			?></script><?php
		}
	}

	/**
	 * get_order_statuses.
	 *
	 * @todo    use `wcj_get_order_statuses_v2`
	 */
	function get_order_statuses() {
		$result = array();
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		foreach( $statuses as $status => $status_name ) {
			$result[ substr( $status, 3 ) ] = $statuses[ $status ];
		}
		return $result;
	}

}

endif;

return new WCJ_Order_Custom_Statuses();
