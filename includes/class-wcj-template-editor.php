<?php
/**
 * Booster for WooCommerce - Module - Template Editor
 *
 * @version 4.0.0
 * @since   3.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Template_Editor' ) ) :

class WCJ_Template_Editor extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 */
	function __construct() {

		$this->id         = 'template_editor';
		$this->short_desc = __( 'Template Editor', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce template editor.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-template-editor';
		parent::__construct();

		add_action( 'woojetpack_after_settings_save',  array( $this, 'create_templates' ), PHP_INT_MAX, 2 );

		if ( $this->is_enabled() ) {
			$this->templates_to_edit = get_option( 'wcj_template_editor_templates_to_edit', array() );
			add_filter( 'wc_get_template', array( $this, 'replace_template' ), PHP_INT_MAX, 5 );
		}

	}

	/**
	 * create_templates.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 * @todo    [dev] also delete on "Reset settings"
	 */
	function create_templates( $sections, $current_section ) {
		if ( $this->id === $current_section ) {
			$this->delete_dir( wcj_get_wcj_uploads_dir( 'templates' ) );
			$templates_content = get_option( 'wcj_template_editor_templates_content', array() );
			foreach ( get_option( 'wcj_template_editor_templates_to_edit', array() ) as $template ) {
				if ( isset( $templates_content[ $template ] ) ) {
					$_template        = explode( '/', $template );
					$_template_file   = $_template[ count( $_template ) - 1 ];
					$_template_dirs   = str_replace( $_template_file, '', $template );
					$_template_path   = wcj_get_wcj_uploads_dir( 'templates' . DIRECTORY_SEPARATOR . $_template_dirs ) . DIRECTORY_SEPARATOR . $_template_file;
					file_put_contents( $_template_path, $templates_content[ $template ] );
				}
			}
		}
	}

	/**
	 * replace_template.
	 *
	 * @version 4.0.0
	 * @since   3.9.0
	 */
	function replace_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( in_array( $template_name, $this->templates_to_edit ) ) {
			$modified_template = wcj_get_wcj_uploads_dir( 'templates', false ) . DIRECTORY_SEPARATOR . $template_name;
			return ( file_exists( $modified_template ) ? $modified_template : $located );
		}
		return $located;
	}

	/**
	 * scan_templates.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 * @todo    [dev] (maybe) optimize
	 */
	function scan_templates( $dir, &$results = array() ) {
		$files = scandir( $dir );
		foreach( $files as $key => $value ) {
			$path = realpath( $dir . DIRECTORY_SEPARATOR . $value );
			if( ! is_dir( $path ) ) {
				$template = str_replace( $this->templates_path, '', str_replace( '\\', '/', $path ) );
				$results[ $template ] = $template;
			} elseif ( '.' != $value && '..' != $value ) {
				$this->scan_templates( $path, $results );
			}
		}
		return $results;
	}

	/**
	 * delete_dir.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 */
	function delete_dir( $dir_path ) {
		if ( ! is_dir( $dir_path ) ) {
			return false;
		}
		if ( '/' != substr( $dir_path, strlen( $dir_path ) - 1, 1 ) ) {
			$dir_path .= '/';
		}
		$files = glob( $dir_path . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				$this->delete_dir( $file );
			} else {
				unlink( $file );
			}
		}
		rmdir( $dir_path );
	}

}

endif;

return new WCJ_Template_Editor();
