<?php
/**
 * Booster for WooCommerce - Price by Country - Core
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes/Price_By_Country
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Price_By_Country_Core' ) ) :

	/**
	 * WCJ_Price_By_Country_Core.
	 */
	class WCJ_Price_By_Country_Core {

		/**
		 * The module scope
		 *
		 * @var varchar $scope Module scope.
		 */
		public $scope;

		/**
		 * The module customer_country_by_ip
		 *
		 * @var varchar $customer_country_by_ip Module customer_country_by_ip.
		 */
		public $customer_country_by_ip;

		/**
		 * The module customer_country_group_id
		 *
		 * @var varchar $customer_country_group_id Module customer_country_group_id.
		 */
		public $customer_country_group_id;

		/**
		 * The module price_hooks_priority
		 *
		 * @var varchar $price_hooks_priority Module price_hooks_priority.
		 */
		public $price_hooks_priority;

		/**
		 * Constructor.
		 *
		 * @version 5.6.2
		 * @todo    [dev] check if we can just always execute `init()` on `init` hook
		 */
		public function __construct() {
			$this->customer_country_group_id = null;
			if ( ( 'no' === get_option( 'wcj_price_by_country_for_bots_disabled', 'no' ) || ! wcj_is_bot() ) && ! wcj_is_admin_product_edit_page() ) {
				if ( in_array( get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ), array( 'by_ip', 'by_user_selection', 'by_ip_then_by_user_selection' ), true ) ) {
					if ( 'wc' === WCJ_SESSION_TYPE ) {
						// `init()` executed on `init` hook because we need to use `WC()->session`
						add_action( 'init', array( $this, 'init' ) );
					} else {
						$this->init();
					}
				}
				if ( 'no' === get_option( 'wcj_price_by_country_admin_quick_edit_product_scope', 'no' ) ) {
					$this->add_hooks();
				} else {
					if ( ! wcj_is_admin_product_quick_edit_page() ) {

						$this->add_hooks();
					}
				}
				// `maybe_init_customer_country_by_ip()` executed on `init` hook - in case we need to call `get_customer_country_by_ip()` `WC_Geolocation` class is ready.
				add_action( 'init', array( $this, 'maybe_init_customer_country_by_ip' ) );
			}
		}

		/**
		 * Init.
		 *
		 * @version 6.0.6
		 * @since   2.9.0
		 */
		public function init() {
			wcj_session_maybe_start();
			if ( isset( $_POST['wcj-country'] ) && '' !== $_POST['wcj-country'] ) {
				$wpnonce = isset( $_REQUEST['wcj-country-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-country-nonce'] ), 'wcj-country' ) : false;
			} else {
				$wpnonce = true;
			}
			$country     = isset( $_REQUEST['wcj-country'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj-country'] ) ) : '';
			$req_country = null;
			if ( $wpnonce && ! empty( $country ) ) {
				$req_country = $country;
			} elseif ( isset( $_REQUEST['wcj_country_selector'] ) && ! empty( $_REQUEST['wcj_country_selector'] ) ) {
				$req_country = sanitize_text_field( wp_unslash( $_REQUEST['wcj_country_selector'] ) );
			}
			if ( ! empty( $req_country ) ) {
				wcj_session_set( 'wcj-country', $req_country );
				do_action( 'wcj_price_by_country_set_country', $req_country, $this->get_currency_by_country( $req_country ) );
			}
		}

		/**
		 * Gets currency by country.
		 *
		 * @version 5.6.2
		 * @since   4.1.0
		 *
		 * @param string $country defines the country.
		 *
		 * @return bool|mixed|void
		 */
		public function get_currency_by_country( $country ) {
			$group_id              = $this->get_country_group_id( $country );
			$country_currency_code = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id );
			return ( '' !== $country_currency_code && null !== $country_currency_code ? $country_currency_code : false );
		}

		/**
		 * Saves currency on session by country.
		 *
		 * @version 5.6.2
		 * @since   4.1.0
		 *
		 * @param string $country defines the country.
		 */
		public function save_currency_on_session_by_country( $country ) {
			$currency = $this->get_currency_by_country( $country );
			if ( ! empty( $currency ) ) {
				wcj_session_set( 'wcj-currency', $currency );
			}
		}

		/**
		 * Maybe_init_customer_country_by_ip.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 */
		public function maybe_init_customer_country_by_ip() {
			if ( 'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				if ( null === wcj_session_get( 'wcj-country' ) ) {
					$country = $this->get_customer_country_by_ip();
					if ( null !== $country && '' !== $country ) {
						wcj_session_set( 'wcj-country', $country );
						do_action( 'wcj_price_by_country_set_country', $country, $this->get_currency_by_country( $country ) );
					}
				}
			}
		}

		/**
		 * Add_hooks.
		 *
		 * @version 5.6.2
		 */
		public function add_hooks() {

			// Select with flags.
			if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wselect_scripts' ) );
			}

			$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'price_by_country' );

			// Price hooks.
			wcj_add_change_price_hooks( $this, $this->price_hooks_priority );

			// Currency hooks.
			add_filter( 'woocommerce_currency', array( $this, 'change_currency_code' ), $this->price_hooks_priority, 1 );

			// Price Filter Widget.
			if ( 'yes' === get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' ) ) {
				add_filter( 'woocommerce_product_query_meta_query', array( $this, 'price_filter_meta_query' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_price_filter_sql', array( $this, 'woocommerce_price_filter_sql' ), 10, 3 );
				add_action( 'wp_footer', array( $this, 'add_compatibility_with_price_filter_widget' ) );
				add_filter( 'posts_clauses', array( $this, 'price_filter_post_clauses' ), 10, 2 );
				add_filter( 'posts_clauses', array( $this, 'price_filter_post_clauses_sort' ), 10, 2 );
				add_action(
					'woocommerce_product_query',
					function ( $query ) {
						$group_id              = $this->get_customer_country_group_id();
						$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
						if ( (float) 1 === (float) $country_exchange_rate ) {
							return;
						}
						wcj_remove_class_filter( 'posts_clauses', 'WC_Query', 'order_by_price_asc_post_clauses', 0 );
						wcj_remove_class_filter( 'posts_clauses', 'WC_Query', 'order_by_price_desc_post_clauses', 0 );
						wcj_remove_class_filter( 'posts_clauses', 'WC_Query', 'price_filter_post_clauses', 0 );
					}
				);
				add_filter(
					'woocommerce_price_filter_widget_step',
					function ( $step ) {
						$step = 1;
						return $step;
					}
				);
			}

			// Price Format.
			if ( wcj_is_frontend() ) {
				if ( 'wc_get_price_to_display' === get_option( 'wcj_price_by_country_price_format_method', 'get_price' ) ) {
					add_filter( 'woocommerce_get_price_including_tax', array( $this, 'format_price_after_including_excluding_tax' ), PHP_INT_MAX, 3 );
					add_filter( 'woocommerce_get_price_excluding_tax', array( $this, 'format_price_after_including_excluding_tax' ), PHP_INT_MAX, 3 );
				}
			}

			// Free Shipping.
			add_filter( 'woocommerce_shipping_free_shipping_instance_option', array( $this, 'convert_free_shipping_min_amount' ), 10, 3 );
			add_filter( 'woocommerce_shipping_free_shipping_option', array( $this, 'convert_free_shipping_min_amount' ), 10, 3 );

			// WooCommerce Points and Rewards plugin.
			add_filter( 'option_wc_points_rewards_redeem_points_ratio', array( $this, 'handle_wc_points_rewards_settings' ) );
			add_filter( 'option_wc_points_rewards_earn_points_ratio', array( $this, 'handle_wc_points_rewards_settings' ) );

			// Auto set default checkout billing country.
			add_action( 'default_checkout_billing_country', array( $this, 'set_default_checkout_country' ), 900 );
		}

		/**
		 * Set_default_checkout_country.
		 *
		 * @version 5.6.8
		 * @since   5.3.0
		 *
		 * @param string $default_country defins the default country.
		 *
		 * @return array|null|string
		 */
		public function set_default_checkout_country( $default_country ) {
			$country = null !== wcj_session_get( 'wcj-country' ) ? wcj_session_get( 'wcj-country' ) : $this->get_customer_country_by_ip();
			if (
			'yes' !== get_option( 'wcj_price_by_country_set_dft_checkout_billing_country', 'no' ) ||
			empty( $country )
			) {
				return $default_country;
			}
			$default_country = $country;
			return $default_country;
		}

		/**
		 * Handle_wc_points_rewards_settings.
		 *
		 * @version 6.0.1
		 * @since   5.2.0
		 *
		 * @param string $value defines the value for points reward.
		 *
		 * @return string
		 */
		public function handle_wc_points_rewards_settings( $value ) {
			if (
			'yes' !== get_option( 'wcj_price_by_country_comp_woo_points_rewards', 'no' )
			|| empty( $value )
			|| false === strpos( $value, ':' )
			) {
				return $value;
			}
			list( $points, $monetary_value ) = explode( ':', $value );
			$new_monetary_value              = $this->change_price( $monetary_value, null );
			if ( w_c_j()->all_modules['currency_exchange_rates']->is_enabled() ) {
				$new_monetary_value = w_c_j()->all_modules['currency_exchange_rates']->force_dot_as_exchange_rate_decimal_separator( $new_monetary_value );
			}
			$value = $points . ':' . $new_monetary_value;
			return $value;
		}

		/**
		 * Convert_free_shipping_min_amount.
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 *
		 * @param string $option defines the option for shipping.
		 * @param string $key defines the key for shipping.
		 * @param string $method defines the method for shipping.
		 *
		 * @return mixed
		 */
		public function convert_free_shipping_min_amount( $option, $key, $method ) {
			if (
			'no' === get_option( 'wcj_price_by_country_compatibility_free_shipping', 'no' )
			|| 'min_amount' !== $key
			|| ! is_numeric( $option )
			|| 0 === (float) $option
			) {
				return $option;
			}
			$option = $this->change_price( $option, null );
			return $option;
		}

		/**
		 * Append_price_filter_post_meta_join.
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 *
		 * @param string $sql defines the sql query.
		 * @param string $country_group_id defines the country group.
		 *
		 * @return string
		 */
		private function append_price_filter_post_meta_join( $sql, $country_group_id ) {
			global $wpdb;
			if ( ! strstr( $sql, 'postmeta AS pm' ) ) {
				$join = $this->get_price_filter_post_meta_join( $country_group_id );
				$sql .= " {$join} ";
			}
			return $sql;
		}

		/**
		 * Get_price_filter_post_meta_join.
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 *
		 * @param string $country_group_id defines the group id of country.
		 *
		 * @return string
		 */
		private function get_price_filter_post_meta_join( $country_group_id ) {
			global $wpdb;
			return "LEFT JOIN {$wpdb->postmeta} AS pm ON $wpdb->posts.ID = pm.post_id AND pm.meta_key='_wcj_price_by_country_{$country_group_id}'";
		}

		/**
		 * Price_filter_post_clauses_sort.
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 *
		 * @param array  $args defines the arguments.
		 * @param object $wp_query defines the wp query object.
		 *
		 * @return mixed
		 */
		public function price_filter_post_clauses_sort( $args, $wp_query ) {
			$group_id              = $this->get_customer_country_group_id();
			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			if (
			! $wp_query->is_main_query()
			|| 'price' !== $wp_query->get( 'orderby' )
			|| empty( $group_id )
			|| (float) 1 === (float) $country_exchange_rate
			) {
				return $args;
			}
			global $wpdb;
			$order            = $wp_query->get( 'order' );
			$original_orderby = $args['orderby'];
			if ( 'desc' === strtolower( $order ) ) {
				$args['orderby'] = 'MIN(pm.meta_value+0)+0 DESC';
			} else {
				$args['orderby'] = 'MAX(pm.meta_value+0)+0 ASC';
			}
			$args['orderby'] = ! empty( $original_orderby ) ? $args['orderby'] . ', ' . $original_orderby : $args['orderby'];
			$args['join']    = $this->append_price_filter_post_meta_join( $args['join'], $group_id );
			return $args;
		}

		/**
		 * Price_filter_post_clauses.
		 *
		 * @version 5.6.8
		 * @since   5.1.0
		 *
		 * @see WC_Query::price_filter_post_clauses()
		 *
		 * @param array  $args defines the arguments.
		 * @param object $wp_query defines the wp query object.
		 *
		 * @return mixed
		 */
		public function price_filter_post_clauses( $args, $wp_query ) {
			global $wpdb;
			// phpcs:disable WordPress.Security.NonceVerification
			$group_id              = $this->get_customer_country_group_id();
			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			if ( ! $wp_query->is_main_query() || ( ! isset( $_GET['max_price'] ) && ! isset( $_GET['min_price'] ) ) || empty( $group_id ) || (float) 1 === (float) $country_exchange_rate ) {
				return $args;
			}
			$current_min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : 0;
			$current_max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : PHP_INT_MAX;
			// phpcs:enable WordPress.Security.NonceVerification
			if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
				$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' );
				$tax_rates = WC_Tax::get_rates( $tax_class );
				if ( $tax_rates ) {
					$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
					$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
				}
			}
			$current_min_price *= $country_exchange_rate;
			$current_max_price *= $country_exchange_rate;
			$args['fields']    .= ', MIN(pm.meta_value+0) AS wcj_min_price, MAX(pm.meta_value+0) AS wcj_max_price';
			$args['join']       = $this->append_price_filter_post_meta_join( $args['join'], $group_id );
			$args['groupby']   .= $wpdb->prepare( ' HAVING wcj_min_price >= %f AND wcj_max_price <= %f', $current_min_price, $current_max_price );
			return $args;
		}

		/**
		 * Adds compatibility with WooCommerce Price Filter widget.
		 *
		 * @see price-slider.js->init_price_filter()
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 */
		public function add_compatibility_with_price_filter_widget() {
			if (
			! is_active_widget( false, false, 'woocommerce_price_filter' )
			|| 'no' === get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' )
			) {
				return;
			}
			?>
			<?php
			$group_id      = $this->get_customer_country_group_id();
			$exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			if ( (string) 1 === $exchange_rate ) {
				return;
			}
			?>
		<input type="hidden" id="wcj_mc_exchange_rate" value="<?php echo esc_html( $exchange_rate ); ?>"/>
		<script>
			var wcj_mc_pf_slider = {
				slider: null,
				convert_rate: 1,
				original_min: 1,
				original_max: 1,
				original_values: [],
				current_min: 1,
				current_max: 1,
				current_values: [],
				step: 1,
				init(slider, convert_rate, step) {
					this.step = step;
					this.slider = slider;
					this.convert_rate = convert_rate;
					this.original_min = jQuery(this.slider).slider("option", "min");
					this.original_max = jQuery(this.slider).slider("option", "max");
					this.original_values = jQuery(this.slider).slider("option", "values");
					this.current_min = this.original_min;
					this.current_max = this.original_max;
					this.current_values[0] = jQuery(this.slider).parent().find('#min_price').val() * 1;
					this.current_values[1] = jQuery(this.slider).parent().find('#max_price').val() * 1;
					if (
						jQuery(this.slider).parent().find('#min_price').val() != this.original_min ||
						jQuery(this.slider).parent().find('#max_price').val() != this.original_max
					) {
						this.current_values[0] *= wcj_mc_pf_slider.convert_rate;
						this.current_values[1] *= wcj_mc_pf_slider.convert_rate;
					}
					this.update_slider();
				},
				update_slider() {
					jQuery(this.slider).slider("destroy");
					var current_min_price = this.current_min;
					var current_max_price = this.current_max;
					jQuery(this.slider).slider({
						range: true,
						animate: true,
						min: current_min_price,
						max: current_max_price,
						step: parseFloat(this.step),
						values: wcj_mc_pf_slider.current_values,
						create: function () {
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #min_price').val((wcj_mc_pf_slider.current_values[0] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #max_price').val((wcj_mc_pf_slider.current_values[1] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							jQuery(document.body).trigger('price_slider_create', [(wcj_mc_pf_slider.current_values[0]).toFixed(2), (wcj_mc_pf_slider.current_values[1]).toFixed(2)]);
						},
						slide: function (event, ui) {
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #min_price').val((ui.values[0] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #max_price').val((ui.values[1] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							var the_min = ui.values[0] == wcj_mc_pf_slider.current_values[0] ? (wcj_mc_pf_slider.current_values[0]).toFixed(2) : ui.values[0];
							var the_max = ui.values[1] == wcj_mc_pf_slider.current_values[1] ? (wcj_mc_pf_slider.current_values[1]).toFixed(2) : ui.values[1];
							jQuery(document.body).trigger('price_slider_slide', [the_min, the_max]);
						},
						change: function (event, ui) {
							jQuery(document.body).trigger('price_slider_change', [ui.values[0], ui.values[1]]);
						}
					});
				}
			};
			var wcj_mc_pf = {
				price_filters: null,
				rate: 1,
				step: 1,
				init: function (price_filters) {
					this.price_filters = price_filters;
					this.rate = document.getElementById('wcj_mc_exchange_rate').value;
					this.update_slider();
				},
				update_slider: function () {
					[].forEach.call(wcj_mc_pf.price_filters, function (el) {
						wcj_mc_pf_slider.init(el, wcj_mc_pf.rate, wcj_mc_pf.step);
					});
				}
			}
			document.addEventListener("DOMContentLoaded", function () {
				var price_filters = document.querySelectorAll('.price_slider.ui-slider');
				if (price_filters.length) {
					wcj_mc_pf.init(price_filters);
				}
			});
		</script>
			<?php
		}

		/**
		 * Woocommerce_price_filter_sql.
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 *
		 * @see WC_Widget_Price_Filter::get_filtered_price()
		 *
		 * @param string $sql defines the sql query.
		 * @param string $meta_query_sql defines the meta query sql.
		 * @param string $tax_query_sql defines the tax query sql.
		 *
		 * @return string
		 */
		public function woocommerce_price_filter_sql( $sql, $meta_query_sql, $tax_query_sql ) {
			$group_id              = $this->get_customer_country_group_id();
			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			if (
			is_admin()
			|| 'no' === get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' )
			|| empty( $group_id )
			|| (float) 1 === (float) $country_exchange_rate
			) {
				return $sql;
			}

			global $wpdb;
			$args       = WC()->query->get_main_query()->query_vars;
			$tax_query  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
			$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

			if ( ! is_post_type_archive( 'product' ) && ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
				$tax_query[] = array(
					'taxonomy' => $args['taxonomy'],
					'terms'    => array( $args['term'] ),
					'field'    => 'slug',
				);
			}

			foreach ( $meta_query + $tax_query as $key => $query ) {
				if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
					unset( $meta_query[ $key ] );
				}
			}

			$search           = WC_Query::get_main_search_query_sql();
			$search_query_sql = $search ? ' AND ' . $search : '';
			$sql              = "
			SELECT IFNULL(MIN(pm.meta_value+0),min_price) AS min_price, IFNULL(MAX(pm.meta_value+0),max_price) AS max_price			
			FROM {$wpdb->wc_product_meta_lookup}
			LEFT JOIN {$wpdb->postmeta} AS pm ON (pm.post_id = product_id AND pm.meta_key='_wcj_price_by_country_{$group_id}')
			WHERE product_id IN (
				SELECT ID FROM {$wpdb->posts}
				" . $tax_query_sql['join'] . $meta_query_sql['join'] . "
				WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
				AND {$wpdb->posts}.post_status = 'publish'
				" . $tax_query_sql['where'] . $meta_query_sql['where'] . $search_query_sql . '
			)';

			return $sql;
		}

		/**
		 * Format_price_after_including_excluding_tax.
		 *
		 * @version 5.6.2
		 * @since   4.4.0
		 *
		 * @param string $return_price defines the return price.
		 * @param int    $qty defines the quantity.
		 * @param object $product defines the product object.
		 *
		 * @return float|int
		 */
		public function format_price_after_including_excluding_tax( $return_price, $qty, $product ) {
			$precision    = get_option( 'woocommerce_price_num_decimals', 2 );
			$return_price = wcj_price_by_country_rounding( $return_price, $precision );
			if ( 'yes' === get_option( 'wcj_price_by_country_make_pretty', 'no' ) && $return_price >= 0.5 && $precision > 0 ) {
				$return_price = wcj_price_by_country_pretty_price( $return_price, $precision );
			}
			return $return_price;
		}

		/**
		 * Enqueue_wselect_scripts.
		 *
		 * @version 5.6.2
		 * @since   2.5.4
		 */
		public function enqueue_wselect_scripts() {
			wp_enqueue_style( 'wcj-wSelect-style', wcj_plugin_url() . '/includes/lib/wSelect/wSelect.css', array(), w_c_j()->version );
			wp_enqueue_script( 'wcj-wSelect', wcj_plugin_url() . '/includes/lib/wSelect/wSelect.min.js', array(), w_c_j()->version, true );
			wp_enqueue_script( 'wcj-wcj-wSelect', wcj_plugin_url() . '/includes/js/wcj-wSelect.js', array(), w_c_j()->version, true );
		}

		/**
		 * Price_filter_meta_query.
		 *
		 * @version 5.6.2
		 * @since   2.5.3
		 * @param string $meta_query defines the meta query.
		 * @param string $_wc_query defines the wc query.
		 */
		public function price_filter_meta_query( $meta_query, $_wc_query ) {
			foreach ( $meta_query as $_key => $_query ) {
				if ( isset( $_query['price_filter'] ) && true === $_query['price_filter'] && isset( $_query['key'] ) ) {
					$group_id = $this->get_customer_country_group_id();
					if ( null !== $group_id && '' !== $group_id ) {
						$meta_query[ $_key ]['key'] = '_wcj_price_by_country_' . $group_id;
					}
				}
			}
			return $meta_query;
		}

		/**
		 * Change_price_grouped.
		 *
		 * @version 5.6.2
		 * @since   2.5.0
		 * @param string $price defines the product price.
		 * @param string $qty defines the quantity of product.
		 * @param object $_product defines the product object.
		 */
		public function change_price_grouped( $price, $qty, $_product ) {
			if ( $_product->is_type( 'grouped' ) ) {
				if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled', 'yes' ) ) {
					foreach ( $_product->get_children() as $child_id ) {
						$the_price   = get_post_meta( $child_id, '_price', true );
						$the_product = wc_get_product( $child_id );
						$the_price   = wcj_get_product_display_price( $the_product, $the_price, 1 );
						if ( (string) $the_price === (string) $price ) {
							return $this->change_price( $price, $child_id );
						}
					}
				} else {
					return $this->change_price( $price, 0 );
				}
			}
			return $price;
		}

		/**
		 * Get_customer_country_by_ip.
		 *
		 * @version 5.6.2
		 * @since   2.5.0
		 */
		public function get_customer_country_by_ip() {
			if ( isset( $this->customer_country_by_ip ) ) {
				return $this->customer_country_by_ip;
			}
			if ( class_exists( 'WC_Geolocation' ) ) {
				// Get the country by IP.
				$location = WC_Geolocation::geolocate_ip( ( 'wc' === get_option( 'wcj_price_by_country_ip_detection_method', 'wc' ) ? '' : wcj_get_the_ip() ) );
				// Base fallback.
				if ( empty( $location['country'] ) ) {
					$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
				}
				if ( ! empty( $location['country'] ) ) {
					$this->customer_country_by_ip = $location['country'];
				}
				return ( isset( $location['country'] ) ) ? $location['country'] : null;
			} else {
				return null;
			}
		}

		/**
		 * Change_price_shipping.
		 *
		 * @version 5.6.2
		 *
		 * @param string $package_rates defines the rates of the package.
		 * @param object $package defines the shipping package object.
		 */
		public function change_price_shipping( $package_rates, $package ) {
			$group_id = $this->get_customer_country_group_id();
			if ( null !== $group_id && '' !== $group_id ) {
				$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
				return wcj_change_price_shipping_package_rates( $package_rates, $country_exchange_rate );
			} else {
				return $package_rates;
			}
		}

		/**
		 * Get_customer_country_group_id.
		 *
		 * @version 5.6.8
		 * @todo    [feature] (maybe) `( 'cart_and_checkout' === get_option( 'wcj_price_by_country_override_scope', 'all' ) && ( is_cart() || is_checkout() ) ) ||`
		 */
		public function get_customer_country_group_id() {

			if ( 'yes' === get_option( 'wcj_price_by_country_revert', 'no' ) && is_checkout() ) {
				$this->customer_country_group_id = -1;
				return null;
			}

			// We already know the group - nothing to calculate - return group.
			if (
			'yes' === get_option( 'wcj_price_by_country_save_country_group_id', 'yes' )
			&& isset( $this->customer_country_group_id ) && null !== $this->customer_country_group_id && $this->customer_country_group_id > 0
			) {
				return $this->customer_country_group_id;
			}

			// Get the country.
			// phpcs:disable WordPress.Security.NonceVerification
			$override_option = get_option( 'wcj_price_by_country_override_on_checkout_with_billing_country', 'no' );
			if ( isset( $_GET['country'] ) && '' !== $_GET['country'] && wcj_is_user_role( 'administrator' ) ) {
				$country = sanitize_text_field( wp_unslash( $_GET['country'] ) );
			} elseif ( 'no' !== $override_option && (
				( 'all' === get_option( 'wcj_price_by_country_override_scope', 'all' ) ) ||
				( 'checkout' === get_option( 'wcj_price_by_country_override_scope', 'all' ) && is_checkout() )
			)
			&& isset( WC()->customer )
			&& ( ( 'yes' === $override_option && '' !== wcj_customer_get_country() ) || ( 'shipping_country' === $override_option && '' !== WC()->customer->get_shipping_country() ) )
			) {
				$country = ( 'yes' === $override_option ) ? wcj_customer_get_country() : WC()->customer->get_shipping_country();
			} else {
				$session_value = wcj_session_get( 'wcj-country' );
				if ( 'by_ip' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
					$country = $this->get_customer_country_by_ip();
				} elseif ( 'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
					$country = null !== $session_value ? $session_value : $this->get_customer_country_by_ip();
				} elseif ( 'by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
					$country = wcj_session_get( 'wcj-country' );
				} elseif ( 'by_wpml' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
					$country = ( defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : null );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification

			if ( null === $country ) {
				$this->customer_country_group_id = -1;
				return null;
			}

			$this->customer_country_group_id = $this->get_country_group_id( $country );
			if ( - 1 !== $this->customer_country_group_id ) {
				return $this->customer_country_group_id;
			}

			// No country group found.
			$this->customer_country_group_id = -1;
			return null;
		}

		/**
		 * Gets country group id.
		 *
		 * @version 5.6.2
		 * @since   4.1.0
		 * @param string $country country code from the group of country.
		 *
		 * @return int
		 */
		public function get_country_group_id( $country ) {
			// Get the country group id - go through all the groups, first found group is returned.
			$wcj_price_by_country_total_groups_number = apply_filters( 'booster_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) );
			for ( $i = 1; $i <= $wcj_price_by_country_total_groups_number; $i ++ ) {
				switch ( get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
					case 'comma_list':
						$country_exchange_rate_group = get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
						$country_exchange_rate_group = str_replace( ' ', '', $country_exchange_rate_group );
						$country_exchange_rate_group = explode( ',', $country_exchange_rate_group );
						break;
					case 'multiselect':
						$country_exchange_rate_group = get_option( 'wcj_price_by_country_countries_group_' . $i, '' );
						break;
					case 'chosen_select':
						$country_exchange_rate_group = get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' );
						break;
				}
				if ( is_array( $country_exchange_rate_group ) && in_array( $country, $country_exchange_rate_group, true ) ) {
					return $i;
				}
			}
			return - 1;
		}

		/**
		 * Change_currency_code.
		 *
		 * @param string $currency contains currency code.
		 */
		public function change_currency_code( $currency ) {
			$group_id = $this->get_customer_country_group_id();
			if ( null !== $group_id && '' !== $group_id ) {
				$country_currency_code = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id );
				if ( null !== $country_currency_code && '' !== $country_currency_code ) {
					return $country_currency_code;
				}
			}
			return $currency;
		}

		/**
		 * Get_variation_prices_hash.
		 *
		 * @version 5.6.2
		 * @since   2.4.3
		 * @param array  $price_hash contains price related data.
		 * @param object $_product object of the product.
		 * @param bool   $display for_display true/false.
		 */
		public function get_variation_prices_hash( $price_hash, $_product, $display ) {
			$group_id = $this->get_customer_country_group_id();
			$price_hash['wcj_price_by_country_group_id_data'] = array(
				$group_id,
				get_option( 'wcj_price_by_country_rounding', 'none' ),
				get_option( 'wcj_price_by_country_make_pretty', 'no' ),
				get_option( 'wcj_price_by_country_make_pretty_min_amount_multiplier', 1 ),
				get_option( 'woocommerce_price_num_decimals', 2 ),
				get_option( 'wcj_price_by_country_local_enabled', 'yes' ),
				get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id, 'EUR' ),
				get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 ),
				get_option( 'wcj_price_by_country_make_empty_price_group_' . $group_id, 'no' ),
			);
			return $price_hash;
		}

		/**
		 * Change_price.
		 *
		 * @version 6.0.5
		 * @param string $price defines the price for conversion.
		 * @param object $product Product Object.
		 */
		public function change_price( $price, $product ) {
			$group_id = $this->get_customer_country_group_id();
			if ( null !== $group_id && '' !== $group_id ) {
				if ( 'yes' === get_option( 'wcj_price_by_country_compatibility_woo_discount_rules', 'no' ) ) {
					global $flycart_woo_discount_rules;
					if (
					! empty( $flycart_woo_discount_rules ) &&
					! has_action( 'woocommerce_before_calculate_totals', array( $flycart_woo_discount_rules, 'applyDiscountRules' ) ) &&
					WC()->cart->find_product_in_cart( WC()->cart->generate_cart_id( wcj_get_product_id( $product ) ) )
					) {
						return $price;
					}
				}
				$do_save         = 'yes' === get_option( 'wcj_price_by_country_save_prices', 'no' );
				$_current_filter = current_filter();
				if ( empty( $_current_filter ) ) {
					$_current_filter = 'wcj_filter__none';
				}
				if ( $do_save && isset( w_c_j()->all_modules['price_by_country']->calculated_products_prices[ wcj_get_product_id( $product ) ][ $_current_filter ] ) ) {
					return w_c_j()->all_modules['price_by_country']->calculated_products_prices[ wcj_get_product_id( $product ) ][ $_current_filter ];
				}
				$new_price = wcj_price_by_country( $price, $product, $group_id );
				w_c_j()->all_modules['price_by_country']->calculated_products_prices[ wcj_get_product_id( $product ) ][ $_current_filter ] = $new_price;

				if ( wcj_is_plugin_activated( 'b2b', 'b2bking.php' ) ) {

					$user_id              = get_current_user_id();
					$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
					if ( ! empty( $product ) ) {
						$b2b_price         = get_post_meta( $product->get_id(), 'b2bking_sale_product_price_group_' . $currentusergroupidnr, true );
						$b2b_regular_price = get_post_meta( $product->get_id(), 'b2bking_regular_product_price_group_' . $currentusergroupidnr, true );
					}

					if ( 'yes' === get_option( 'wcj_price_by_country_b2b_sale_price_group', 'no' ) ) {

						if ( empty( $b2b_price ) && ! empty( $b2b_regular_price ) && ! $product->is_type( 'variable' ) ) {

							$b2b_regular_price     = get_post_meta( $product->get_id(), 'b2bking_regular_product_price_group_' . $currentusergroupidnr, true );
							$country_exchange_rate = wcj_get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
							$modified_price        = $b2b_regular_price * $country_exchange_rate;

							return $modified_price;
						}
					}
				}

				return $new_price;
			}
			// No changes.
			return $price;
		}
	}

endif;

return new WCJ_Price_By_Country_Core();
