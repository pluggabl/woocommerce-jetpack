<?php
/**
 * Booster for WooCommerce - Custom Email
 *
 * An email sent to recipient list when selected triggers are called.
 *
 * @version 6.0.5
 * @since   2.3.9
 * @author  Pluggabl LLC.
 * @extends WC_Email
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_WCJ_Custom' ) ) :
		/**
		 * WC_Email_WCJ_Custom.
		 *
		 * @version 5.3.4
		 */
	class WC_Email_WCJ_Custom extends WC_Email {

		/**
		 * Constructor.
		 *
		 * @version 5.3.4
		 * @param   int $id get id..
		 */
		public function __construct( $id = 1 ) {

			$this->id                 = 'wcj_custom_' . $id;
			$this->customer_email     = ( '%customer%' === $this->get_option( 'recipient' ) );
			$this->original_recipient = $this->get_option( 'recipient' );
			$this->title              = wcj_get_option( 'wcj_emails_custom_emails_admin_title_' . $id, __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $id );
			$this->description        = __( 'Custom emails are sent to the recipient list when selected triggers are called.', 'woocommerce-jetpack' );

			$this->heading = __( 'Custom Heading', 'woocommerce' );
			$this->subject = __( '[{site_title}] Custom Subject - Order ({order_number}) - {order_date}', 'woocommerce-jetpack' );

			// Triggers for this email.
			$trigger_hooks = $this->get_option( 'trigger' );
			if ( ! empty( $trigger_hooks ) && is_array( $trigger_hooks ) ) {
				$is_woocommerce_checkout_order_processed_notification_added = false;
				foreach ( $trigger_hooks as $trigger_hook ) {
					if ( false !== strpos( $trigger_hook, 'woocommerce_new_order_notification' ) && false === $is_woocommerce_checkout_order_processed_notification_added ) {
						add_action( 'woocommerce_checkout_order_processed_notification', array( $this, 'trigger' ), PHP_INT_MAX );
						$is_woocommerce_checkout_order_processed_notification_added = true;
					} else {
						add_action( $trigger_hook, array( $this, 'trigger' ), PHP_INT_MAX );
					}
				}
			}

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			if ( ! $this->customer_email ) {
				$this->recipient = $this->get_option( 'recipient' );

				if ( ! $this->recipient ) {
					$this->recipient = get_option( 'admin_email' );
				}
			}
		}

		/**
		 * Validate Custom Textarea Field.
		 *
		 * @param   string      $key Posted key.
		 * @param   string|null $value Posted Value.
		 * @version 2.5.6
		 * @since   2.5.6
		 * @return  string
		 */
		public function validate_custom_textarea_field( $key, $value ) {
			$value = is_null( $value ) ? '' : $value;
			return stripslashes( $value );
		}

		/**
		 * Generate Custom Textarea HTML.
		 *
		 * @param   mixed $key posted key.
		 * @param   mixed $data get data.
		 * @version 2.5.6
		 * @since   2.5.6
		 * @return  string
		 */
		public function generate_custom_textarea_html( $key, $data ) {
			return $this->generate_textarea_html( $key, $data );
		}

		/**
		 * Proxy to parent's get_option and attempt to localize the result using gettext.
		 *
		 * @version 6.0.0
		 * @since   2.4.1
		 * @param   string $key posted key.
		 * @param   mixed  $empty_value get empty value.
		 * @return  mixed
		 */
		public function get_option( $key, $empty_value = null ) {
			$grandparent = get_parent_class( 'WC_Email' );
			$value       = $grandparent::get_option( $key, $empty_value );
			return ( is_array( $value ) ) ? $value : apply_filters( 'woocommerce_email_get_option', $value, $this, $value, $key, $empty_value );
		}

		/**
		 * Trigger.
		 *
		 * @version 6.0.5
		 *  @param   int $object_id get object id.
		 */
		public function trigger( $object_id ) {

			if ( ! $this->is_enabled() ) {
				return;
			}

			if ( $object_id ) {

				$this->object = wc_get_order( $object_id ); // must be `object` as it's named so in parent class (`WC_Email`). E.g. for attachments.

				if ( 'woocommerce_checkout_order_processed_notification' === current_filter() ) {
					// Check status.
					$is_status_found = false;
					$trigger_hooks   = $this->get_option( 'trigger' );
					foreach ( $trigger_hooks as $trigger_hook ) {
						$pos = strpos( $trigger_hook, 'woocommerce_new_order_notification' );
						if ( false !== ( $pos ) ) {
							$the_status = 'wc-' . substr( $trigger_hook, 35 );
							if ( 'wc-wcj_any_status' === $the_status || wcj_get_order_status( $this->object ) === $the_status ) {
								$is_status_found = true;
								break;
							}
						}
					}
					if ( false === $is_status_found ) {
						return;
					}
				}

				if ( 'woocommerce_created_customer_notification' === current_filter() ) {
					$user            = get_user_by( 'ID', $object_id );
					$this->recipient = $user->user_email;
				} else {
					if ( $this->customer_email ) {
						$this->recipient = wcj_get_order_billing_email( $this->object );
					} elseif ( false !== strpos( $this->original_recipient, '%customer%' ) ) {
						$this->recipient = str_replace( '%customer%', wcj_get_order_billing_email( $this->object ), $this->original_recipient );
					}

					$this->find['order-date']   = '{order_date}';
					$this->find['order-number'] = '{order_number}';

					$this->replace['order-date'] = date_i18n( wc_date_format(), strtotime( wcj_get_order_date( $this->object ) ) );
					if ( ! empty( $this->object ) ) {
						$this->replace['order-number'] = $this->object->get_order_number();
					}
					global $post;
					$post = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->object->post : get_post( $object_id ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					$_GET['order_id'] = $object_id;
				}
			}

			$this->recipient = do_shortcode( $this->recipient );

			if ( ! $this->get_recipient() ) {
				if ( $object_id ) {
					wp_reset_postdata();
				}
				return;
			}

			$this->send( $this->get_recipient(), do_shortcode( $this->get_subject() ), do_shortcode( $this->get_content() ), $this->get_headers(), $this->get_attachments() );

			if ( $object_id ) {
				wp_reset_postdata();
			}
		}

		/**
		 * Get_content_html.
		 *
		 * @version 5.3.4
		 * @access  public
		 * @return  string
		 * @todo    (maybe) use `wc_get_template` for custom templates (same for `get_content_plain()`).
		 */
		public function get_content_html() {
			$content = $this->get_option( 'content_html_template' );
			if ( 'yes' === $this->get_option( 'wrap_in_wc_template' ) ) {
				$content = wcj_wrap_in_wc_email_template( $content, $this->get_heading() );
			}
			return do_shortcode( $content );
		}

		/**
		 * Get_content_plain.
		 *
		 * @version 5.3.4
		 * @access  public
		 * @return  string
		 */
		public function get_content_plain() {
			return do_shortcode( $this->get_option( 'content_plain_template' ) );
		}

		/**
		 * Initialise settings form fields.
		 *
		 * @version 5.6.1
		 * @todo    (maybe) `chosen_select` class in `trigger`.
		 */
		public function init_form_fields() {

			ob_start();
			include 'email-html.php';
			$default_html_template = ob_get_clean();
			ob_start();
			include 'email-plain.php';
			$default_plain_template = ob_get_clean();

			$status_change_triggers = array();
			$new_order_triggers     = array();
			$status_triggers        = array();
			$order_statuses         = wcj_get_order_statuses();
			foreach ( $order_statuses as $slug => $name ) {
				/* translators: %s: search term */
				$new_order_triggers[ 'woocommerce_new_order_notification_' . $slug ] = sprintf( __( 'New order (%s)', 'woocommerce-jetpack' ), $name );
				/* translators: %s: search term */
				$status_triggers[ 'woocommerce_order_status_' . $slug . '_notification' ] = sprintf( __( 'Order status updated to %s', 'woocommerce-jetpack' ), $name );
				foreach ( $order_statuses as $slug2 => $name2 ) {
					if ( $slug !== $slug2 ) {
						/* translators: %1$s,%2$s: search term */
						$status_change_triggers[ 'woocommerce_order_status_' . $slug . '_to_' . $slug2 . '_notification' ] = sprintf( __( 'Order status %1$s to %2$s', 'woocommerce-jetpack' ), $name, $name2 );
					}
				}
			}

			$this->form_fields = array(
				'enabled'                => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'no',
				),
				'trigger'                => array(
					'title'       => __( 'Trigger(s)', 'woocommerce-jetpack' ),
					'type'        => 'multiselect',
					'placeholder' => '',
					'default'     => array(),
					'desc_tip'    => __( 'Please note, that all new orders in WooCommerce by default are created with Pending Payment status. If you want to change the default order status - you can use Booster\'s "Order Custom Statuses" module (in WooCommerce > Settings > Booster > Shipping & Orders > Order Custom Statuses).', 'woocommerce-jetpack' ),
					'description' => __( 'Hold the <code>ctrl</code> key and select the options if you want to trigger mail on more than one option.', 'woocommerce-jetpack' ),
					'options'     => array_merge(
						array(
							'woocommerce_new_order_notification_wcj_any_status'           => __( 'New order (Any status)', 'woocommerce-jetpack' ),
						),
						$new_order_triggers,
						$status_triggers,
						array(
							'woocommerce_reset_password_notification'                     => __( 'Reset password notification', 'woocommerce-jetpack' ),
							'woocommerce_order_fully_refunded_notification'               => __( 'Order fully refunded notification', 'woocommerce-jetpack' ),
							'woocommerce_order_partially_refunded_notification'           => __( 'Order partially refunded notification', 'woocommerce-jetpack' ),
							'woocommerce_new_customer_note_notification'                  => __( 'New customer note notification', 'woocommerce-jetpack' ),
							'woocommerce_low_stock_notification'                          => __( 'Low stock notification', 'woocommerce-jetpack' ),
							'woocommerce_no_stock_notification'                           => __( 'No stock notification', 'woocommerce-jetpack' ),
							'woocommerce_product_on_backorder_notification'               => __( 'Product on backorder notification', 'woocommerce-jetpack' ),
							'woocommerce_created_customer_notification'                   => __( 'Created customer notification', 'woocommerce-jetpack' ),
						),
						$status_change_triggers
					),
					'css'         => 'height:300px;',
				),
				'recipient'              => array(
					'title'       => __( 'Recipient(s)', 'woocommerce' ),
					'type'        => 'text',
					/* translators: %s: search term */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce' ), esc_attr( get_option( 'admin_email' ) ) ) . ' ' .
					/* translators: %customer%: search term */
						__( 'Or enter <code>%customer%</code> to send to customer billing email.', 'woocommerce-jetpack' ),
					'placeholder' => '',
					'default'     => '',
					'css'         => 'width:100%;',
				),
				'subject'                => array(
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					/* translators: %s: search term */
					'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce' ), $this->subject ),
					'placeholder' => '',
					'default'     => '',
					'css'         => 'width:100%;',
				),
				'email_type'             => array(
					'title'       => __( 'Email type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
				'wrap_in_wc_template'    => array(
					'title'   => __( 'Wrap in WC Email Template', 'woocommerce' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				'heading'                => array(
					'type'        => 'text',
					'desc_tip'    => __( 'WC Email Heading. Used only if "Wrap in WC Email Template" is enabled and only for HTML templates.', 'woocommerce-jetpack' ),
					/* translators: %s: search term */
					'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce' ), $this->heading ),
					'placeholder' => '',
					'default'     => '',
					'css'         => 'width:100%;',
				),
				'content_html_template'  => array(
					'title'       => __( 'HTML template', 'woocommerce' ),
					'type'        => 'custom_textarea',
					'desc_tip'    => __( 'You can use shortcodes here. E.g. Booster\'s order shortcodes.', 'woocommerce' ),
					'description' => '',
					'placeholder' => '',
					'default'     => $default_html_template,
					'css'         => 'width:100%;height:500px;',
				),
				'content_plain_template' => array(
					'title'       => __( 'Plain text template', 'woocommerce' ),
					'type'        => 'textarea',
					'desc_tip'    => __( 'You can use shortcodes here. E.g. Booster\'s order shortcodes.', 'woocommerce' ),
					'description' => '',
					'placeholder' => '',
					'default'     => $default_plain_template,
					'css'         => 'width:100%;height:500px;',
				),
			);
		}
	}

endif;
