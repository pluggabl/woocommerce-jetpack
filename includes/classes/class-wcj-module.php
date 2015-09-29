<?php
/**
 * WooCommerce Jetpack Module
 *
 * The WooCommerce Jetpack Module class.
 *
 * @version 2.2.10
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

	/**
	 * Constructor.
	 */
	public function __construct( $type = 'module' ) {
		// Settings hooks
		add_filter( 'wcj_settings_sections',     array( $this, 'settings_section' ) );
		add_filter( 'wcj_settings_' . $this->id, array( $this, 'get_settings' ), 100 );
		$this->type = $type;
		if ( 'module' === $this->type ) {
			$this->parent_id = '';
			//add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
		}
	}

	/**
	 * get_settings.
	 *
	 * @since 2.2.6
	 */
	function get_settings() {
		$settings = array();
		return $this->add_enable_module_setting( $settings );
	}

	/**
	 * save_meta_box.
	 *
	 * @since 2.2.6
	 */
	function save_meta_box( $post_id, $post ) {
		// Check that we are saving with current metabox displayed.
		if ( ! isset( $_POST[ 'woojetpack_' . $this->id . '_save_post' ] ) ) return;
		// Save options
		foreach ( $this->get_meta_box_options() as $option ) {
			$option_value = isset( $_POST[ $option['name'] ] ) ? $_POST[ $option['name'] ] : $option['default'];
			update_post_meta( $post_id, '_' . $option['name'], $option_value );
		}
	}

	/**
	 * add_meta_box.
	 *
	 * @version 2.2.10
	 * @since   2.2.6
	 */
	function add_meta_box() {
		$screen   = ( isset( $this->meta_box_screen ) )   ? $this->meta_box_screen   : 'product';
		$context  = ( isset( $this->meta_box_context ) )  ? $this->meta_box_context  : 'normal';
		$priority = ( isset( $this->meta_box_priority ) ) ? $this->meta_box_priority : 'high';
		add_meta_box(
			'wc-jetpack-' . $this->id,
			__( 'WooCommerce Jetpack', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
			array( $this, 'create_meta_box' ),
			$screen,
			$context,
			$priority
		);
	}

	/**
	 * create_meta_box.
	 *
	 * @since 2.2.6
	 */
	function create_meta_box() {
		$current_post_id = get_the_ID();
		$html = '';
		//$html .= '<div style="width:40%;">';
		$html .= '<table>';
		foreach ( $this->get_meta_box_options() as $option ) {
			$option_value = get_post_meta( $current_post_id, '_' . $option['name'], true );
			$input_ending = ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
			switch ( $option['type'] ) {
				case 'number':
					$field_html = '<input class="short" type="number"' . $input_ending;
					break;
				case 'price':
					$field_html = '<input class="short wc_input_price" type="number" step="0.0001"' . $input_ending;
					break;
				case 'date':
					$field_html = '<input class="input-text" display="date" type="text"' . $input_ending;
					break;
				case 'textarea':
					$field_html = '<textarea style="min-width:300px;"' . ' id="' . $option['name'] . '" name="' . $option['name'] . '">' . $option_value . '</textarea>';
					break;
			}
			$html .= '<tr>';
			$html .= '<th style="text-align:left;">' . $option['title'] . '</th>';
			$html .= '<td>' . $field_html . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		$html .= '<input type="hidden" name="woojetpack_' . $this->id . '_save_post" value="woojetpack_' . $this->id . '_save_post">';
		//$html .= '</div>';
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
	 * @version 2.2.10
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
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	function get_back_to_settings_link_html() {
		$cat_id = $this->get_cat_by_section( $this->id );
		$the_link = admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $cat_id . '&section=' . $this->id );
		return '<a href="' .  $the_link . '">' . __( 'Back to Module Settings', 'woocommerce-jetpack' ) . '</a>';
	}

	/**
	 * add_tools_list.
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	function add_tools_list( $settings ) {
		return array_merge( $settings, array(
			array(
				'title'    => $this->short_desc . ' ' . __( 'Tools', 'woocommerce-jetpack' ),
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
	 * add_tools.
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	function add_tools( $tools_array ) {
		$this->tools_array = $tools_array;
		add_action( 'wcj_module_tools_' . $this->id, array( $this, 'add_tool_link' ), PHP_INT_MAX );
	}

	/**
	 * add_tool_link.
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	function add_tool_link() {
		//echo '<ul>';
		foreach ( $this->tools_array as $tool_id => $tool_desc ) {
			//echo '<li>';
			if ( $this->is_enabled() ) {
				echo '<a href="' . admin_url( 'admin.php?page=wcj-tools&tab=' . $tool_id ) . '"><code>' . $tool_desc . '</code></a>';
			}
			else {
				echo '<code>' . $tool_desc . '</code>';
			}
			//echo '</li>';
			echo '<br>';
		}
		//echo '</ul>';
	}

	/**
	 * settings_section.
	 * only for `module`
	 *
	 * @version 2.2.7
	 */
	function add_enable_module_setting( $settings, $module_desc = '' ) {
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
				'desc_tip' => $this->desc,
				'id'       => 'wcj_' . $this->id . '_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
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
