<?php
/**
 * Booster for WooCommerce - Module - Agent Ready Product Answers (Lite)
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Agent_Ready_Answers_Lite' ) ) :
	/**
	 * WCJ_Agent_Ready_Answers_Lite.
	 */
	class WCJ_Agent_Ready_Answers_Lite extends WCJ_Module {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id         = 'agent_ready_answers_lite';
			$this->short_desc = __( 'Product FAQ (Lite)', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add one FAQ per product with FAQPage JSON-LD schema. Upgrade to Elite for unlimited FAQs, Facts Table, Answer Chips, and agent REST feed.', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add unlimited FAQs, Facts Table, Answer Chips, and agent REST feed with Booster Elite.', 'woocommerce-jetpack' );
			$this->link_slug  = 'agent-ready-product-answers';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( $this->is_elite_active() ) {
					add_action( 'admin_notices', array( $this, 'elite_active_notice' ) );
					return;
				}

				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

				add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_faq_section' ), 25 );

				add_action( 'wp_head', array( $this, 'output_faqpage_jsonld' ) );

				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
			}
		}

		/**
		 * Check if Elite module is active
		 */
		private function is_elite_active() {
			return class_exists( 'WCJ_Agent_Ready_Answers' ) && 
				   wcj_get_option( 'wcj_agent_ready_answers_enabled', 'no' ) === 'yes';
		}

		/**
		 * Elite active notice
		 */
		public function elite_active_notice() {
			echo '<div class="notice notice-info"><p>' . 
				 __( 'Agent-Ready Product Answers (Elite) is active. The Lite version features are disabled.', 'woocommerce-jetpack' ) . 
				 '</p></div>';
		}

		/**
		 * Add meta box
		 */
		public function add_meta_box() {
			add_meta_box(
				'wcj-agent-ready-answers-lite',
				__( 'Product FAQ (Lite)', 'woocommerce-jetpack' ),
				array( $this, 'create_meta_box' ),
				'product',
				'normal',
				'high'
			);
		}

		/**
		 * Create meta box content
		 */
		public function create_meta_box() {
			$current_post_id = get_the_ID();
			$faq_data = get_post_meta( $current_post_id, '_wcj_ara_faq_lite', true );
			
			wp_nonce_field( 'wcj_ara_lite_meta_box', 'wcj_ara_lite_nonce' );
			
			echo '<table class="widefat striped">';
			echo '<tr>';
			echo '<th style="width:20%;">' . __( 'Question', 'woocommerce-jetpack' ) . '</th>';
			echo '<td><input type="text" name="wcj_ara_faq_question" value="' . esc_attr( $faq_data['question'] ?? '' ) . '" style="width:100%;" /></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Short Answer', 'woocommerce-jetpack' ) . '<br><small>' . __( '(≤160 chars)', 'woocommerce-jetpack' ) . '</small></th>';
			echo '<td><textarea name="wcj_ara_faq_short_answer" maxlength="160" style="width:100%;height:60px;" placeholder="' . esc_attr__( 'Brief answer for quick display', 'woocommerce-jetpack' ) . '">' . esc_textarea( $faq_data['short_answer'] ?? '' ) . '</textarea>';
			echo '<div class="wcj-ara-char-counter"><span id="wcj-ara-short-counter">0</span>/160</div></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Long Answer', 'woocommerce-jetpack' ) . '</th>';
			echo '<td><textarea name="wcj_ara_faq_long_answer" style="width:100%;height:120px;" placeholder="' . esc_attr__( 'Detailed answer for FAQ section', 'woocommerce-jetpack' ) . '">' . esc_textarea( $faq_data['long_answer'] ?? '' ) . '</textarea></td>';
			echo '</tr>';
			echo '</table>';
			
			echo '<div style="margin-top:20px;padding:15px;background:#f0f8ff;border:1px solid #0073aa;border-radius:4px;">';
			echo '<h4 style="margin-top:0;">' . __( 'Upgrade to Booster Elite', 'woocommerce-jetpack' ) . '</h4>';
			echo '<p>' . __( 'Get unlimited FAQs, Facts Table, Answer Chips, agent REST feed, Compare Matrix, and Bundles Assist.', 'woocommerce-jetpack' ) . '</p>';
			echo '<a href="https://booster.io/buy-booster/" target="_blank" class="button-primary">' . __( 'Upgrade Now', 'woocommerce-jetpack' ) . '</a>';
			echo '</div>';
			
			echo '<script>
			jQuery(document).ready(function($) {
				function updateCounter() {
					var length = $("#wcj-ara-short-counter").closest("td").find("textarea").val().length;
					$("#wcj-ara-short-counter").text(length);
					if (length > 160) {
						$("#wcj-ara-short-counter").css("color", "red");
					} else {
						$("#wcj-ara-short-counter").css("color", "");
					}
				}
				$("textarea[name=wcj_ara_faq_short_answer]").on("input", updateCounter);
				updateCounter();
			});
			</script>';
		}

		/**
		 * Save meta box
		 */
		public function save_meta_box( $post_id, $post ) {
			if ( ! isset( $_POST['wcj_ara_lite_nonce'] ) || ! wp_verify_nonce( $_POST['wcj_ara_lite_nonce'], 'wcj_ara_lite_meta_box' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$faq_data = array(
				'question'     => sanitize_text_field( $_POST['wcj_ara_faq_question'] ?? '' ),
				'short_answer' => sanitize_textarea_field( substr( $_POST['wcj_ara_faq_short_answer'] ?? '', 0, 160 ) ),
				'long_answer'  => sanitize_textarea_field( $_POST['wcj_ara_faq_long_answer'] ?? '' ),
			);

			if ( empty( $faq_data['question'] ) && empty( $faq_data['short_answer'] ) && empty( $faq_data['long_answer'] ) ) {
				delete_post_meta( $post_id, '_wcj_ara_faq_lite' );
			} else {
				update_post_meta( $post_id, '_wcj_ara_faq_lite', $faq_data );
			}
		}

		/**
		 * Render FAQ section on frontend
		 */
		public function render_faq_section() {
			if ( ! is_product() ) {
				return;
			}

			global $product;
			$faq_data = get_post_meta( $product->get_id(), '_wcj_ara_faq_lite', true );
			
			if ( empty( $faq_data['question'] ) || empty( $faq_data['long_answer'] ) ) {
				return;
			}

			$section_title = wcj_get_option( 'wcj_agent_ready_answers_lite_section_title', __( 'Frequently Asked Questions', 'woocommerce-jetpack' ) );

			echo '<div class="wcj-ara-faq-section" id="wcj-ara-faq-section">';
			echo '<h3 class="wcj-ara-faq-title">' . esc_html( $section_title ) . '</h3>';
			echo '<div class="wcj-ara-faq-item">';
			echo '<details class="wcj-ara-faq-details">';
			echo '<summary class="wcj-ara-faq-question">' . esc_html( $faq_data['question'] ) . '</summary>';
			echo '<div class="wcj-ara-faq-answer">' . wp_kses_post( wpautop( $faq_data['long_answer'] ) ) . '</div>';
			echo '</details>';
			echo '</div>';
			echo '</div>';
		}

		/**
		 * Output FAQPage JSON-LD schema
		 */
		public function output_faqpage_jsonld() {
			if ( ! is_product() ) {
				return;
			}

			global $product;
			$faq_data = get_post_meta( $product->get_id(), '_wcj_ara_faq_lite', true );
			
			if ( empty( $faq_data['question'] ) || empty( $faq_data['long_answer'] ) ) {
				return;
			}

			$schema = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'@id'        => get_permalink( $product->get_id() ) . '#faq',
				'mainEntity' => array(
					array(
						'@type'          => 'Question',
						'name'           => $faq_data['question'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $faq_data['long_answer'],
						),
					),
				),
			);

			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}

		/**
		 * Enqueue frontend scripts
		 */
		public function enqueue_frontend_scripts() {
			if ( ! is_product() ) {
				return;
			}

			wp_enqueue_style( 'wcj-agent-ready-answers-lite', wcj_plugin_url() . '/includes/css/wcj-agent-ready-answers-lite.css', array(), w_c_j()->version );
			wp_enqueue_script( 'wcj-agent-ready-answers-lite', wcj_plugin_url() . '/includes/js/wcj-agent-ready-answers-lite.js', array( 'jquery' ), w_c_j()->version, true );
		}

	}

endif;

return new WCJ_Agent_Ready_Answers_Lite();
