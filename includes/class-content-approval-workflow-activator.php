<?php
/**
 * Fired during plugin activation
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/includes
 */

if ( ! class_exists( 'Content_Approval_Workflow_Activator' ) ) {
	/**
	 * Class Content_Approval_Workflow_Activator
	 *
	 * Fired during plugin activation.
	 *
	 * This class defines all code necessary to run during the plugin's activation.
	 *
	 * @since      1.0.0
	 * @package    Content_Approval_Workflow
	 * @subpackage Content_Approval_Workflow/includes
	 * @author     Qrolic Technologies <info@qrolic.com>
	 */
	class Content_Approval_Workflow_Activator {

		/**
		 * Activate the plugin.
		 *
		 * Perform actions such as updating options, flushing rewrite rules, etc.
		 *
		 * @since 1.0.0
		 */
		public static function activate() {
			$general_option = get_option( 'caw_general_settings', array() );
			$email_option   = get_option( 'caw_email_settings', array() );
			$history_option = get_option( 'caw_history_settings', array() );

			$plugin_version = get_option( 'caw_plugin_version', '' );

			if ( version_compare( $plugin_version, CONTENT_APPROVAL_WORKFLOW_VERSION, '<' ) ) {
				$general_option['post_types'] = get_option( 'caw_post_types', array() );
				delete_option( 'caw_post_types' );
			}

			if ( empty( $general_option['post_types'] ) || ! is_array( $general_option['post_types'] ) ) {
				$general_option['post_types'] = get_post_types( array( 'public' => true ), 'names' );
			} else {
				$general_option['post_types'] = array_map( 'sanitize_text_field', $general_option['post_types'] );
			}

			$wp_roles   = wp_roles();
			$role_slugs = array_keys( $wp_roles->get_names() );

			if ( empty( $general_option['roles_can_request'] ) || ! is_array( $general_option['roles_can_request'] ) ) {
				$general_option['roles_can_request'] = $role_slugs;
			} else {
				$general_option['roles_can_request'] = array_map( 'sanitize_text_field', $general_option['roles_can_request'] );
			}

			if ( empty( $general_option['roles_can_approve'] ) || ! is_array( $general_option['roles_can_approve'] ) ) {
				$general_option['roles_can_approve'] = $role_slugs;
			} else {
				$general_option['roles_can_approve'] = array_map( 'sanitize_text_field', $general_option['roles_can_approve'] );
			}

			if ( empty( $general_option['min_required_reviews'] ) ) {
				$general_option['min_required_reviews'] = 1;
			}

			if ( empty( $history_option['duration_of_log'] ) || ! is_numeric( $history_option['duration_of_log'] ) ) {
				$history_option['duration_of_log'] = 30;
			} else {
				$history_option['duration_of_log'] = absint( $history_option['duration_of_log'] );
			}

			if ( empty( $general_option['publish_without_approval'] ) ) {
				$general_option['publish_without_approval'] = 'yes';
			} else {
				$general_option['publish_without_approval'] = sanitize_text_field( $general_option['publish_without_approval'] );
			}

			if ( empty( $general_option['show_approve_column'] ) ) {
				$general_option['show_approve_column'] = 'yes';
			} else {
				$general_option['show_approve_column'] = sanitize_text_field( $general_option['show_approve_column'] );
			}

			// Default email templates
			$default_email_templates = array(
				'ask_for_review' => array(
					'subject' => esc_html__( 'Review Request for {post_title}', 'content-approval-workflow' ),
					'message' => '<p>' . esc_html__( 'Hello', 'content-approval-workflow' ) . ' {recipient}</p><p>' . esc_html__( 'You have received a review request for the following post:', 'content-approval-workflow' ) . '</p><p><strong>' . esc_html__( 'Post Title', 'content-approval-workflow' ) . '</strong> <a href="{post_link}">{post_title}</a> </p><p>' . esc_html__( 'Please review the post and provide your feedback.', 'content-approval-workflow' ) . '</p><p>' . esc_html__( 'Thank you!', 'content-approval-workflow' ) . '</p>',
				),
				'approve_review' => array(
					'subject' => esc_html__( 'Review Approved for {post_title}', 'content-approval-workflow' ),
					'message' => '<p>' . esc_html__( 'Hello', 'content-approval-workflow' ) . ' {assignee}</p><p>' . esc_html__( 'Your review for the following post has been approved:', 'content-approval-workflow' ) . '</p><p><strong>' . esc_html__( 'Post Title', 'content-approval-workflow' ) . '</strong> <a href="{post_link}">{post_title}</a> </p><p>' . esc_html__( 'Thank you!', 'content-approval-workflow' ) . '</p>',
				),
				'feedback'       => array(
					'subject' => esc_html__( 'Feedback added for {post_title}', 'content-approval-workflow' ),
					'message' => '<p>' . esc_html__( 'Hello', 'content-approval-workflow' ) . ' {recipient} </p><p>{feedback_author} ' . esc_html__( ' has added a new feedback on the following post:', 'content-approval-workflow' ) . '</p><p><strong>' . esc_html__( 'Post Title', 'content-approval-workflow' ) . '</strong> <a href="{post_link}">{post_title}</a> </p><p>' . esc_html__( 'Please review the feedback.', 'content-approval-workflow' ) . '</p><p>' . esc_html__( 'Thank you!', 'content-approval-workflow' ) . '</p>',
				),
			);

			foreach ( $default_email_templates as $template_key => $template_data ) {
				$email_option[ $template_key . '_subject' ] = empty( $email_option[ $template_key . '_subject' ] ) ? sanitize_text_field( $template_data['subject'] ) : sanitize_text_field( $email_option[ $template_key . '_subject' ] );
				$email_option[ $template_key . '_message' ] = empty( $email_option[ $template_key . '_message' ] ) ? wp_kses_post( $template_data['message'] ) : wp_kses_post( $email_option[ $template_key . '_message' ] );
			}

			update_option( 'caw_general_settings', $general_option );
			update_option( 'caw_email_settings', $email_option );
			update_option( 'caw_history_settings', $history_option );
			update_option( 'caw_plugin_version', CONTENT_APPROVAL_WORKFLOW_VERSION );

			flush_rewrite_rules();
		}
	}
}
