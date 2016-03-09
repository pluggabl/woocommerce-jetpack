<?php
/**
 * WooCommerce Jetpack Left to Free Shipping Widget
 *
 * The WooCommerce Jetpack Left to Free Shipping Widget class.
 *
 * @version 2.4.4
 * @since   2.4.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Widget_Left_to_Free_Shipping' ) ) :

class WCJ_Widget_Left_to_Free_Shipping extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'wcj_widget_left_to_free_shipping',
			'description' => __( 'Booster: Left to Free Shipping Widget', 'woocommerce-jetpack' ),
		);
		parent::__construct( 'wcj_widget_left_to_free_shipping', __( 'Booster - Left to Free Shipping', 'woocommerce-jetpack' ), $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		echo wcj_get_left_to_free_shipping( $instance['content'] );
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
		$title   = ! empty( $instance['title'] )   ? $instance['title']   : __( 'Left to Free Shipping', 'woocommerce-jetpack' );
		$content = ! empty( $instance['content'] ) ? $instance['content'] : __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		<label for="<?php echo $this->get_field_id( 'content' ); ?>"><?php _e( 'Content:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'content' ); ?>" name="<?php echo $this->get_field_name( 'content' ); ?>" type="text" value="<?php echo esc_attr( $content ); ?>">
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = array();
		$instance['title'] =   ( ! empty( $new_instance['title'] ) )   ? strip_tags( $new_instance['title'] )   : '';
		$instance['content'] = ( ! empty( $new_instance['content'] ) ) ? strip_tags( $new_instance['content'] ) : '';
		return $instance;
	}
}

endif;

// register WCJ_Widget_Left_to_Free_Shipping widget
if ( ! function_exists( 'register_wcj_widget_left_to_free_shipping' ) ) {
	function register_wcj_widget_left_to_free_shipping() {
		register_widget( 'WCJ_Widget_Left_to_Free_Shipping' );
	}
}
add_action( 'widgets_init', 'register_wcj_widget_left_to_free_shipping' );
