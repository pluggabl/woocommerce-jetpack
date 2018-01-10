<?php
/**
 * Booster for WooCommerce - Module - Products XML
 *
 * @version 3.3.0
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 * @todo    create all files at once (manually and synchronize update)
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Products_XML' ) ) :

class WCJ_Products_XML extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.0
	 * @since   2.5.7
	 */
	function __construct() {

		$this->id         = 'products_xml';
		$this->short_desc = __( 'Products XML Feeds', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce products XML feeds.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-products-xml-feeds';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init',           array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init',     array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init',     array( $this, 'wcj_create_products_xml' ) );
			add_action( 'admin_notices',  array( $this, 'admin_notices' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_add_custom_intervals' ) );
			$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_products_xml_total_files', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				add_action( 'wcj_create_products_xml_hook_' . $i, array( $this, 'create_products_xml_cron' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function schedule_the_events() {
		$update_intervals  = array(
			'minutely',
			'hourly',
			'twicedaily',
			'daily',
			'weekly',
		);
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_products_xml_total_files', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$event_hook = 'wcj_create_products_xml_hook_' . $i;
			if ( 'yes' === get_option( 'wcj_products_xml_enabled_' . $i, 'yes' ) ) {
				$selected_interval = apply_filters( 'booster_option', 'weekly', get_option( 'wcj_create_products_xml_period_' . $i, 'weekly' ) );
				foreach ( $update_intervals as $interval ) {
					$event_timestamp = wp_next_scheduled( $event_hook, array( $interval, $i ) );
					if ( $selected_interval === $interval ) {
						update_option( 'wcj_create_products_xml_cron_time_' . $i, $event_timestamp );
					}
					if ( ! $event_timestamp && $selected_interval === $interval ) {
						wp_schedule_event( time(), $selected_interval, $event_hook, array( $selected_interval, $i ) );
					} elseif ( $event_timestamp && $selected_interval !== $interval ) {
						wp_unschedule_event( $event_timestamp, $event_hook, array( $interval, $i ) );
					}
				}
			} else { // unschedule all events
				update_option( 'wcj_create_products_xml_cron_time_' . $i, '' );
				foreach ( $update_intervals as $interval ) {
					$event_timestamp = wp_next_scheduled( $event_hook, array( $interval, $i ) );
					if ( $event_timestamp ) {
						wp_unschedule_event( $event_timestamp, $event_hook, array( $interval, $i ) );
					}
				}
			}
		}
	}

	/**
	 * cron_add_custom_intervals.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function cron_add_custom_intervals( $schedules ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __( 'Once Weekly', 'woocommerce-jetpack' )
		);
		$schedules['minutely'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute', 'woocommerce-jetpack' )
		);
		return $schedules;
	}

	/**
	 * admin_notices.
	 *
	 * @version 3.3.0
	 * @since   2.5.7
	 */
	function admin_notices() {
		if ( isset( $_GET['wcj_create_products_xml_result'] ) ) {
			if ( 0 == $_GET['wcj_create_products_xml_result'] ) {
				$class   = 'notice notice-error';
				$message = __( 'An error has occurred while creating products XML file.', 'woocommerce-jetpack' );
			} else {
				$class   = 'notice notice-success is-dismissible';
				$message = sprintf( __( 'Products XML file #%s created successfully.', 'woocommerce-jetpack' ), $_GET['wcj_create_products_xml_result'] );
			}
			echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
		}
	}

	/**
	 * wcj_create_products_xml.
	 *
	 * @version 3.3.0
	 * @since   2.5.7
	 */
	function wcj_create_products_xml() {
		if ( isset( $_GET['wcj_create_products_xml'] ) ) {
			$file_num = $_GET['wcj_create_products_xml'];
			$result = $this->create_products_xml( $file_num );
			if ( false !== $result ) {
				update_option( 'wcj_products_time_file_created_' . $file_num, current_time( 'timestamp' ) );
			}
			wp_safe_redirect( add_query_arg( 'wcj_create_products_xml_result', ( false === $result ? 0 : $file_num ), remove_query_arg( 'wcj_create_products_xml' ) ) );
			exit;
		}
	}

	/**
	 * create_products_xml_cron.
	 *
	 * @version 2.6.0
	 * @since   2.5.7
	 */
	function create_products_xml_cron( $interval, $file_num ) {
		$result = $this->create_products_xml( $file_num );
		if ( false !== $result ) {
			update_option( 'wcj_products_time_file_created_' . $file_num, current_time( 'timestamp' ) );
		}
		die();
	}

	/**
	 * create_products_xml.
	 *
	 * @version 3.2.4
	 * @since   2.5.7
	 * @todo    check the `str_replace` and `html_entity_decode` part
	 */
	function create_products_xml( $file_num ) {
		$xml_items = '';
		$xml_header_template  = get_option( 'wcj_products_xml_header_'        . $file_num, '' );
		$xml_footer_template  = get_option( 'wcj_products_xml_footer_'        . $file_num, '' );
		$xml_item_template    = get_option( 'wcj_products_xml_item_'          . $file_num, '' );
		$products_in_ids      = wcj_maybe_convert_string_to_array( get_option( 'wcj_products_xml_products_incl_' . $file_num, '' ) );
		$products_ex_ids      = wcj_maybe_convert_string_to_array( get_option( 'wcj_products_xml_products_excl_' . $file_num, '' ) );
		$products_cats_in_ids = get_option( 'wcj_products_xml_cats_incl_'     . $file_num, '' );
		$products_cats_ex_ids = get_option( 'wcj_products_xml_cats_excl_'     . $file_num, '' );
		$products_tags_in_ids = get_option( 'wcj_products_xml_tags_incl_'     . $file_num, '' );
		$products_tags_ex_ids = get_option( 'wcj_products_xml_tags_excl_'     . $file_num, '' );
		$products_scope       = get_option( 'wcj_products_xml_scope_'         . $file_num, 'all' );
		$offset = 0;
		$block_size = get_option( 'wcj_products_xml_block_size', 256 );
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			if ( 'all' != $products_scope ) {
				$args['meta_query'] = WC()->query->get_meta_query();
				switch ( $products_scope ) {
					case 'sale_only':
						$args['post__in']     = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
						break;
					case 'not_sale_only':
						$args['post__not_in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
						break;
					case 'featured_only':
						$args['post__in']     = array_merge( array( 0 ), wc_get_featured_product_ids() );
						break;
					case 'not_featured_only':
						$args['post__not_in'] = array_merge( array( 0 ), wc_get_featured_product_ids() );
						break;
				}
			}
			if ( ! empty( $products_in_ids ) ) {
				$args['post__in'] = $products_in_ids;
			}
			if ( ! empty( $products_ex_ids ) ) {
				$args['post__not_in'] = $products_ex_ids;
			}
			if ( ! empty( $products_cats_in_ids ) ) {
				if ( ! isset( $args['tax_query'] ) ) {
					$args['tax_query'] = array();
				}
				$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $products_cats_in_ids,
					'operator' => 'IN',
				);
			}
			if ( ! empty( $products_cats_ex_ids ) ) {
				if ( ! isset( $args['tax_query'] ) ) {
					$args['tax_query'] = array();
				}
				$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $products_cats_ex_ids,
					'operator' => 'NOT IN',
				);
			}
			if ( ! empty( $products_tags_in_ids ) ) {
				if ( ! isset( $args['tax_query'] ) ) {
					$args['tax_query'] = array();
				}
				$args['tax_query'][] = array(
					'taxonomy' => 'product_tag',
					'field'    => 'term_id',
					'terms'    => $products_tags_in_ids,
					'operator' => 'IN',
				);
			}
			if ( ! empty( $products_tags_ex_ids ) ) {
				if ( ! isset( $args['tax_query'] ) ) {
					$args['tax_query'] = array();
				}
				$args['tax_query'][] = array(
					'taxonomy' => 'product_tag',
					'field'    => 'term_id',
					'terms'    => $products_tags_ex_ids,
					'operator' => 'NOT IN',
				);
			}
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$xml_items .= str_replace( '&', '&amp;', html_entity_decode( do_shortcode( $xml_item_template ) ) );
			}
			$offset += $block_size;
		}
		wp_reset_postdata();
		return file_put_contents(
			ABSPATH . get_option( 'wcj_products_xml_file_path_' . $file_num, ( ( 1 == $file_num ) ? 'products.xml' : 'products_' . $file_num . '.xml' ) ),
			do_shortcode( $xml_header_template ) . $xml_items . do_shortcode( $xml_footer_template )
		);
	}

}

endif;

return new WCJ_Products_XML();
