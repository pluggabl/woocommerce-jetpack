<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_WCJ_Custom' ) ) :

/**
 * Custom Email
 *
 * An email sent to recipient list when selected triggers are called.
 *
 * @version 2.4.1
 * @since   2.3.9
 * @author  Algoritmika Ltd.
 * @extends WC_Email
 */
class WC_Email_WCJ_Custom extends WC_Email {

	/**
	 * Constructor
	 *
	 * @version 2.4.0
	 */
	function __construct( $id = 1 ) {

		$this->id               = 'wcj_custom' . '_' . $id;
		$this->customer_email   = ( '%customer%' === $this->get_option( 'recipient' ) ) ? true : false;
		$this->title            = __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $id;
		$this->description      = __( 'Custom emails are sent to the recipient list when selected triggers are called.', 'woocommerce-jetpack' );

		$this->heading          = __( 'Custom Heading', 'woocommerce' );
		$this->subject          = __( '[{site_title}] Custom Subject - Order ({order_number}) - {order_date}', 'woocommerce-jetpack' );

		/* $this->template_html    = 'emails/admin-new-order.php';
		$this->template_plain   = 'emails/plain/admin-new-order.php'; */

		// Triggers for this email
		$trigger_hooks = $this->get_option( 'trigger' );
		if ( ! empty( $trigger_hooks ) && is_array( $trigger_hooks ) ) {
			foreach ( $trigger_hooks as $trigger_hook ) {
				add_action( $trigger_hook, array( $this, 'trigger' ) );
			}
		}

		// Call parent constructor
		parent::__construct();

		// Other settings
		if ( ! $this->customer_email ) {
			$this->recipient = $this->get_option( 'recipient' );

			if ( ! $this->recipient )
				$this->recipient = get_option( 'admin_email' );
		}
	}

	/**
	 * Proxy to parent's get_option and attempt to localize the result using gettext.
	 *
	 * @version 2.4.1
	 * @since   2.4.1
	 * @param   string $key
	 * @param   mixed  $empty_value
	 * @return  mixed
	 */
	public function get_option( $key, $empty_value = null ) {
		$grandparent = get_parent_class( 'WC_Email' );
		$value = $grandparent::get_option( $key, $empty_value );
		return ( is_array( $value ) ) ? $value : apply_filters( 'woocommerce_email_get_option', __( $value ), $this, $value, $key, $empty_value );
	}

	/**
	 * Trigger.
	 *
	 * @version 2.4.0
	 */
	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object       = wc_get_order( $order_id );

			if ( $this->customer_email ) {
				$this->recipient = $this->object->billing_email;
			}

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		if ( $order_id ) {
			global $post;
			$order = wc_get_order( $order_id );
			$post = $order->post;
			setup_postdata( $post );
		}
		$this->send( $this->get_recipient(), $this->get_subject(), do_shortcode( $this->get_content() ), $this->get_headers(), $this->get_attachments() );
		if ( $order_id ) {
			wp_reset_postdata();
		}
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		/* ob_start();
		wc_get_template( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => false
		), '', wcj_plugin_path() . '/templates' );
		return ob_get_clean(); */
		return $this->get_option( 'content_html_template' );
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		/* ob_start();
		wc_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => true
		), '', wcj_plugin_path() . '/templates' );
		return ob_get_clean(); */
		return $this->get_option( 'content_plain_template' );
	}

	/**
	 * get_order_statuses.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
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
	 * Initialise settings form fields
	 *
	 * @version 2.4.0
	 */
	function init_form_fields() {

		ob_start();
		include( 'email-html.php' );
		$default_html_template = ob_get_clean();
		ob_start();
		include( 'email-plain.php' );
		$default_plain_template = ob_get_clean();

		$status_change_triggers = array();
		$status_triggers = array();
		$order_statuses = $this->get_order_statuses();
		foreach ( $order_statuses as $slug => $name ) {
			$status_triggers[ 'woocommerce_order_status_' . $slug . '_notification' ] = sprintf( __( 'Order status %s', 'woocommerce-jetpack' ), $name );
			foreach ( $order_statuses as $slug2 => $name2 ) {
				if ( $slug != $slug2 ) {
					$status_change_triggers[ 'woocommerce_order_status_' . $slug . '_to_' . $slug2 . '_notification' ] = sprintf( __( 'Order status %s to %s', 'woocommerce-jetpack' ), $name, $name2 );
				}
			}
		}

		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'woocommerce' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', 'woocommerce' ),
				'default'       => 'no',
			),
			'trigger' => array(
				'title'         => __( 'Trigger(s)', 'woocommerce-jetpack' ),
				'type'          => 'multiselect',
				'placeholder'   => '',
				'default'       => array(),
				'options'       => array_merge(
					$status_triggers,
					array(
						'woocommerce_reset_password_notification'                     => __( 'Reset password notification', 'woocommerce-jetpack' ),
						'woocommerce_order_fully_refunded_notification'               => __( 'Order fully refunded notification', 'woocommerce-jetpack' ),
						'woocommerce_order_partially_refunded_notification'           => __( 'Order partially refunded notification', 'woocommerce-jetpack' ),
						'woocommerce_new_customer_note_notification'                  => __( 'New customer note notification', 'woocommerce-jetpack' ),
					),
					$status_change_triggers
				),
				'css'           => 'height:300px;',
//				'class'         => 'chosen_select',
			),
			'recipient' => array(
				'title'         => __( 'Recipient(s)', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce' ), esc_attr( get_option('admin_email') ) ) . ' ' . __( 'Or enter <code>%customer%</code> to send to customer billing email.', 'woocommerce-jetpack' ),
				'placeholder'   => '',
				'default'       => '',
			),
			'subject' => array(
				'title'         => __( 'Subject', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce' ), $this->subject ),
				'placeholder'   => '',
				'default'       => '',
			),
			'heading' => array(
				'title'         => __( 'Email Heading', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce' ), $this->heading ),
				'placeholder'   => '',
				'default'       => '',
			),
			'email_type' => array(
				'title'         => __( 'Email type', 'woocommerce' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options(),
			),
			'content_html_template' => array(
				'title'         => __( 'HTML template', 'woocommerce' ),
				'type'          => 'textarea',
				'description'   => '',
				'placeholder'   => '',
				'default'       => $default_html_template,
				'css'           => 'width:66%;min-width:300px;height:500px;',
			),
			'content_plain_template' => array(
				'title'         => __( 'Plain text template', 'woocommerce' ),
				'type'          => 'textarea',
				'description'   => '',
				'placeholder'   => '',
				'default'       => $default_plain_template,
				'css'           => 'width:66%;min-width:300px;height:500px;',
			),
		);
	}
}

endif;

//return new WC_Email_WCJ_Custom();
