<?php
/**
 * WooCommerce Jetpack Products XML
 *
 * The WooCommerce Jetpack Products XML class.
 *
 * @version 2.5.7
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 * @todo    create all files at once (manually and synchronize update); move (maybe) to "PRODUCTS" category;
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
		$this->link       = 'http://booster.io/features/woocommerce-products-xml-feed/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init',           array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init',     array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init',     array( $this, 'wcj_create_products_xml' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_add_custom_intervals' ) );
			$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_products_xml_total_files', 1 ) );
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
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_products_xml_total_files', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$event_hook = 'wcj_create_products_xml_hook_' . $i;
			if ( 'yes' === get_option( 'wcj_products_xml_enabled_' . $i, 'yes' ) ) {
				$selected_interval = apply_filters( 'booster_get_option', 'weekly', get_option( 'wcj_create_products_xml_period_' . $i, 'weekly' ) );
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
			$result = $this->create_products_xml( $_GET['wcj_create_products_xml'] );
			add_action( 'admin_notices', array( $this, ( ( false !== $result ) ? 'admin_notice__success' : 'admin_notice__error' ) ) );
		}
	}

	/**
	 * create_products_xml_cron.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function create_products_xml_cron( $interval, $file_num ) {
		$this->create_products_xml( $file_num );
		die();
	}

	/**
	 * create_products_xml.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function create_products_xml( $file_num ) {
		$xml_items = '';
		$xml_header_template = get_option( 'wcj_products_xml_header_' . $file_num, '' );
		$xml_footer_template = get_option( 'wcj_products_xml_footer_' . $file_num, '' );
		$xml_item_template   = get_option( 'wcj_products_xml_item_'   . $file_num, '' );
		$offset = 0;
		$block_size = 256;
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
		return file_put_contents(
			ABSPATH . get_option( 'wcj_products_xml_file_path_' . $file_num, ( ( 1 == $file_num ) ? 'products.xml' : 'products_' . $file_num . '.xml' ) ),
			$xml_header_template . $xml_items . $xml_footer_template
		);
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_products_xml_options',
			),
			array(
				'title'    => __( 'Total Files', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_total_files',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc_tip' => __( 'Press Save changes after you change this number.', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
					apply_filters( 'booster_get_message', '', 'readonly' ) : array( 'step' => '1', 'min'  => '1', ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_products_xml_options',
			),
		);
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_products_xml_total_files', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$products_xml_cron_desc = '';
			if ( $this->is_enabled() ) {
				if ( '' != get_option( 'wcj_create_products_xml_cron_time_' . $i, '' ) ) {
					$scheduled_time_diff = get_option( 'wcj_create_products_xml_cron_time_' . $i, '' ) - time();
					if ( $scheduled_time_diff > 0 ) {
						$products_xml_cron_desc = '<em>' . sprintf( __( '%s seconds till next update.', 'woocommerce-jetpack' ), $scheduled_time_diff ) . '</em>';
					}
				}
				$products_xml_cron_desc .= '<br><a href="' . add_query_arg( 'wcj_create_products_xml', $i ) . '">' . __( 'Create Now', 'woocommerce-jetpack' ) . '</a>';
			}
			$default_file_name = ( ( 1 == $i ) ? 'products.xml' : 'products_' . $i . '.xml' );
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'XML File', 'woocommerce-jetpack' ) . ' #' . $i,
					'type'     => 'title',
					'id'       => 'wcj_products_xml_options_' . $i,
				),
				array(
					'title'    => __( 'Enabled', 'woocommerce-jetpack' ),
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'id'       => 'wcj_products_xml_enabled_' . $i,
					'default'  => 'yes',
					'type'     => 'checkbox',
				),
				array(
					'title'    => __( 'XML Header', 'woocommerce-jetpack' ),
					'id'       => 'wcj_products_xml_header_' . $i,
					'default'  => '<?xml version = "1.0" encoding = "utf-8" ?>' . PHP_EOL . '<root>' . PHP_EOL,
					'type'     => 'custom_textarea',
					'css'      => 'width:66%;min-width:300px;min-height:150px;',
				),
				array(
					'title'    => __( 'XML Item', 'woocommerce-jetpack' ),
					'desc'     => sprintf(
						__( 'You can use shortcodes here. Please take a look at <a target="_blank" href="%s">Booster\'s products shortcodes</a>.', 'woocommerce-jetpack' ),
						'http://booster.io/category/shortcodes/products-shortcodes/'
					),
					'id'       => 'wcj_products_xml_item_' . $i,
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
					'title'    => __( 'XML Footer', 'woocommerce-jetpack' ),
					'id'       => 'wcj_products_xml_footer_' . $i,
					'default'  => '</root>',
					'type'     => 'custom_textarea',
					'css'      => 'width:66%;min-width:300px;min-height:150px;',
				),
				array(
					'title'    => __( 'XML File Path and Name', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Path on server:', 'woocommerce-jetpack' ) . ' ' . ABSPATH . get_option( 'wcj_products_xml_file_path_' . $i, $default_file_name ),
					'desc'     => __( 'URL:', 'woocommerce-jetpack' ) . ' ' . '<a target="_blank" href="' . site_url() . '/' . get_option( 'wcj_products_xml_file_path_' . $i, $default_file_name ) . '">' . site_url() . '/' . get_option( 'wcj_products_xml_file_path_' . $i, $default_file_name ) . '</a>', // todo
					'id'       => 'wcj_products_xml_file_path_' . $i,
					'default'  => $default_file_name,
					'type'     => 'text',
					'css'      => 'width:66%;min-width:300px;',
				),
				array(
					'title'    => __( 'Update Period', 'woocommerce-jetpack' ),
					'desc'     => $products_xml_cron_desc,
					'id'       => 'wcj_create_products_xml_period_' . $i,
					'default'  => 'weekly',
					'type'     => 'select',
					'options'  => array(
						'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
						'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
						'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
						'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
						'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
					),
					'desc_tip' => __( 'Possible update periods are: every minute, hourly, twice daily, daily and weekly.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_get_message', '', 'desc_no_link' ),
					'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_products_xml_options_' . $i,
				),
			) );
		}
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Products_XML();
