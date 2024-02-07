<?php
/**
 * Booster for WooCommerce - Module - My Account
 *
 * @version 7.1.6
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_My_Account' ) ) :
	/**
	 * WCJ_My_Account.
	 */
	class WCJ_My_Account extends WCJ_Module {

		/**
		 * The module custom_pages
		 *
		 * @var varchar $custom_pages Module.
		 */
		public $custom_pages;

		/**
		 * The module account_menu_items
		 *
		 * @var array
		 */
		public $account_menu_items = array();

		/**
		 * The module account_menu_endpoints
		 *
		 * @var array
		 */
		public $account_menu_endpoints = array();

		/**
		 * The module menu_order_default
		 *
		 * @var varchar $menu_order_default Module.
		 */
		public $menu_order_default;
		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.9.0
		 * @todo    [dev] Custom Menu Pages: add "Type" option with values: "param" (i.e. as it is now) or "endpoint"
		 * @todo    [dev] Custom Menu Pages: deprecate "Add Custom Menu Items" (and add "link" value in "Type" options)
		 */
		public function __construct() {

			$this->id         = 'my_account';
			$this->short_desc = __( 'My Account', 'woocommerce-jetpack' );
			$this->desc       = __( 'WooCommerce "My Account" page customization. Customize Menu Order (Plus). Add Custom Menu Items (Plus). Custom Pages (1 allowed in free version). Custom Info Blocks (1 allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'WooCommerce "My Account" page customization.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-my-account';
			parent::__construct();

			$this->account_menu_items     = array(
				'dashboard'       => __( 'Dashboard', 'woocommerce' ),
				'orders'          => __( 'Orders', 'woocommerce' ),
				'downloads'       => __( 'Downloads', 'woocommerce' ),
				'edit-address'    => __( 'Addresses', 'woocommerce' ),
				'payment-methods' => __( 'Payment methods', 'woocommerce' ),
				'edit-account'    => __( 'Account details', 'woocommerce' ),
				'customer-logout' => __( 'Logout', 'woocommerce' ),
			);
			$this->account_menu_endpoints = array(
				'orders'          => __( 'Orders', 'woocommerce' ),
				'view-order'      => __( 'View order', 'woocommerce' ),
				'downloads'       => __( 'Downloads', 'woocommerce' ),
				'edit-account'    => __( 'Edit account', 'woocommerce' ) . ' (' . __( 'Account details', 'woocommerce' ) . ')',
				'edit-address'    => __( 'Addresses', 'woocommerce' ),
				'payment-methods' => __( 'Payment methods', 'woocommerce' ),
				'lost-password'   => __( 'Lost password', 'woocommerce' ),
				'customer-logout' => __( 'Logout', 'woocommerce' ),
			);
			$this->menu_order_default     = implode( PHP_EOL, array_keys( $this->account_menu_items ) );

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'maybe_add_my_account_order_actions' ), 10, 2 );
				add_action( 'wp_footer', array( $this, 'maybe_add_js_conformation' ) );
				add_action( 'init', array( $this, 'process_woocommerce_mark_order_status' ) );
				// Custom pages.
				if ( 'yes' === wcj_get_option( 'wcj_my_account_custom_pages_enabled', 'no' ) ) {
					add_action( 'woocommerce_account_page_endpoint', array( $this, 'customize_dashboard' ), PHP_INT_MAX );
					add_filter( 'the_title', array( $this, 'set_custom_page_title' ), PHP_INT_MAX );
					add_filter( 'the_title', array( $this, 'set_custom_page_title_with_endpoint' ) );
					add_filter( 'woocommerce_account_menu_items', array( $this, 'add_custom_page_menu_item' ), PHP_INT_MAX );
					add_filter( 'woocommerce_get_endpoint_url', array( $this, 'set_custom_page_url' ), PHP_INT_MAX, 4 );
					add_action( 'init', array( $this, 'add_endpoints' ) );
					$this->customize_dashboard_for_endpoints();
				}
				// Custom info.
				if ( 'yes' === wcj_get_option( 'wcj_my_account_custom_info_enabled', 'no' ) ) {
					$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_my_account_custom_info_total_number', 1 ) );
					for ( $i = 1; $i <= $total_number; $i++ ) {
						add_action(
							get_option( 'wcj_my_account_custom_info_hook_' . $i, 'woocommerce_account_dashboard' ),
							array( $this, 'add_my_account_custom_info' ),
							get_option( 'wcj_my_account_custom_info_priority_' . $i, 10 )
						);
					}
				}
				// Registration extra fields.
				if ( 'yes' === wcj_get_option( 'wcj_my_account_registration_extra_fields_user_role_enabled', 'no' ) ) {
					add_action( 'woocommerce_register_form', array( $this, 'add_registration_extra_fields' ), PHP_INT_MAX );
					add_action( 'woocommerce_created_customer', array( $this, 'process_registration_extra_fields' ), PHP_INT_MAX, 3 );
				}
				// Menu & Endpoints.
				if ( 'yes' === wcj_get_option( 'wcj_my_account_menu_customize_enabled', 'no' ) ) {
					foreach ( $this->account_menu_endpoints as $account_menu_endpoint_id => $account_menu_endpoint_title ) {
						add_filter( 'woocommerce_endpoint_' . $account_menu_endpoint_id . '_title', array( $this, 'customize_endpoint_title' ), PHP_INT_MAX, 2 );
					}
					add_filter( 'woocommerce_account_menu_items', array( $this, 'customize_menu' ), PHP_INT_MAX );
					if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_my_account_menu_order_custom_items_enabled', 'no' ) ) ) {
						add_filter( 'woocommerce_get_endpoint_url', array( $this, 'customize_menu_custom_endpoints' ), PHP_INT_MAX, 4 );
					}
				}
				// Dashboard customization.
				if ( 'yes' === wcj_get_option( 'wcj_my_account_custom_dashboard_enabled', 'no' ) ) {
					add_action( 'woocommerce_account_page_endpoint', array( $this, 'customize_dashboard' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Customize_dashboard_for_endpoints.
		 *
		 * @version 4.8.0
		 * @since   4.8.0
		 */
		public function customize_dashboard_for_endpoints() {
			foreach ( $this->get_custom_pages() as $custom_menu_page_id => $custom_menu_page_data ) {
				$endpoint = $custom_menu_page_data['endpoint'];
				if ( empty( $endpoint ) ) {
					continue;
				}
				add_action( 'woocommerce_account_' . $endpoint . '_endpoint', array( $this, 'customize_dashboard' ), PHP_INT_MAX );
			}
		}

		/**
		 * Register new endpoint to use inside My Account page.
		 *
		 * @version 4.8.0
		 * @since   4.8.0
		 *
		 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
		 */
		public function add_endpoints() {
			foreach ( $this->get_custom_pages() as $custom_menu_page_id => $custom_menu_page_data ) {
				$endpoint = $custom_menu_page_data['endpoint'];
				if ( empty( $endpoint ) ) {
					continue;
				}
				add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
			}
		}

		/**
		 * Get_custom_pages.
		 *
		 * @version 4.8.0
		 * @since   4.3.0
		 * @todo    [dev] customizable ID (i.e. instead of `sanitize_title( $title[ $i ] )`)
		 */
		public function get_custom_pages() {
			if ( isset( $this->custom_pages ) ) {
				return $this->custom_pages;
			}
			$this->custom_pages                       = array();
			$title                                    = wcj_get_option( 'wcj_my_account_custom_pages_title', array() );
			$content                                  = wcj_get_option( 'wcj_my_account_custom_pages_content', array() );
			$endpoint                                 = wcj_get_option( 'wcj_my_account_custom_pages_endpoint', array() );
			$wcj_my_account_custom_pages_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_my_account_custom_pages_total_number', 1 ) );
			for ( $i = 1; $i <= $wcj_my_account_custom_pages_total_number; $i ++ ) {
				if ( ! empty( $title[ $i ] ) && ! empty( $content[ $i ] ) ) {
					$endpoint[ $i ]                                       = isset( $endpoint[ $i ] ) ? $endpoint[ $i ] : '';
					$this->custom_pages[ sanitize_title( $title[ $i ] ) ] = array(
						'endpoint' => $endpoint[ $i ],
						'title'    => $title[ $i ],
						'content'  => $content[ $i ],
					);
				}
			}
			return $this->custom_pages;
		}

		/**
		 * Set_custom_page_title.
		 *
		 * @version 5.6.8
		 * @since   4.3.0
		 * @param string $title defines the title.
		 */
		public function set_custom_page_title( $title ) {
			$wpnonce = isset( $_REQUEST['wcj_section_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_section_nonce'] ), 'wcj_section' ) : false;
			if (
				! $wpnonce || (
				! isset( $_GET['section'] ) ||
				is_admin() ||
				! in_the_loop() ||
				! is_account_page()
				)
			) {
				return $title;
			}
			if ( ! isset( $this->custom_pages ) ) {
				$this->get_custom_pages();
			}
			$endpoint = sanitize_text_field( wp_unslash( $_GET['section'] ) );
			return ( isset( $this->custom_pages[ $endpoint ] ) ? $this->custom_pages[ $endpoint ]['title'] : $title );
		}

		/**
		 * Set endpoint title.
		 *
		 * @version 4.8.0
		 * @since   4.8.0
		 *
		 * @param string $title defines the title.
		 * @return string
		 */
		public function set_custom_page_title_with_endpoint( $title ) {
			if (
				is_admin() ||
				! is_account_page() ||
				! in_the_loop()
			) {
				return $title;
			}
			global $wp_query;

			if ( ! isset( $this->custom_pages ) ) {
				$this->get_custom_pages();
			}
			$endpoints = wp_list_pluck( $this->custom_pages, 'endpoint' );
			$intersect = array_intersect_key( $wp_query->query_vars, array_flip( array_filter( $endpoints ) ) );
			if ( ! empty( $intersect ) ) {
				reset( $intersect );
				$filter = wp_list_filter( $this->custom_pages, array( 'endpoint' => key( $intersect ) ) );
				reset( $filter );
				$title = ( isset( $this->custom_pages[ key( $filter ) ] ) ? $this->custom_pages[ key( $filter ) ]['title'] : $title );
			}
			return $title;
		}

		/**
		 * Set_custom_page_url.
		 *
		 * @version 5.6.8
		 * @since   4.3.0
		 * @todo    [dev] (maybe) customizable `section` (e.g. `wcj-section`)
		 * @param string $url defines the url.
		 * @param string $endpoint defines the endpoint.
		 * @param string $value defines the value.
		 * @param string $permalink defines the permalink.
		 */
		public function set_custom_page_url( $url, $endpoint, $value, $permalink ) {
			$myaccount_page_id = wcj_get_option( 'woocommerce_myaccount_page_id' );
			if ( ! isset( $this->custom_pages ) ) {
				$this->get_custom_pages();
			}
			$section_link_arg = array(
				'section'           => $endpoint,
				'wcj_section_nonce' => wp_create_nonce( 'wcj_section' ),
			);
			return ( isset( $this->custom_pages[ $endpoint ] ) && empty( $this->custom_pages[ $endpoint ]['endpoint'] ) && ( $myaccount_page_id ) ? esc_url( add_query_arg( $section_link_arg, get_permalink( $myaccount_page_id ) ) ) : esc_url( $url ) );
		}

		/**
		 * Add_custom_page_menu_item.
		 *
		 * @version 4.8.0
		 * @since   4.3.0
		 * @param array $items defines the items.
		 */
		public function add_custom_page_menu_item( $items ) {
			foreach ( $this->get_custom_pages() as $custom_menu_page_id => $custom_menu_page_data ) {
				$custom_menu_page_id           = ! empty( $custom_menu_page_data['endpoint'] ) ? $custom_menu_page_data['endpoint'] : $custom_menu_page_id;
				$items[ $custom_menu_page_id ] = $custom_menu_page_data['title'];
			}
			return $items;
		}

		/**
		 * Customize_menu_custom_endpoints.
		 *
		 * @version 3.8.0
		 * @since   3.8.0
		 * @param string $url defines the url.
		 * @param string $endpoint defines the endpoint.
		 * @param string $value defines the value.
		 * @param string $permalink defines the permalink.
		 */
		public function customize_menu_custom_endpoints( $url, $endpoint, $value, $permalink ) {
			$custom_items = array_map( 'trim', explode( PHP_EOL, wcj_get_option( 'wcj_my_account_menu_order_custom_items', '' ) ) );
			foreach ( $custom_items as $custom_item ) {
				$parts = array_map( 'trim', explode( '|', $custom_item, 3 ) );
				if ( 3 === count( $parts ) && $parts[0] === $endpoint ) {
					return $parts[2];
				}
			}
			return $url;
		}

		/**
		 * Customize_dashboard.
		 *
		 * @version 5.6.8
		 * @since   3.8.0
		 * @see     woocommerce/templates/myaccount/dashboard.php
		 * @param string | array $value defines the value.
		 */
		public function customize_dashboard( $value ) {
			// Custom pages.
			if ( 'yes' === wcj_get_option( 'wcj_my_account_custom_pages_enabled', 'no' ) ) {
				if ( isset( $_GET['section'] ) || 'woocommerce_account_page_endpoint' !== current_filter() ) {
					if ( ! isset( $this->custom_pages ) ) {
						$this->get_custom_pages();
					}
					$endpoint = 'woocommerce_account_page_endpoint' !== current_filter() ? str_replace( array( 'woocommerce_account_', '_endpoint' ), array( '' ), current_filter() ) : false;
					if ( false !== $endpoint ) {
						$endpoint_tab = wp_list_filter( $this->custom_pages, array( 'endpoint' => $endpoint ) );
					}
					$wpnonce = isset( $_REQUEST['wcj_section_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_section_nonce'] ), 'wcj_section' ) : false;
					$page_id = $wpnonce && sanitize_text_field( wp_unslash( ( $_GET['section'] ) ) ) ? sanitize_text_field( wp_unslash( ( $_GET['section'] ) ) ) : ( false !== $endpoint ? array_keys( wp_list_pluck( $endpoint_tab, 'endpoint' ) )[0] : '' );
					if ( isset( $this->custom_pages[ $page_id ] ) ) {
						echo do_shortcode( $this->custom_pages[ $page_id ]['content'] );
						return;
					}
				}
				if ( 'no' === wcj_get_option( 'wcj_my_account_custom_dashboard_enabled', 'no' ) ) {
					wc_get_template(
						'myaccount/dashboard.php',
						array(
							'current_user' => get_user_by( 'id', get_current_user_id() ),
						)
					);
					return;
				}
			}

			// Dashboard customization.
			$current_user   = get_user_by( 'id', get_current_user_id() );
			$custom_content = wcj_get_option( 'wcj_my_account_custom_dashboard_content', '' );
			if ( '' !== ( $custom_content ) ) {
				echo do_shortcode( $custom_content );
			}

			if ( 'no' === wcj_get_option( 'wcj_my_account_custom_dashboard_hide_hello', 'no' ) ) {
				echo '<p>';
				printf(
					/* translators: 1: user display name 2: logout url */
					wp_kses_post( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ) ),
					'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
					esc_url( wc_logout_url( wc_get_page_permalink( 'myaccount' ) ) )
				);
				echo '</p>';
			}

			if ( 'no' === wcj_get_option( 'wcj_my_account_custom_dashboard_hide_info', 'no' ) ) {
				echo '<p>';
				printf(
					/* translators: 1: user display name 2: logout url */
					wp_kses_post( __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' ) ),
					esc_url( wc_get_endpoint_url( 'orders' ) ),
					esc_url( wc_get_endpoint_url( 'edit-address' ) ),
					esc_url( wc_get_endpoint_url( 'edit-account' ) )
				);
				echo '</p>';
			}

			/**
			 * My Account dashboard.
			 *
			 * @since 2.6.0
			 */
			do_action( 'woocommerce_account_dashboard' );

			/**
			 * Deprecated woocommerce_before_my_account action.
			 *
			 * @deprecated 2.6.0
			 */
			do_action( 'woocommerce_before_my_account' );

			/**
			 * Deprecated woocommerce_after_my_account action.
			 *
			 * @deprecated 2.6.0
			 */
			do_action( 'woocommerce_after_my_account' );

		}

		/**
		 * Customize_endpoint_title.
		 *
		 * @version 3.8.0
		 * @since   3.8.0
		 * @todo    (maybe) 'orders': `if ( ! empty( $wp->query_vars['orders'] ) ) { $title = sprintf( __( 'Orders (page %d)', 'woocommerce' ), intval( $wp->query_vars['orders'] ) ); }`
		 * @todo    (maybe) 'view-order': `$title = ( $order = wc_get_order( $wp->query_vars['view-order'] ) ) ? sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ) : '';`
		 * @todo    (maybe) 'order-pay'      => __( 'Pay for order', 'woocommerce' )
		 * @todo    (maybe) 'order-received' => __( 'Order received', 'woocommerce' )
		 * @param string $title defines the title.
		 * @param string $endpoint defines the endpoint.
		 */
		public function customize_endpoint_title( $title, $endpoint ) {
			$menu_titles = wcj_get_option( 'wcj_my_account_menu_title', array() );
			if ( ! empty( $menu_titles[ $endpoint ] ) ) {
				return $menu_titles[ $endpoint ];
			}
			return $title;
		}

		/**
		 * Customize_menu.
		 *
		 * @version 5.6.2
		 * @since   3.8.0
		 * @todo    (maybe) option to disable menu
		 * @param array $items defines the items.
		 */
		public function customize_menu( $items ) {
			$menu_titles = wcj_get_option( 'wcj_my_account_menu_title', array() );
			foreach ( $items as $item_id => $item_title ) {
				if ( ! empty( $menu_titles[ $item_id ] ) ) {
					$items[ $item_id ] = $menu_titles[ $item_id ];
				}
			}
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_my_account_menu_order_custom_items_enabled', 'no' ) ) ) {
				$custom_items = array_map( 'trim', explode( PHP_EOL, wcj_get_option( 'wcj_my_account_menu_order_custom_items', '' ) ) );
				foreach ( $custom_items as $custom_item ) {
					$parts = array_map( 'trim', explode( '|', $custom_item, 3 ) );
					if ( 3 === count( $parts ) ) {
						$items[ $parts[0] ] = $parts[1];
					}
				}
			}
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_my_account_menu_order_customize_enabled', 'no' ) ) ) {
				$menu_order = array_map( 'trim', explode( PHP_EOL, wcj_get_option( 'wcj_my_account_menu_order', $this->menu_order_default ) ) );
				$menu_order = array_map( 'strtolower', $menu_order );
				$menu_order = array_filter(
					$menu_order,
					function ( $item ) use ( $items ) {
						return in_array( $item, array_keys( $items ), true );
					}
				);
				$items      = array_merge( array_flip( $menu_order ), $items );
			}
			return $items;
		}

		/**
		 * Add_registration_extra_fields.
		 *
		 * @version 5.6.8
		 * @since   3.6.0
		 * @todo    (maybe) more fields to choose from (i.e. not only "user role" field)
		 * @todo    (maybe) customizable position (check for other hooks or at least customizable priority on `woocommerce_register_form`)
		 * @todo    (maybe) move to new module (e.g. "Registration Form")
		 */
		public function add_registration_extra_fields() {
			$user_roles_options_html = '';
			$wpnonce                 = isset( $_REQUEST['woocommerce-register-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['woocommerce-register-nonce'] ), 'woocommerce-register' ) : false;
			$current_user_role_input = ( $wpnonce && ! empty( $_POST['wcj_user_role'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_user_role'] ) ) :
				get_option( 'wcj_my_account_registration_extra_fields_user_role_default', 'customer' );
			$user_roles_options      = wcj_get_option( 'wcj_my_account_registration_extra_fields_user_role_options', array( 'customer' ) );
			$all_user_roles          = wcj_get_user_roles_options();
			foreach ( $user_roles_options as $user_role_id ) {
				$user_roles_options_html .= '<option value="' . $user_role_id . '" ' . selected( $user_role_id, $current_user_role_input, false ) . '>' .
											( isset( $all_user_roles[ $user_role_id ] ) ? $all_user_roles[ $user_role_id ] : $user_role_id ) . '</option>';
			}
			?><p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="reg_wcj_user_role"><?php esc_html_e( 'User role', 'woocommerce-jetpack' ); ?></label>
			<select name="wcj_user_role" id="reg_wcj_user_role"><?php echo wp_kses_post( $user_roles_options_html ); ?></select>
			</p>
			<?php
		}

		/**
		 * Process_registration_extra_fields.
		 *
		 * @version 5.6.8
		 * @since   3.6.0
		 * @todo    (maybe) optional admin confirmation for some user roles (probably will need to create additional `...-pending` user roles)
		 * @param id             $customer_id defines the customer_id.
		 * @param string | array $new_customer_data defines the new_customer_data.
		 * @param string         $password_generated defines the password_generated.
		 */
		public function process_registration_extra_fields( $customer_id, $new_customer_data, $password_generated ) {
			$wpnonce = isset( $_REQUEST['woocommerce-register-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['woocommerce-register-nonce'] ), 'woocommerce-register' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_user_role'] ) && '' !== $_POST['wcj_user_role'] ) {
				$user_roles_options = wcj_get_option( 'wcj_my_account_registration_extra_fields_user_role_options', array( 'customer' ) );
				if ( ! empty( $user_roles_options ) && in_array( $_POST['wcj_user_role'], $user_roles_options, true ) ) {
					wp_update_user(
						array(
							'ID'   => $customer_id,
							'role' => sanitize_text_field( wp_unslash( $_POST['wcj_user_role'] ) ),
						)
					);
				}
			}
		}

		/**
		 * Add_my_account_custom_info.
		 *
		 * @version 5.6.2
		 * @since   3.4.0
		 */
		public function add_my_account_custom_info() {
			$current_filter          = current_filter();
			$current_filter_priority = wcj_current_filter_priority();
			$total_number            = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_my_account_custom_info_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if (
					'' !== wcj_get_option( 'wcj_my_account_custom_info_content_' . $i ) &&
					wcj_get_option( 'wcj_my_account_custom_info_hook_' . $i, 'woocommerce_account_dashboard' ) === $current_filter &&
					wcj_get_option( 'wcj_my_account_custom_info_priority_' . $i, '10' ) === (string) $current_filter_priority
				) {
					echo do_shortcode( wcj_get_option( 'wcj_my_account_custom_info_content_' . $i ) );
				}
			}
		}

		/**
		 * Maybe_add_my_account_order_actions.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @see     http://snippet.fm/snippets/add-order-complete-action-to-woocommerce-my-orders-customer-table/
		 * @param array  $actions defines the actions.
		 * @param string $order defines the order.
		 */
		public function maybe_add_my_account_order_actions( $actions, $order ) {
			$statuses_to_add = wcj_get_option( 'wcj_my_account_add_order_status_actions', '' );
			if ( ! empty( $statuses_to_add ) ) {
				$all_statuses = wcj_get_order_statuses();
				foreach ( $statuses_to_add as $status_to_add ) {
					if ( $status_to_add !== $order->get_status() ) {
						$actions[ 'wcj_mark_' . $status_to_add . '_by_customer' ] = array(
							'url'  => wp_nonce_url(
								add_query_arg(
									array(
										'wcj_action' => 'wcj_woocommerce_mark_order_status',
										'status'     => $status_to_add,
										'order_id'   => $order->get_id(),
									)
								),
								'wcj-woocommerce-mark-order-status'
							),
							'name' => $all_statuses[ $status_to_add ],
						);
					}
				}
			}
			return $actions;
		}

		/**
		 * Maybe_add_js_conformation.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 */
		public function maybe_add_js_conformation() {
			$statuses_to_add = wcj_get_option( 'wcj_my_account_add_order_status_actions', '' );
			if ( ! empty( $statuses_to_add ) ) {
				echo '<script>';
				foreach ( $statuses_to_add as $status_to_add ) {
					echo 'jQuery("a.wcj_mark_' . wp_kses_post( $status_to_add ) . '_by_customer").each( function() { jQuery(this).attr("onclick", "return confirm(\'' .
						wp_kses_post( 'Are you sure?', 'woocommerce-jetpack' ) . '\')") } );';
				}
				echo '</script>';
			}
		}

		/**
		 * Process_woocommerce_mark_order_status.
		 *
		 * @version 5.6.3
		 * @since   2.9.0
		 */
		public function process_woocommerce_mark_order_status() {
			$statuses_to_allow = wcj_get_option( 'wcj_my_account_add_order_status_actions', '' );
			if (
				isset( $_GET['wcj_action'] ) && 'wcj_woocommerce_mark_order_status' === $_GET['wcj_action'] &&
				isset( $_GET['status'] ) &&
				in_array( $_GET['status'], $statuses_to_allow, true ) &&
				isset( $_GET['order_id'] ) &&
				isset( $_GET['_wpnonce'] )
			) {
				if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wcj-woocommerce-mark-order-status' ) ) {
					$_order = wc_get_order( sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) );
					if ( $_order->get_customer_id() === get_current_user_id() ) {
						$_order->update_status( sanitize_text_field( wp_unslash( $_GET['status'] ) ) );
						wp_safe_redirect( remove_query_arg( array( 'wcj_action', 'status', 'order_id', '_wpnonce' ) ) );
						exit;
					}
				}
			}
		}

	}

endif;

return new WCJ_My_Account();
