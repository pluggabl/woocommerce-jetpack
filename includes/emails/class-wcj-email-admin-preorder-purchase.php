<?php
/**
 * Admin Pre-order Purchase Email
 *
 * @version 7.3.1
 * @since   1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/includes/emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Email_Admin_Preorder_Purchase' ) ) :

	/**
	 * Admin Pre-order Purchase Email
	 *
	 * @version 1.0.0
	 */
	class WCJ_Email_Admin_Preorder_Purchase extends WC_Email {

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 */
		public function __construct() {
			$this->id             = 'wcj_admin_preorder_purchase';
			$this->title          = __( 'Booster : Admin Pre-order Purchase', 'woocommerce-jetpack' );
			$this->description    = __( 'This email is sent to the admin when a customer places a pre-order.', 'woocommerce-jetpack' );
			$this->template_base  = WCJ_FREE_PLUGIN_PATH . '/includes/emails/templates/';
			$this->template_html  = 'wcj-admin-preorder-purchase.php';
			$this->template_plain = 'plain/wcj-admin-preorder-purchase.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @version 1.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] New Pre-order Purchase', 'woocommerce-jetpack' );
		}

		/**
		 * Get email heading.
		 *
		 * @version 1.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'New Pre-order Purchase', 'woocommerce-jetpack' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @version 1.0.0
		 * @param int $order_id The order ID.
		 */
		public function trigger( $order_id ) {
			$this->setup_locale();

			if ( $order_id ) {
				$this->object = wc_get_order( $order_id );
				if ( is_a( $this->object, 'WC_Order' ) ) {
					$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
					$this->placeholders['{order_number}'] = $this->object->get_order_number();
				}
			}

			if ( $this->is_enabled() && $this->get_recipient() && $this->object ) {
				$this->send(
					$this->get_recipient(),
					$this->get_subject(),
					$this->get_content(),
					$this->get_headers(),
					$this->get_attachments()
				);
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @version 1.0.0
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'         => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => false,
					'email'         => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Get content plain.
		 *
		 * @version 1.0.0
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'         => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => true,
					'email'         => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Initialize Settings Form Fields
		 *
		 * @version 1.0.0
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'woocommerce-jetpack' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce-jetpack' ),
					'default' => 'yes',
				),
				'recipient'  => array(
					'title'       => __( 'Recipient(s)', 'woocommerce-jetpack' ),
					'type'        => 'text',
					/* translators: %s: Default recipient email */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce-jetpack' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'woocommerce-jetpack' ),
					'type'        => 'text',
					/* translators: %s: Available placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce-jetpack' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading'    => array(
					'title'       => __( 'Email Heading', 'woocommerce-jetpack' ),
					'type'        => 'text',
					/* translators: %s: Available placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce-jetpack' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'woocommerce-jetpack' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce-jetpack' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

endif;

return new WCJ_Email_Admin_Preorder_Purchase();
