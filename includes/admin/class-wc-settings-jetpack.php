<?php
/**
 * Booster for WooCommerce - Settings
 *
 * @version 3.3.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Jetpack' ) ) :

class WC_Settings_Jetpack extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 */
	function __construct() {

		$this->id    = 'jetpack';
		$this->label = __( 'Booster', 'woocommerce-jetpack' );

		$this->cats  = include( 'wcj-modules-cats.php' );

		$this->custom_dashboard_modules = apply_filters( 'wcj_custom_dashboard_modules', array() );

		add_filter( 'woocommerce_settings_tabs_array',         array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id,       array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id,  array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id,       array( $this, 'output_cats_submenu' ) );
		add_action( 'woocommerce_sections_' . $this->id,       array( $this, 'output_sections_submenu' ) );

		require_once( 'class-wcj-settings-custom-fields.php' );
	}

	/**
	 * Output cats
	 *
	 * @version 2.7.0
	 */
	function output_cats_submenu() {
		$current_cat = empty( $_REQUEST['wcj-cat'] ) ? 'dashboard' : sanitize_title( $_REQUEST['wcj-cat'] );
		if ( empty( $this->cats ) ) {
			return;
		}
		echo '<ul class="subsubsub" style="text-transform: uppercase !important; font-weight: bold; margin-bottom: 10px !important;">';
		$array_keys = array_keys( $this->cats );
		foreach ( $this->cats as $id => $label_info ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&wcj-cat=' . sanitize_title( $id ) ) . '" class="' . ( $current_cat == $id ? 'current' : '' ) . '">' . $label_info['label'] . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}
		echo '</ul>' . '<br class="clear" />';
	}

	/**
	 * Output sections (modules) sub menu
	 *
	 * @version 3.0.0
	 */
	function output_sections_submenu() {
		global $current_section;
		$sections = $this->get_sections();
		$current_cat = empty( $_REQUEST['wcj-cat'] ) ? 'dashboard' : sanitize_title( $_REQUEST['wcj-cat'] );
		if ( 'dashboard' === $current_cat ) {

			// Counting modules
			$all    = 0;
			$active = 0;
			foreach ( $this->module_statuses as $module_status ) {
				if ( isset( $module_status['id'] ) && isset( $module_status['default'] ) ) {
					if ( 'yes' === get_option( $module_status['id'], $module_status['default'] ) ) {
						$active++;
					} elseif ( wcj_is_module_deprecated( $module_status['id'], true ) ) {
						continue;
					}
					$all++;
				}
			}

			$sections['alphabetically'] = __( 'Alphabetically', 'woocommerce-jetpack' ) . ' <span class="count">(' . $all . ')</span>';
			$sections['by_category']    = __( 'By Category', 'woocommerce-jetpack' )    . ' <span class="count">(' . $all . ')</span>';
			$sections['active']         = __( 'Active', 'woocommerce-jetpack' )         . ' <span class="count">(' . $active . ')</span>';
			$sections['manager']        = __( 'Manage Settings', 'woocommerce-jetpack' );
			if ( ! empty( $this->custom_dashboard_modules ) ) {
				foreach ( $this->custom_dashboard_modules as $custom_dashboard_module_id => $custom_dashboard_module_data ) {
					$sections[ $custom_dashboard_module_id ] = $custom_dashboard_module_data['title'];
				}
			}
			if ( '' == $current_section ) {
				$current_section = 'by_category';
			}
		}
		if ( ! empty( $this->cats[ $current_cat ]['all_cat_ids'] ) ) {
			foreach ( $sections as $id => $label ) {
				if ( ! in_array( $id, $this->cats[ $current_cat ]['all_cat_ids'] ) ) {
					unset( $sections[ $id ] );
				}
			}
		}
		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}
		foreach ( $this->cats[ $current_cat ]['all_cat_ids'] as $key => $id ) {
			if ( wcj_is_module_deprecated( $id, false, true ) ) {
				unset( $this->cats[ $current_cat ]['all_cat_ids'][ $key ] );
			}
		}
		echo '<ul class="subsubsub">';
		foreach ( $this->cats[ $current_cat ]['all_cat_ids'] as $id ) {
			$label = $sections[ $id ];
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&wcj-cat=' . $current_cat . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $this->cats[ $current_cat ]['all_cat_ids'] ) == $id ? '' : '|' ) . ' </li>';
		}
		echo '</ul>' . '<br class="clear" />';
	}

	/**
	 * get_cat_by_section
	 */
	function get_cat_by_section( $section ) {
		foreach ( $this->cats as $id => $label_info ) {
			if ( ! empty( $label_info['all_cat_ids'] ) ) {
				if ( in_array( $section, $label_info['all_cat_ids'] ) ) {
						return $id;
				}
			}
		}
		return '';
	}

	/**
	 * Get sections (modules)
	 *
	 * @return array
	 */
	function get_sections() {
		return apply_filters( 'wcj_settings_sections', array( '' => __( 'Dashboard', 'woocommerce-jetpack' ) ) );
	}

	/**
	 * active.
	 *
	 * @version 2.8.0
	 */
	function active( $active ) {
		return ( 'yes' === $active ) ? 'active' : 'inactive';
	}

	/**
	 * is_dashboard_section.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function is_dashboard_section( $current_section ) {
		return in_array( $current_section, array_merge( array( '', 'alphabetically', 'by_category', 'active', 'manager' ), array_keys( $this->custom_dashboard_modules ) ) );
	}

	/**
	 * Output the settings.
	 *
	 * @version 3.0.0
	 * @todo    (maybe) admin_notices
	 */
	function output() {

		global $current_section, $wcj_notice;

		if ( '' != $wcj_notice ) {
			echo '<div id="wcj_message" class="updated"><p><strong>' . $wcj_notice . '</strong></p></div>';
		}

		$is_dashboard = $this->is_dashboard_section( $current_section );

		// Deprecated message
		if ( $replacement_module = wcj_is_module_deprecated( $current_section ) ) {
			echo '<div id="wcj_message" class="error">';
			echo '<p>';
			echo '<strong>';
			echo sprintf(
				__( 'Please note that current <em>%s</em> module is deprecated and will be removed in future updates. Please use <em>%s</em> module instead.', 'woocommerce-jetpack' ),
				WCJ()->modules[ $current_section ]->short_desc,
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $replacement_module['cat'] . '&section=' . $replacement_module['module'] ) . '">' .
					$replacement_module['title'] . '</a>'
			);
			echo ' <span style="color:red;">' . __( 'Module will be removed from the module\'s list as soon as you disable it.', 'woocommerce-jetpack' ) . '</span>';
			echo '</strong>';
			echo '</p>';
			echo '</div>';
		}

		// "Under development" message
		if ( isset( WCJ()->modules[ $current_section ]->dev ) && true === WCJ()->modules[ $current_section ]->dev ) {
			echo '<div id="wcj_message" class="error">';
			echo '<p>';
			echo '<strong>';
			echo sprintf( __( 'Please note that <em>%s</em> module is currently under development. Until stable module version is released, options can be changed or some options can be moved to paid plugin version.', 'woocommerce-jetpack' ), WCJ()->modules[ $current_section ]->short_desc );
			echo '</strong>';
			echo '</p>';
			echo '</div>';
		}

		if ( 'yes' === get_option( 'wcj_admin_tools_enabled' ) && 'yes' === get_option( 'wcj_debuging_enabled', 'no' ) ) {
			// Breadcrumbs
			$breadcrumbs_html = '';
			$breadcrumbs_html .= '<p>';
			$breadcrumbs_html .= '<code>';
			$breadcrumbs_html .= __( 'WooCommerce', 'woocommerce-jetpack' );
			$breadcrumbs_html .= ' > ';
			$breadcrumbs_html .= __( 'Settings', 'woocommerce-jetpack' );
			$breadcrumbs_html .= ' > ';
			$breadcrumbs_html .= __( 'Booster', 'woocommerce-jetpack' );
			$breadcrumbs_html .= ' > ';
			foreach ( $this->cats as $id => $label_info ) {
				if ( $this->get_cat_by_section( $current_section ) === $id ) {
					$breadcrumbs_html .= $label_info['label'];
					break;
				}
			}
			if ( $is_dashboard && isset( $_GET['wcj-cat'] ) && 'dashboard' != $_GET['wcj-cat'] ) {
				$breadcrumbs_html .= $this->cats[ $_GET['wcj-cat'] ]['label'];
			}
			if ( ! $is_dashboard ) {
				$breadcrumbs_html .= ' > ';
				$sections = $this->get_sections();
				$breadcrumbs_html .= $sections[ $current_section ];
			}
			$breadcrumbs_html .= '</code>';
			$breadcrumbs_html .= '</p>';
			echo $breadcrumbs_html;
		}

		$settings = $this->get_settings( $current_section );

		if ( ! $is_dashboard ) {
			WC_Admin_Settings::output_fields( $settings );
		} else {
			$this->output_dashboard( $current_section );
		}
	}

	/**
	 * output_dashboard.
	 *
	 * @version 3.3.0
	 */
	function output_dashboard( $current_section ) {

		if ( '' == $current_section ) {
			$current_section = 'by_category';
		}

		$the_settings = $this->get_settings();

		echo '<h3>' . $the_settings[0]['title'] . '</h3>';
		if ( isset( $this->custom_dashboard_modules[ $current_section ] ) ) {
			echo '<p>' . $this->custom_dashboard_modules[ $current_section ]['desc'] . '</p>';
		} elseif ( 'manager' != $current_section ) {
			echo '<p>' . $the_settings[0]['desc'] . '</p>';
		} else {
			echo '<p>' . __( 'This section lets you export, import or reset all Booster\'s modules settings.', 'woocommerce-jetpack' ) . '</p>';
		}

		if ( 'alphabetically' === $current_section ) {
			$this->output_dashboard_modules( $the_settings );
		} elseif ( 'by_category' === $current_section ) {
			foreach ( $this->cats as $cat_id => $cat_label_info ) {
				if ( 'dashboard' === $cat_id ) {
					continue;
				}
				if ( isset( $_GET['wcj-cat'] ) && 'dashboard' != $_GET['wcj-cat'] ) {
					if ( $cat_id != $_GET['wcj-cat'] ) {
						continue;
					}
				} else {
					echo '<h4>' . $cat_label_info['label'] . '</h4>';
				}
				$this->output_dashboard_modules( $the_settings, $cat_id );
			}
		} elseif ( 'active' === $current_section ) {
			$this->output_dashboard_modules( $the_settings, 'active_modules_only' );
		} elseif ( 'manager' === $current_section ) {
			$table_data = array(
				array(
					'<button style="width:100px;" class="button-primary" type="submit" name="booster_export_settings">' . __( 'Export', 'woocommerce-jetpack' ) . '</button>',
					'<em>' . __( 'Export all Booster\'s options to a file.', 'woocommerce-jetpack' ) . '</em>',
				),
				array(
					'<button style="width:100px;" class="button-primary" type="submit" name="booster_import_settings">' . __( 'Import', 'woocommerce-jetpack' ) . '</button>' .
						' ' . '<input type="file" name="booster_import_settings_file">',
					'<em>' . __( 'Import all Booster\'s options from a file.', 'woocommerce-jetpack' ) . '</em>',
				),
				array(
					'<button style="width:100px;" class="button-primary" type="submit" name="booster_reset_settings" onclick="return confirm(\'' . __( 'This will reset settings to defaults for all Booster modules. Are you sure?', 'woocommerce-jetpack' ) . '\')">'  . __( 'Reset', 'woocommerce-jetpack' )  . '</button>',
					'<em>' . __( 'Reset all Booster\'s options.', 'woocommerce-jetpack' ) . '</em>',
				),
			);
			$manager_settings = $this->get_manager_settings();
			foreach ( $manager_settings as $manager_settings_field ) {
				$table_data[] = array(
					'<label for="' . $manager_settings_field['id'] . '">' .
						'<input name="' . $manager_settings_field['id'] . '" id="' . $manager_settings_field['id'] . '" type="' . $manager_settings_field['type'] . '" class="" value="1" ' . checked( get_option( $manager_settings_field['id'], $manager_settings_field['default'] ), 'yes', false ) . '>' .
						' ' . '<strong>' . $manager_settings_field['title'] . '</strong>' .
					'</label>',
					'<em>' . $manager_settings_field['desc'] . '</em>',
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'none' ) );
		}

		if ( isset( $this->custom_dashboard_modules[ $current_section ] ) ) {
			$table_data = array();
			foreach ( $this->custom_dashboard_modules[ $current_section ]['settings'] as $_settings ) {
				$table_data[] = array(
					$_settings['title'],
					'<label for="' . $_settings['id'] . '">' .
						'<input name="' . $_settings['id'] .
							'" id="'    . $_settings['id'] .
							'" type="'  . $_settings['type'] .
							'" class="' . $_settings['class'] .
							'" value="' . get_option( $_settings['id'], $_settings['default'] )
						. '">' . ' ' . '<em>' . $_settings['desc'] . '</em>' .
					'</label>',
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical' ) );
		}

		$plugin_data  = get_plugin_data( WCJ_PLUGIN_FILE );
		$plugin_title = ( isset( $plugin_data['Name'] ) ? '[' . $plugin_data['Name'] . '] ' : '' );
		echo '<p style="text-align:right;color:gray;font-size:x-small;font-style:italic;">' . $plugin_title .
			__( 'Version', 'woocommerce-jetpack' ) . ': ' . get_option( WCJ_VERSION_OPTION, 'N/A' ) . '</p>';

	}

	/**
	 * compare_for_usort.
	 */
	private function compare_for_usort( $a, $b ) {
		return strcmp( $a['title'], $b['title'] );
	}

	/**
	 * output_dashboard_modules.
	 *
	 * @version 3.3.0
	 */
	function output_dashboard_modules( $settings, $cat_id = '' ) {
		?>
		<table class="wp-list-table widefat plugins">
			<thead>
			<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'woocommerce-jetpack' ); ?></label><input id="cb-select-all-1" type="checkbox" style="margin-top:15px;"></th>
			<th scope="col" id="name" class="manage-column column-name" style=""><?php _e( 'Module', 'woocommerce-jetpack' ); ?></th>
			<th scope="col" id="description" class="manage-column column-description" style=""><?php _e( 'Description', 'woocommerce-jetpack' ); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
			<th scope="col" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All', 'woocommerce-jetpack' ); ?></label><input id="cb-select-all-2" type="checkbox" style="margin-top:15px;"></th>
			<th scope="col" class="manage-column column-name" style=""><?php _e( 'Module', 'woocommerce-jetpack' ); ?></th>
			<th scope="col" class="manage-column column-description" style=""><?php _e( 'Description', 'woocommerce-jetpack' ); ?></th>
			</tr>
			</tfoot>
			<tbody id="the-list"><?php
				$html = '';
				usort( $settings, array( $this, 'compare_for_usort' ) );
				$total_modules = 0;
				foreach ( $settings as $the_feature ) {
					if ( 'checkbox' !== $the_feature['type'] ) {
						continue;
					}
					$section = $the_feature['id'];
					$section = str_replace( 'wcj_', '', $section );
					$section = str_replace( '_enabled', '', $section );
					if ( wcj_is_module_deprecated( $section, false, true ) ) {
						continue;
					}
					if ( '' != $cat_id ) {
						if ( 'active_modules_only' === $cat_id ) {
							if ( 'no' === get_option( $the_feature['id'], 'no' ) ) {
								continue;
							}
						} elseif ( $cat_id != $this->get_cat_by_section( $section ) ) {
							continue;
						}
					}
					$total_modules++;
					$html .= '<tr id="' . $the_feature['id'] . '" ' . 'class="' . $this->active( get_option( $the_feature['id'] ) ) . '">';
					$html .= '<th scope="row" class="check-column">';
					$html .= '<label class="screen-reader-text" for="' . $the_feature['id'] . '">' . $the_feature['desc'] . '</label>';
					$html .= '<input type="checkbox" name="' . $the_feature['id'] . '" value="1" id="' . $the_feature['id'] . '" ' . checked( get_option( $the_feature['id'] ), 'yes', false ) . '>';
					$html .= '</th>';
					$html .= '<td class="plugin-title">' . '<strong>' . $the_feature['title'] . '</strong>';
					$html .= '<div class="row-actions visible">';
					$html .= '<span class="0"><a href="' . admin_url() . 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $this->get_cat_by_section( $section ) . '&section=' . $section . '">' . __( 'Settings', 'woocommerce' ) . '</a></span>';
					if ( isset( $the_feature['wcj_link'] ) && '' != $the_feature['wcj_link'] ) {
						$html .= ' | <span class="0"><a href="' . $the_feature['wcj_link'] . '?utm_source=module_documentation&utm_medium=dashboard_link&utm_campaign=booster_documentation" target="_blank">' . __( 'Documentation', 'woocommerce' ) . '</a></span>';
					}
					$html .= '</div>';
					$html .= '</td>';
					$html .= '<td class="column-description desc">';
					$html .= '<div class="plugin-description"><p>' . ( ( isset( $the_feature['wcj_desc'] ) ) ? $the_feature['wcj_desc'] : $the_feature['desc_tip'] ) . '</p></div>';
					$html .= '</td>';
					$html .= '</tr>';
				}
				echo $html;
				if ( 0 == $total_modules && 'active_modules_only' === $cat_id ) {
					echo '<tr><td colspan="3">' . '<em>' . __( 'No active modules found.', 'woocommerce-jetpack' ) . '</em>' . '</td></tr>';
				}
			?></tbody>
		</table><p style="color:gray;font-size:x-small;font-style:italic;"><?php echo __( 'Total Modules:' ) . ' ' . $total_modules; ?></p><?php
	}

	/**
	 * Save settings
	 *
	 * @version 2.2.6
	 */
	function save() {
		global $current_section;
		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
		echo apply_filters( 'booster_message', '', 'global' );
		do_action( 'woojetpack_after_settings_save', $this->get_sections(), $current_section );
	}

	/**
	 * get_manager_settings.
	 *
	 * @version 3.2.4
	 * @since   2.6.0
	 * @return  array
	 */
	function get_manager_settings() {
		return array(
			array(
				'title'   => __( 'Autoload Booster\'s Options', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Choose if you want Booster\'s options to be autoloaded when calling add_option. After saving this option, you need to Reset all Booster\'s settings. Leave default value (i.e. Enabled) if not sure.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_autoload_options',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Use List Instead of Comma Separated Text for Products in Settings', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'desc'    => sprintf( __( 'Supported modules: %s.', 'woocommerce-jetpack' ), implode( ', ', array(
					__( 'Gateways per Product or Category', 'woocommerce-jetpack' ),
					__( 'Global Discount', 'woocommerce-jetpack' ),
					__( 'Product Info', 'woocommerce-jetpack' ),
					__( 'Product Input Fields', 'woocommerce-jetpack' ),
					__( 'Products XML', 'woocommerce-jetpack' ),
					__( 'Related Products', 'woocommerce-jetpack' ),
				) ) ),
				'id'      => 'wcj_list_for_products',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Get settings array
	 *
	 * @version 3.0.0
	 * @return  array
	 */
	function get_settings( $current_section = '' ) {
		if ( ! $this->is_dashboard_section( $current_section ) ) {
			return apply_filters( 'wcj_settings_' . $current_section, array() );
		} elseif ( 'manager' === $current_section ) {
			return $this->get_manager_settings();
		} elseif ( isset( $this->custom_dashboard_modules[ $current_section ] ) ) {
			return $this->custom_dashboard_modules[ $current_section ]['settings'];
		} else {
			$cat_id = ( isset( $_GET['wcj-cat'] ) && '' != $_GET['wcj-cat'] ) ? $_GET['wcj-cat'] : 'dashboard';
			$settings[] = array(
				'title' => __( 'Booster for WooCommerce', 'woocommerce-jetpack' ) . ' - ' . $this->cats[ $cat_id ]['label'],
				'type'  => 'title',
				'desc'  => $this->cats[ $cat_id ]['desc'],
				'id'    => 'wcj_' . $cat_id . '_options',
			);
			if ( 'dashboard' === $cat_id ) {
				$settings = array_merge( $settings, $this->module_statuses );
			} else {
				$cat_module_statuses = array();
				foreach ( $this->module_statuses as $module_status ) {
					$section = $module_status['id'];
					$section = str_replace( 'wcj_', '', $section );
					$section = str_replace( '_enabled', '', $section );
					if ( $cat_id === $this->get_cat_by_section( $section ) ) {
						$cat_module_statuses[] = $module_status;
					}
				}
				$settings = array_merge( $settings, $cat_module_statuses );
			}
			$settings[] = array(
				'type'  => 'sectionend',
				'id'    => 'wcj_' . $cat_id . '_options',
				'title' => '', // for usort
			);
			return $settings;
		}
	}

	/**
	 * add_module_statuses
	 */
	function add_module_statuses( $statuses ) {
		$this->module_statuses = $statuses;
	}
}

endif;

return new WC_Settings_Jetpack();
