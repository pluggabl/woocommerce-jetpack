<?php
/**
 * WooCommerce Jetpack Multicurrency Widget
 *
 * The WooCommerce Jetpack Multicurrency Widget class.
 *
 * @version 2.5.0
 * @since   2.4.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Widget_Multicurrency' ) ) :

class WCJ_Widget_Multicurrency extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'wcj_widget_multicurrency',
			'description' => __( 'Booster: Multicurrency Switcher Widget', 'woocommerce-jetpack' ),
		);
		parent::__construct( 'wcj_widget_multicurrency', __( 'Booster - Multicurrency Switcher', 'woocommerce-jetpack' ), $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @version 2.5.0
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		if ( ! wcj_is_module_enabled( 'multicurrency' ) ) {
			echo __( 'Multicurrency module not enabled!', 'woocommerce-jetpack' );
		} else {
			switch ( $instance['switcher_type'] ) {
				case 'link_list':
					echo do_shortcode( '[wcj_currency_select_link_list]' );
					break;
				case 'radio_list':
					echo do_shortcode( '[wcj_currency_select_radio_list]' );
					break;
				default:
					echo do_shortcode( '[wcj_currency_select_drop_down_list]' );
					break;
			}
		}
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @version 2.4.5
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
		$title         = ! empty( $instance['title'] )         ? $instance['title']         : '';
		$switcher_type = ! empty( $instance['switcher_type'] ) ? $instance['switcher_type'] : 'drop_down';
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'switcher_type' ); ?>"><?php _e( 'Type:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'switcher_type' ); ?>" name="<?php echo $this->get_field_name( 'switcher_type' ); ?>">
			<option value="drop_down" <?php  selected( $switcher_type, 'drop_down' ); ?>><?php  echo __( 'Drop down', 'woocommerce-jetpack' ); ?>
			<option value="radio_list" <?php selected( $switcher_type, 'radio_list' ); ?>><?php echo __( 'Radio list', 'woocommerce-jetpack' ); ?>
			<option value="link_list" <?php  selected( $switcher_type, 'link_list' ); ?>><?php  echo __( 'Link list', 'woocommerce-jetpack' ); ?>
		</select>
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @version 2.4.5
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = array();
		$instance['title']         = ( ! empty( $new_instance['title'] ) )         ? strip_tags( $new_instance['title'] )         : '';
		$instance['switcher_type'] = ( ! empty( $new_instance['switcher_type'] ) ) ? $new_instance['switcher_type']               : 'drop_down';
		return $instance;
	}
}

endif;

// register WCJ_Widget_Multicurrency widget
if ( ! function_exists( 'register_wcj_widget_multicurrency' ) ) {
	function register_wcj_widget_multicurrency() {
		register_widget( 'WCJ_Widget_Multicurrency' );
	}
}
add_action( 'widgets_init', 'register_wcj_widget_multicurrency' );
