<?php
/**
 * The file that defines the Content_Approval_Workflow_Post_Meta_Box class.
 *
 * A class for handling custom meta boxes in the WordPress post editor.
 *
 * @package Content_Approval_Workflow
 */

if ( ! class_exists( 'Content_Approval_Workflow_Post_Meta_Box' ) ) {
	/**
	 * The Content_Approval_Workflow_Post_Meta_Box class.
	 *
	 * This class handles custom meta boxes for the WordPress post editor.
	 *
	 * @since 1.0.0
	 */
	class Content_Approval_Workflow_Post_Meta_Box extends Content_Approval_Workflow_Common {

		/**
		 * Constructor for the Content_Approval_Workflow_Post_Meta_Box class.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		}

		/**
		 * Adds meta boxes for Content Approval Workflow to selected post types.
		 *
		 * This function adds a meta box named 'assigned_users_meta_box' to the selected post types.
		 * The meta box provides functionality related to Content Approval Workflow, allowing users to manage
		 * assigned users and review status for the selected post types.
		 *
		 * @since 1.0.0
		 */
		public function add_meta_boxes() {
			$selected_post_types = ! empty( $this->options['general']['post_types'] ) ? $this->options['general']['post_types'] : '';
			$post_type_label     = strtolower( get_post_type_object( get_post_type() )->labels->singular_name );
			$ignore_review       = 'ignore' === get_post_meta( get_the_ID(), 'review_process_status', true );
			$checked             = ! $ignore_review ? 'checked' : '';
			$tooltip_message     = esc_html__(
				'Disable this if you want to disable the approval process for this ',
				'content-approval-workflow'
			) . esc_html( $post_type_label ) . '.';

			$switch = sprintf(
				'<span>%s</span>
				<div class="cancel-remaining">
					<label class="switch">
						<input type="checkbox" class="cancel-review" data-view="%s" %s>
						<span class="slider"></span>
					</label>
				</div>
				<span class="caw-tooltip-container">
					<span id="caw-tooltip" class="dashicons dashicons-info"></span>
					<span class="caw-tooltiptext caw-tooltip-top">%s</span>
				</span>',
				esc_html__( 'Content Approval Workflow', 'content-approval-workflow' ),
				esc_attr( $ignore_review ? 'hide' : 'show' ),
				esc_attr( $checked ),
				$tooltip_message
			);

			add_meta_box(
				'assigned_users_meta_box',
				$switch,
				array( $this, 'render_meta_box' ),
				$selected_post_types,
				'advanced',
				'default'
			);
		}

		/**
		 * Render the content of the custom meta box in the post editor.
		 *
		 * Displays the user assignment meta box and associated actions.
		 *
		 * @param WP_Post $post The current post object.
		 *
		 * @since 1.0.0
		 */
		public function render_meta_box( $post ) {
			$current_user_id = get_current_user_id();

			echo '<div id="caw-main-content">';
			$this->output_approval_request( $post, $current_user_id );
			$this->output_approval_history();
			echo '</div>';

			include_once CAW_PLUGIN_PATH . 'admin/partials/tmpl-unapproved-publishing-failed.php';
		}

		/**
		 * Output the user assignment meta box and associated actions in the WordPress post editor.
		 *
		 * This function generates HTML output for the user assignment meta box, which allows users to manage assigned
		 * users and review status for the selected post types. It checks user roles and permissions to determine
		 * whether the current user can request or approve a review. Additionally, it retrieves content approval feedbacks
		 * and displays them in the meta box if the user has the necessary permissions.
		 *
		 * @param WP_Post $post            The current post object.
		 * @param int     $current_user_id The ID of the current user.
		 *
		 * @since 1.0.0
		 *
		 * @access private
		 *
		 * @return void
		 */
		private function output_approval_request( $post, $current_user_id ) {
			$value                = json_decode( get_post_meta( $post->ID, 'review_request_users', true ) ) ?? array();
			$user_assignee_id     = in_array( $current_user_id, array_map( 'intval', $value ), true ) ? get_post_meta( $post->ID, 'assignee_user', true ) : null;
			$user_review_status   = array_unique( array_map( 'intval', get_post_meta( $post->ID, 'approval_review_status_users' ) ) );
			$pending_review_users = array_map( 'intval', get_post_meta( $post->ID, 'pending_review_status_users' ) );
			$users                = get_users(
				array(
					'fields'  => array( 'ID', 'display_name' ),
					'orderby' => 'display_name',
				)
			);

			// Unserialize the stored value if it exists.
			$user_reviews = array();

			// Check if the current user is assigned for review.
			if ( in_array( (int) $current_user_id, array_map( 'intval', $value ), true ) ) {
				$user_assignee_id = get_post_meta( $post->ID, 'assignee_user', true );
			}

			$user_reviews         = array_filter( $users, fn ( $user ) => in_array( $user->ID, $user_review_status, true ) );
			$roles_can_request    = ! empty( $this->options['general']['roles_can_request'] ) ? (array) $this->options['general']['roles_can_request'] : array();
			$roles_can_approve    = ! empty( $this->options['general']['roles_can_approve'] ) ? (array) $this->options['general']['roles_can_approve'] : array();
			$current_user_roles   = wp_get_current_user()->roles;
			$user_can_request     = array_intersect( $roles_can_request, $current_user_roles );
			$user_can_approve     = array_intersect( $roles_can_approve, $current_user_roles );
			$min_required_reviews = ! empty( $this->options['general']['min_required_reviews'] ) ? (int) $this->options['general']['min_required_reviews'] : 0;
			$approved_reviews     = count( get_post_meta( $post->ID, 'approval_review_status_users' ) );
			$remaining_reviews    = get_post_meta( $post->ID, 'remaining_reviews', true );
			// Render the HTML for the user assignment meta box.
			include_once CAW_PLUGIN_PATH . 'admin/partials/post-metabox-template.php';

			if ( 0 < count( $user_can_request ) || 0 < count( $user_can_approve ) ) {
				// Gets content approval feedbacks.
				$caw_feedbacks = get_comments(
					array(
						'post_id' => get_the_ID(),
						'status'  => 'approve',
						'type'    => 'caw_feedback',
						'orderby' => 'comment_date',
						'order'   => 'DESC',
						'number'  => 10,
						'offset'  => 0,
					)
				);

				$total_feedbacks = get_comments(
					array(
						'post_id' => get_the_ID(),
						'status'  => 'approve',
						'type'    => 'caw_feedback',
					)
				);

				$caw_total_feedbacks = count( $total_feedbacks );

				// Displays content approval feedbacks.
				include plugin_dir_path( __FILE__ ) . '../admin/partials/content-approval-workflow-admin-feedback.php';
				include plugin_dir_path( __FILE__ ) . '../admin/partials/tmpl-content-approval-feedback.php';

			} else {
				echo esc_html__( 'You are not allowed to request or approve a review.', 'content-approval-workflow' );
			}
		}

		/**
		 * Output the approval history using a shortcode in the WordPress post editor.
		 *
		 * This function uses the [caw_history_log] shortcode to display the approval history in a table format.
		 * It enqueues the necessary JavaScript file for the table, retrieves distinct users, posts, and assignees from the
		 * database, and includes template files to generate the HTML output.
		 *
		 * @since 1.1.0
		 *
		 * @access private
		 *
		 * @return void
		 */
		private function output_approval_history() {
			echo '<div class="caw-approval-history-container">';
			echo '<h2 class="caw-approval-heading">' . esc_html__( 'Content Approval History', 'content-approval-workflow' ) . '</h2>';
			echo do_shortcode( '[caw_history_log page="post"]' );
			echo '</div>';
		}

		/**
		 * Checks post approval status before publishing.
		 *
		 * This method is hooked to the 'publish_{post_type}' action and is responsible for checking
		 * if the post should be published without approval based on plugin settings.
		 *
		 * @param int    $post_id    The ID of the post being published.
		 * @param object $post       The post object.
		 * @param string $old_status The old status of the post before the change.
		 *
		 * @return void
		 */
		public function check_post_approval( $post_id, $post, $old_status ) {
			if ( 'publish' !== $old_status && 'ignore' !== get_post_meta( $post_id, 'review_process_status', true ) ) {
				$min_required_reviews = ! empty( $this->options['general']['min_required_reviews'] ) ? (int) $this->options['general']['min_required_reviews'] : 0;
				$approved_reviews     = count( get_post_meta( $post_id, 'approval_review_status_users' ) );
				$remaining_reviews    = $min_required_reviews - $approved_reviews;

				if ( 0 < $remaining_reviews ) {
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_status' => 'draft',
						),
					);
				}
			}
		}
	}
}
