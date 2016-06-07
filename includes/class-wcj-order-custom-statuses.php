<?php
/**
 * WooCommerce Jetpack Order Custom Statuses
 *
 * The WooCommerce Jetpack Order Custom Statuses class.
 *
 * @version 2.5.2
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Order_Custom_Statuses' ) ) :

class WCJ_Order_Custom_Statuses extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->id         = 'order_custom_statuses';
		$this->short_desc = __( 'Order Custom Statuses', 'woocommerce-jetpack' );
		$this->desc       = __( 'Custom statuses for WooCommerce orders.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-order-custom-statuses/';
		parent::__construct();

		$this->add_tools( array(
			'custom_statuses' => array(
				'title' => __( 'Custom Statuses', 'woocommerce-jetpack' ),
				'desc'  => __( 'Tool lets you add or delete any custom status for WooCommerce orders.', 'woocommerce-jetpack' ),
			),
		) );

		$this->default_statuses = array(
			'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
			'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
		);

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
		}
	}

	/**
	 * add_custom_order_statuses_to_reports.
	 *
	 * @version 2.3.8
	 */
	public function add_custom_order_statuses_to_reports( $order_statuses ) {
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
	public function set_default_order_status() {
		return get_option( 'wcj_orders_custom_statuses_default_status', 'pending' );
	}

	/**
	 * register_custom_post_statuses.
	 */
	public function register_custom_post_statuses() {
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
	public function add_custom_statuses_to_filter( $order_statuses ) {
		$wcj_orders_custom_statuses_array = ( '' == get_option( 'wcj_orders_custom_statuses_array' ) ) ? array() : get_option( 'wcj_orders_custom_statuses_array' );
		$order_statuses = ( '' == $order_statuses ) ? array() : $order_statuses;
		return array_merge( $order_statuses, $wcj_orders_custom_statuses_array );
	}

	/**
	 * hook_statuses_icons_css.
	 *
	 * @verison 2.5.2
	 */
	public function hook_statuses_icons_css() {
		$output = '<style>';
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		foreach( $statuses as $status => $status_name ) {
			if ( ! array_key_exists( $status, $this->default_statuses ) ) {
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
	 * @version 2.5.2
	 */
	public function add_custom_status( $new_status, $new_status_label, $new_status_icon_content, $new_status_icon_color ) {

		// Checking function arguments
		if ( ! isset( $new_status ) || '' == $new_status ) {
			return '<div class="error"><p>' . __( 'Status slug is empty. Status not added.', 'woocommerce-jetpack' ) . '</p></div>';
		}
		if ( strlen( $new_status ) > 17 ) {
			return '<div class="error"><p>' . __( 'The length of status slug must be 17 or less characters.', 'woocommerce-jetpack' ) . '</p></div>';
		}
		if ( ! isset( $new_status_label ) || '' == $new_status_label ) {
			return '<div class="error"><p>' . __( 'Status label is empty. Status not added.', 'woocommerce-jetpack' ) . '</p></div>';
		}

		// Checking status
		$statuses_updated = ( '' == get_option( 'wcj_orders_custom_statuses_array' ) ) ? array() : get_option( 'wcj_orders_custom_statuses_array' );
		$new_key = 'wc-' . $_POST['new_status'];
		if ( isset( $statuses_updated[ $new_key ] ) )
			return '<div class="error"><p>' . __( 'Duplicate slug. Status not added.', 'woocommerce-jetpack' ) . '</p></div>';
		$statuses_updated[ $new_key ] = $_POST['new_status_label'];

		// Adding custom status
		$result = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
		$result = update_option( 'wcj_orders_custom_status_icon_data_' . $new_status, array(
			'content' => $new_status_icon_content,
			'color'   => $new_status_icon_color,
		) );
		if ( true === $result ) {
			return '<div class="updated"><p>' . __( 'New status have been successfully added!', 'woocommerce-jetpack' ) . '</p></div>';
		} else {
			return '<div class="error"><p>' . __( 'Status was not added.', 'woocommerce-jetpack' ) . '</p></div>';
		}
	}

	/**
	 * create_custom_statuses_tool.
	 *
	 * @version 2.5.2
	 */
	public function create_custom_statuses_tool() {
		$result_message = '';
		if ( isset( $_POST['add_custom_status'] ) ) {
			$result_message = $this->add_custom_status( $_POST['new_status'], $_POST['new_status_label'], $_POST['new_status_icon_content'], $_POST['new_status_icon_color'] );
		} elseif ( isset( $_GET['delete'] ) && ( '' != $_GET['delete'] ) ) {
			$statuses_updated = apply_filters( 'wc_order_statuses', $statuses_updated );
			unset( $statuses_updated[ $_GET['delete'] ] );
			$result = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
			if ( true === $result ) {
				$result_message = '<div class="updated"><p>' . __( 'Status have been successfully deleted.', 'woocommerce-jetpack' ) . '</p></div>';
			} else {
				$result_message = '<div class="error"><p>' . __( 'Delete failed.', 'woocommerce-jetpack' ) . '</p></div>';
			}
		}
		echo $this->get_back_to_settings_link_html() . '<br>';
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
				echo '<th>' . __( 'Delete', 'woocommerce-jetpack' ) . '</th>';
				echo '</tr>';
				$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
				foreach( $statuses as $status => $status_name ) {
					echo '<tr>';
					echo '<td>' . esc_attr( $status ) . '</td>';
					echo '<td>' . esc_html( $status_name ) . '</td>';
					if ( array_key_exists( $status, $this->default_statuses ) ) {
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
						echo '<td>' . '<a href="' . add_query_arg( 'delete', $status ) . '">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>' . '</td>';
					}
					echo '</tr>';
				}
			?></table>
			<p></p>
		</div><?php
		?><div class="metabox-holder" style="width:300px;">
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Add', 'woocommerce-jetpack' ); ?></span></h3>
					<div class="inside">
						<form method="post" action="<?php echo remove_query_arg( 'delete' ); ?>">
							<ul>
								<li><?php _e( 'Slug (without wc- prefix)', 'woocommerce-jetpack' ); ?> <input type="text" name="new_status" style="width:100%;"></li>
								<li><?php _e( 'Label', 'woocommerce-jetpack' ); ?> <input type="text" name="new_status_label" style="width:100%;"></li>
								<li><?php _e( 'Icon Code', 'woocommerce-jetpack' ); ?> <input type="text" name="new_status_icon_content" value="e011"></li>
								<li><?php _e( 'Icon Color', 'woocommerce-jetpack' ); ?> <input type="color" name="new_status_icon_color" value="#999999"><br><?php
									echo sprintf( __( 'You can check icon codes <a target="_blank" href="%s">here</a>.', 'woocommerce-jetpack' ), 'https://rawgit.com/woothemes/woocommerce-icons/master/demo.html' ); ?></li>
							</ul>
							<input class="button-primary" type="submit" name="add_custom_status" value="Add new custom status">
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
	public function bulk_admin_footer() {
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
	 */
	function get_order_statuses() {
		$result = array();
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		foreach( $statuses as $status => $status_name ) {
			$result[ substr( $status, 3 ) ] = $statuses[ $status ];
		}
		return $result;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Custom Statuses', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_orders_custom_statuses_options',
			),
			array(
				'title'    => __( 'Default Order Status', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable Custom Statuses feature to add custom statuses to the list.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can change the default order status here. However payment gateways can change this status immediatelly on order creation. E.g. BACS gateway will change status to On-hold.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_custom_statuses_default_status',
				'default'  => apply_filters( 'woocommerce_default_order_status', 'pending' ),
				'type'     => 'select',
				'options'  => $this->get_order_statuses(),
			),
			array(
				'title'    => __( 'Add All Statuses to Admin Order Bulk Actions', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_custom_statuses_add_to_bulk_actions',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Add Custom Statuses to Admin Reports', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_custom_statuses_add_to_reports',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_orders_custom_statuses_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Order_Custom_Statuses();
