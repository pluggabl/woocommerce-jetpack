<?php
/**
 * Booster for WooCommerce Module
 *
 * @version 3.3.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Module' ) ) :

/* abstract */ class WCJ_Module {

	public $id;
	public $short_desc;
	public $desc;
	public $parent_id; // for `submodule` only
	public $type;      // `module` or `submodule`
	public $link;

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct( $type = 'module' ) {
		add_filter( 'wcj_settings_sections',     array( $this, 'settings_section' ) );
		add_filter( 'wcj_settings_' . $this->id, array( $this, 'get_settings' ), 100 );
		$this->type = $type;
		if ( 'module' === $this->type ) {
			$this->parent_id = '';
		}
		add_action( 'init', array( $this, 'add_settings' ) );
		add_action( 'init', array( $this, 'reset_settings' ), PHP_INT_MAX );
	}

	/**
	 * save_meta_box_validate_value.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function save_meta_box_validate_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'booster_option', false, true ) ) {
			return $option_value;
		}
		if ( 'no' === $option_value ) {
			return $option_value;
		}
		if ( $this->id === $module_id && $this->meta_box_validate_value === $option_name ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_' . $this->meta_box_validate_value,
				'meta_value'     => 'yes',
				'post__not_in'   => array( get_the_ID() ),
			);
			$loop = new WP_Query( $args );
			$c = $loop->found_posts + 1;
			if ( $c >= 2 ) {
				add_filter( 'redirect_post_location', array( $this, 'validate_value_add_notice_query_var' ), 99 );
				return 'no';
			}
		}
		return $option_value;
	}

	/**
	 * validate_value_add_notice_query_var.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function validate_value_add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'validate_value_add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_' . $this->id . '_meta_box_admin_notice' => true ), $location );
	}

	/**
	 * validate_value_admin_notices.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function validate_value_admin_notices() {
		if ( ! isset( $_GET[ 'wcj_' . $this->id . '_meta_box_admin_notice' ] ) ) {
			return;
		}
		echo '<div class="error">' . '<p>' . '<div class="message">' .
			sprintf(
				__( 'Booster: Free plugin\'s version is limited to only one "%1$s" product with settings on per product basis enabled at a time. You will need to get <a href="%2$s" target="_blank">Booster Plus</a> to add unlimited number of "%1$s" products.', 'woocommerce-jetpack' ),
				$this->short_desc, 'https://booster.io/plus/'
			) .
		'</div>' . '</p>' . '</div>';
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function get_meta_box_options() {
		$filename = wcj_plugin_path() . '/includes/settings/meta-box/wcj-settings-meta-box-' . str_replace( '_', '-', $this->id ) . '.php';
		return ( file_exists ( $filename ) ? require( $filename ) : array() );
	}

	/**
	 * maybe_fix_settings.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 */
	function maybe_fix_settings( $settings ) {
		if ( ! WCJ_IS_WC_VERSION_BELOW_3_2_0 ) {
			foreach ( $settings as &$setting ) {
				if ( isset( $setting['type'] ) && 'select' === $setting['type'] ) {
					if ( ! isset( $setting['class'] ) || '' === $setting['class'] ) {
						$setting['class'] = 'wc-enhanced-select';
					} else {
						$setting['class'] .= ' ' . 'wc-enhanced-select';
					}
				}
				if ( isset( $setting['type'] ) && 'text' === $setting['type'] && isset( $setting['class'] ) && 'widefat' === $setting['class'] ) {
					if ( ! isset( $setting['css'] ) || '' === $setting['css'] ) {
						$setting['css'] = 'width:100%;';
					} else {
						$setting['css'] .= ' ' . 'width:100%;';
					}
				}
			}
		}
		return $settings;
	}

	/**
	 * add_settings_from_file.
	 *
	 * @version 3.2.1
	 * @since   2.8.0
	 */
	function add_settings_from_file( $settings ) {
		$filename = wcj_plugin_path() . '/includes/settings/wcj-settings-' . str_replace( '_', '-', $this->id ) . '.php';
		$settings = ( file_exists ( $filename ) ? require( $filename ) : $settings );
		return $this->maybe_fix_settings( $settings );
	}

	/*
	 * add_settings.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function add_settings() {
		add_filter( 'wcj_' . $this->id . '_settings', array( $this, 'add_settings_from_file' ) );
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'booster_option', false, true ) ) {
			return $option_value;
		}
		if ( 'no' === $option_value ) {
			return $option_value;
		}
		if ( $this->id === $module_id && $this->co === $option_name ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 3,
				'meta_key'       => '_' . $this->co,
				'meta_value'     => 'yes',
				'post__not_in'   => array( get_the_ID() ),
			);
			$loop = new WP_Query( $args );
			$c = $loop->found_posts + 1;
			if ( $c >= 4 ) {
				add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				return 'no';
			}
		}
		return $option_value;
	}

	/**
	 * add_notice_query_var.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_' . $this->id . '_admin_notice' => true ), $location );
	}

	/**
	 * admin_notices.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function admin_notices() {
		if ( ! isset( $_GET[ 'wcj_' . $this->id . '_admin_notice' ] ) ) {
			return;
		}
		echo '<div class="error"><p><div class="message">' . $this->get_the_notice() . '</div></p></div>';
	}

	/**
	 * reset_settings.
	 *
	 * @version 2.5.9
	 * @since   2.4.0
	 */
	function reset_settings() {
		if ( isset( $_GET['wcj_reset_settings'] ) && $this->id === $_GET['wcj_reset_settings'] && wcj_is_user_role( 'administrator' ) && ! isset( $_POST['save'] ) ) {
			foreach ( $this->get_settings() as $settings ) {
				$default_value = isset( $settings['default'] ) ? $settings['default'] : '';
				update_option( $settings['id'], $default_value );
			}
			wp_safe_redirect( remove_query_arg( 'wcj_reset_settings' ) );
			exit();
		}
	}

	/**
	 * add_standard_settings.
	 *
	 * @version 2.4.0
	 * @since   2.3.10
	 */
	function add_standard_settings( $settings = array(), $module_desc = '' ) {
		if ( isset( $this->tools_array ) && ! empty( $this->tools_array ) ) {
			$settings = $this->add_tools_list( $settings );
		}
		$settings = $this->add_reset_settings_button( $settings );
		return $this->add_enable_module_setting( $settings, $module_desc );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.7.0
	 * @since   2.2.6
	 */
	function get_settings() {
		return $this->add_standard_settings( apply_filters( 'wcj_' . $this->id . '_settings', array() ) );
	}

	/**
	 * save_meta_box.
	 *
	 * @since 2.5.0
	 */
	function save_meta_box( $post_id, $post ) {
		// Check that we are saving with current metabox displayed.
		if ( ! isset( $_POST[ 'woojetpack_' . $this->id . '_save_post' ] ) ) {
			return;
		}
		// Save options
		foreach ( $this->get_meta_box_options() as $option ) {
			if ( 'title' === $option['type'] ) {
				continue;
			}
			$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
			if ( $is_enabled ) {
				$option_value  = ( isset( $_POST[ $option['name'] ] ) ) ? $_POST[ $option['name'] ] : $option['default'];
				$the_post_id   = ( isset( $option['product_id'] )     ) ? $option['product_id']     : $post_id; // todo: maybe also order_id?
				$the_meta_name = ( isset( $option['meta_name'] ) )      ? $option['meta_name']      : '_' . $option['name'];
				update_post_meta( $the_post_id, $the_meta_name, apply_filters( 'wcj_save_meta_box_value', $option_value, $option['name'], $this->id ) );
			}
		}
	}

	/**
	 * add_meta_box.
	 *
	 * @version 2.3.10
	 * @since   2.2.6
	 */
	function add_meta_box() {
		$screen   = ( isset( $this->meta_box_screen ) )   ? $this->meta_box_screen   : 'product';
		$context  = ( isset( $this->meta_box_context ) )  ? $this->meta_box_context  : 'normal';
		$priority = ( isset( $this->meta_box_priority ) ) ? $this->meta_box_priority : 'high';
		add_meta_box(
			'wc-jetpack-' . $this->id,
			__( 'Booster', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
			array( $this, 'create_meta_box' ),
			$screen,
			$context,
			$priority
		);
	}

	/**
	 * create_meta_box.
	 *
	 * @version 3.3.0
	 * @todo    `placeholder` for textarea
	 * @todo    `class` for all types (now only for select)
	 * @todo    `show_value` for all types (now only for multiple select)
	 */
	function create_meta_box() {
		$current_post_id = get_the_ID();
		$html = '';
		$html .= '<table class="widefat striped">';
		foreach ( $this->get_meta_box_options() as $option ) {
			$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
			if ( $is_enabled ) {
				if ( 'title' === $option['type'] ) {
					$html .= '<tr>';
					$html .= '<th colspan="3" style="' . ( isset( $option['css'] ) ? $option['css'] : 'text-align:left;font-weight:bold;' ) . '">' . $option['title'] . '</th>';
					$html .= '</tr>';
				} else {
					$custom_attributes = '';
					$the_post_id   = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $current_post_id; // todo: maybe also order_id?
					$the_meta_name = ( isset( $option['meta_name'] ) )  ? $option['meta_name']  : '_' . $option['name'];
					if ( get_post_meta( $the_post_id, $the_meta_name ) ) {
						$option_value = get_post_meta( $the_post_id, $the_meta_name, true );
					} else {
						$option_value = ( isset( $option['default'] ) ) ? $option['default'] : '';
					}
					$css          = ( isset( $option['css'] )   ? $option['css']   : '' );
					$class        = ( isset( $option['class'] ) ? $option['class'] : '' );
					$show_value   = ( isset( $option['show_value'] ) && $option['show_value'] );
					$input_ending = '';
					if ( 'select' === $option['type'] ) {
						if ( isset( $option['multiple'] ) ) {
							$custom_attributes = ' multiple';
							$option_name       = $option['name'] . '[]';
						} else {
							$option_name       = $option['name'];
						}
						if ( isset( $option['custom_attributes'] ) ) {
							$custom_attributes .= ' ' . $option['custom_attributes'];
						}
						$options = '';
						foreach ( $option['options'] as $select_option_key => $select_option_value ) {
							$selected = '';
							if ( is_array( $option_value ) ) {
								foreach ( $option_value as $single_option_value ) {
									if ( '' != ( $selected = selected( $single_option_value, $select_option_key, false ) ) ) {
										break;
									}
								}
							} else {
								$selected = selected( $option_value, $select_option_key, false );
							}
							$options .= '<option value="' . $select_option_key . '" ' . $selected . '>' . $select_option_value . '</option>';
						}
					} elseif ( 'textarea' === $option['type'] ) {
						if ( '' === $css ) {
							$css = 'min-width:300px;';
						}
					} else {
						$input_ending = ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
						if ( isset( $option['custom_attributes'] ) ) {
							$input_ending = ' ' . $option['custom_attributes'] . $input_ending;
						}
						if ( isset( $option['placeholder'] ) ) {
							$input_ending = ' placeholder="' . $option['placeholder'] . '"' . $input_ending;
						}
					}
					switch ( $option['type'] ) {
						case 'price':
							$field_html = '<input style="' . $css . '" class="short wc_input_price" type="number" step="' .
								apply_filters( 'wcj_get_meta_box_options_type_price_step', '0.0001' ) . '"' . $input_ending;
							break;
						case 'date':
							$field_html = '<input style="' . $css . '" class="input-text" display="date" type="text"' . $input_ending;
							break;
						case 'textarea':
							$field_html = '<textarea style="' . $css . '" id="' . $option['name'] . '" name="' . $option['name'] . '">' . $option_value . '</textarea>';
							break;
						case 'select':
							$field_html = '<select' . $custom_attributes . ' class="' . $class . '" style="' . $css . '" id="' . $option['name'] . '" name="' .
								$option_name . '">' . $options . '</select>' .
								( $show_value && ! empty( $option_value ) ? sprintf( '<em>' . __( 'Selected: %s.', 'woocommerce-jetpack' ), implode( ', ', $option_value ) ) . '</em>' : '' );
							break;
						default:
							$field_html = '<input style="' . $css . '" class="short" type="' . $option['type'] . '"' . $input_ending;
							break;
					}
					$html .= '<tr>';
					$maybe_tooltip = ( isset( $option['tooltip'] ) && '' != $option['tooltip'] ) ? '<span style="float:right;">' . wc_help_tip( $option['tooltip'], true ) . '</span>' : '';
					$html .= '<th style="text-align:left;width:25%;font-weight:bold;">' . $option['title'] . $maybe_tooltip . '</th>';
					if ( isset( $option['desc'] ) && '' != $option['desc'] ) {
						$html .= '<td style="font-style:italic;width:25%;">' . $option['desc'] . '</td>';
					}
					$html .= '<td>' . $field_html . '</td>';
					$html .= '</tr>';
				}
			}
		}
		$html .= '</table>';
		$html .= '<input type="hidden" name="woojetpack_' . $this->id . '_save_post" value="woojetpack_' . $this->id . '_save_post">';
		echo $html;
	}

	/**
	 * is_enabled.
	 *
	 * @version 3.3.0
	 */
	function is_enabled() {
		return wcj_is_module_enabled( ( 'module' === $this->type ? $this->id : $this->parent_id ) );
	}

	/**
	 * add_enabled_option.
	 * only for `module`
	 *
	function add_enabled_option( $settings ) {
		$all_settings = $this->get_settings();
		$settings[] = $all_settings[1];
		return $settings;
	}

	/**
	 * settings_section.
	 *
	 * @version 2.3.0
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = isset( $this->section_title ) ? $this->section_title : $this->short_desc;
		return $sections;
	}

	/**
	 * get_cat_by_section
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	function get_cat_by_section( $section ) {
		$cats = include( wcj_plugin_path() . '/includes/admin/' . 'wcj-modules-cats.php' );
		foreach ( $cats as $id => $label_info ) {
			if ( ( ! empty( $label_info['all_cat_ids'] ) ) &&
				 ( is_array( $label_info['all_cat_ids'] ) ) &&
				 ( in_array( $section, $label_info['all_cat_ids'] ) )
				) {
					return $id;
				}
		}
		return '';
	}

	/**
	 * get_back_to_settings_link_html.
	 *
	 * @version 2.3.10
	 * @since   2.2.3
	 */
	function get_back_to_settings_link_html() {
		$cat_id = $this->get_cat_by_section( $this->id );
		$the_link = admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $cat_id . '&section=' . $this->id );
		return '<a href="' .  $the_link . '"><< ' . __( 'Back to Module Settings', 'woocommerce-jetpack' ) . '</a>';
	}

	/**
	 * add_tools_list.
	 *
	 * @version 2.3.8
	 * @since   2.2.3
	 */
	function add_tools_list( $settings ) {
		return array_merge( $settings, array(
			array(
				'title'    => /* $this->short_desc . ' ' .  */__( 'Tools', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_' . $this->id . '_tools_options'
			),
			array(
				'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_module_tools',
				'type'     => 'module_tools',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_' . $this->id . '_tools_options'
			),
		) );
	}

	/**
	 * get_tool_header_html.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function get_tool_header_html( $tool_id ) {
		$html = '';
		if ( isset( $this->tools_array[ $tool_id ] ) ) {
			$html .= '<p>' .  $this->get_back_to_settings_link_html() . '</p>';
			$html .= '<h3>' . __( 'Booster', 'woocommerce-jetpack' ) . ' - ' . $this->tools_array[ $tool_id ]['title'] . '</h3>';
			$html .= '<p style="font-style:italic;">' . $this->tools_array[ $tool_id ]['desc']  . '</p>';
		}
		return $html;
	}

	/**
	 * add_tools.
	 *
	 * @version 2.3.10
	 * @since   2.2.3
	 */
	function add_tools( $tools_array, $args = array() ) {
		$this->tools_array = $tools_array;
		add_action( 'wcj_module_tools_' . $this->id, array( $this, 'add_tool_link' ), PHP_INT_MAX );
		$hook_priority = isset( $args['tools_dashboard_hook_priority'] ) ? $args['tools_dashboard_hook_priority'] : 10;
		if ( $this->is_enabled() ) {
			add_filter( 'wcj_tools_tabs', array( $this, 'add_module_tools_tabs' ), $hook_priority );
			foreach ( $this->tools_array as $tool_id => $tool_data ) {
				add_action( 'wcj_tools_' . $tool_id, array( $this, 'create_' . $tool_id . '_tool' ) );
			}
		}
		add_action( 'wcj_tools_dashboard', array( $this, 'add_module_tools_info_to_tools_dashboard' ), $hook_priority );
	}

	/**
	 * add_module_tools_tabs.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function add_module_tools_tabs( $tabs ) {
		foreach ( $this->tools_array as $tool_id => $tool_data ) {
			$tool_title = ( isset( $tool_data['tab_title'] ) ) ?
				$tool_data['tab_title'] :
				$tool_data['title'];
			$tabs[] = array(
				'id'    => $tool_id,
				'title' => $tool_title,
			);
		}
		return $tabs;
	}

	/**
	 * add_module_tools_info_to_tools_dashboard.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function add_module_tools_info_to_tools_dashboard() {
		$is_enabled_html = ( $this->is_enabled() ) ?
			'<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' )  . '</span>' :
			'<span style="color:gray;font-style:italic;">'  . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		foreach ( $this->tools_array as $tool_id => $tool_data ) {
			$tool_title = $tool_data['title'];
			$tool_desc  = $tool_data['desc'];
			$additional_style_html = '';
			$additional_info_html = '';
			if ( isset( $tool_data['deprecated'] ) && true === $tool_data['deprecated'] ) {
				$additional_style_html = 'color:gray;font-style:italic;';
				$additional_info_html  = ' - ' . __( 'Deprecated', 'woocommerce-jetpack' );
			}
			echo '<tr>';
			echo '<td style="' . $additional_style_html . '">' . $tool_title . $additional_info_html . '</td>';
			echo '<td style="' . $additional_style_html . '">' . $this->short_desc . '</td>';
			echo '<td style="' . $additional_style_html . '">' . $tool_desc . '</td>';
			echo '<td style="' . $additional_style_html . '">' . $is_enabled_html . '</td>';
			echo '</tr>';
		}
	}

	/**
	 * add_tool_link.
	 *
	 * @version 2.3.10
	 * @since   2.2.3
	 */
	function add_tool_link() {
		foreach ( $this->tools_array as $tool_id => $tool_data ) {
			$tool_title = $tool_data['title'];
			echo '<p>';
			echo ( $this->is_enabled() ) ?
				'<a href="' . admin_url( 'admin.php?page=wcj-tools&tab=' . $tool_id ) . '"><code>' . $tool_title . '</code></a>' :
				'<code>' . $tool_title . '</code>';
			echo '</p>';
		}
	}

	/**
	 * add_reset_settings_button.
	 *
	 * @version 2.5.9
	 * @since   2.4.0
	 */
	function add_reset_settings_button( $settings ) {
		$reset_button_style = "background: red; border-color: red; box-shadow: 0 1px 0 red; text-shadow: 0 -1px 1px #a00,1px 0 1px #a00,0 1px 1px #a00,-1px 0 1px #a00;";
		$reset_settings_setting = array(
			array(
				'title' => __( 'Reset Settings', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'id'    => 'wcj_' . $this->id . '_reset_settings_options',
			),
			array(
				'title'    => ( 'module' === $this->type ) ?
					__( 'Reset Module to Default Settings', 'woocommerce-jetpack' ) :
					__( 'Reset Submodule to Default Settings', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_reset_settings',
				'type'     => 'custom_link',
				'link'     => '<a onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')" class="button-primary" style="' .
					$reset_button_style . '" href="' . add_query_arg( 'wcj_reset_settings', $this->id ) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_' . $this->id . '_reset_settings_options',
			),
		);
		return array_merge( $settings, $reset_settings_setting );
	}

	/**
	 * settings_section.
	 * only for `module`
	 *
	 * @version 2.8.0
	 */
	function add_enable_module_setting( $settings, $module_desc = '' ) {
		if ( 'module' != $this->type ) {
			return $settings;
		}
		if ( '' === $module_desc && isset( $this->extra_desc ) ) {
			$module_desc = $this->extra_desc;
		}
		if ( ! isset( $this->link ) && isset( $this->link_slug ) && '' != $this->link_slug ) {
			$this->link = 'https://booster.io/features/' . $this->link_slug . '/';
		}
		$the_link = '';
		if ( isset( $this->link ) && '' != $this->link ) {
			$the_link = '<p><a class="button-primary"' .
				' style="background: green; border-color: green; box-shadow: 0 1px 0 green; text-shadow: 0 -1px 1px #0a0,1px 0 1px #0a0,0 1px 1px #0a0,-1px 0 1px #0a0;"' .
				' href="' . $this->link . '?utm_source=module_documentation&utm_medium=module_button&utm_campaign=booster_documentation" target="_blank">' . __( 'Documentation', 'woocommerce-jetpack' ) . '</a></p>';
		}
		$enable_module_setting = array(
			array(
				'title' => $this->short_desc . ' ' . __( 'Module Options', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'desc'  => $module_desc,
				'id'    => 'wcj_' . $this->id . '_module_options',
			),
			array(
				'title'    => $this->short_desc,
				'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip' => $this->desc . $the_link,
				'id'       => 'wcj_' . $this->id . '_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'wcj_desc' => $this->desc,
				'wcj_link' => ( isset( $this->link ) ? $this->link : '' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_' . $this->id . '_module_options',
			),
		);
		return array_merge( $enable_module_setting, $settings );
	}
}

endif;
