<?php

/**
 * Handles the settings related to Content Approval Workflow plugin.
 *
 * This class provides functionality to add and manage plugin settings.
 *
 * @package ContentApprovalWorkflow
 * @since   1.0.0
 */

if ( ! class_exists( 'Content_Approval_Workflow_Setting' ) ) {
	/**
	 * The Content_Approval_Workflow_Setting class.
	 *
	 * This class handles settings related to the Content Approval Workflow plugin.
	 *
	 * @since 1.0.0
	 */
	class Content_Approval_Workflow_Setting extends Content_Approval_Workflow_Common {
		/**
		 * The tabs for different settings sections.
		 *
		 * @var array $tabs Array containing information about different settings tabs.
		 */
		private $tabs;

		/**
		 * Constructor for the Content_Approval_Workflow_Setting class.
		 *
		 * Initializes the Content Approval Workflow settings by defining tabs and their respective fields.
		 * These settings are used for managing various aspects of the plugin such as general settings,
		 * notification settings, email templates, and content approval history settings.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function __construct() {
			parent::__construct();

			// Define the settings tabs and their respective fields.
			$this->tabs = apply_filters(
				'caw_settings_tabs',
				array(
					'general'      => array(
						'tab_name' => esc_html__( 'General', 'content-approval-workflow' ),
						'label'    => esc_html__( 'General Settings', 'content-approval-workflow' ),
						'fields'   => array(
							'post_types'               => esc_html__( 'Post Types', 'content-approval-workflow' ),
							'roles_can_request'        => esc_html__( 'Following roles can request for a review', 'content-approval-workflow' ),
							'roles_can_approve'        => esc_html__( 'Following roles can approve a review', 'content-approval-workflow' ),
							'min_required_reviews'     => esc_html__( 'Minimum reviews required for final approval:', 'content-approval-workflow' ),
							'publish_without_approval' => esc_html__( 'Allow to publish article without approval', 'content-approval-workflow' ),
							'show_approve_column'      => esc_html__( 'Show approve column on posts page.', 'content-approval-workflow' ),
						),
					),
					'notification' => array(
						'tab_name' => esc_html__( 'Notification', 'content-approval-workflow' ),
						'label'    => esc_html__( 'Notification Settings', 'content-approval-workflow' ),
						'fields'   => array(
							'due_date_days'            => esc_html__( 'Pending Review Notification Days', 'content-approval-workflow' ),
							'pending_review_frequency' => esc_html__( 'Pending Review Frequency', 'content-approval-workflow' ),
						),
					),
					'email'        => array(
						'tab_name' => esc_html__( 'Email', 'content-approval-workflow' ),
						'label'    => esc_html__( 'Email Template', 'content-approval-workflow' ),
						'fields'   => array(
							'ask_for_review_subject' => esc_html__( 'Subject', 'content-approval-workflow' ),
							'ask_for_review_message' => esc_html__( 'Message', 'content-approval-workflow' ),
							'approve_review_subject' => esc_html__( 'Subject', 'content-approval-workflow' ),
							'approve_review_message' => esc_html__( 'Message', 'content-approval-workflow' ),
							'feedback_subject'       => esc_html__( 'Subject', 'content-approval-workflow' ),
							'feedback_message'       => esc_html__( 'Message', 'content-approval-workflow' ),
						),
					),
					'history'      => array(
						'tab_name' => esc_html__( 'History', 'content-approval-workflow' ),
						'label'    => esc_html__( 'Content Approval History', 'content-approval-workflow' ),
						'fields'   => array(
							'duration_of_log' => esc_html__( 'Time to remove old log data', 'content-approval-workflow' ),
						),
					),
				),
			);
		}

		/**
		 * Adds the Content Approval Workflow plugin settings page to the WordPress admin menu.
		 *
		 * This method uses the add_submenu_page function to create a submenu page under the 'Settings'
		 * menu in the WordPress admin. It allows users with the 'manage_options' capability to access
		 * the Content Approval Workflow settings page, which is defined by the create_admin_setting_page method.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function add_plugin_page() {
			add_submenu_page(
				'options-general.php',
				esc_html__( 'Content Approval Workflow', 'content-approval-workflow' ),
				esc_html__( 'Content Approval Workflow', 'content-approval-workflow' ),
				'manage_options',
				'content-approval-workflow-settings',
				array( $this, 'create_admin_setting_page' )
			);
		}

		/**
		 * Initializes the Content Approval Workflow plugin settings.
		 *
		 * This method iterates through defined tabs and sets up the necessary settings sections and fields
		 * using the WordPress Settings API. It registers callbacks for displaying section information, field inputs,
		 * and other related actions. Additionally, it allows customization through action hooks and filters.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function initialize_settings() {
			foreach ( $this->tabs as $tab => $data ) {

				// Add a settings section for the current tab.
				add_settings_section(
					'caw_' . $tab . '_section',
					$data['label'],
					array( $this, $tab . '_section_info' ),
					'caw_' . $tab . '_settings'
				);

				// Apply filters to allow customization of fields for the current tab.
				$data['fields'] = apply_filters( 'caw_tab_fields_' . $tab, $data['fields'] );

				foreach ( $data['fields'] as $field => $field_title ) {
					// Execute actions before rendering each field.
					do_action( 'caw_before_field_' . $field );

					// Register settings for the current tab.
					register_setting( 'caw_' . $tab . '_settings', 'caw_' . $tab . '_settings' );

					// Get the callback function for rendering the field.
					$callback       = apply_filters( 'caw_field_callback', array( $this, $field . '_field_callback' ), $field );
					$allowed_fields = array( 'pending_review_frequency', 'duration_of_log', 'min_required_reviews', 'due_date_days', 'ask_for_review_subject', 'approve_review_subject', 'feedback_subject' );
					$field_titles   = in_array( $field, $allowed_fields, true ) ? '<label for="caw_' . $field . '">' . esc_html( $field_title ) . '</label>' : esc_html( $field_title );

					// Add the settings field to the current section.
					add_settings_field(
						'caw_' . $field,
						$field_titles,
						$callback,
						'caw_' . $tab . '_settings',
						'caw_' . $tab . '_section',
						array( 'field' => $field ),
					);

					// Execute actions after rendering each field.
					do_action( 'caw_after_field_' . $field );
				}

				// Execute actions after processing all fields in the current section.
				do_action( 'caw_after_section_' . $tab );
			}
		}

		/**
		 * Create the admin settings page and handle form submissions.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function create_admin_setting_page() {
			$caw_tabs = $this->tabs;

			include CAW_PLUGIN_PATH . 'admin/partials/settings-page-template.php';
		}

		/**
		 * Callback to display general settings section info.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function general_section_info() {
			//
		}

		/**
		 * Callback to display post types field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function post_types_field_callback() {
			$this->checkbox_field_callback( 'post_types', 'general', get_post_types( array( 'public' => true ), 'objects' ) );
		}

		/**
		 * Callback to display roles can request field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function roles_can_request_field_callback() {
			$wp_roles = wp_roles();

			$this->checkbox_field_callback( 'roles_can_request', 'general', $wp_roles->get_names() );
		}

		/**
		 * Callback to display roles can approve field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function roles_can_approve_field_callback() {
			$wp_roles = wp_roles();

			$this->checkbox_field_callback( 'roles_can_approve', 'general', $wp_roles->get_names() );
		}

		/**
		 * Callback to display minimum required reviews field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function min_required_reviews_field_callback() {
			$this->number_field_callback( 'min_required_reviews', 'general', esc_html__( 'Reviews', 'content-approval-workflow' ) );
		}

		/**
		 * Callback function to render the "Publish Without Approval" radio field in the general settings.
		 *
		 * This function is used as a callback for rendering a radio field in the general settings page.
		 * It generates the HTML markup for a set of radio buttons with options for publishing without approval.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function publish_without_approval_field_callback() {
			$options = array(
				'yes' => __( 'Yes', 'content-approval-workflow' ),
				'no'  => __( 'No', 'content-approval-workflow' ),
			);

			$this->radio_field_callback( 'publish_without_approval', 'general', $options );
		}

		/**
		 * Callback function to render the "Show approve column on posts page." radio field in the general settings.
		 *
		 * This function is used as a callback for rendering a radio field in the general settings page.
		 * It generates the HTML markup for a set of radio buttons.
		 *
		 * @return void
		 */
		public function show_approve_column_field_callback() {
			$options = array(
				'yes' => __( 'Yes', 'content-approval-workflow' ),
				'no'  => __( 'No', 'content-approval-workflow' ),
			);

			$this->radio_field_callback( 'show_approve_column', 'general', $options );
		}

		/**
		 * Callback to display notification settings section info.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function notification_section_info() {
			//
		}

		/**
		 * Callback to display days field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function due_date_days_field_callback() {
			$this->number_field_callback( 'due_date_days', 'notification' );
		}

		/**
		 * Callback to display frequency field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function pending_review_frequency_field_callback() {
			$this->select_field_callback(
				'pending_review_frequency',
				'notification',
				array(
					'none'    => esc_html__( 'None', 'content-approval-workflow' ),
					'daily'   => esc_html__( 'Daily', 'content-approval-workflow' ),
					'weekly'  => esc_html__( 'Weekly', 'content-approval-workflow' ),
					'monthly' => esc_html__( 'Monthly', 'content-approval-workflow' ),
				),
			);
		}

		/**
		 * Callback to display email settings section info.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function email_section_info() {
			include_once CAW_PLUGIN_PATH . 'admin/partials/email-tab-template.php';
		}

		/**
		 * Callback to display ask for review subject field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function ask_for_review_subject_field_callback() {
			$this->text_field_callback( 'ask_for_review_subject', 'email' );
		}

		/**
		 * Callback to display ask for review message field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function ask_for_review_message_field_callback() {
			$this->editor_field_callback( 'ask_for_review_message', 'email' );
		}

		/**
		 * Callback to display approve review subject field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function approve_review_subject_field_callback() {
			$this->text_field_callback( 'approve_review_subject', 'email' );
		}

		/**
		 * Callback to display approve review message field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function approve_review_message_field_callback() {
			$this->editor_field_callback( 'approve_review_message', 'email' );
		}

		/**
		 * Callback to display feedback subject field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function feedback_subject_field_callback() {
			$this->text_field_callback( 'feedback_subject', 'email' );
		}

		/**
		 * Callback to display feedback message field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function feedback_message_field_callback() {
			$this->editor_field_callback( 'feedback_message', 'email' );
		}

		/**
		 * Callback to display history settings section info.
		 *
		 * This method echoes the shortcode output for displaying the Content Approval Workflow history log.
		 * It also registers the 'caw_history_log' shortcode to be used in WordPress.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function history_section_info() {
			echo do_shortcode( '[caw_history_log page="settings"]' );
		}

		/**
		 * Callback to display duration of log field.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function duration_of_log_field_callback() {
			// Callback function for rendering the duration of log field.
			$this->number_field_callback( 'duration_of_log', 'history', esc_html__( 'Days', 'content-approval-workflow' ) );
		}
	}
}
