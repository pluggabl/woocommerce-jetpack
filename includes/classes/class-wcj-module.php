<?php
/**
 * WooCommerce Jetpack Module
 *
 * The WooCommerce Jetpack Module class.
 *
 * @version 2.5.3
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
	 * @version 2.4.3
	 */
	public function __construct( $type = 'module' ) {
		add_filter( 'wcj_settings_sections',     array( $this, 'settings_section' ) );
		add_filter( 'wcj_settings_' . $this->id, array( $this, 'get_settings' ), 100 );
		$this->type = $type;
		if ( 'module' === $this->type ) {
			$this->parent_id = '';
		}
		add_action( 'init', array( $this, 'reset_settings' ), PHP_INT_MAX );
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'wcj_get_option_filter', false, true ) ) {
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
	 * @version 2.5.0
	 * @since   2.4.0
	 */
	function reset_settings() {
		if ( isset( $_GET['wcj_reset_settings'] ) && $this->id === $_GET['wcj_reset_settings'] && wcj_is_user_role( 'administrator' ) && ! isset( $_POST['save'] ) ) {
			global $wcj_notice;
			if ( ! isset( $_GET['wcj_confirm_reset_settings'] ) ) {
				$wcj_notice .= __( 'Are you sure you want to reset current module\'s settings to default values?', 'woocommerce-jetpack' );
				$yes_button_style = "background: green; border-color: green; box-shadow: 0 1px 0 green; text-shadow: 0 -1px 1px #0a0,1px 0 1px #0a0,0 1px 1px #0a0,-1px 0 1px #0a0;";
				$no_button_style  = "background: red; border-color: red; box-shadow: 0 1px 0 red; text-shadow: 0 -1px 1px #a00,1px 0 1px #a00,0 1px 1px #a00,-1px 0 1px #a00;";
				$wcj_notice .= ' ' . '<a class="button-primary" style="' . $yes_button_style . '" href="' . add_query_arg( 'wcj_confirm_reset_settings', 'yes' ) . '">' . __( 'Yes', 'woocommerce-jetpack' ) . '</a>';
				$wcj_notice .= ' ' . '<a class="button-primary" style="' . $no_button_style  . '" href="' . remove_query_arg( 'wcj_reset_settings' )             . '">' . __( 'No', 'woocommerce-jetpack' )  . '</a>';
			} else {
				foreach ( $this->get_settings() as $settings ) {
					$default_value = isset( $settings['default'] ) ? $settings['default'] : '';
					update_option( $settings['id'], $default_value );
				}
				$wcj_notice .= __( 'Settings have been reset to defaults.', 'woocommerce-jetpack' );
			}
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
	 * @version 2.4.0
	 * @since   2.2.6
	 */
	function get_settings() {
		return $this->add_standard_settings();
	}

	/**
	 * save_meta_box.
	 *
	 * @since 2.5.0
	 */
	function save_meta_box( $post_id, $post ) {
		// Check that we are saving with current metabox displayed.
		if ( ! isset( $_POST[ 'woojetpack_' . $this->id . '_save_post' ] ) ) return;
		// Save options
		foreach ( $this->get_meta_box_options() as $option ) {
			if ( 'title' === $option['type'] ) {
				continue;
			}
			$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
			if ( $is_enabled ) {
				$option_value  = ( isset( $_POST[ $option['name'] ] ) ) ? $_POST[ $option['name'] ] : $option['default'];
				$the_post_id   = ( isset( $option['product_id'] )     ) ? $option['product_id']     : $post_id;
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
	 * @since 2.5.0
	 */
	function create_meta_box() {
		$current_post_id = get_the_ID();
		$html = '';
		$html .= '<table>';
		foreach ( $this->get_meta_box_options() as $option ) {
			$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
			if ( $is_enabled ) {
				if ( 'title' === $option['type'] ) {
					$html .= '<tr>';
					$html .= '<th cospan="2" style="text-align:left;">' . $option['title'] . '</th>';
					$html .= '</tr>';
				} else {
					$custom_attributes = '';
					$the_post_id   = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $current_post_id;
					$the_meta_name = ( isset( $option['meta_name'] ) )  ? $option['meta_name']  : '_' . $option['name'];
					if ( get_post_meta( $the_post_id, $the_meta_name ) ) {
						$option_value = get_post_meta( $the_post_id, $the_meta_name, true );
					} else {
						$option_value = ( isset( $option['default'] ) ) ? $option['default'] : '';
					}
					$input_ending = '';
					if ( 'select' === $option['type'] ) {
						if ( isset( $option['multiple'] ) ) {
							$custom_attributes = ' multiple';
							$option_name       = $option['name'] . '[]';
						} else {
							$option_name       = $option['name'];
						}
						$options = '';
						foreach ( $option['options'] as $select_option_key => $select_option_value ) {
							$selected = '';
							if ( is_array( $option_value ) ) {
								foreach ( $option_value as $single_option_value ) {
									$selected .= selected( $single_option_value, $select_option_key, false );
								}
							} else {
								$selected = selected( $option_value, $select_option_key, false );
							}
							$options .= '<option value="' . $select_option_key . '" ' . $selected . '>' . $select_option_value . '</option>';
						}
					} else {
						$input_ending = ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
					}
					switch ( $option['type'] ) {
						case 'price':
							$field_html = '<input class="short wc_input_price" type="number" step="0.0001"' . $input_ending;
							break;
						case 'date':
							$field_html = '<input class="input-text" display="date" type="text"' . $input_ending;
							break;
						case 'textarea':
							$field_html = '<textarea style="min-width:300px;"' . ' id="' . $option['name'] . '" name="' . $option['name'] . '">' . $option_value . '</textarea>';
							break;
						case 'select':
							$field_html = '<select' . $custom_attributes . ' id="' . $option['name'] . '" name="' . $option_name . '">' . $options . '</select>';
							break;
						default:
							$field_html = '<input class="short" type="' . $option['type'] . '"' . $input_ending;
							break;
					}
					$html .= '<tr>';
					$html .= '<th style="text-align:left;">' . $option['title'] . '</th>';
					if ( isset( $option['desc'] ) && '' != $option['desc'] ) {
						$html .= '<td style="font-style:italic;">' . $option['desc'] . '</td>';
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
	 */
	public function is_enabled() {
		$the_id = ( 'module' === $this->type ) ? $this->id : $this->parent_id;
		return ( 'yes' === get_option( 'wcj_' . $the_id . '_enabled' ) ) ? true : false;
	}

	/**
	 * add_enabled_option.
	 * only for `module`
	 *
	public function add_enabled_option( $settings ) {
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
	public function add_module_tools_info_to_tools_dashboard() {
		$is_enabled_html = ( $this->is_enabled() ) ?
			'<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' )  . '</span>' :
			'<span style="color:gray;font-style:italic;">'  . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		foreach ( $this->tools_array as $tool_id => $tool_data ) {
			$tool_title = $tool_data['title'];
			$tool_desc  = $tool_data['desc'];
			$additional_style_html = '';
			$additional_info_html = '';
			if ( isset( $tool_data['depreciated'] ) && true === $tool_data['depreciated'] ) {
				$additional_style_html = 'color:gray;font-style:italic;';
				$additional_info_html  = ' - ' . __( 'Depreciated', 'woocommerce-jetpack' );
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
	 * @version 2.4.7
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
				'link'     => '<a class="button-primary" style="' . $reset_button_style . '" href="' . add_query_arg( 'wcj_reset_settings', $this->id, remove_query_arg( 'wcj_confirm_reset_settings' ) ) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
			),
			/* array(
				'title'    => __( 'Reset to Default Settings', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_reset_settings',
				'type'     => 'custom_link',
				'link'     => '<input name="wcj_reset_settings" class="button-primary" type="submit" value="Reset settings">',
			), */
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
	 * @version 2.5.3
	 */
	function add_enable_module_setting( $settings, $module_desc = '' ) {
		if ( 'module' != $this->type ) {
			return $settings;
		}
		$the_link = '';
		if ( isset( $this->link ) &&  '' != $this->link ) {
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
