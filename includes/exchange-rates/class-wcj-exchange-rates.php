<?php
/**
 * Booster for WooCommerce Exchange Rates
 *
 * @version  2.9.0
 * @author   Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Exchange_Rates' ) ) :

class WCJ_Exchange_Rates {

	/**
	 * Constructor.
	 */
	function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_exchange_rates_script' ) );
		add_action( 'admin_init',            array( $this, 'register_script' ) );

		add_action( 'woocommerce_admin_field_exchange_rate', array( $this, 'output_settings_button' ) );
	}

	/**
	 * register_script.
	 *
	 * @version 2.9.0
	 */
	function register_script() {
		if (
			isset( $_GET['section'] ) &&
			in_array( $_GET['section'], array(
				'multicurrency',
				'multicurrency_base_price',
				'currency_per_product',
				'price_by_country',
				'payment_gateways_currency',
				'currency_exchange_rates',
			) )
		) {
			wp_register_script( 'wcj-exchange-rates-ajax',  trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-ajax-exchange-rates.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-exchange-rates-ajax', 'ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			) );
		}
	}

	/**
	 * enqueue_exchange_rates_script.
	 *
	 * @version 2.6.0
	 */
	function enqueue_exchange_rates_script() {
	    if (
			isset( $_GET['section'] ) &&
			in_array( $_GET['section'], array(
				'multicurrency',
				'multicurrency_base_price',
				'currency_per_product',
				'price_by_country',
				'payment_gateways_currency',
				'currency_exchange_rates',
			) )
		) {
			wp_enqueue_script( 'wcj-exchange-rates-ajax' );
		}
	}

	/**
	 * output_settings_button.
	 *
	 * @version 2.6.0
	 * @todo    (maybe) wcj_currency_exchange_rates_precision
	 */
	function output_settings_button( $value ) {

		$value['type'] = 'number';

		$option_value = get_option( $value['id'], $value['default'] );

		// Custom attribute handling
		$custom_attributes = array();
//		$step = sprintf( "%f", ( 1 / pow( 10, absint( get_option( 'wcj_currency_exchange_rates_precision', 6 ) ) ) ) );
//		$value['custom_attributes'] = array( 'step' => $step, 'min'  => '0' );
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		$custom_attributes_button = array();
		if ( ! empty( $value['custom_attributes_button'] ) && is_array( $value['custom_attributes_button'] ) ) {
			foreach ( $value['custom_attributes_button'] as $attribute => $attribute_value ) {
				$custom_attributes_button[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		$tip = '';
		$description = '';
		$exchange_rate_servers = wcj_get_currency_exchange_rate_servers();
		$exchange_rate_server = $exchange_rate_servers[ get_option( 'wcj_currency_exchange_rates_server', 'yahoo' ) ];
		$value_title = sprintf( __( 'Grab %s rate from %s', 'woocommerce-jetpack' ), $value['value'], $exchange_rate_server );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tip; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="<?php echo esc_attr( $value['type'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); ?>
					/>
				<input
					name="<?php echo esc_attr( $value['id'] . '_button' ); ?>"
					id="<?php echo esc_attr( $value['id'] . '_button' ); ?>"
					type="button"
					value="<?php echo esc_attr( $value['value'] ); ?>"
					title="<?php echo esc_attr( $value_title ); ?>"
					class="exchage_rate_button"
					<?php echo implode( ' ', $custom_attributes_button ); ?>
					/>
			</td>
		</tr>
		<?php
	}

}

endif;

return new WCJ_Exchange_Rates();
