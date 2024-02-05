<?php
/**
 * Booster for WooCommerce - Module - Template Editor
 *
 * @version 7.1.6
 * @since   3.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Template_Editor' ) ) :
	/**
	 * WCJ_Template_Editor.
	 *
	 * @version 7.1.6
	 * @since   3.9.0
	 */
	class WCJ_Template_Editor extends WCJ_Module {

		/**
		 * The module templates_to_edit
		 *
		 * @var varchar $templates_to_edit Module templates_to_edit.
		 */
		public $templates_to_edit;

		/**
		 * Constructor.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 */
		public function __construct() {

			$this->id         = 'template_editor';
			$this->short_desc = __( 'Template Editor', 'woocommerce-jetpack' );
			$this->desc       = __( 'WooCommerce template editor.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-template-editor';
			parent::__construct();

			add_action( 'woojetpack_after_settings_save', array( $this, 'create_templates' ), PHP_INT_MAX, 2 );

			if ( $this->is_enabled() ) {
				$this->templates_to_edit = wcj_get_option( 'wcj_template_editor_templates_to_edit', array() );
				add_filter( 'wc_get_template', array( $this, 'replace_template' ), PHP_INT_MAX, 5 );
			}

		}

		/**
		 * Create_templates.
		 *
		 * @version 7.0.0
		 * @since   3.9.0
		 * @todo    [dev] also delete on "Reset settings"
		 * @param string | array $sections defines the sections.
		 * @param string | array $current_section defines the current_section.
		 */
		public function create_templates( $sections, $current_section ) {
			if ( $this->id === $current_section ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				global $wp_filesystem;
				WP_Filesystem();
				$this->delete_dir( wcj_get_wcj_uploads_dir( 'templates' ) );
				$templates_content = wcj_get_option( 'wcj_template_editor_templates_content', array() );
				foreach ( wcj_get_option( 'wcj_template_editor_templates_to_edit', array() ) as $template ) {
					if ( isset( $templates_content[ $template ] ) ) {
						$_template      = explode( '/', $template );
						$_template_file = $_template[ count( $_template ) - 1 ];
						$_template_dirs = str_replace( $_template_file, '', $template );
						$_template_path = wcj_get_wcj_uploads_dir( 'templates' . DIRECTORY_SEPARATOR . $_template_dirs ) . DIRECTORY_SEPARATOR . $_template_file;
						if ( $templates_content[ $template ] ) {
							$wp_filesystem->put_contents( $_template_path, $templates_content[ $template ], FS_CHMOD_FILE );
						}
					}
				}
			}
		}

		/**
		 * Replace_template.
		 *
		 * @version 4.0.0
		 * @since   3.9.0
		 * @param string $located defines the located.
		 * @param string $template_name defines the template_name.
		 * @param  array  $args defines the args.
		 * @param string $template_path defines the template_path.
		 * @param string $default_path defines the default_path.
		 */
		public function replace_template( $located, $template_name, $args, $template_path, $default_path ) {
			if ( in_array( $template_name, $this->templates_to_edit, true ) ) {
				$modified_template = wcj_get_wcj_uploads_dir( 'templates', false ) . DIRECTORY_SEPARATOR . $template_name;
				return ( file_exists( $modified_template ) ? $modified_template : $located );
			}
			return $located;
		}

		/**
		 * Gets path by template.
		 *
		 * @version 4.7.1
		 * @since   4.7.1
		 *
		 * @param string $template Get template.
		 *
		 * @return int|string
		 */
		public function get_path_by_template( $template ) {
			$templates_by_path = wcj_get_option( 'wcj_template_editor_templates_by_path', array() );
			foreach ( $templates_by_path as $path => $templates ) {
				if ( in_array( $template, $templates, true ) ) {
					return $path;
				}
			}
			return '';
		}

		/**
		 * Gets paths from 'wcj_template_editor_paths' option
		 *
		 * @version 4.7.1
		 * @since   4.7.1
		 *
		 * @return array
		 */
		public function get_paths() {
			$paths_arr = explode( "\n", str_replace( "\r", '', wcj_get_option( 'wcj_template_editor_paths', '/woocommerce/templates/' ) ) );
			return array_map(
				function ( $path ) {
					return trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR . $path ) );
				},
				$paths_arr
			);
		}

		/**
		 * Scan_templates.
		 *
		 * @version 4.7.1
		 * @since   3.9.0
		 * @todo    [dev] (maybe) optimize
		 * @param  string $dir defines the dir.
		 * @param string $original_path defines the original_path.
		 * @param array  $results defines the results.
		 */
		public function scan_templates( $dir, $original_path, &$results = array() ) {
			$files = scandir( $dir );
			foreach ( $files as $key => $value ) {
				$path = realpath( $dir . DIRECTORY_SEPARATOR . $value );
				if ( ! is_dir( $path ) ) {
					$template             = str_replace( $original_path, '', str_replace( '\\', '/', $path ) );
					$results[ $template ] = $template;
				} elseif ( '.' !== $value && '..' !== $value ) {
					$this->scan_templates( $path, $original_path, $results );
				}
			}
			return $results;
		}

		/**
		 * Delete_dir.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @param  string $dir_path defines the dir_path.
		 */
		public function delete_dir( $dir_path ) {
			if ( ! is_dir( $dir_path ) ) {
				return false;
			}
			if ( '/' !== substr( $dir_path, strlen( $dir_path ) - 1, 1 ) ) {
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
