<?php
/**
 * WooCommerce Jetpack Settings
 *
 * The WooCommerce Jetpack Settings class.
 *
 * @version 2.5.3
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Jetpack' ) ) :

class WC_Settings_Jetpack extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @version 2.2.8
	 */
	function __construct() {

		$this->id    = 'jetpack';
		$this->label = __( 'Booster', 'woocommerce-jetpack' );

		$this->cats  = include( 'wcj-modules-cats.php' );

		add_filter( 'woocommerce_settings_tabs_array',         array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id,       array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id,  array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id,       array( $this, 'output_cats_submenu' ) );
		add_action( 'woocommerce_sections_' . $this->id,       array( $this, 'output_sections_submenu' ) );

//		add_action( 'woocommerce_admin_field_save_button',     array( $this, 'output_save_settings_button' ) );
		add_action( 'woocommerce_admin_field_custom_number',   array( $this, 'output_custom_number' ) );
		add_action( 'woocommerce_admin_field_custom_link',     array( $this, 'output_custom_link' ) );
		add_action( 'woocommerce_admin_field_module_tools',    array( $this, 'output_module_tools' ) );
		add_action( 'woocommerce_admin_field_custom_textarea', array( $this, 'output_custom_textarea' ) );
	}

	/**
	 * output_save_settings_button.
	 *
	function output_save_settings_button( $value ) {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>" style="padding-top: 0px;">
				<input name="save" class="button-primary" type="submit" value="<?php _e( 'Update', 'woocommerce-jetpack' ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * output_custom_textarea.
	 *
	 * @version 2.2.6
	 * @since   2.2.6
	 */
	function output_custom_textarea( $value ) {
		$option_value = get_option( $value['id'], $value['default'] );

		$custom_attributes = ( isset( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) ? $value['custom_attributes'] : array();
		$description = ' <p class="description">' . $value['desc'] . '</p>';
		$tooltip_html = '';//' <p class="description">' . $value['desc_tip'] . '</p>';
//		$tooltip_html = $value['desc_tip'];
//		$tooltip_html = '<img class="help_tip" data-tip="' . esc_attr( $tooltip_html ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<?php echo $description; ?>

				<textarea
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); ?>
					><?php echo esc_textarea( $option_value );  ?></textarea>
			</td>
		</tr><?php
	}

	/**
	 * output_module_tools.
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	function output_module_tools( $value ) {
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<?php if ( isset( $_GET['section'] ) ) do_action( 'wcj_module_tools_' . $_GET['section'] ); ?>
			</td>
		</tr><?php
	}

	/**
	 * output_custom_link.
	 *
	 * @version 2.2.8
	 * @since   2.2.8
	 */
	function output_custom_link( $value ) {
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<?php echo $value['link']; ?>
			</td>
		</tr><?php
	}

	/**
	 * output_custom_number.
	 *
	 * @version 2.4.0
	 */
	function output_custom_number( $value ) {
		$type         = 'number';//$value['type'];
		$option_value = get_option( $value['id'], $value['default'] );

		$tooltip_html = '';
//		$custom_attributes = ( is_array( $value['custom_attributes'] ) ) ? $value['custom_attributes'] : array();
		$description = ' <span class="description">' . $value['desc'] . '</span>';
		$save_button = apply_filters( 'wcj_get_option_filter', '', ' <input name="save" class="button-primary" type="submit" value="' . __( 'Save changes', 'woocommerce' ) . '">' );

		// Custom attribute handling
		$custom_attributes = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="<?php echo esc_attr( $type ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); ?>
					/><?php echo $save_button; ?><?php echo $description; ?>
			</td>
		</tr><?php
	}

	/**
	 * Output cats
	 *
	 * @version 2.3.9
	 */
	function output_cats_submenu() {
		$current_cat = empty( $_REQUEST['wcj-cat'] ) ? 'dashboard' : sanitize_title( $_REQUEST['wcj-cat'] );
		if ( empty( $this->cats ) ) {
			return;
		}
		echo '<ul class="subsubsub" style="text-transform: uppercase !important; font-weight: bold; margin-bottom: 10px !important;">';
		$array_keys = array_keys( $this->cats );
		foreach ( $this->cats as $id => $label_info ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&wcj-cat=' . sanitize_title( $id ) ) . '&section=' . $label_info['default_cat_id'] . '" class="' . ( $current_cat == $id ? 'current' : '' ) . '">' . $label_info['label'] . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}
		echo '</ul>' . '<br class="clear" />';
	}

	/**
	 * Output sections (modules) sub menu
	 *
	 * @version 2.5.2
	 */
	function output_sections_submenu() {
		global $current_section;
		$sections = $this->get_sections();
		$current_cat = empty( $_REQUEST['wcj-cat'] ) ? 'dashboard' : sanitize_title( $_REQUEST['wcj-cat'] );
		if ( 'dashboard' === $current_cat ) {
			$sections['alphabetically'] = __( 'Alphabetically', 'woocommerce-jetpack' );
			$sections['by_category']    = __( 'By Category', 'woocommerce-jetpack' );
			$sections['active']         = __( 'Active', 'woocommerce-jetpack' );
			$sections['manager']        = __( 'Manage Settings', 'woocommerce-jetpack' );
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
			if ( ! empty( $label_info['all_cat_ids'] ) )
				if ( in_array( $section, $label_info['all_cat_ids'] ) )
						return $id;
		}
		return '';
	}

	/**
	 * Get sections (modules)
	 *
	 * @return array
	 */
	function get_sections() {
		return apply_filters( 'wcj_settings_sections', array(
			'' => __( 'Dashboard', 'woocommerce-jetpack' ),
		) );
	}

	/**
	 * active.
	 */
	function active( $active ) {
		if ( 'yes' === $active ) return 'active';
		else return 'inactive';
	}

	/**
	 * Output the settings.
	 *
	 * @version 2.5.3
	 */
	function output() {

		global $current_section, $wcj_notice;

		if ( '' != $wcj_notice ) {
			echo '<div id="wcj_message" class="updated"><p><strong>' . $wcj_notice . '</strong></p></div>';
		}

		$is_dashboard = ( '' != $current_section && 'alphabetically' != $current_section && 'by_category' != $current_section && 'active' != $current_section && 'manager' != $current_section )
			? false : true;

		// Depreciated message
		$depreciated_modules_and_links = array(
			'product_info' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=products&section=product_custom_info' ) . '">' . __( 'Product Info', 'woocommerce-jetpack' ) . '</a>',
		);
		if ( array_key_exists( $current_section, $depreciated_modules_and_links ) ) {
			echo '<div id="wcj_message" class="error">';
			echo '<p>';
			echo '<strong>';
			echo sprintf( __( 'Please note that current <em>%s</em> module is depreciated and will be removed in future updates. Please use <em>%s</em> module instead.', 'woocommerce-jetpack' ), WCJ()->modules[ $current_section ]->short_desc, $depreciated_modules_and_links[ $current_section ] );
			echo '</strong>';
			echo '</p>';
			echo '</div>';
		}

		// Multicurrency message
		/* $multicurrency_modules_enabled = 0;
		$multicurrency_modules_titles = array();
		$multicurrency_modules = array( 'price_by_country', 'multicurrency', 'payment_gateways_currency' );
		foreach ( $multicurrency_modules as $multicurrency_module ) {
			if ( wcj_is_module_enabled( $multicurrency_module ) ) {
				$multicurrency_modules_enabled++;
				$multicurrency_modules_titles[] = WCJ()->modules[ $multicurrency_module ]->short_desc;
			}
		}
		if ( $multicurrency_modules_enabled > 1 ) {
			echo '<div id="wcj_message" class="error">';
			echo '<p>';
			echo '<strong>';
			echo sprintf( __( 'Please note that only single multicurrency module can be enabled simultaneously. You have <em>%s</em> modules enabled, please choose one of them.', 'woocommerce-jetpack' ), implode( ', ', $multicurrency_modules_titles ) );
			echo '</strong>';
			echo '</p>';
			echo '</div>';
		} */

		// Caching message
		/* $known_caching_plugins = array( 'w3-total-cache/w3-total-cache.php' );
		$no_caching_modules = array();
//		$no_caching_modules = array_merge( $no_caching_modules, $multicurrency_modules );
		$caching_plugin_is_active = false;
		foreach ( $known_caching_plugins as $caching_plugin ) {
			// Check if caching_plugin is active
			if (
				in_array( $caching_plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
				( is_multisite() && array_key_exists( $caching_plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
			) {
				$caching_plugin_is_active = true;
				break;
			}
		}
		$no_caching_module_is_active = false;
		$no_caching_modules_titles = array();
		foreach ( $no_caching_modules as $no_caching_module ) {
			if ( wcj_is_module_enabled( $no_caching_module ) ) {
				$no_caching_module_is_active = true;
				$no_caching_modules_titles[] = WCJ()->modules[ $no_caching_module ]->short_desc;
				//break;
			}
		}
		if ( $caching_plugin_is_active && $no_caching_module_is_active ) {
			echo '<div id="wcj_message" class="error">';
			echo '<p>';
			echo '<strong>';
			echo sprintf( __( 'Please note that <em>%s</em> modules require caching to be turned off.', 'woocommerce-jetpack' ), implode( ', ', $no_caching_modules_titles ) );
			echo '</strong>';
			echo '</p>';
			echo '</div>';
		} */

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
			//$breadcrumbs_html .= $settings[0]['title'];
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
		}
		else {
			$this->output_dashboard( $current_section );
		}
	}

	/**
	 * output_dashboard.
	 *
	 * @version 2.5.2
	 */
	function output_dashboard( $current_section ) {

		if ( '' == $current_section ) $current_section = 'by_category';

		$the_settings = $this->get_settings();

		echo '<h3>' . $the_settings[0]['title'] . '</h3>';
		if ( 'manager' != $current_section ) {
			echo '<p>' . $the_settings[0]['desc'] . '</p>';
		} else {
			echo '<p>' . __( 'This section lets you export, import or reset all Booster\'s modules settings.', 'woocommerce-jetpack' ) . '</p>';
		}

		$readme_html = '';
		$readme_html .= '<pre>';

		if ( 'alphabetically' === $current_section ) {
			$this->output_dashboard_modules( $the_settings );
		} elseif ( 'by_category' === $current_section ) {
			foreach ( $this->cats as $cat_id => $cat_label_info ) {
				if ( 'dashboard' === $cat_id ) continue;
				echo '<h4>' . $cat_label_info['label'] . '</h4>';
				$readme_html .= PHP_EOL . '**' . $cat_label_info['label'] . '**' . PHP_EOL . PHP_EOL;
				$readme_html .= $this->output_dashboard_modules( $the_settings, $cat_id );
			}
		} elseif ( 'active' === $current_section ) {
			$this->output_dashboard_modules( $the_settings, 'active_modules_only' );
		} elseif ( 'manager' === $current_section ) {
			echo '<p><button class="button-primary" type="submit" name="booster_export_settings">' . __( 'Export', 'woocommerce-jetpack' ) . '</button></p>';
			echo '<p><button class="button-primary" type="submit" name="booster_import_settings">' . __( 'Import', 'woocommerce-jetpack' ) . '</button>';
			echo ' ' . '<input type="file" name="booster_import_settings_file"></p>';
			echo '<p><button class="button-primary" type="submit" name="booster_reset_settings" onclick="return confirm(\'' . __( 'This will reset settings to defaults for all Booster modules. Are you sure?', 'woocommerce-jetpack' ) . '\')">'  . __( 'Reset', 'woocommerce-jetpack' )  . '</button></p>';
		}

		echo '<p style="text-align:right;color:gray;font-size:x-small;font-style:italic;">' . __( 'Version' ) . ': ' . get_option( 'booster_for_woocommerce_version', 'N/A' ) . '</p>';

		$readme_html .= '</pre>';
		if ( isset( $_GET['woojetpack_readme'] ) ) echo $readme_html;
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
	 * @version 2.5.2
	 */
	function output_dashboard_modules( $settings, $cat_id = '' ) {
		$readme_html = '';
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

					if ( 'checkbox' !== $the_feature['type'] ) continue;

					$section = $the_feature['id'];
					$section = str_replace( 'wcj_', '', $section );
					$section = str_replace( '_enabled', '', $section );

					if ( '' != $cat_id ) {
						if ( 'active_modules_only' === $cat_id ) {
							if ( 'no' === get_option( $the_feature['id'] ) ) continue;
						}
						elseif ( $cat_id != $this->get_cat_by_section( $section ) ) continue;
					}

					$total_modules++;

					$html .= '<tr id="' . $the_feature['id'] . '" ' . 'class="' . $this->active( get_option( $the_feature['id'] ) ) . '">';

					$html .= '<th scope="row" class="check-column">';
					$html .= '<label class="screen-reader-text" for="' . $the_feature['id'] . '">' . $the_feature['desc'] . '</label>';
					$html .= '<input type="checkbox" name="' . $the_feature['id'] . '" value="1" id="' . $the_feature['id'] . '" ' . checked( get_option( $the_feature['id'] ), 'yes', false ) . '>';
					$html .= '</th>';

					$html .= '<td class="plugin-title"><strong>' . $the_feature['title'] . '</strong>';
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

					$readme_html .= '* *' . $the_feature['title'] . '* - ' . ( ( isset( $the_feature['wcj_desc'] ) ) ? $the_feature['wcj_desc'] : $the_feature['desc_tip'] ) . PHP_EOL;
				}
				echo $html;
				if ( 0 == $total_modules && 'active_modules_only' === $cat_id ) {
					echo '<tr><td colspan="3">' . '<em>' . __( 'No active modules found.', 'woocommerce-jetpack' ) . '</em>' . '</td></tr>';
				}
			?></tbody>
		</table><p style="color:gray;font-size:x-small;font-style:italic;"><?php echo __( 'Total Modules:' ) . ' ' . $total_modules; ?></p><?php
		return $readme_html;
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
		echo apply_filters('get_wc_jetpack_plus_message', '', 'global' );
		do_action( 'woojetpack_after_settings_save', $this->get_sections(), $current_section );
	}

	/**
	 * Get settings array
	 *
	 * @version 2.3.8
	 * @return  array
	 */
	function get_settings( $current_section = '' ) {
		if ( '' != $current_section && 'alphabetically' != $current_section && 'by_category' != $current_section && 'active' != $current_section ) {
			return apply_filters( 'wcj_settings_' . $current_section, array() );
		}
		else {
			$settings[] = array(
				'title' => __( 'Booster for WooCommerce - Dashboard', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'desc'  => __( 'This dashboard lets you enable/disable any Booster\'s module. Each checkbox comes with short module\'s description. Please visit <a href="http://booster.io" target="_blank">http://booster.io</a> for detailed info on each feature.', 'woocommerce-jetpack' ),
				'id'    => 'wcj_options'
			);
			//$settings = apply_filters( 'wcj_features_status', $settings );
			$settings = array_merge( $settings, $this->module_statuses );
			$settings[] = array( 'type' => 'sectionend', 'id' => 'wcj_options', 'title' => '', 'desc' => '', );
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
