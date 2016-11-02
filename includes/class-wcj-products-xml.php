<?php
/**
 * WooCommerce Jetpack Products XML
 *
 * The WooCommerce Jetpack Products XML class.
 *
 * @version 2.5.7
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Products_XML' ) ) :

class WCJ_Products_XML extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	public function __construct() {

		$this->id         = 'products_xml';
		$this->short_desc = __( 'Products XML', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce products XML feed.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-booster-products-xml-feed/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init',                         array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init',                   array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init',                   array( $this, 'wcj_create_products_xml' ) );
			add_action( 'wcj_create_products_xml_hook', array( $this, 'create_products_xml_cron' ) );
			add_filter( 'cron_schedules',               array( $this, 'cron_add_custom_intervals' ) );
		}
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function schedule_the_events() {
		$selected_interval = get_option( 'wcj_create_products_xml_period', 'daily' );
		$update_intervals  = array(
			'minutely',
			'hourly',
			'twicedaily',
			'daily',
			'weekly',
		);
		foreach ( $update_intervals as $interval ) {
			$event_hook = 'wcj_create_products_xml_hook';
			$event_timestamp = wp_next_scheduled( $event_hook, array( $interval ) );
			if ( $selected_interval === $interval ) {
				update_option( 'wcj_create_products_xml_cron_time', $event_timestamp );
			}
			if ( ! $event_timestamp && $selected_interval === $interval ) {
				wp_schedule_event( time(), $selected_interval, $event_hook, array( $selected_interval ) );
			} elseif ( $event_timestamp && $selected_interval !== $interval ) {
				wp_unschedule_event( $event_timestamp, $event_hook, array( $interval ) );
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
	 * admin_notice__success.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function admin_notice__success() {
		echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Products XML file created successfully.', 'woocommerce-jetpack' ) . '</p></div>';
	}

	/**
	 * admin_notice__error.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function admin_notice__error() {
		echo '<div class="notice notice-error"><p>' . __( 'An error has occurred while creating products XML file.', 'woocommerce-jetpack' ) . '</p></div>';
	}

	/**
	 * wcj_create_products_xml.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function wcj_create_products_xml() {
		if ( isset( $_GET['wcj_create_products_xml'] ) ) {
			$result = $this->create_products_xml();
			add_action( 'admin_notices', array( $this, ( ( false !== $result ) ? 'admin_notice__success' : 'admin_notice__error' ) ) );
		}
	}

	/**
	 * create_products_xml_cron.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function create_products_xml_cron() {
		$this->create_products_xml();
		die();
	}

	/**
	 * create_products_xml.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function create_products_xml() {
		$xml_items = '';
		$xml_header_template = get_option( 'wcj_products_xml_header', '' );
		$xml_footer_template = get_option( 'wcj_products_xml_footer', '' );
		$xml_item_template   = get_option( 'wcj_products_xml_item', '' );
		$offset = 0;
		$block_size = 1024;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$xml_items .= str_replace( '&', '&amp;', html_entity_decode( do_shortcode( $xml_item_template ) ) ); // todo
			}
			$offset += $block_size;
		}
		wp_reset_postdata();
		return file_put_contents( ABSPATH . get_option( 'wcj_products_xml_file_path', 'products.xml' ), $xml_header_template . $xml_items . $xml_footer_template );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_settings() {
		$products_xml_cron_desc = '';
		if ( $this->is_enabled() ) {
			if ( '' != get_option( 'wcj_create_products_xml_cron_time', '' ) ) {
				$scheduled_time_diff = get_option( 'wcj_create_products_xml_cron_time', '' ) - time();
				if ( $scheduled_time_diff > 0 ) {
					$products_xml_cron_desc = '<em>' . sprintf( __( '%s seconds till next update.', 'woocommerce-jetpack' ), $scheduled_time_diff ) . '</em>';
				}
			}
		}
		$settings = array(
			array(
				'title'    => __( 'Products XML Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_products_xml_options',
			),
			array(
				'title'    => __( 'Products XML Header', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_header',
				'default'  => '<?xml version = "1.0" encoding = "utf-8" ?>' . PHP_EOL . '<root>' . PHP_EOL,
				'type'     => 'custom_textarea',
				'css'      => 'width:66%;min-width:300px;min-height:300px;',
			),
			array(
				'title'    => __( 'Products XML Item', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_item',
				'default'  =>
					'<item>' . PHP_EOL .
						"\t" . '<name>[wcj_product_title]</name>' . PHP_EOL .
						"\t" . '<link>[wcj_product_url]</link>' . PHP_EOL .
						"\t" . '<price>[wcj_product_price hide_currency="yes"]</price>' . PHP_EOL .
						"\t" . '<image>[wcj_product_image_url image_size="full"]</image>' . PHP_EOL .
						"\t" . '<category_full>[wcj_product_categories_names]</category_full>' . PHP_EOL .
						"\t" . '<category_link>[wcj_product_categories_urls]</category_link>' . PHP_EOL .
					'</item>' . PHP_EOL,
				'type'     => 'custom_textarea',
				'css'      => 'width:66%;min-width:300px;min-height:300px;',
			),
			array(
				'title'    => __( 'Products XML Footer', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_footer',
				'default'  => '</root>',
				'type'     => 'custom_textarea',
				'css'      => 'width:66%;min-width:300px;min-height:300px;',
			),
			array(
				'title'    => __( 'Result XML File Path and Name', 'woocommerce-jetpack' ),
				'desc'     => '<a target="_blank" href="' . site_url() . '/' . get_option( 'wcj_products_xml_file_path', 'products.xml' ) . '">' . site_url() . '/' . get_option( 'wcj_products_xml_file_path', 'products.xml' ) . '</a>', // todo
				'id'       => 'wcj_products_xml_file_path',
				'default'  => 'products.xml',
				'type'     => 'text',
				'css'      => 'width:66%;min-width:300px;',
			),
			array(
				'title'    => __( 'Update Period', 'woocommerce-jetpack' ),
				'desc'     => $products_xml_cron_desc .
					'<br><a href="' . add_query_arg( 'wcj_create_products_xml', '1' ) . '">' . __( 'Create Now', 'woocommerce-jetpack' ) . '</a>', // todo
				'id'       => 'wcj_create_products_xml_period',
				'default'  => 'daily',
				'type'     => 'select',
				'options'  => array(
					'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
					'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
					'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
					'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
					'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
				),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_products_xml_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Products_XML();
