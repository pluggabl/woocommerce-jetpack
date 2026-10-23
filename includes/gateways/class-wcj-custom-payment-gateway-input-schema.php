<?php
/**
 * Static custom gateway input profile for the Checkout Block.
 *
 * @package Booster_For_WooCommerce/gateways
 * @since 9.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Native profile only. This does not change Classic shortcode rendering. */
class WCJ_Custom_Payment_Gateway_Input_Schema {
	const ENVELOPE_KEY = 'wcj_custom_gateway_fields';
	const JSON_DEPTH   = 4;

	/**
	 * Check the canonical renderer and its supported output hooks without executing them.
	 *
	 * @return bool
	 */
	private static function hooks_supported() {
		global $shortcode_tags, $wp_filter;
		$renderer = isset( $shortcode_tags['wcj_input_field'] ) ? $shortcode_tags['wcj_input_field'] : null;
		if ( ! is_array( $renderer ) || array_keys( $renderer ) !== array( 0, 1 ) || ! is_object( $renderer[0] ) || 'WCJ_Input_Field_Shortcodes' !== get_class( $renderer[0] ) || 'wcj_shortcode' !== $renderer[1] ) {
			return false;
		}
		foreach ( array( 'wcj_shortcodes_kses', 'pre_do_shortcode_tag', 'do_shortcode_tag', 'woocommerce_gateway_description' ) as $hook ) {
			if ( false !== has_filter( $hook ) ) {
				return false;
			}
		}
		try {
			$method = new ReflectionMethod( $renderer[0], 'wcj_shortcode' );
			if ( 'WCJ_Shortcodes' !== $method->getDeclaringClass()->getName() ) {
				return false;
			}
			if ( isset( $wp_filter['wcj_shortcode_result'] ) ) {
				$hook = $wp_filter['wcj_shortcode_result'];
				if ( ! $hook instanceof WP_Hook || ! is_array( $hook->callbacks ) ) {
					return false;
				}
				foreach ( $hook->callbacks as $callbacks ) {
					if ( ! is_array( $callbacks ) ) {
						return false;
					}
					foreach ( $callbacks as $callback ) {
						$function = isset( $callback['function'] ) ? $callback['function'] : null;
						if ( ! is_array( $function ) || array_keys( $function ) !== array( 0, 1 ) || ! $function[0] instanceof WCJ_Shortcodes || 'add_result_key_param_to_shortcode_result' !== $function[1] ) {
							return false;
						}
						$method = new ReflectionMethod( $function[0], $function[1] );
						if ( 'WCJ_Shortcodes' !== $method->getDeclaringClass()->getName() ) {
							return false;
						}
					}
				}
			}
		} catch ( Throwable $error ) {
			return false;
		}
		return true;
	}

	/**
	 * Reject controls explicitly, including those Booster permits in post HTML.
	 *
	 * @param string $html Markup.
	 * @return bool
	 */
	private static function has_controls( $html ) {
		return 1 === preg_match( '/<\s*\/?\s*(?:form|input|select|option|textarea|button|script|style|iframe|object|embed|link|meta|base|svg|math|template)(?=[\s\/>])/i', $html );
	}

	/**
	 * Check that WP's attribute parser consumed a complete attribute list.
	 *
	 * @param string $raw Raw shortcode attributes.
	 * @return array|false
	 */
	private static function attributes( $raw ) {
		$atts   = shortcode_parse_atts( $raw );
		$offset = 0;
		preg_match_all( get_shortcode_atts_regex(), $raw, $tokens, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
		foreach ( $tokens as $token ) {
			if ( '' !== trim( substr( $raw, $offset, $token[0][1] - $offset ) ) || ! preg_match( '/^[\w-]+\s*=/', $token[0][0] ) ) {
				return false;
			}
			// An unquoted value must not disguise an unmatched opening quote.
			if ( isset( $token[6] ) && -1 !== $token[6][1] && preg_match( '/[\x22\x27]/', $token[6][0] ) ) {
				return false;
			}
			$offset = $token[0][1] + strlen( $token[0][0] );
		}
		return '' === trim( substr( $raw, $offset ) ) ? $atts : false;
	}

	/**
	 * Derive the entire static profile from the actual gateway's settings snapshot.
	 *
	 * @param string $id Gateway ID.
	 * @param mixed  $description Raw configured description, not rendered HTML.
	 * @return array
	 */
	public static function build( $id, $description ) {
		global $shortcode_tags;
		$unsupported = array(
			'unsupported' => true,
			'reason'      => 'configuration',
		);
		if ( ! is_string( $id ) || ! preg_match( '/^jetpack_custom_gateway(?:_\d+)?$/D', $id ) || ! is_string( $description ) || strlen( $description ) > 32768 || 1 !== preg_match( '//u', $description ) ) {
			return $unsupported;
		}
		if ( ! self::hooks_supported() ) {
			return array(
				'unsupported' => true,
				'reason'      => 'hooks',
			);
		}
		if ( self::has_controls( $description ) ) {
			return $unsupported;
		}
		// Check every tag/comment, not merely the first HTML element.
		preg_match_all( '/<!--[\s\S]*?(?:-->|$)|<[^>]*(?:>|$)/', $description, $tags );
		foreach ( $tags[0] as $tag ) {
			if ( false !== strpos( $tag, '[' ) ) {
				return $unsupported;
			}
		}
		if ( preg_match( '/\[wcj_input_field(?![\s\]\/])/', $description ) || false !== strpos( $description, '[/wcj_input_field' ) ) {
			return $unsupported;
		}
		$description = str_replace( '[wcj_input_field', '[wcj_input_field name_array="' . $id . '" attach_to="' . $id . '"', $description );
		preg_match_all( '/' . get_shortcode_regex( array_keys( $shortcode_tags ) ) . '/s', $description, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
		$fields       = array();
		$segments     = array();
		$identity     = array();
		$names        = array();
		$storage_keys = array();
		$offset       = 0;
		$defaults     = array(
			'name'        => '',
			'label'       => '',
			'value'       => '',
			'placeholder' => '',
			'class'       => '',
			'type'        => 'text',
			'required'    => 'no',
		);
		$allowed      = array_merge( array_keys( $defaults ), array( 'name_array', 'attach_to' ) );
		foreach ( $matches as $match ) {
			if ( 'wcj_input_field' !== $match[2][0] || '' !== $match[1][0] || '' !== $match[6][0] || '' !== $match[5][0] || count( $fields ) >= 20 ) {
				return $unsupported;
			}
			$atts = self::attributes( $match[3][0] );
			if ( false === $atts ) {
				return $unsupported;
			}
			foreach ( $atts as $key => $value ) {
				if ( ! is_string( $key ) || ! in_array( $key, $allowed, true ) || ! is_string( $value ) || false !== strpos( $value, '<' ) ) {
					return $unsupported;
				}
			}
			if ( ! isset( $atts['name_array'], $atts['attach_to'] ) || $id !== $atts['name_array'] || $id !== $atts['attach_to'] ) {
				return $unsupported;
			}
			$atts = array_merge( $defaults, $atts );
			if ( 'text' !== $atts['type'] || ! in_array( $atts['required'], array( 'yes', 'no' ), true ) ) {
				return $unsupported;
			}
			foreach ( $atts as $key => $value ) {
				$atts[ $key ] = wcj_sanitize_input_attribute_values( $value );
			}
			$name = $atts['name'];
			$key  = '' !== $atts['label'] ? $atts['label'] : $name;
			if ( '' === $name || strlen( $name ) > 64 || isset( $names[ $name ] ) || isset( $storage_keys[ $key ] ) ) {
				return $unsupported;
			}
			foreach ( array( 'label', 'placeholder', 'value', 'class' ) as $attribute ) {
				if ( strlen( $atts[ $attribute ] ) > 256 ) {
					return $unsupported;
				}
			}
			$names[ $name ]       = true;
			$storage_keys[ $key ] = true;
			$required             = 'yes' === $atts['required'];
			$fields[]             = array(
				'name'        => $name,
				'label'       => $atts['label'],
				'placeholder' => $atts['placeholder'],
				'default'     => $atts['value'],
				'className'   => $atts['class'],
				'required'    => $required,
			);
			$identity[]           = array( $name, $atts['label'], $required, 'text' );
			$segments[]           = substr( $description, $offset, $match[0][1] - $offset );
			$offset               = $match[0][1] + strlen( $match[0][0] );
		}
		$segments[] = substr( $description, $offset );
		foreach ( $segments as $index => $segment ) {
			if ( false !== strpos( $segment, '[wcj_input_field' ) ) {
				return $unsupported;
			}
			$segments[ $index ] = wp_kses_post( wpautop( wptexturize( $segment ) ) );
			if ( ! is_string( $segments[ $index ] ) || self::has_controls( $segments[ $index ] ) ) {
				return $unsupported;
			}
		}
		return array(
			'fields'   => $fields,
			'segments' => $segments,
			'hash'     => hash( 'sha256', wp_json_encode( array( $id, $identity ) ) ),
		);
	}

	/**
	 * Validate our raw scalar wire entry without Woo's key/value normalization.
	 *
	 * @param string $id Actual selected gateway ID.
	 * @param array  $schema Complete supported schema.
	 * @param mixed  $payment_data Raw request payment_data.
	 * @return array Validated storage values or a fixed error category.
	 */
	public static function validate( $id, $schema, $payment_data ) {
		$invalid = array( 'error' => 'invalid' );
		$found   = false;
		$raw     = null;
		if ( null === $payment_data ) {
			$payment_data = array();
		}
		if ( ! is_array( $payment_data ) ) {
			return $invalid;
		}
		foreach ( $payment_data as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['key'] ) || ! is_string( $entry['key'] ) ) {
				continue;
			}
			if ( self::ENVELOPE_KEY === sanitize_key( $entry['key'] ) ) {
				if ( $found || self::ENVELOPE_KEY !== $entry['key'] || 2 !== count( $entry ) || ! isset( $entry['value'] ) || ! is_string( $entry['value'] ) ) {
					return $invalid;
				}
				$found = true;
				$raw   = $entry['value'];
			}
		}
		if ( ! $found ) {
			return empty( $schema['fields'] ) ? array( 'values' => array() ) : $invalid;
		}
		if ( strlen( $raw ) > 98304 ) {
			return $invalid;
		}
		$envelope = json_decode( $raw, false, self::JSON_DEPTH );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $envelope ) || 4 !== count( $envelope ) || 1 !== $envelope[0] || $id !== $envelope[1] || ! is_string( $envelope[2] ) || ! is_array( $envelope[3] ) ) {
			return $invalid;
		}
		if ( ! hash_equals( $schema['hash'], $envelope[2] ) ) {
			return array( 'error' => 'stale' );
		}
		if ( count( $schema['fields'] ) !== count( $envelope[3] ) ) {
			return $invalid;
		}
		$values = array();
		foreach ( $schema['fields'] as $index => $field ) {
			$pair = $envelope[3][ $index ];
			if ( ! is_array( $pair ) || 2 !== count( $pair ) || ! is_string( $pair[0] ) || ! is_string( $pair[1] ) || $field['name'] !== $pair[0] || strlen( $pair[1] ) > 2048 || 1 !== preg_match( '//u', $pair[1] ) || preg_match( '/[\x00-\x1f\x7f]/', $pair[1] ) ) {
				return $invalid;
			}
			$value = sanitize_text_field( $pair[1] );
			$label = '' !== $field['label'] ? $field['label'] : $field['name'];
			if ( $field['required'] && '' === $value ) {
				return array(
					'error' => 'required',
					'label' => $label,
				);
			}
			$values[ $label ] = $value;
		}
		return array( 'values' => $values );
	}
}
