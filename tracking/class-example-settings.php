<?php
/**
 * Discussion Board admin class
 *
 * @package Booster_For_WooCommerce/tracking

// this file is used to demonstrate how to add a setting to your own theme
// or plugin that will allow the user to opt in or out
// of tracking after they've made their initial choice on installation.

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin admin class.
 */

if ( ! class_exists( 'PUT_Example_Settings' ) ) { // Don't initialise if there's already a Discussion Board activated.

		/**
		 * PUT_Example_Settings
		 *
		 * @since 1.0.0
		 */
	class PUT_Example_Settings {
		/**
		 * Construct
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
		}

		/**
		 * Initialize the class and start calling our hooks and filters.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'add_settings_submenu' ) );
			add_action( 'admin_init', array( $this, 'register_options_init' ) );
			add_action( 'admin_init', array( $this, 'save_registered_setting' ) );
		}

		/**
		 * We save this artificially to let the tracker know that we're allowed to export this option's data
		 */
		public function save_registered_setting() {
			$options                              = get_option( 'wisdom_example_options_settings' );
			$options['wisdom_registered_setting'] = 1;
			update_option( 'wisdom_example_options_settings', $options );
		}

		/**
		 * Add_settings_submenu
		 */
		public function add_settings_submenu() {
			add_submenu_page( 'options-general.php', __( 'Example Settings', 'plugin-usage-tracker' ), __( 'Example Settings', 'plugin-usage-tracker' ), 'manage_options', 'example-settings-page', array( $this, 'options_page' ) );
		}
		/**
		 * Register_options_init
		 */
		public function register_options_init() {

			register_setting( 'wisdom_example_options', 'wisdom_example_options_settings' );

			add_settings_section(
				'wisdom_example_options_section',
				__( 'Example Settings', 'plugin-usage-tracker' ),
				array( $this, 'example_settings_section_callback' ),
				'wisdom_example_options'
			);
			add_settings_field(
				'text_field_example',
				__( 'Text field:', 'plugin-usage-tracker' ),
				array( $this, 'text_field_example_render' ),
				'wisdom_example_options',
				'wisdom_example_options_section'
			);
			add_settings_field(
				'checkbox_example',
				__( 'Checkbox', 'plugin-usage-tracker' ),
				array( $this, 'checkbox_example_render' ),
				'wisdom_example_options',
				'wisdom_example_options_section'
			);
			add_settings_field(
				'select_example',
				__( 'Select', 'plugin-usage-tracker' ),
				array( $this, 'select_example_render' ),
				'wisdom_example_options',
				'wisdom_example_options_section'
			);
			add_settings_field(
				'wisdom_opt_out',
				__( 'Opt out', 'plugin-usage-tracker' ),
				array( $this, 'opt_out_example_render' ),
				'wisdom_example_options',
				'wisdom_example_options_section'
			);

			// Set default options.
			$options = get_option( 'wisdom_example_options_settings' );
			if ( false === $options ) {
				// Get defaults.
				$defaults = $this->get_default_options_settings();
				update_option( 'wisdom_example_options_settings', $defaults );
			}

		}
		/**
		 * Get_default_options_settings
		 */
		public function get_default_options_settings() {
			$defaults = array(
				'text_field_example'        => __( 'Default setting', 'plugin-usage-tracker' ),
				'checkbox_example'          => 1,
				'select_example'            => 'option-1',
				'wisdom_opt_out'            => '',
				'wisdom_registered_setting' => 1, // For plugin-usage-tracker.
			);
			return $defaults;
		}
		/**
		 * Text_field_example_render
		 */
		public function text_field_example_render() {
			$options = get_option( 'wisdom_example_options_settings' );
			$value   = '';
			if ( isset( $options['text_field_example'] ) ) {
				$value = $options['text_field_example'];
			}
			?>
			<input type='text' name='wisdom_example_options_settings[text_field_example]' value="<?php echo wp_kses_post( $value ); ?>" />
			<?php
		}
		/**
		 * Checkbox_example_render
		 */
		public function checkbox_example_render() {
			$options = get_option( 'wisdom_example_options_settings' );
			?>
			<input type='checkbox' name='wisdom_example_options_settings[checkbox_example]' <?php checked( ! empty( $options['checkbox_example'] ), 1 ); ?> value='1'>
			<?php
		}
		/**
		 * Opt_out_example_render
		 */
		public function opt_out_example_render() {
			$options = get_option( 'wisdom_example_options_settings' );
			?>
			<input type='checkbox' name='wisdom_example_options_settings[wisdom_opt_out]' <?php checked( ! empty( $options['wisdom_opt_out'] ), 1 ); ?> value='1'>
			<p class="description"><?php wp_kses_post( 'You previously opted in to sending tracking details. You can change that setting here.', 'plugin-usage-tracker' ); ?></p>
			<?php
		}
		/**
		 * Select_example_render
		 */
		public function select_example_render() {
			$options = get_option( 'wisdom_example_options_settings' );
			$value   = '';
			if ( isset( $options['select_example'] ) ) {
				$value = $options['select_example'];
			}
			?>
			<select name='wisdom_example_options_settings[select_example]'>
				<option value="option-1" <?php selected( esc_attr( $value ), 'option-1' ); ?>><?php wp_kses_post( 'Option 1', 'plugin-usage-tracker' ); ?></option>
				<option value="option-2" <?php selected( esc_attr( $value ), 'option-2' ); ?>><?php wp_kses_post( 'Option 2', 'plugin-usage-tracker' ); ?></option>
				<option value="option-3" <?php selected( esc_attr( $value ), 'option-3' ); ?>><?php wp_kses_post( 'Option 3', 'plugin-usage-tracker' ); ?></option>
			</select>
			<?php
		}
		/**
		 * Example_settings_section_callback
		 */
		public function example_settings_section_callback() {
			echo '<p>' . wp_kses_post( 'These settings are for example only.', 'plugin-usage-tracker' ) . '</p>';
		}
		/**
		 * Options_page
		 */
		public function options_page() {
			?>

			<div class="wrap">	
				<form action='options.php' method='post'>
					<?php
					settings_fields( 'wisdom_example_options' );
					do_settings_sections( 'wisdom_example_options' );
					submit_button();
					?>
				</form>
			</div>
			<?php
		}
	}

}

$put_example_settings = new PUT_Example_Settings();
$put_example_settings->init();
