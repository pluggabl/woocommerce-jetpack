<?php
/**
 * Booster for WooCommerce - Widget - Country Switcher
 *
 * @version 2.5.5
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Widget_Country_Switcher' ) ) :

class WCJ_Widget_Country_Switcher extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   => 'wcj_widget_country_switcher',
			'description' => __( 'Booster: Country Switcher Widget', 'woocommerce-jetpack' ),
		);
		parent::__construct( 'wcj_widget_country_switcher', __( 'Booster - Country Switcher', 'woocommerce-jetpack' ), $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @version 2.5.5
	 * @param array $args
	 * @param array $instance
	 */
	function widget( $args, $instance ) {
		// outputs the content of the widget
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		if ( ! wcj_is_module_enabled( 'price_by_country' ) ) {
			echo __( 'Prices and Currencies by Country module not enabled!', 'woocommerce-jetpack' );
		} elseif ( 'by_ip' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
			echo __( 'Customer Country Detection Method must include "by user selection"!', 'woocommerce-jetpack' );
		} else {
			if ( ! isset( $instance['replace_with_currency'] ) ) {
				$instance['replace_with_currency'] = 'no';
			}
			echo do_shortcode( '[wcj_country_select_drop_down_list countries="' . $instance['countries'] . '" replace_with_currency="' . $instance['replace_with_currency'] . '"]' );
			/* switch ( $instance['switcher_type'] ) {
				case 'link_list':
					echo do_shortcode( '[wcj_currency_select_link_list]' );
					break;
				case 'radio_list':
					echo do_shortcode( '[wcj_currency_select_radio_list]' );
					break;
				default:
					echo do_shortcode( '[wcj_currency_select_drop_down_list]' );
					break;
			} */
		}
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @version 2.5.5
	 * @param array $instance The widget options
	 */
	function form( $instance ) {
		// outputs the options form on admin
		$title                 = ! empty( $instance['title'] )                 ? $instance['title']                 : '';
		$countries             = ! empty( $instance['countries'] )             ? $instance['countries']             : '';
		$replace_with_currency = ! empty( $instance['replace_with_currency'] ) ? $instance['replace_with_currency'] : 'no';
//		$switcher_type         = ! empty( $instance['switcher_type'] )         ? $instance['switcher_type']         : 'drop_down';
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'countries' ); ?>"><?php _e( 'Countries:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'countries' ); ?>" name="<?php echo $this->get_field_name( 'countries' ); ?>" type="text" value="<?php echo esc_attr( $countries ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'replace_with_currency' ); ?>"><?php _e( 'Replace with currency:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'replace_with_currency' ); ?>" name="<?php echo $this->get_field_name( 'replace_with_currency' ); ?>">
			<option value="no" <?php  selected( $replace_with_currency, 'no' ); ?>><?php  echo __( 'No', 'woocommerce-jetpack' ); ?>
			<option value="yes" <?php selected( $replace_with_currency, 'yes' ); ?>><?php echo __( 'Yes', 'woocommerce-jetpack' ); ?>
		</select>
		</p>
		<?php /*<p>
		<label for="<?php echo $this->get_field_id( 'switcher_type' ); ?>"><?php _e( 'Type:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'switcher_type' ); ?>" name="<?php echo $this->get_field_name( 'switcher_type' ); ?>">
			<option value="drop_down" <?php  selected( $switcher_type, 'drop_down' ); ?>><?php  echo __( 'Drop down', 'woocommerce-jetpack' ); ?>
			<option value="radio_list" <?php selected( $switcher_type, 'radio_list' ); ?>><?php echo __( 'Radio list', 'woocommerce-jetpack' ); ?>
			<option value="link_list" <?php  selected( $switcher_type, 'link_list' ); ?>><?php  echo __( 'Link list', 'woocommerce-jetpack' ); ?>
		</select>
		</p>*/ ?>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @version 2.5.5
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = array();
		$instance['title']                 = ( ! empty( $new_instance['title'] ) )                 ? strip_tags( $new_instance['title'] )         : '';
		$instance['countries']             = ( ! empty( $new_instance['countries'] ) )             ? $new_instance['countries']                   : '';
		$instance['replace_with_currency'] = ( ! empty( $new_instance['replace_with_currency'] ) ) ? $new_instance['replace_with_currency']       : 'no';
//		$instance['switcher_type']         = ( ! empty( $new_instance['switcher_type'] ) )         ? $new_instance['switcher_type']               : 'drop_down';
		return $instance;
	}
}

endif;

// register WCJ_Widget_Country_Switcher widget
if ( ! function_exists( 'register_wcj_widget_country_switcher' ) ) {
	function register_wcj_widget_country_switcher() {
		register_widget( 'WCJ_Widget_Country_Switcher' );
	}
}
add_action( 'widgets_init', 'register_wcj_widget_country_switcher' );
