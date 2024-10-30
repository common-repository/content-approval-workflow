<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/admin
 */

if ( ! class_exists( 'Content_Approval_Workflow_Admin' ) ) {
	/**
	 * Handles the admin-specific functionality of the plugin.
	 *
	 * This class defines the plugin name, version, and provides two example hooks
	 * demonstrating how to enqueue the admin-specific stylesheet and JavaScript.
	 *
	 * @package    Content_Approval_Workflow
	 * @subpackage Content_Approval_Workflow/admin
	 * @author     Qrolic Technologies <info@qrolic.com>
	 */
	class Content_Approval_Workflow_Admin extends Content_Approval_Workflow_Common {

		/**
		 * The Name of this plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string    $plugin_name    The Name of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string    $version    The current version of this plugin.
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 1.0.0
		 * @param string $plugin_name The name of this plugin.
		 * @param string $version     The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version     = $version;
		}

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_styles() {
			do_action( 'caw_admin_enqueue_styles', $this->plugin_name, $this->version );
		}

		/**
		 * Enqueue scripts for the admin area.
		 *
		 * This method is responsible for enqueuing necessary styles and scripts based on the current admin screen.
		 * It includes logic to load styles and scripts for the plugin settings page and specific post types.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {

			if ( ! is_admin() ) {
				return;
			}

			$general_option      = get_option( 'caw_general_settings', array() );
			$selected_post_types = ! empty( $general_option['post_types'] ) ? (array) $general_option['post_types'] : array();
			$screen              = get_current_screen();

			wp_enqueue_style( $this->plugin_name . '_admin', CAW_PLUGIN_URL . 'dist/admin/css/admin.bundle.css', array(), $this->version, 'all' );
			wp_enqueue_script( $this->plugin_name . '_admin', CAW_PLUGIN_URL . 'dist/admin/js/admin.bundle.js', array(), $this->version, false );

			if ( 'settings_page_content-approval-workflow-settings' === $screen->base ) {
				$load_history_table = wp_create_nonce( 'caw_load_history_table' );

				wp_enqueue_style( $this->plugin_name, CAW_PLUGIN_URL . 'dist/admin/css/settings.bundle.css', array(), $this->version, 'all' );
				wp_enqueue_script( $this->plugin_name, CAW_PLUGIN_URL . 'dist/admin/js/settings.bundle.js', array( 'jquery' ), $this->version, false );
				wp_localize_script(
					$this->plugin_name,
					'cawNonceAjaxObject',
					array(
						'ajax_url'           => esc_url( admin_url( 'admin-ajax.php' ) ),
						'load_history_table' => esc_attr( $load_history_table ),
					),
				);
			}

			if ( ! empty( $selected_post_types ) && $screen && in_array( $screen->base, array( 'post', 'page' ), true ) ) {
				global $post;

				if ( in_array( $post->post_type, $selected_post_types, true ) ) {
					wp_enqueue_style( $this->plugin_name, CAW_PLUGIN_URL . 'dist/admin/css/post.bundle.css', array(), $this->version, 'all' );
					wp_enqueue_script( $this->plugin_name, CAW_PLUGIN_URL . 'dist/admin/js/post.bundle.js', array( 'jquery' ), $this->version, false );
					do_action( 'caw_admin_enqueue_scripts', $this->plugin_name, $this->version );

					$save_review_request   = wp_create_nonce( 'caw_save_review_request' );
					$approve_review        = wp_create_nonce( 'caw_approve_review' );
					$load_more_users       = wp_create_nonce( 'caw_load_more_users' );
					$save_review_feedback  = wp_create_nonce( 'caw_save_review_feedback' );
					$cancel_review_request = wp_create_nonce( 'caw_cancel_review_request' );
					$load_more_feedbacks   = wp_create_nonce( 'caw_load_more_feedbacks' );
					$ignore_review_process = wp_create_nonce( 'caw_ignore_review_process' );
					$get_approval_status   = wp_create_nonce( 'caw_get_approval_status' );

					$this->nonce = array(
						'ajax_url'              => esc_url( admin_url( 'admin-ajax.php' ) ),
						'save_review_request'   => esc_attr( $save_review_request ),
						'approve_review'        => esc_attr( $approve_review ),
						'load_more_users'       => esc_attr( $load_more_users ),
						'save_review_feedback'  => esc_attr( $save_review_feedback ),
						'cancel_review_request' => esc_attr( $cancel_review_request ),
						'load_more_feedbacks'   => esc_attr( $load_more_feedbacks ),
						'ignore_review_process' => esc_attr( $ignore_review_process ),
						'get_approval_status'   => esc_attr( $get_approval_status ),
					);

					wp_localize_script(
						$this->plugin_name,
						'cawNonceAjaxObject',
						$this->nonce,
					);

					// Localize the script with feedback response.
					$feedback_respnose = array(
						'error_message'         => esc_html__( 'Please enter a valid feedback!', 'content-approval-workflow' ),
						'added_success_message' => esc_html__( 'Feedback added successfully!', 'content-approval-workflow' ),
					);

					wp_localize_script( $this->plugin_name, 'feedbackRespnose', $feedback_respnose );
				}
			}
		}
	}
}
