<?php

/**
 * Booster for WooCommerce - Settings
 *
 * @version 5.5.6
 * @since   1.0.0
 * @author  Pluggabl LLC.
 */

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

if (!class_exists('WC_Settings_Jetpack')) :

    class WC_Settings_Jetpack extends WC_Settings_Page
    {

        /**
         * Constructor.
         *
         * @version 5.3.1
         */
        public function __construct()
        {

            $this->id = 'jetpack';
            $this->label = __('Booster', 'woocommerce-jetpack');

            $this->cats = include 'wcj-modules-cats.php';

            $this->custom_dashboard_modules = apply_filters('wcj_custom_dashboard_modules', array());

            add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
            add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
            add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
            //add_action('woocommerce_sections_' . $this->id, array($this, 'output_cats_submenu'));
            //add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections_submenu'));
            add_action('woocommerce_sections_' . $this->id, array($this, 'dasboard_menu'));
            //  Created New Dashborad desing css 
            add_action( 'admin_enqueue_scripts', array($this,'wcj_new_desing_dashboard') );

            // Create free version notices
            add_action('woocommerce_after_settings_' . $this->id, array($this, 'create_free_version_footer_review_notice'));
            add_action('woocommerce_settings_' . $this->id, array($this, 'create_free_version_notice_about_plus'));
           

            // Create a PRO version ratting notice
            add_action('woocommerce_after_settings_' . $this->id, array($this, 'create_pro_version_footer_review_notice'));

            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));

            require_once 'class-wcj-settings-custom-fields.php';
        }

        /**
            * wcj_new_desing_dashboard.
            *
            * @version 5.5.6
            * @since   5.5.6
          */
   
          public function wcj_new_desing_dashboard(){   
            $page=$_GET['tab'];
            
            if( $page ==='jetpack'){
              wp_enqueue_style('wcj-admin-wcj-new_desing', wcj_plugin_url() . '/includes/css/admin-style.css');
              wp_enqueue_script('wcj-admin-script', wcj_plugin_url() . '/includes/js/admin-script.js', array('jquery'), '5.0.0', true);
              //wp_enqueue_script('wcj-admin-dasboard-js', wcj_plugin_url() . '/includes/js/wcj-new_desing.js');
            }
          }


        
        /**
         * create_free_version_notice_about_plus.
         *
         * @version 5.5.6
         * @since   5.3.0
         */
        public function create_free_version_notice_about_plus()
        {
            if ('woocommerce-jetpack.php' !== basename(WCJ_PLUGIN_FILE)) {
                return;
            }
            $class = 'notice notice-info';
            $message = sprintf(__('You\'re using Booster free version. To unlock more features please consider <a target="_blank" href="%s">Upgrade Booster to unlock this feature</a>.', 'woocommerce-jetpack'), 'https://booster.io/buy-booster/');
            $booster_icon = '<span class="wcj-booster-logo"></span>';
        ?>
            <style>
               .wcj-booster-logo { width: 19px; height: 19px; display: inline-block; background: url('https://ps.w.org/woocommerce-jetpack/assets/icon-128x128.png?rev=1813426') center/cover; vertical-align: middle; position: relative; top: -1px; margin: 0 6px 0 0; }
            </style>
        <?php
            echo '<div class="' . $class . '"><p>' . $booster_icon . $message . '</p></div>';
        }

        /**
         * create_free_version_footer_review_notice.
         *
         * @version 5.5.6
         * @since   5.3.0
         */
        public function create_free_version_footer_review_notice()
        {
            if ('woocommerce-jetpack.php' !== basename(WCJ_PLUGIN_FILE)) {
                return;
            }
            $class = 'notice notice-info inline';
            $link = 'https://wordpress.org/support/plugin/woocommerce-jetpack/reviews/?filter=5#new-post';
            $star = '<span class="wcj-review-icon dashicons dashicons-star-filled"></span>';
            $stars_link = '<a href="' . $link . '" target="_blank">' . $star . $star . $star . $star . $star . '</a>';
            $message = sprintf(__('Please rate <strong>Booster for WooCommerce</strong> %s on <a href="%s" target="_blank">WordPress.org</a> to help us spread the word. Thank you from Booster team!', 'woocommerce-jetpack'), $stars_link, $link);
        ?>
            <style>
          .wcj-review-icon { vertical-align: middle; margin: -6px 0 0 0; }
          </style>
        <?php
           // echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }
        /**
         * create_pro_version_footer_review_notice.
         *
         * @version 5.5.6
         * @since   5.3.1
         */
        public function create_pro_version_footer_review_notice()
        {
            $sec_link = wcj_plugin_url();
        ?>
            <div class="circleBox">
                <div class="circle-badge" style="float:right">
                    <img src="<?php echo $sec_link ?>/assets/images/pop_icon.png">
                </div>
                <div class="subCircles">
                    <div class="sub-circle">
                        <div class="form_label">
                            <a href="https://booster.io/submit-idea/" target="_blank">
                                <label>Suggest a feature</label>
                                <div class="ic_list">
                                    <img src="<?php echo $sec_link ?>/assets/images/suggestion-w.png">
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="sub-circle">
                        <a href="https://wordpress.org/support/plugin/woocommerce-jetpack/#new-topic-0" target="_blank">
                            <div class="form_label">
                                <label>Booster Free Support (72 business hours response)</label>
                                <div class="ic_list"><img src="<?php echo $sec_link ?>/assets/images/support-3d-w.png"></div>
                            </div>
                        </a>
                    </div>
                    <div class="sub-circle">
                        <a href="https://booster.io/my-account/booster-contact/" target="_blank">
                            <div class="form_label">
                                <label>Booster Plus Premium Support (4 hours - 24 hours response)</label>
                                <div class="ic_list"><img src="<?php echo $sec_link ?>/assets/images/support-24-h-w.png"></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <?php
            if ('booster-plus-for-woocommerce.php' !== basename(WCJ_PLUGIN_FILE)) {
                return;
            }
            $class = 'notice notice-info inline';
            $link = 'https://wordpress.org/support/plugin/woocommerce-jetpack/reviews/?filter=5#new-post';
            $star = '<span class="wcj-review-icon dashicons dashicons-star-filled"></span>';
            $stars_link = '<a href="' . $link . '" target="_blank">' . $star . $star . $star . $star . $star . '</a>';
            $message = sprintf(__('Please rate <strong>Booster for WooCommerce</strong> %s on  <a href="%s" target="_blank">WordPress.org</a> to help us spread the word. Thank you from Booster team!', 'woocommerce-jetpack'), $stars_link, $link);
            ?>
           <style>
              .wcj-review-icon { vertical-align: middle; margin: -6px 0 0 0; }
            </style>
        <?php
            echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }

        /**
         * enqueue_admin_script.
         *
         * @version 5.4.3
         * @since   5.4.2
         */
        function enqueue_admin_script()
        {
            wp_enqueue_script('wcj-admin-js',  trailingslashit(wcj_plugin_url()) . 'includes/js/wcj-admin.js', array('jquery'), WCJ()->version, true);
            wp_localize_script('wcj-admin-js', 'admin_object', array('admin_object'), false);
        }

        /**
         * Output cats
         *
         * @version 5.5.6
         */
        public function output_cats_submenu(){
            global $current_section; 
            $current_cat = empty($_REQUEST['wcj-cat']) ? 'dashboard' : sanitize_title($_REQUEST['wcj-cat']);
            if (empty($this->cats)) { return; }
            echo '<ul class="wcj-dashboard-header">';
            $array_keys = array_keys($this->cats);
            foreach ($this->cats as $id => $label_info) {
              $dashboard_section = "";
              if($id == "dashboard"){
                $dashboard_section = "&section=by_category";
              } 
              echo '<li class="wcj-header-item ' . ($current_cat == $id ? 'active' : '') . '"><a
                   href="' . admin_url('admin.php?page=wc-settings&tab=' . $this->id . '&wcj-cat=' . sanitize_title($id)."".$dashboard_section) . '"
                   class="' . ($current_cat == $id ? 'current' : '') . '">' . $label_info['label'] . '</a> ' .
               (end($array_keys) == $id ? '' : '') . ' </li>';
            }
            echo '</ul>' . '<br class="clear" />'; 
          }

        /**
         * Output sections (modules) sub menu
         *
         * @version 5.5.6
         * @todo    (maybe) for case insensitive sorting: `array_multisort( array_map( 'strtolower', $menu ), $menu );` instead of `asort( $menu );` (see http://php.net/manual/en/function.asort.php)
         */
        public function output_sections_submenu()
        {
            global $current_section;
            $sections = $this->get_sections();
            $current_cat = empty($_REQUEST['wcj-cat']) ? 'dashboard' : sanitize_title($_REQUEST['wcj-cat']);
            if ('dashboard' === $current_cat) {

                // Counting modules
                $all = 0;
                $active = 0;
                foreach ($this->module_statuses as $module_status) {
                    if (isset($module_status['id']) && isset($module_status['default'])) {
                        if ('yes' === wcj_get_option($module_status['id'], $module_status['default'])) {
                            $active++;
                        } elseif (wcj_is_module_deprecated($module_status['id'], true)) {
                            continue;
                        }
                        $all++;
                    }
                }

                $sections['by_category'] = '<span><img src="'.wcj_plugin_url().'/assets/images/home.png"></span>'.__('Dashboard', 'woocommerce-jetpack');
                $sections['all_module'] = '<span><img src="'.wcj_plugin_url().'/assets/images/multiple 1.png"></span>'.__('All Module', 'woocommerce-jetpack');
                $sections['active'] = '<span><img src="'.wcj_plugin_url().'/assets/images/t-swich.png"></span>' .__('Active Modules', 'woocommerce-jetpack');
                $sections['manager'] = '<span><img src="'.wcj_plugin_url().'/assets/images/setting.png"></span>'.__('Manage Settings', 'woocommerce-jetpack');
                if (!empty($this->custom_dashboard_modules)) {
                  foreach ($this->custom_dashboard_modules as $custom_dashboard_module_id => $custom_dashboard_module_data)
                  {
                    $sections[$custom_dashboard_module_id] = $custom_dashboard_module_data['title'];
                  }
                }
                if ('' == $current_section) {
                  $current_section = 'by_category';
                }
            }
            if (!empty($this->cats[$current_cat]['all_cat_ids'])) {
              foreach ($sections as $id => $label) {
                if (!in_array($id, $this->cats[$current_cat]['all_cat_ids'])) {
                  unset($sections[$id]);
                }
              }
            }
            if (empty($sections) || 1 === count($sections)) {
               return;
            }
            foreach ($this->cats[$current_cat]['all_cat_ids'] as $key => $id) {
              if (wcj_is_module_deprecated($id, false, true)) {
                unset($this->cats[$current_cat]['all_cat_ids'][$key]);
              }
            }
            $menu = array();
            foreach ($this->cats[$current_cat]['all_cat_ids'] as $id) {
              $menu[$id] = $sections[$id];
            }
            if ('dashboard' !== $current_cat && 'pdf_invoicing' !== $current_cat) {
              asort($menu);
            }

            $menu_links = array();

            foreach ($menu as $id => $label) {
              $url = admin_url('admin.php?page=wc-settings&tab=' . $this->id . '&wcj-cat=' . $current_cat . '&section=' . sanitize_title($id));
              $menu_links[] = '<li class="wcj-sidebar-item ' . ($current_section == $id ? 'active' : '') . '"><a href="' . $url . '" class="' . ($current_section == $id ? 'current' : '') . '">' . $label . '</a></li>';
            }
            echo implode(' ', $menu_links) . '<br class="clear" />';
        }
        /**
         * get_cat_by_section
         */
        public function get_cat_by_section($section)
        {
            foreach ($this->cats as $id => $label_info) {
                if (!empty($label_info['all_cat_ids'])) {
                    if (in_array($section, $label_info['all_cat_ids'])) {
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
        public function get_sections()
        {
            return apply_filters('wcj_settings_sections', array('' => __('Dashboard', 'woocommerce-jetpack')));
        }

        /**
         * active.
         *
         * @version 2.8.0
         */
        public function active($active)
        {
            return ('yes' === $active) ? 'active' : 'inactive';
        }

        /**
         * is_dashboard_section.
         *
         * @version 5.5.6
         * @since   3.0.0
         */
        public function is_dashboard_section($current_section)
        {
            return in_array($current_section, array_merge(array('', 'all_module', 'by_category', 'active', 'manager'), array_keys($this->custom_dashboard_modules)));
        }

        /**
         * Output the settings.
         *
         * @version 5.5.6
         * @todo    (maybe) admin_notices
         */
        public function output()
        {

            global $current_section, $wcj_notice;

            if ('' != $wcj_notice) {
                echo '<div id="wcj_message" class="updated"><p><strong>' . $wcj_notice . '</strong></p></div>';
            }
            if($current_section !=='by_category'){
            $is_dashboard = $this->is_dashboard_section($current_section);

            // Deprecated message
            if ($replacement_module = wcj_is_module_deprecated($current_section)) {
                echo '<div id="wcj_message" class="error">';
                echo '<p>';
                echo '<strong>';
                echo sprintf(
                    __('Please note that current <em>%s</em> module is deprecated and will be removed in future updates. Please use <em>%s</em> module instead.', 'woocommerce-jetpack'),
                    WCJ()->modules[$current_section]->short_desc,
                    '<a href="' . admin_url('admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $replacement_module['cat'] . '&section=' . $replacement_module['module']) . '">' .
                        $replacement_module['title'] . '</a>'
                );
                echo ' <span style="color:red;">' . __('Module will be removed from the module\'s list as soon as you disable it.', 'woocommerce-jetpack') . '</span>';
                echo '</strong>';
                echo '</p>';
                echo '</div>';
            }

            // "Under development" message
            if (isset(WCJ()->modules[$current_section]->dev) && true === WCJ()->modules[$current_section]->dev) {
                echo '<div id="wcj_message" class="error">';
                echo '<p>';
                echo '<strong>';
                echo sprintf(__('Please note that <em>%s</em> module is currently under development. Until stable module version is released, options can be changed or some options can be moved to paid plugin version.', 'woocommerce-jetpack'), WCJ()->modules[$current_section]->short_desc);
                echo '</strong>';
                echo '</p>';
                echo '</div>';
            }

            if ('yes' === wcj_get_option('wcj_debug_tools_enabled', 'no') && 'yes' === wcj_get_option('wcj_debuging_enabled', 'no')) {
                // Breadcrumbs
                $breadcrumbs_html = '';
                $breadcrumbs_html .= '<p>';
                $breadcrumbs_html .= '<code>';
                $breadcrumbs_html .= __('WooCommerce', 'woocommerce-jetpack');
                $breadcrumbs_html .= ' > ';
                $breadcrumbs_html .= __('Settings', 'woocommerce-jetpack');
                $breadcrumbs_html .= ' > ';
                $breadcrumbs_html .= __('Booster', 'woocommerce-jetpack');
                $breadcrumbs_html .= ' > ';
                foreach ($this->cats as $id => $label_info) {
                    if ($this->get_cat_by_section($current_section) === $id) {
                        $breadcrumbs_html .= $label_info['label'];
                        break;
                    }
                }
                if ($is_dashboard && isset($_GET['wcj-cat']) && 'dashboard' != $_GET['wcj-cat']) {
                    $breadcrumbs_html .= $this->cats[$_GET['wcj-cat']]['label'];
                }
                if (!$is_dashboard) {
                    $breadcrumbs_html .= ' > ';
                    $sections = $this->get_sections();
                    $breadcrumbs_html .= $sections[$current_section];
                }
                $breadcrumbs_html .= '</code>';
                $breadcrumbs_html .= '</p>';
                echo $breadcrumbs_html;
            }

            $settings = $this->get_settings($current_section);

            if (!$is_dashboard) {
                WC_Admin_Settings::output_fields($settings);
            } else {
                $this->output_dashboard($current_section);
            }
        }
        }

        /**
         * output_dashboard.
         *
         * @version 5.5.6
         */
        public function output_dashboard($current_section)
        {

            if ('' == $current_section) {
                $current_section = 'by_category';
            }

            $the_settings = $this->get_settings();

            echo '<h3>' . $the_settings[0]['title'] . '</h3>';
            if (isset($this->custom_dashboard_modules[$current_section])) {
                echo '<p>' . $this->custom_dashboard_modules[$current_section]['desc'] . '</p>';
            } elseif ('manager' != $current_section) {
                echo '<p>' . $the_settings[0]['desc'] . '</p>';
            } else {
                echo '<p>' . __('This section lets you export, import or reset all Booster\'s modules settings.', 'woocommerce-jetpack') . '</p>';
            }

            if ('all_module' === $current_section) {
                $this->output_dashboard_modules($the_settings);
            } elseif ('by_category' === $current_section) {
                foreach ($this->cats as $cat_id => $cat_label_info) {
                    if ('dashboard' === $cat_id) {
                        continue;
                    }
                    if (isset($_GET['wcj-cat']) && 'dashboard' != $_GET['wcj-cat']) {
                        if ($cat_id != $_GET['wcj-cat']) {
                            continue;
                        }
                    } else {
                        echo '<h4>' . $cat_label_info['label'] . '</h4>';
                    }
                    $this->output_dashboard_modules($the_settings, $cat_id);
                }
            } elseif ('active' === $current_section) {
                $this->output_dashboard_modules($the_settings, 'active_modules_only');
            } elseif ('manager' === $current_section) {
                $table_data = array(
                    array(
                        '<button style="width:100px;" class="button-primary" type="submit" name="booster_export_settings">' . __('Export', 'woocommerce-jetpack') . '</button>',
                        '<em>' . __('Export all Booster\'s options to a file.', 'woocommerce-jetpack') . '</em>',
                    ),
                    array(
                        '<button style="width:100px;" class="button-primary" type="submit" name="booster_import_settings">' . __('Import', 'woocommerce-jetpack') . '</button>' .
                            ' ' . '<input type="file" name="booster_import_settings_file">',
                        '<em>' . __('Import all Booster\'s options from a file.', 'woocommerce-jetpack') . '</em>',
                    ),
                    array(
                        '<button style="width:100px;" class="button-primary" type="submit" name="booster_reset_settings"' .
                            wcj_get_js_confirmation(__('This will reset settings to defaults for all Booster modules. Are you sure?', 'woocommerce-jetpack')) . '>' .
                            __('Reset', 'woocommerce-jetpack') . '</button>',
                        '<em>' . __('Reset all Booster\'s options.', 'woocommerce-jetpack') . '</em>',
                    ),
                    array(
                        '<button style="width:100px;" class="button-primary" type="submit" name="booster_reset_settings_meta"' .
                            wcj_get_js_confirmation(__('This will delete all Booster meta. Are you sure?', 'woocommerce-jetpack')) . '>' .
                            __('Reset meta', 'woocommerce-jetpack') . '</button>',
                        '<em>' . __('Reset all Booster\'s meta.', 'woocommerce-jetpack') . '</em>',
                    ),
                );
                $manager_settings = $this->get_manager_settings();
                foreach ($manager_settings as $manager_settings_field) {
                    $table_data[] = array(
                        '<label for="' . $manager_settings_field['id'] . '">' .
                            '<input name="' . $manager_settings_field['id'] . '" id="' . $manager_settings_field['id'] . '" type="' . $manager_settings_field['type'] . '"' .
                            ' class="" value="1" ' . checked(wcj_get_option($manager_settings_field['id'], $manager_settings_field['default']), 'yes', false) . '>' .
                            ' ' . '<strong>' . $manager_settings_field['title'] . '</strong>' .
                            '</label>',
                        '<em>' . $manager_settings_field['desc'] . '</em>',
                    );
                }
                echo wcj_get_table_html($table_data, array('table_class' => 'widefat striped', 'table_heading_type' => 'none'));
            }

           if (isset($this->custom_dashboard_modules[$current_section])) {
          $table_data = array();
          foreach ($this->custom_dashboard_modules[$current_section]['settings'] as $_settings) {
            $table_data[] = array(
            $_settings['title'],
              '<label class="'.$_settings['id'].'_label" for="' . $_settings['id'] . '">' .
              '<input name="' . $_settings['id'] .
              '" id="' . $_settings['id'] .
              '" type="' . $_settings['type'] .
              '" class="' . $_settings['class'] .
              '" value="' . wcj_get_option($_settings['id'], $_settings['default']) . '">' . ' ' . '<em>' . $_settings['desc'] . '</em>' .'</label>',
            );
          }
          echo wcj_get_table_html($table_data, array('table_class' => 'widefat striped', 'table_heading_type' => 'vertical'));
        }

            $plugin_data = get_plugin_data(WCJ_PLUGIN_FILE);
            $plugin_title = (isset($plugin_data['Name']) ? '[' . $plugin_data['Name'] . '] ' : '');
            echo '<p style="text-align:right;color:gray;font-size:x-small;font-style:italic;">' . $plugin_title .
                __('Version', 'woocommerce-jetpack') . ': ' . wcj_get_option(WCJ_VERSION_OPTION, 'N/A') . '</p>';
        }

        /**
         * compare_for_usort.
         */
        private function compare_for_usort($a, $b)
        {
            return strcmp($a['title'], $b['title']);
        }

        /**
         * output_dashboard_modules.
         *
         * @version 3.3.0
         */
        public function output_dashboard_modules($settings, $cat_id = '')
        {
        ?>
            <div class="wcj-active-modules">
            <table class="wp-list-table widefat striped plugins">
                <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'woocommerce-jetpack'); ?></label><input id="cb-select-all-1" type="checkbox" style="margin-top:15px;"></th>
                        <th scope="col" id="name" class="manage-column column-name" style="">
                            <?php _e('Module', 'woocommerce-jetpack'); ?></th>
                        <th scope="col" id="description" class="manage-column column-description" style="">
                            <?php _e('Description', 'woocommerce-jetpack'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'woocommerce-jetpack'); ?></label><input id="cb-select-all-2" type="checkbox" style="margin-top:15px;"></th>
                        <th scope="col" class="manage-column column-name" style=""><?php _e('Module', 'woocommerce-jetpack'); ?>
                        </th>
                        <th scope="col" class="manage-column column-description" style="">
                            <?php _e('Description', 'woocommerce-jetpack'); ?></th>
                    </tr>
                </tfoot>
                <tbody id="the-list"><?php
                                        $html = '';
                                        usort($settings, array($this, 'compare_for_usort'));
                                        $total_modules = 0;
                                        foreach ($settings as $the_feature) {
                                            if ('checkbox' !== $the_feature['type']) {
                                                continue;
                                            }
                                            $section = $the_feature['id'];
                                            $section = str_replace('wcj_', '', $section);
                                            $section = str_replace('_enabled', '', $section);
                                            if (wcj_is_module_deprecated($section, false, true)) {
                                                continue;
                                            }
                                            if ('' != $cat_id) {
                                                if ('active_modules_only' === $cat_id) {
                                                    if ('no' === wcj_get_option($the_feature['id'], 'no')) {
                                                        continue;
                                                    }
                                                } elseif ($cat_id != $this->get_cat_by_section($section)) {
                                                    continue;
                                                }
                                            }
                                            $total_modules++;
                                            $html .= '<tr id="' . $the_feature['id'] . '" ' . 'class="' . $this->active(wcj_get_option($the_feature['id'])) . '">';
                                            $html .= '<th scope="row" class="check-column">';
                                            $html .= '<label class="screen-reader-text" for="' . $the_feature['id'] . '">' . $the_feature['desc'] . '</label>';
                                            $html .= '<input type="checkbox" name="' . $the_feature['id'] . '" value="1" id="' . $the_feature['id'] . '" ' . checked(wcj_get_option($the_feature['id']), 'yes', false) . '>';
                                            $html .= '</th>';
                                            $html .= '<td class="plugin-title">' . '<strong>' . $the_feature['title'] . '</strong>';
                                            $html .= '<div class="row-actions visible">';
                                            $html .= '<span class="0"><a href="' . admin_url() . 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $this->get_cat_by_section($section) . '&section=' . $section . '">' . __('Settings', 'woocommerce') . '</a></span>';
                                            if (isset($the_feature['wcj_link']) && '' != $the_feature['wcj_link']) {
                                                $html .= ' | <span class="0"><a href="' . $the_feature['wcj_link'] . '?utm_source=module_documentation&utm_medium=dashboard_link&utm_campaign=booster_documentation" target="_blank">' . __('Documentation', 'woocommerce') . '</a></span>';
                                            }
                                            $html .= '</div>';
                                            $html .= '</td>';
                                            $html .= '<td class="column-description desc">';
                                            $html .= '<div class="plugin-description"><p>' . ((isset($the_feature['wcj_desc'])) ? $the_feature['wcj_desc'] : $the_feature['desc_tip']) . '</p></div>';
                                            $html .= '</td>';
                                            $html .= '</tr>';
                                        }
                                        echo $html;
                                        if (0 == $total_modules && 'active_modules_only' === $cat_id) {
                                            echo '<tr><td colspan="3">' . '<em>' . __('No active modules found.', 'woocommerce-jetpack') . '</em>' . '</td></tr>';
                                        }
                                        ?></tbody>
            </table>
           <p style="color:#000;font-size:12px;font-style:italic;">
                <?php echo __('Total Modules:') . ' ' . $total_modules; ?>
          </p>
        </div><?php
            }

            /**
             * Save settings.
             *
             * @version 5.3.8
             */
            function save()
            {
                global $current_section;
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::save_fields($settings);
                $this->disable_autoload_options_from_section($settings);
                add_action('admin_notices', array($this, 'booster_message_global'));
                do_action('woojetpack_after_settings_save', $this->get_sections(), $current_section);
            }

            /**
             * disable_autoload_options.
             *
             * @version 5.3.3
             * @since   5.3.3
             *
             * @param $settings
             */
            public function disable_autoload_options_from_section($settings)
            {
                $fields = wp_list_filter($settings, array('autoload' => false));
                $fields = wp_list_filter($fields, array('type' => 'title'), 'NOT');
                $fields = wp_list_filter($fields, array('type' => 'sectionend'), 'NOT');
                $field_ids = wp_list_pluck($fields, 'id');
                $fields_ids_str = '\'' . implode('\',\'', $field_ids) . '\'';
                global $wpdb;
                $sql = "
			UPDATE {$wpdb->options} SET autoload = 'no'
			WHERE option_name IN ({$fields_ids_str}) AND autoload != 'no'
			";
                $wpdb->query($sql);
            }

            /**
             * booster_message_global.
             *
             * @version 3.6.0
             * @since   3.6.0
             */
            public function booster_message_global()
            {
                if ('' != ($message = apply_filters('booster_message', '', 'global'))) {
                    echo $message;
                }
            }

            /**
             * get_manager_settings.
             *
             * @version 3.5.0
             * @since   2.6.0
             * @return  array
             */
            public function get_manager_settings()
            {
                return array(
                    array(
                        'title' => __('Autoload Booster\'s Options', 'woocommerce-jetpack'),
                        'type' => 'checkbox',
                        'desc' => __('Choose if you want Booster\'s options to be autoloaded when calling add_option. After saving this option, you need to Reset all Booster\'s settings. Leave default value (i.e. Enabled) if not sure.', 'woocommerce-jetpack'),
                        'id' => 'wcj_autoload_options',
                        'default' => 'yes',
                    ),
                    array(
                        'title' => __('Load Modules on Init Hook', 'woocommerce-jetpack'),
                        'type' => 'checkbox',
                        'desc' => __('Choose if you want to load Booster Modules on Init hook.', 'woocommerce-jetpack') . ' ' . __('It will load the locale appropriately if users change it from the profile page.', 'woocommerce-jetpack'),
                        'id' => 'wcj_load_modules_on_init',
                        'default' => 'no',
                    ),
                    array(
                        'title' => __('Use List Instead of Comma Separated Text for Products in Settings', 'woocommerce-jetpack'),
                        'type' => 'checkbox',
                        'desc' => sprintf(__('Supported modules: %s.', 'woocommerce-jetpack'), implode(', ', array(
                            __('Gateways per Product or Category', 'woocommerce-jetpack'),
                            __('Global Discount', 'woocommerce-jetpack'),
                            __('Product Info', 'woocommerce-jetpack'),
                            __('Product Input Fields', 'woocommerce-jetpack'),
                            __('Products XML', 'woocommerce-jetpack'),
                            __('Related Products', 'woocommerce-jetpack'),
                        ))),
                        'id' => 'wcj_list_for_products',
                        'default' => 'yes',
                    ),
                    array(
                        'title' => __('Use List Instead of Comma Separated Text for Products Categories in Settings', 'woocommerce-jetpack'),
                        'type' => 'checkbox',
                        'desc' => sprintf(__('Supported modules: %s.', 'woocommerce-jetpack'), implode(', ', array(
                            __('Product Info', 'woocommerce-jetpack'),
                        ))),
                        'id' => 'wcj_list_for_products_cats',
                        'default' => 'yes',
                    ),
                    array(
                        'title' => __('Use List Instead of Comma Separated Text for Products Tags in Settings', 'woocommerce-jetpack'),
                        'type' => 'checkbox',
                        'desc' => sprintf(__('Supported modules: %s.', 'woocommerce-jetpack'), implode(', ', array(
                            __('Product Info', 'woocommerce-jetpack'),
                        ))),
                        'id' => 'wcj_list_for_products_tags',
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
            public function get_settings($current_section = '')
            {
                if (!$this->is_dashboard_section($current_section)) {
                    return apply_filters('wcj_settings_' . $current_section, array());
                } elseif ('manager' === $current_section) {
                    return $this->get_manager_settings();
                } elseif (isset($this->custom_dashboard_modules[$current_section])) {
                    return $this->custom_dashboard_modules[$current_section]['settings'];
                } else {
                    $cat_id = (isset($_GET['wcj-cat']) && '' != $_GET['wcj-cat']) ? $_GET['wcj-cat'] : 'dashboard';
                    $settings[] = array(
                        'title' => __('Booster for WooCommerce', 'woocommerce-jetpack') . ' - ' . $this->cats[$cat_id]['label'],
                        'type' => 'title',
                        'desc' => $this->cats[$cat_id]['desc'],
                        'id' => 'wcj_' . $cat_id . '_options',
                    );
                    if ('dashboard' === $cat_id) {
                        $settings = array_merge($settings, $this->module_statuses);
                    } else {
                        $cat_module_statuses = array();
                        foreach ($this->module_statuses as $module_status) {
                            $section = $module_status['id'];
                            $section = str_replace('wcj_', '', $section);
                            $section = str_replace('_enabled', '', $section);
                            if ($cat_id === $this->get_cat_by_section($section)) {
                                $cat_module_statuses[] = $module_status;
                            }
                        }
                        $settings = array_merge($settings, $cat_module_statuses);
                    }
                    $settings[] = array(
                        'type' => 'sectionend',
                        'id' => 'wcj_' . $cat_id . '_options',
                        'title' => '', // for usort
                    );
                    return $settings;
                }
            }

            /**
             * add_module_statuses
             */
            public function add_module_statuses($statuses)
            {
                $this->module_statuses = $statuses;
            }

    function version_details (){

      $file=wcj_plugin_path()."/version-details.txt";
      if (file_exists($file)) {

        $doc=file_get_contents($file);
        $line=explode("\n",$doc);
        foreach($line as $newline){
            echo $newline.'<br>';
        }
      }
      
    }


    /**
     * dasboard_menu
     *
     * @version 5.5.6
     * @return  array
     */

    public function dasboard_menu (){ 
        global $current_section;
        $_section = isset( $_GET['section'] ) ? $_GET['section'] : "" ;
        $_wcj_cat = isset( $_GET['wcj-cat'] ) ? $_GET['wcj-cat'] : "" ;
        $_wcj_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : "" ;
        if($_wcj_tab == "jetpack" && ($_section=="" || $_section=="by_category") && ($_wcj_cat=="" || $_wcj_cat=="dashboard")){
          ?><style>
            button.button-primary.woocommerce-save-button {
              display: none;
            }
          </style> <?php
        }
        ?>
        <div class="wcj-main-container dashboard">
          <div class="wcj-body">
            <section class="wcj-sidebar">
              <div class="wcj-dashboard-logo">
                <img src="<?php echo wcj_plugin_url().'/assets/images/logo.png'; ?>">
              </div>
              <div class="wcj-nav-sidebar">
                <ul>
                  <?php echo $this->output_sections_submenu(); ?>
                  <a href="https://booster.io/contact-us/" class="wcj-button" target="_blank"><?php _e('Need Help?', 'woocommerce-jetpack');?><span><img src="<?php echo wcj_plugin_url().'/assets/images/need-help.png'; ?>"></span></a>
                </ul>
              </div>
            </section>
            <section class="wcj-body-container wcj-setting-jetpack">
              <header>
                <?php echo $this->output_cats_submenu(); ?>
              </header>
              <div class="wcj-setting-jetpack-body <?php if($_section === 'pdf_invoicing_extra_columns'){ echo 'wcj_invoice_pdf_dtl'; }?>" style="<?php if($_section === 'by_category'){ echo 'display:none;'; } if($_wcj_tab == "jetpack" && $_wcj_cat==""){ echo "padding: 0px;"; }  ?>">
                <?php if($current_section === 'by_category') { ?>
              </div>
              <div class="wcj-body-part">
                <div class="wcj-sub-cnt-top">
                  <div class="wcj-sub-cnt-top-left">
                    <div class="wcj-tit">
                      <h1><?php _e('My Account', 'woocommerce-jetpack');?></h1>
                    </div>
                  </div>
                  <div class="wcj-btn-main">
                    <a href="https://booster.io/buy-booster/" class="wcj-button" target="_blank"><?php _e('Get Booster Plus', 'woocommerce-jetpack');?></a>
                  </div>
                </div>
                <div class="wcj-body-sec-part-main">
                  <div class="wcj-body-parts wcj-body-left">
                    <div class="wcj-info-section wcj-box">
                      <div class="wcj-tit">
                        <h3><?php _e('Getting Started', 'woocommerce-jetpack');?></h3>
                      </div>
                      <ul class="wcj-dashboard-info-ul">
                        <li><a target="_blank" href="https://booster.io/docs/dashboard"><?php _e('Dashboard', 'woocommerce-jetpack');?></a></li>
                        <li><a target="_blank" href="https://booster.io/docs/how-to-navigate-through-booster-categories-and-modules-smoothly"><?php _e('Navigating Categories', 'woocommerce-jetpack');?></a></li>
                        <li><a target="_blank" href="https://booster.io/docs/how-to-get-started-with-booster"><?php _e('How to get started with booster', 'woocommerce-jetpack');?></a></li>
                      </ul>
                    </div>
                    <div class="wcj-faq-section wcj-box">
                      <div class="wcj-tit">
                        <h3><?php _e('Frequently Asked Questions', 'woocommerce-jetpack');?></h3>
                      </div>
                      <div class="wcj-faq-body">
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('Do I need to have coding skills to use Booster Plus?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e('Absolutely not. You can configure pretty much everything Booster Plus has to offer without any coding knowledge.', 'woocommerce-jetpack');?></p>
                          </div>
                        </div>
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('Will Booster Plus slow down my website?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e('Absolutely not. Booster Plus is carefully built with performance in mind.', 'woocommerce-jetpack');?></p>
                          </div>
                        </div>
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('Do you offer refunds?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e('If you are not completely satisfied with Booster Plus within the fist 30 days, you can request a refund and we will give you 100% of your money back – no questions asked.', 'woocommerce-jetpack');?></p>
                          </div>
                        </div>
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('Can I use Booster Plus on client sites?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e('Yes, you can use Booster Plus on client sites. You can purchase the multiple sites license of Booster Plus.', 'woocommerce-jetpack');?></p>
                          </div>
                        </div>
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('Do you have an affiliate program?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e('Yes, We do have an affiliate program. ', 'woocommerce-jetpack');?><a href="https://booster.io/affiliate-program/" target="_blannk"><?php _e('Click here', 'woocommerce-jetpack');?></a><?php _e(' for the details.', 'woocommerce-jetpack');?></p>
                          </div>
                        </div>
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('Why should I choose the Booster Plus suite over other individual plugins?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e("Oh, that's an easy one! Implementing just a few modules from the Booster Plus suite is more cost-effective than using dozens of individual plugins often priced at $15-$30 each. On top of that, stacking your site with a lot of different plugins can make it bloated and slow. What's worse, all those individual plugins don't always play nice together. But Booster Plus is the solution to all that nonsense. The Booster Plus suite features over 100 compatible modules that allow you to add custom features and functionality to your WooCommerce site easily.", "woocommerce-jetpack");?></p>
                          </div>
                        </div>
                        <div class="wcj-faq-sing-box">
                          <div class="wcj-accordion">
                            <h6><?php _e('What features does Booster Plus have?', 'woocommerce-jetpack');?></h6>
                            <span><img src="<?php echo wcj_plugin_url().'/assets/images/down-arw.png'; ?>"></span>
                          </div>
                          <div class="wcj-panel">
                            <p><?php _e('You can see all the features at ', 'woocommerce-jetpack');?><a href="https://booster.io/about/" target="_blank"><?php _e('About Booster', 'woocommerce-jetpack');?></a><?php _e(' page.', 'woocommerce-jetpack');?></p>
                          </div>
                        </div>
                        <div class="wcj-additional-que">
                          <h6><?php _e('Still have a question?', 'woocommerce-jetpack');?></h6>
                          <a href="https://booster.io/contact-us/" class="wcj-button" target="_blank"><?php _e('CONTACT BOOSTER SUPPORT', 'woocommerce-jetpack');?></a>
                        </div>
                    </div>
                  </div>
                  <div class="wcj-social-connect wcj-box">
                    <div class="wcj-tit">
                      <h3><?php _e('Connect with Booster', 'woocommerce-jetpack');?></h3>
                    </div>
                    <div class="wcj-socialmedia-connect">
                      <div class="wcj-btn-main">
                        <a href="https://booster.io/" class="wcj-button" target="_blank"><?php _e('BOOSTER WEBSITE', 'woocommerce-jetpack');?></a>
                      </div>
                      <ul class="wcj-social-icn">
                        <li><a href="https://www.youtube.com/channel/UCVQg0c4XIirUI3UnGoX9HVg?sub_confirmation=1"><img src="<?php echo wcj_plugin_url().'/assets/images/YouTube.png'; ?>"></a></li>
                        <li><a href="https://www.facebook.com/booster.for.woocommerce"><img src="<?php echo wcj_plugin_url().'/assets/images/Facebook.png'; ?>"></a></li>
                        <li><a href="https://twitter.com/intent/follow?screen_name=BoosterForWoo"><img src="<?php echo wcj_plugin_url().'/assets/images/Twitter.png'; ?>"></a></li>
                        <li><a href="https://www.linkedin.com/company/booster-for-woocommerce"><img src="<?php echo wcj_plugin_url().'/assets/images/Linkedin.png'; ?>"></a></li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="wcj-body-parts wcj-body-right">
                  <div class="wcj-partitions action-section">
                    <div class="wcj-tit">
                      <h3><?php _e('Actions', 'woocommerce-jetpack');?></h3>
                    </div>
                    <div class="wcj-action-sub-part">
                      <div class="wcj-action-sub-sing-box">
                        <div class="wcj-action-single-bx">
                          <div class="wcj-action-sub-sing-icon">
                            <img src="<?php echo wcj_plugin_url().'/assets/images/ArrowSquareIn.png'; ?>">
                          </div>
                          <div class="wcj-actions-part">
                            <input type="file" name="booster_import_settings_file">
                            <h6><button style="width:100px;" class="button-primary" type="submit" name="booster_import_settings"><?php _e('Import', 'woocommerce-jetpack');?></button></h6>
                            <p><?php _e('Import Booster options', 'woocommerce-jetpack');?></p>
                          </div>
                          </div>
                          <div class="wcj-action-single-bx">
                            <div class="wcj-action-sub-sing-icon">
                              <img src="<?php echo wcj_plugin_url().'/assets/images/Export.png'; ?>">
                            </div>
                            <div class="wcj-actions-part">
                              <h6><button style="width:100px;" class="button-primary wcj-export-btn" type="submit" name="booster_export_settings"><?php _e('Export', 'woocommerce-jetpack');?></button></h6>
                              <p><?php _e('Export Booster options', 'woocommerce-jetpack');?></p>
                            </div>
                          </div>
                      </div>
                      <div class="wcj-action-sub-sing-box">
                        <div class="wcj-action-single-bx">
                          <div class="wcj-action-sub-sing-icon">
                            <img src="<?php echo wcj_plugin_url().'/assets/images/Repeat.png'; ?>">
                          </div>
                          <div class="wcj-actions-part">
                            <h6><button style="width:100px;" class="button-primary" type="submit" name="booster_reset_settings" onclick="return confirm('This will reset settings to defaults for all Booster modules. Are you sure?')"><?php _e('Reset', 'woocommerce-jetpack');?></button></h6>
                            <p><?php _e("Reset all Booster's options", "woocommerce-jetpack");?></p>
                          </div>
                          </div>
                          <div class="wcj-action-single-bx">
                            <div class="wcj-action-sub-sing-icon">
                              <img src="<?php echo wcj_plugin_url().'/assets/images/RepeatOnce.png'; ?>">
                            </div>
                            <div class="wcj-actions-part">
                              <h6><button style="width:100px;" class="button-primary" type="submit" name="booster_reset_settings_meta" onclick="return confirm('This will delete all Booster meta. Are you sure?')"><?php _e('Reset meta', 'woocommerce-jetpack');?></button></h6>
                              <p><?php _e("Reset all Booster's meta", "woocommerce-jetpack");?></p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="wcj-partitions documentation-section">
                      <div class="wcj-tit">
                        <h3><?php _e('Documentation', 'woocommerce-jetpack');?></h3>
                      </div>
                      <div class="wcj-documentation-dtl">
                        <div class="wcj-documentation-icon">
                          <img src="<?php echo wcj_plugin_url().'/assets/images/documentation.png'; ?>">
                        </div>
                        <div class="wcj-documentation-sm-rh-dtl">
                          <p><?php _e('Here you can find all documentation of Booster', 'woocommerce-jetpack');?></p>
                          <div class="wcj-btn-main">
                            <a href="https://booster.io/docs/" class="wcj-button" target="_blank"><?php _e('See Documentation', 'woocommerce-jetpack');?></a>
                          </div>
                        </div>
                    </div>
                  </div>
                  <div class="wcj-partitions updates-section">
                    <div class="wcj-tit">
                      <h3><?php _e('Latest updates', 'woocommerce-jetpack');?></h3>
                    </div>
                    <div class="wcj-partitions-sub-dtl">
                      <h6><?php _e('Version ', 'woocommerce-jetpack');?><?php echo WCJ()->version; ?></h6>
                      <ul class="wcj-updates-ul">
                        <li><?php $this->version_details() ?></li>
                      </ul>
                      <div class="wcj-btn-main">
                        <a href="https://booster.io/changelog/" class="wcj-button" target="_blank"><?php _e('SEE MORE', 'woocommerce-jetpack');?></a>
                      </div>
                    </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
   </div>
   <div class="wcj-footer">
      <div class="wcj-review-footer">
        <p><?php _e('Please rate ', 'woocommerce-jetpack');?><strong><?php _e('Booster for Woocommerce', 'woocommerce-jetpack');?></strong>
          <span class="wcj-woo-star">
            <img src="<?php echo wcj_plugin_url().'/assets/images/star.png'; ?>">
            <img src="<?php echo wcj_plugin_url().'/assets/images/star.png'; ?>">
            <img src="<?php echo wcj_plugin_url().'/assets/images/star.png'; ?>">
            <img src="<?php echo wcj_plugin_url().'/assets/images/star.png'; ?>">
            <img src="<?php echo wcj_plugin_url().'/assets/images/star.png'; ?>">
          </span>
          <strong><a href="https://wordpress.org/support/plugin/woocommerce-jetpack/reviews/?rate=5#new-post" target="_blank"><?php _e('WordPress.org', 'woocommerce-jetpack');?></a></strong><?php _e(' to help us spread the word. Thank you from Booster team!', 'woocommerce-jetpack');?>
        </p>
      </div>
      <div class="wcj-review-footer wcj-premium-features-footer">
        <h6><?php _e('Upgrade today to unlock these popular premium features:', 'woocommerce-jetpack');?></h6>
        <ul>
        <li><strong><?php _e('+ PDF Invoices and Packing slips –', 'woocommerce-jetpack');?></strong><?php _e('Add ability to create Proforma Invoices, Credit Notes and Packaging slips', 'woocommerce-jetpack');?></li>
          <li><strong><?php _e('+ Empty Cart –', 'woocommerce-jetpack');?></strong><?php _e('customize empty cart button text, different button positions on cart page', 'woocommerce-jetpack');?></li>
          <li><strong><?php _e('+ Cart and checkout –', 'woocommerce-jetpack');?></strong><?php _e('add multiple – custom fields, custom info blocks, check out file uploads', 'woocommerce-jetpack');?></li>
          <li><strong><?php _e('+ Mini cart –', 'woocommerce-jetpack');?></strong><?php _e('More custom information options', 'woocommerce-jetpack');?> </li>
          <li><strong><?php _e('+ Prices and currencies –', 'woocommerce-jetpack');?></strong><?php _e('add more unlimited number of currencies to WooCommerce', 'woocommerce-jetpack');?> </li>
          <li><strong><?php _e('+ Export options –', 'woocommerce-jetpack');?></strong><?php _e('more fields enabled', 'woocommerce-jetpack');?> </li>
          <li><strong><?php _e('+ Add to cart –', 'woocommerce-jetpack');?></strong><?php _e('customize add to cart messages, Button labels - multiple category groups allowed+ +', 'woocommerce-jetpack');?></li>
          <li><?php _e('+ More configuration options for payments and shipping', 'woocommerce-jetpack');?></li>
        </ul>
        <div class="wcj-btn-main">
          <a href="https://booster.io/buy-booster/" class="wcj-button" target="_blank"><?php _e('Upgrade to Booster Plus', 'woocommerce-jetpack');?></a>
        </div>
      </div>
     
   </div>
</div>
<?php
    }
  }



  

}

endif;

return new WC_Settings_Jetpack();
