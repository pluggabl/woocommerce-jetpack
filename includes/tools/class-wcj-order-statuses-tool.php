<?php
/**
 * Booster for WooCommerce - Tool - Order Statuses
 *
 * @version 7.2.5
 * @since   3.2.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Order_Statuses_Tool' ) ) :

		/**
		 * WCJ_Order_Statuses_Tool.
		 *
		 * @version 7.1.6
		 * @since   3.2.2
		 */
	class WCJ_Order_Statuses_Tool {

		/**
		 * The module id
		 *
		 * @var varchar $id Module.
		 */
		public $id;
		/**
		 * The module module
		 *
		 * @var varchar $module Module.
		 */
		public $module;
		/**
		 * Constructor.
		 *
		 * @version 3.2.2
		 * @since   3.2.2
		 * @param int    $tool_id get id.
		 * @param string $module get module.
		 */
		public function __construct( $tool_id, $module ) {
			$this->id     = $tool_id;
			$this->module = $module;
		}

		/**
		 * Add_custom_status.
		 *
		 * @version 3.6.0
		 * @param int | string | bool $new_status Get new status.
		 * @param string              $new_status_label Get status label.
		 * @param string              $new_status_icon_content Get icon content.
		 * @param int | string        $new_status_icon_color get icon color.
		 * @param int | string        $new_status_text_color Get text color.
		 */
		public function add_custom_status( $new_status, $new_status_label, $new_status_icon_content, $new_status_icon_color, $new_status_text_color ) {
			// Checking function arguments.
			if ( '' === $new_status ) {
				return '<div class="error"><p>' . __( 'Status slug is empty. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
			}
			if ( strlen( $new_status ) > 17 ) {
				return '<div class="error"><p>' . __( 'The length of status slug must be 17 or less characters (excluding prefix). Status was not added!', 'woocommerce-jetpack' ) .
				'</p></div>';
			}
			if ( '' === $new_status_label ) {
				return '<div class="error"><p>' . __( 'Status label is empty. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
			}
			// Checking status for duplicates.
			$new_key      = 'wc-' . $new_status;
			$all_statuses = wcj_get_order_statuses( false );
			if ( isset( $all_statuses[ $new_key ] ) ) {
				return '<div class="error"><p>' . __( 'Duplicate slug. Status was not added!', 'woocommerce-jetpack' ) . '</p></div>';
			}
			// Adding custom status.
			$statuses_updated             = $this->module->get_custom_order_statuses();
			$statuses_updated[ $new_key ] = $new_status_label;
			$result                       = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
			$result                       = update_option(
				'wcj_orders_custom_status_icon_data_' . $new_status,
				array(
					'content'    => $new_status_icon_content,
					'color'      => $new_status_icon_color,
					'text_color' => $new_status_text_color,
				)
			);
			return ( true === $result ?
				'<div class="updated"><p>' . sprintf(
					/* translators: %s: search term */
					__( '%s status has been successfully added.', 'woocommerce-jetpack' ),
					'<code>' . $new_status . '</code>'
				) . '</p></div>' :
			'<div class="error"><p>' . sprintf(
				/* translators: %s: search term */
				__( '%s status was not added!', 'woocommerce-jetpack' ),
				'<code>' . $new_status . '</code>'
			) . '</p></div>'
			);
		}

		/**
		 * Edit_custom_status.
		 *
		 * @version 3.6.0
		 * @since   3.2.2
		 * @param int | string | bool $new_status Get new status.
		 * @param string              $new_status_label Get status label.
		 * @param string              $new_status_icon_content Get icon content.
		 * @param int | string        $new_status_icon_color get icon color.
		 * @param int | string        $new_status_text_color Get text color.
		 */
		public function edit_custom_status( $new_status, $new_status_label, $new_status_icon_content, $new_status_icon_color, $new_status_text_color ) {
			if ( '' === $new_status_label ) {
				return '<div class="error"><p>' . __( 'Status label is empty. Status was not edited!', 'woocommerce-jetpack' ) . '</p></div>';
			} else {
				$statuses_updated                        = $this->module->get_custom_order_statuses();
				$statuses_updated[ 'wc-' . $new_status ] = $new_status_label;
				$result                                  = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
				$result_icon_data                        = update_option(
					'wcj_orders_custom_status_icon_data_' . $new_status,
					array(
						'content'    => $new_status_icon_content,
						'color'      => $new_status_icon_color,
						'text_color' => $new_status_text_color,
					)
				);
				return ( $result || $result_icon_data ?
				'<div class="updated"><p>' . sprintf(
					/* translators: %s: search term */
					__( '%s status has been successfully edited!', 'woocommerce-jetpack' ),
					'<code>' . $new_status . '</code>'
				) . '</p></div>' :
				'<div class="error"><p>' . sprintf(
					/* translators: %s: search term */
					__( '%s status was not edited!', 'woocommerce-jetpack' ),
					'<code>' . $new_status . '</code>'
				) . '</p></div>'
				);
			}
		}

		/**
		 * Delete_custom_status.
		 *
		 * @version 3.2.2
		 * @since   3.2.2
		 * @todo    (maybe) change all orders to fallback status
		 * @todo    (maybe) option to change fallback status from 'on-hold' to any other status
		 * @param int | string $status get status.
		 */
		public function delete_custom_status( $status ) {
			$statuses_updated = $this->module->get_custom_order_statuses();
			unset( $statuses_updated[ $status ] );
			$result = update_option( 'wcj_orders_custom_statuses_array', $statuses_updated );
			delete_option( 'wcj_orders_custom_status_icon_data_' . substr( $status, 3 ) );
			return ( true === $result ?
			'<div class="updated"><p>' . sprintf(
				/* translators: %s: search term */
				__( '%s status has been successfully deleted.', 'woocommerce-jetpack' ),
				'<code>' . $status . '</code>'
			) . '</p></div>' :
			'<div class="error"><p>' . sprintf(
				/* translators: %s: search term */
				__( '%s status delete failed!', 'woocommerce-jetpack' ),
				'<code>' . $status . '</code>'
			) . '</p></div>'
			);
		}

		/**
		 * Delete_custom_status_all.
		 *
		 * @version 3.2.2
		 * @since   3.2.2
		 */
		public function delete_custom_status_all() {
			$custom_statuses = $this->module->get_custom_order_statuses( true );
			delete_option( 'wcj_orders_custom_statuses_array' );
			foreach ( $custom_statuses as $slug => $label ) {
				delete_option( 'wcj_orders_custom_status_icon_data_' . $slug );
			}
			wp_safe_redirect( remove_query_arg( 'delete_all' ) );
			exit;
		}

		/**
		 * Get_custom_statuses_table.
		 *
		 * @version 7.2.4
		 * @since   3.2.2
		 */
		public function get_custom_statuses_table() {
			$table_data      = array();
			$table_header    = array(
				__( 'Slug', 'woocommerce-jetpack' ),
				__( 'Label', 'woocommerce-jetpack' ),
				__( 'Icon Code', 'woocommerce-jetpack' ),
				__( 'Color', 'woocommerce-jetpack' ),
				__( 'Text Color', 'woocommerce-jetpack' ),
				__( 'Actions', 'woocommerce-jetpack' ),
			);
			$all_statuses    = wcj_get_order_statuses( false );
			$custom_statuses = $this->module->get_custom_order_statuses();
			foreach ( $all_statuses as $status => $status_name ) {
				$row = array(
					esc_attr( $status ),
					esc_html( $status_name ),
				);
				if ( ! array_key_exists( $status, $custom_statuses ) ) {
					$row = array_merge( $row, array( '', '', '', '' ) );
				} else {
					$icon_data       = $this->module->get_status_icon_data( substr( $status, 3 ) );
					$color_html      = '<input disabled type="color" value="' . $icon_data['color'] . '">';
					$text_color_html = '<input disabled type="color" value="' . $icon_data['text_color'] . '">';
					$delete_button   = '<a class="button-primary" href="' . esc_url(
						add_query_arg(
							array(
								'delete' => $status,
								'delete_custom_status-nonce' => wp_create_nonce( 'delete_custom_status' ),
							),
							remove_query_arg( array( 'edit', 'edit-custom-statuses-nonce', 'delete_custom_status-nonce', 'delete_all_custom_status-nonce' ) )
						)
					) .
					'" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>';
					$edit_button     = '<a class="button-primary"' . ( '' !== apply_filters( 'booster_message', '', 'desc' ) ?
					' disabled title="' . __( 'Get Booster Plus to enable.', 'woocommerce-jetpack' ) . '"' :
					' href="' . esc_url(
						add_query_arg(
							array(
								'edit' => $status,
								'edit-custom-statuses-nonce' => wp_create_nonce( 'edit-custom-statuses' ),
							),
							remove_query_arg(
								array(
									'delete',
									'edit-custom-statuses-nonce',
									'delete_all_custom_status-nonce',
								)
							)
						)
					) . '"' ) . '>' . __( 'Edit', 'woocommerce-jetpack' ) . '</a>';
					$row             = array_merge(
						$row,
						array(
							$icon_data['content'],
							$color_html,
							$text_color_html,
							$delete_button . ' ' . $edit_button,
						)
					);
				}
				$table_data[] = $row;
			}
			$columns_styles = array();
			for ( $i = 1; $i <= 6; $i++ ) {
				$columns_styles[] = 'width:16%;';
			}
			return ( ! empty( $table_data ) ?
			'<h4>' . __( 'Statuses', 'woocommerce-jetpack' ) . '</h4>' .
				wcj_get_table_html(
					array_merge( array( $table_header ), $table_data ),
					array(
						'table_class'        => 'widefat striped',
						'table_heading_type' => 'horizontal',
						'columns_styles'     => $columns_styles,
					)
				) :
			''
			);
		}

		/**
		 * Get_custom_statuses_add_edit_table.
		 *
		 * @version 7.2.4
		 * @since   3.2.2
		 */
		public function get_custom_statuses_add_edit_table() {
			$wpnonce    = isset( $_REQUEST['edit-custom-statuses-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['edit-custom-statuses-nonce'] ), 'edit-custom-statuses' ) : false;
			$is_editing = ( $wpnonce && isset( $_GET['edit'] ) );
			if ( $is_editing ) {
				$edit_slug             = sanitize_text_field( wp_unslash( $_GET['edit'] ) );
				$custom_order_statuses = $this->module->get_custom_order_statuses();
				$edit_label            = isset( $custom_order_statuses[ $edit_slug ] ) ? $custom_order_statuses[ $edit_slug ] : '';
				$edit_icon_data        = $this->module->get_status_icon_data( substr( $edit_slug, 3 ) );
			}
			$slug_html             = '<input type="text" name="new_status" style="width:100%;"' . ( $is_editing ? ' value="' . substr( $edit_slug, 3 ) . '" readonly' : '' ) . '>';
			$label_html            = '<input type="text" name="new_status_label" style="width:100%;"' . ( $is_editing ? ' value="' . $edit_label . '"' : '' ) . '>';
			$icon_code_input_html  = '<input type="text" name="new_status_icon_content" value="' . ( $is_editing ? $edit_icon_data['content'] : 'e011' ) . '">';
			$icon_color_input_html = '<input type="color" name="new_status_icon_color" value="' . ( $is_editing ? $edit_icon_data['color'] : '#999999' ) . '">';
			$text_color_input_html = '<input type="color" name="new_status_text_color" value="' . ( $is_editing ? $edit_icon_data['text_color'] : '#000000' ) . '">';
			$action_button         = '<input class="button-primary" type="submit" name="' . ( $is_editing ? 'edit_custom_status' : 'add_custom_status' ) . '"' .
				' value="' . ( $is_editing ? __( 'Edit custom status', 'woocommerce-jetpack' ) : __( 'Add new custom status', 'woocommerce-jetpack' ) ) . '">';
			$clear_button          = ( $is_editing ?
				' <a class="button-primary" href="' . ( remove_query_arg( array( 'delete', 'edit', 'edit-custom-statuses-nonce', 'delete_custom_status-nonce', 'delete_all_custom_status-nonce' ) ) ) . '">' . __( 'Clear', 'woocommerce-jetpack' ) . '</a>' : '' );
			$nonce_field           = wp_nonce_field( $is_editing ? 'edit_custom_status' : 'add_custom_status', $is_editing ? 'edit_custom_status-nonce' : 'add_custom_status-nonce' );
			$table_data            = array(
				array(
					__( 'Slug (without <code>wc-</code> prefix)', 'woocommerce-jetpack' ),
					__( 'Label', 'woocommerce-jetpack' ),
					__( 'Icon Code', 'woocommerce-jetpack' ),
					__( 'Color', 'woocommerce-jetpack' ),
					__( 'Text Color', 'woocommerce-jetpack' ),
					__( 'Actions', 'woocommerce-jetpack' ),
				),
				array(
					$slug_html,
					$label_html,
					$icon_code_input_html,
					$icon_color_input_html,
					$text_color_input_html,
					$action_button . $clear_button . $nonce_field,
				),
			);
			$columns_styles        = array();
			for ( $i = 1; $i <= 6; $i++ ) {
				$columns_styles[] = 'width:16%;';
			}
			return '<h4>' . ( $is_editing ? __( 'Edit', 'woocommerce-jetpack' ) : __( 'Add', 'woocommerce-jetpack' ) ) . '</h4>' .
			'<form method="post" action="' . esc_url( remove_query_arg( 'delete' ) ) . '">' .
				wcj_get_table_html(
					$table_data,
					array(
						'table_class'        => 'widefat striped',
						'table_heading_type' => 'horizontal',
						'columns_styles'     => $columns_styles,
					)
				) .
			'</form>';
		}

		/**
		 * Process_actions.
		 *
		 * @version
		 * @since   3.2.2
		 * @todo    (maybe) use `init` hook for processing actions
		 */
		public function process_actions() {
			$add_custom_status_wpnonce        = isset( $_REQUEST['add_custom_status-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['add_custom_status-nonce'] ), 'add_custom_status' ) : false;
			$edit_custom_status_wpnonce       = isset( $_REQUEST['edit_custom_status-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['edit_custom_status-nonce'] ), 'edit_custom_status' ) : false;
			$delete_custom_status_wpnonce     = isset( $_REQUEST['delete_custom_status-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['delete_custom_status-nonce'] ), 'delete_custom_status' ) : false;
			$delete_all_custom_status_wpnonce = isset( $_REQUEST['delete_all_custom_status-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['delete_all_custom_status-nonce'] ), 'delete_all_custom_status' ) : false;

			$new_status              = isset( $_POST['new_status'] ) ? sanitize_text_field( wp_unslash( sanitize_key( $_POST['new_status'] ) ) ) : '';
			$new_status_label        = isset( $_POST['new_status_label'] ) ? sanitize_text_field( wp_unslash( $_POST['new_status_label'] ) ) : '';
			$new_status_icon_content = isset( $_POST['new_status_icon_content'] ) ? sanitize_text_field( wp_unslash( $_POST['new_status_icon_content'] ) ) : '';
			$new_status_icon_color   = isset( $_POST['new_status_icon_color'] ) ? sanitize_text_field( wp_unslash( $_POST['new_status_icon_color'] ) ) : '';
			$new_status_text_color   = isset( $_POST['new_status_text_color'] ) ? sanitize_text_field( wp_unslash( $_POST['new_status_text_color'] ) ) : '';

			if ( $add_custom_status_wpnonce && isset( $_POST['add_custom_status'] ) ) {
				return $this->add_custom_status( $new_status, $new_status_label, $new_status_icon_content, $new_status_icon_color, $new_status_text_color );
			} elseif ( $edit_custom_status_wpnonce && isset( $_POST['edit_custom_status'] ) ) {
				return $this->edit_custom_status( $new_status, $new_status_label, $new_status_icon_content, $new_status_icon_color, $new_status_text_color );
			} elseif ( $delete_custom_status_wpnonce && isset( $_GET['delete'] ) && '' !== $_GET['delete'] ) {
				return $this->delete_custom_status( sanitize_text_field( wp_unslash( $_GET['delete'] ) ) );
			} elseif ( $delete_all_custom_status_wpnonce && isset( $_GET['delete_all'] ) && '' !== $_GET['delete_all'] ) {
				return $this->delete_custom_status_all();
			}
		}

		/**
		 * Get_delete_all_custom_statuses_button.
		 *
		 * @version 7.2.4
		 * @since   3.2.2
		 */
		public function get_delete_all_custom_statuses_button() {
			return '<p> <a class="button-primary" href="' . esc_url(
				add_query_arg(
					array(
						'delete_all'                     => '1',
						'delete_all_custom_status-nonce' => wp_create_nonce( 'delete_all_custom_status' ),
					),
					(
						remove_query_arg(
							array( 'edit', 'delete', 'edit-custom-statuses-nonce', 'delete_custom_status-nonce' )
						)
					)
				)
			) .
			'" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete All Custom Statuses', 'woocommerce-jetpack' ) . '</a>' .
			'</p>';
		}

		/**
		 * Create_tool.
		 *
		 * @version 7.2.5
		 * @since   3.2.2
		 */
		public function create_tool() {
			$html  = '';
			$html .= '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			$html .= '<div class="wrap">';
			$html .= $this->process_actions();
			$html .= $this->module->get_tool_header_html( $this->id );
			$html .= $this->get_custom_statuses_add_edit_table();
			$html .= $this->get_custom_statuses_table();
			$html .= $this->get_delete_all_custom_statuses_button();
			$html .= '</div>';
			$html .= '</div>';

			$allowed_tags                 = wp_kses_allowed_html( 'post' );
			$allowed_tags['a']['onclick'] = true;
			echo wp_kses( $html, $allowed_tags );
		}
	}

endif;
