<?php
/**
 * File containing the Content_Approval_Workflow_Dashbord_Meta_Box class.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package Content_Approval_Workflow
 */

if ( ! class_exists( 'Content_Approval_Workflow_Dashbord_Meta_Box' ) ) {

	/**
	 * The Content_Approval_Workflow_Dashbord_Meta_Box class.
	 *
	 * This class defines the dashboard meta box and its related functionality.
	 *
	 * @since 1.0.0
	 */
	class Content_Approval_Workflow_Dashbord_Meta_Box extends Content_Approval_Workflow_Common {
		/**
		 * Initializes the Constructor of Content_Approval_Workflow_Common Class.
		 */
		public function register() {
			parent::__construct();
		}

		/**
		 * Adds custom dashboard widgets for pending reviews and content to review.
		 *
		 * This method checks the current user's roles against the roles allowed to request and approve, then adds
		 * corresponding dashboard widgets if the user has the necessary permissions.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function add_dashboard_widgets() {
			// Get roles allowed to request and approve
			$roles_can_request = $this->options['general']['roles_can_request'];
			$roles_can_approve = $this->options['general']['roles_can_approve'];

			// Get current user roles
			$current_user_roles = wp_get_current_user()->roles;

			// Determine roles the user can request and approve
			$user_can_request = array_intersect( $roles_can_request, $current_user_roles );
			$user_can_approve = array_intersect( $roles_can_approve, $current_user_roles );

			// Add dashboard widget for pending review if user can request
			if ( 0 < count( $user_can_request ) ) {
				wp_add_dashboard_widget( 'pending_review', esc_html__( 'Pending Review', 'content-approval-workflow' ), array( $this, 'display_pending_review' ) );
			}

			// Add dashboard widget for content to review if user can approve
			if ( 0 < count( $user_can_approve ) ) {
				wp_add_dashboard_widget( 'content_to_review', esc_html__( 'Content to Review', 'content-approval-workflow' ), array( $this, 'display_content_to_review' ) );
			}
		}

		/**
		 * Display content to review in a dashboard widget.
		 *
		 * This method retrieves and displays the posts that are assigned to the current user for review.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function display_content_to_review() {
			// Display posts assigned by the current user for review
			$this->display_posts(
				esc_html__( 'Assigned By', 'content-approval-workflow' ),
				'pending_review_status_users',
				'assignee_user',
			);
		}

		/**
		 * Display posts pending review in a dashboard widget.
		 *
		 * This method retrieves and displays the posts that are assigned to the current user for review.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function display_pending_review() {
			// Display posts assigned to the current user for review
			$this->display_posts(
				esc_html__( 'Assigned To', 'content-approval-workflow' ),
				'assignee_user',
				'pending_review_status_users',
			);
		}
	}
}
