<?php
/**
 * Booster for WooCommerce - Settings Custom Fields
 *
 * @version 6.0.1
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Settings_Custom_Fields' ) ) :
		/**
		 * WCJ_Settings_Custom_Fields.
		 */
	class WCJ_Settings_Custom_Fields {

		/**
		 * Constructor.
		 *
		 * @version 3.2.4
		 * @since   2.8.0
		 */
		public function __construct() {
			add_action( 'woocommerce_admin_field_wcj_save_settings_button', array( $this, 'output_wcj_save_settings_button' ) );
			add_action( 'woocommerce_admin_field_wcj_number_plus_checkbox_start', array( $this, 'output_wcj_number_plus_checkbox_start' ) );
			add_action( 'woocommerce_admin_field_wcj_number_plus_checkbox_end', array( $this, 'output_wcj_number_plus_checkbox_end' ) );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'format_wcj_number_plus_checkbox_end' ), PHP_INT_MAX, 3 );
			add_action( 'woocommerce_admin_field_custom_textarea', array( $this, 'output_custom_textarea' ) );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'unclean_custom_textarea' ), PHP_INT_MAX, 3 );
			add_action( 'woocommerce_admin_field_custom_number', array( $this, 'output_custom_number' ) );
			add_action( 'woocommerce_admin_field_custom_link', array( $this, 'output_custom_link' ) );
			add_action( 'woocommerce_admin_field_module_tools', array( $this, 'output_module_tools' ) );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'maybe_unclean_field' ), PHP_INT_MAX, 3 );
			add_action( 'woocommerce_admin_field_exchange_rate', array( $this, 'output_exchange_rate_settings_button' ) );
		}

		/**
		 * Output_exchange_rate_settings_button.
		 *
		 * @version 6.0.1
		 * @param  Array $value Get values.
		 */
		public function output_exchange_rate_settings_button( $value ) {

			$value['type'] = 'number';

			$option_value = wcj_get_option( $value['id'], $value['default'] );

			// Custom attribute handling.
			$custom_attributes = array();
			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			} else {
				if (
				! w_c_j()->all_modules['currency_exchange_rates']->is_enabled()
				|| 'yes' !== wcj_get_option( 'wcj_currency_exchange_rates_point_decimal_separator', 'no' )
				) {
					$custom_attributes = array( 'step="' . sprintf( '%.12f', 1 / pow( 10, 12 ) ) . '"', 'min="0"' );
				} else {
					$custom_attributes = array( 'step="0.00000001"', 'min="0"' );
				}
			}
			$custom_attributes_button = array();
			if ( ! empty( $value['custom_attributes_button'] ) && is_array( $value['custom_attributes_button'] ) ) {
				foreach ( $value['custom_attributes_button'] as $attribute => $attribute_value ) {
					$custom_attributes_button[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}
			$tip                  = '';
			$description          = '';
			$exchange_rate_server = wcj_get_currency_exchange_rate_server_name( $value['custom_attributes_button']['currency_from'], $value['custom_attributes_button']['currency_to'] );
			/* translators: %s: translation added */
			$value_title = sprintf( __( 'Grab raw %1$s rate from %2$s.', 'woocommerce-jetpack' ), $value['value'], $exchange_rate_server ) .
			' ' . __( 'Doesn\'t apply rounding, offset etc.', 'woocommerce-jetpack' );
			?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo wp_kses_post( $tip ); ?>
			</th>
			<td class="forminp forminp-<?php echo wp_kses_post( sanitize_title( $value['type'] ) ); ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="<?php echo esc_attr( $value['type'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ); ?>
					/>
				<input
					name="<?php echo esc_attr( $value['id'] . '_button' ); ?>"
					id="<?php echo esc_attr( $value['id'] . '_button' ); ?>"
					type="button"
					value="<?php echo esc_attr( $value['value'] ); ?>"
					title="<?php echo esc_attr( $value_title ); ?>"
					class="exchage_rate_button"
					<?php echo wp_kses_post( implode( ' ', $custom_attributes_button ) ); ?>
					/>
			</td>
		</tr>
			<?php
		}

		/**
		 * Maybe_unclean_field.
		 *
		 * @version 3.1.3
		 * @since   3.1.3
		 * @param  Array $value Get values.
		 * @param  Array $option Get options.
		 * @param  Array $raw_value Get raw value.
		 */
		public function maybe_unclean_field( $value, $option, $raw_value ) {
			return ( isset( $option['wcj_raw'] ) && $option['wcj_raw'] ? $raw_value : $value );
		}

		/**
		 * Output_wcj_save_settings_button.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param  Array $value Get values.
		 */
		public function output_wcj_save_settings_button( $value ) {
			// Output.
			?>
		<tr valign="top">
			<th scope="row" class="titledesc"></th>
			<td class="forminp forminp-<?php echo wp_kses_post( sanitize_title( $value['type'] ) ); ?>">
				<input name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php echo esc_html( $value['title'] ); ?>">
			</td>
		</tr>
			<?php
		}

		/**
		 * Format_wcj_number_plus_checkbox_end.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @param  Array $value Get values.
		 * @param  Array $option Get options.
		 * @param  Array $raw_value Get raw value.
		 */
		public function format_wcj_number_plus_checkbox_end( $value, $option, $raw_value ) {
			return ( 'wcj_number_plus_checkbox_end' === $option['type'] ) ? ( '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no' ) : $value;
		}

		/**
		 * Output_wcj_number_plus_checkbox_start.
		 *
		 * @version 6.0.0
		 * @since   2.8.0
		 * @param  Array $value Get values.
		 */
		public function output_wcj_number_plus_checkbox_start( $value ) {
			// Custom attribute handling.
			$custom_attributes = array();
			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}
			// Description handling.
			$field_description = WC_Admin_Settings::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];
			// Option value.
			$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
			// Output.
			?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo wp_kses_post( $tooltip_html ); ?>
			</th>
			<td class="forminp forminp-number-checkbox">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="number"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ); ?>
					/> 
					<?php
					echo wp_kses_post( $description ) . ' ';
		}

		/**
		 * Output_wcj_number_plus_checkbox_end.
		 *
		 * @version 6.0.0
		 * @since   2.8.0
		 * @param  Array $value Get values.
		 */
		public function output_wcj_number_plus_checkbox_end( $value ) {
			// Custom attribute handling.
			$custom_attributes = array();
			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}
			// Description handling.
			$field_description = WC_Admin_Settings::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];
			// Option value.
			$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
			// Output.
			?>
				<label for="<?php echo wp_kses_post( $value['id'] ); ?>">
					<input
						name="<?php echo esc_attr( $value['id'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						type="checkbox"
						class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
						value="1"
						<?php checked( $option_value, 'yes' ); ?>
						<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ); ?>
					/> <?php echo wp_kses_post( $description ); ?>
				</label> <?php echo wp_kses_post( $tooltip_html ); ?>
			</td>
		</tr>
			<?php
		}

		/**
		 * Unclean_custom_textarea.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param  Array $value Get values.
		 * @param  Array $option Get options.
		 * @param  Array $raw_value Get raw value.
		 */
		public function unclean_custom_textarea( $value, $option, $raw_value ) {
			return ( 'custom_textarea' === $option['type'] ) ? $raw_value : $value;
		}

		/**
		 * Output_custom_textarea.
		 *
		 * @version 5.6.8
		 * @since   2.2.6
		 * @param  Array $value Get values.
		 */
		public function output_custom_textarea( $value ) {
			$option_value      = wcj_get_option( $value['id'], $value['default'] );
			$custom_attributes = ( isset( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) ?
			$value['custom_attributes'] : array();
			$description       = ' <p class="description">' . $value['desc'] . '</p>';
			$tooltip_html      = ( $value['desc_tip'] && isset( $value['desc_tip'] ) && '' !== $value['desc_tip'] ) ?
			'<span class="woocommerce-help-tip" data-tip="' . $value['desc_tip'] . '"></span>' : '';
			// Output.
			?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo wp_kses_post( $tooltip_html ); ?>
			</th>
			<td class="forminp forminp-<?php echo wp_kses_post( $value['type'] ); ?>">
				<?php echo wp_kses_post( $description ); ?>

				<textarea
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ); ?>
					><?php echo esc_textarea( $option_value ); ?></textarea>
			</td>
		</tr>
			<?php
		}

		/**
		 * Output_module_tools.
		 *
		 * @version 5.6.8
		 * @since   2.2.3
		 * @param  Array $value Get values.
		 */
		public function output_module_tools( $value ) {
			?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ); ?>"></span>
			</th>
			<td class="forminp forminp-<?php echo wp_kses_post( $value['type'] ); ?>">
				<?php
				$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
				if ( $wpnonce && isset( $_GET['section'] ) ) {
					do_action( 'wcj_module_tools_' . sanitize_text_field( wp_unslash( $_GET['section'] ) ) );}
				?>
			</td>
		</tr>
			<?php
		}

		/**
		 * Output_custom_link.
		 *
		 * @version 5.6.8
		 * @since   2.2.8
		 * @param  Array $value Get values.
		 */
		public function output_custom_link( $value ) {
			$tooltip_html = ( $value['desc_tip'] && isset( $value['desc_tip'] ) && '' !== $value['desc_tip'] ) ?
			'<span class="woocommerce-help-tip" data-tip="' . $value['desc_tip'] . '"></span>' : '';
			?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label><?php echo wp_kses_post( $tooltip_html ); ?>
			</th>
			<td class="forminp forminp-<?php echo wp_kses_post( $value['type'] ); ?>">
				<?php echo wp_kses_post( $value['link'] ); ?>
			</td>
		</tr>
			<?php
		}

		/**
		 * Output_custom_number.
		 *
		 * @version 5.6.8
		 * @param  Array $value Get values.
		 */
		public function output_custom_number( $value ) {
			$type         = 'number';
			$option_value = get_option( $value['id'], $value['default'] );
			$tooltip_html = ( $value['desc_tip'] && isset( $value['desc_tip'] ) && '' !== $value['desc_tip'] ) ?
			'<span class="woocommerce-help-tip" data-tip="' . $value['desc_tip'] . '"></span>' : '';
			$description  = ' <span class="description">' . $value['desc'] . '</span>';
			$save_button  = apply_filters(
				'booster_option',
				'',
				' <input name="save" class="button-primary" type="submit" value="' . __( 'Save changes', 'woocommerce' ) . '">'
			);
			// Custom attribute handling.
			$custom_attributes = array();
			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}
			// Output.
			?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo wp_kses_post( $tooltip_html ); ?>
			</th>
			<td class="forminp forminp-<?php echo wp_kses_post( $value['type'] ); ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="<?php echo esc_attr( $type ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ); ?>
					/><?php echo wp_kses_post( $save_button ); ?><?php echo wp_kses_post( $description ); ?>
			</td>
		</tr>
			<?php
		}

	}

endif;

return new WCJ_Settings_Custom_Fields();
