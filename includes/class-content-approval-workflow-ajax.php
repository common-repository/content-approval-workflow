<?php
/**
 * Handles AJAX requests related to content approval workflow.
 *
 * @package ContentApprovalWorkflow
 * @since   1.0.0
 */

if ( ! class_exists( 'Content_Approval_Workflow_Ajax' ) ) {
	/**
	 * Class Content_Approval_Workflow_Ajax
	 *
	 * Handles AJAX requests related to content approval workflow.
	 *
	 * @since 1.0.0
	 */
	class Content_Approval_Workflow_Ajax extends Content_Approval_Workflow_Common {

		/**
		 * Initializes the Constructor of Content_Approval_Workflow_Common.
		 * @since 1.0.0
		 * @access public
		 */
		public function register() {
			parent::__construct();
		}

		/**
		 * Save the review request for a post.
		 *
		 * This function handles the saving of review requests for a given post. It checks for a valid nonce
		 * to secure the request, retrieves post ID and selected users from the request, and performs various
		 * operations to update post meta and status based on the selected users.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		public function save_review_request() {
			// Check for a valid nonce to secure request.
			check_ajax_referer( 'caw_save_review_request', 'nonce' );

			// Retrive post ID and selected users from the request.
			$post_id        = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );
			$selected_users = array_unique( array_map( 'intval', filter_input( INPUT_POST, 'selected_users', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) ?? array() ) );

			if ( false === $post_id || null === $post_id || ! get_post( $post_id ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID or post not found.', 'content-approval-workflow' ) ) );
			}

			// Check for existing review request users.
			$previously_assigned_users = array_map( 'intval', json_decode( get_post_meta( $post_id, 'review_request_users', true ) ) ?? array() );
			$newly_assigned_users      = array_diff( $selected_users, $previously_assigned_users );

			delete_post_meta( $post_id, 'pending_review_status_users' );
			delete_post_meta( $post_id, 'approval_review_status_users' );
			delete_post_meta( $post_id, 'review_status', 'pending' );
			delete_post_meta( $post_id, 'review_request_users' );

			foreach ( $selected_users as $user ) {
				add_post_meta( $post_id, 'pending_review_status_users', (int) $user );
			}
			update_post_meta( $post_id, 'review_status', esc_html( 'pending' ) );
			update_post_meta( $post_id, 'assignee_user', (int) get_current_user_id() );
			update_post_meta( $post_id, 'review_request_users', wp_json_encode( $selected_users ) );
			update_post_meta( $post_id, 'review_date', esc_html( current_time( 'mysql' ) ) );

			$remaining_reviews = get_post_meta( $post_id, 'remaining_reviews', true );
			if ( empty( $remaining_reviews ) || 'ready' === $remaining_reviews ) {
				$min_required_reviews = ! empty( $this->options['general']['min_required_reviews'] ) ? (int) $this->options['general']['min_required_reviews'] : 0;
				update_post_meta( $post_id, 'remaining_reviews', $min_required_reviews );
			}

			$ready_message     = esc_html__( 'Ready for final approval. ', 'content-approval-workflow' );
			$remaining_message = esc_html__( 'Remaining reviews for final approval: ', 'content-approval-workflow' );
			$assigned_message  = esc_html__( 'Not Assigned ', 'content-approval-workflow' );
			$remaining         = (int) get_post_meta( $post_id, 'remaining_reviews', true );
			if ( empty( $newly_assigned_users ) ) {
				wp_send_json_success(
					array(
						'message'          => esc_html__( 'Review request saved successfully.', 'content-approval-workflow' ),
						'remaining_review' => $remaining,
						'reviews_massges'  => array( $ready_message, $remaining_message, $assigned_message ),
					)
				);
			}

			$warning = $this->send_review_request_emails( $post_id, $selected_users ) ? false : esc_html__( 'Error sending review request emails.', 'content-approval-workflow' );
			wp_send_json_success(
				array(
					'message'          => esc_html__( 'Review request sent successfully.', 'content-approval-workflow' ),
					'warning'          => $warning,
					'remaining_review' => $remaining,
					'reviews_massges'  => array( $ready_message, $remaining_message, $assigned_message ),
				)
			);
		}

		/**
		 * Approve the review for a post.
		 *
		 * This function handles the approval of a review for a given post. It checks for a valid nonce,
		 * retrieves necessary information about the post and users involved, updates database records,
		 * and sends email notifications to the assignee user.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		public function approve_review() {

			check_ajax_referer( 'caw_approve_review', 'nonce' );

			$post_id          = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );
			$current_user_id  = (int) get_current_user_id();
			$assignee_user_id = (int) get_post_meta( $post_id, 'assignee_user', true );
			$assignee_user    = get_the_author_meta( 'display_name', $assignee_user_id );
			$post_title       = (string) get_the_title( $post_id );
			$permalink        = esc_url( get_permalink( $post_id ) );
			$author_id        = get_post_field( 'post_author', $post_id );
			$author_name      = ( $author_id ) ? get_the_author_meta( 'display_name', $author_id ) : '';

			if ( false === $post_id && false === $assignee_user_id ) {
				wp_send_json_error( array( 'message' => esc_html__( 'An error occurred while processing the request.', 'content-approval-workflow' ) ) );
			}

			$table_data = array(
				'post_id'     => $post_id,
				'assigned_id' => $current_user_id,
				'assignee_id' => $assignee_user_id,
				'status'      => esc_html( 'Approved' ),
			);

			$this->db->caw_db_insert( $table_data );

			$str_array = array(
				'{post_title}'  => $post_title,
				'{post_link}'   => $permalink,
				'{post_author}' => $author_name,
				'{assignee}'    => $assignee_user,
			);

			delete_post_meta( $post_id, 'pending_review_status_users', get_current_user_id() );

			add_post_meta( $post_id, 'approval_review_status_users', get_current_user_id() );

			$remaining_reviews = get_post_meta( $post_id, 'remaining_reviews', true );

			$remaining_reviews = --$remaining_reviews;
			if ( 0 === $remaining_reviews ) {
				$remaining_reviews = 'ready';
			}

			update_post_meta( $post_id, 'remaining_reviews', $remaining_reviews );
			if ( 'ready' === $remaining_reviews ) {
				$final_approve_message = 'ready';
			} else {
				$final_approve_message = 'remaining reviews';
			}
			$subject_option = $this->options['email']['approve_review_subject'];
			$message_option = $this->options['email']['approve_review_message'];

			$assignee_email    = sanitize_email( get_userdata( $assignee_user_id )->user_email );
			$subject           = str_replace( array_keys( $str_array ), array_values( $str_array ), $subject_option );
			$message           = '<html><body>' . str_replace( array_keys( $str_array ), array_values( $str_array ), $message_option ) . '</body></html>';
			$headers           = array( 'Content-Type: text/html; charset=UTF-8' );
			$ready_message     = esc_html__( 'Ready for final approval. ', 'content-approval-workflow' );
			$remaining_message = esc_html__( 'Remaining reviews for final approval: ', 'content-approval-workflow' );
			$assigned_message  = esc_html__( 'Not Assigned ', 'content-approval-workflow' );
			if ( wp_mail( $assignee_email, $subject, $message, $headers ) ) {
				wp_send_json_success(
					array(
						'message'               => esc_html__( 'Review approved and email sent successfully.', 'content-approval-workflow' ),
						'remaining_reviews'     => $remaining_reviews,
						'reviews_massges'       => array( $ready_message, $remaining_message, $assigned_message ),
						'final_approve_message' => $final_approve_message,
					)
				);
			} else {
				wp_send_json_success(
					array(
						'message'               => esc_html__( 'Review approved successfully.', 'content-approval-workflow' ),
						'warning'               => esc_html__( 'Error sending approval email.', 'content-approval-workflow' ),
						'remaining_reviews'     => $remaining_reviews,
						'reviews_massges'       => array( $ready_message, $remaining_message, $assigned_message ),
						'final_approve_message' => $final_approve_message,
					)
				);
			}
		}

		/**
		 * Cancel a review request via AJAX.
		 *
		 * This function handles the cancellation of a review request for a given post. It checks for a valid nonce,
		 * retrieves post ID and assignee user ID from the request, and removes the relevant post meta entries to
		 * cancel the review request. A success response is sent with a message indicating the successful cancellation.
		 *
		 * @since 1.1.0
		 *
		 * @access public
		 * @return void
		 */
		public function cancel_review_request() {
			check_ajax_referer( 'caw_cancel_review_request', 'nonce' );

			$post_id          = (int) filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );
			$assignee_user_id = (int) filter_input( INPUT_POST, 'assignee_user_id', FILTER_VALIDATE_INT );

			if ( false === $post_id && false === $assignee_user_id ) {
				wp_send_json_error( array( 'message' => esc_html__( 'An error occurred while processing the request.', 'content-approval-workflow' ) ) );
			}

			delete_post_meta( $post_id, 'pending_review_status_users', get_current_user_id() );
			delete_post_meta( $post_id, 'approval_review_status_users', get_current_user_id() );

			wp_send_json_success( array( 'message' => esc_html__( 'Review request calncelled successfully. Please do not forget to submit a feedback', 'content-approval-workflow' ) ) );
		}

		/**
		 * Load more users for review assignment.
		 *
		 * This function handles the AJAX request to load more users available for review assignment.
		 * It checks for a valid nonce, retrieves the post ID from the request, and queries users based
		 * on specified criteria. The response includes user data with their ID, display name, and a flag
		 * indicating whether they are selected for the review request. The response is then sorted with
		 * selected users first, and a JSON response is sent back to the client.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		public function load_more_users() {
			check_ajax_referer( 'caw_load_more_users', 'nonce' );

			$post_id = (int) filter_input( INPUT_POST, 'postID', FILTER_VALIDATE_INT );

			if ( null === $post_id ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid input data.', 'content-approval-workflow' ) ) );
			}

			$user_query = array(
				'orderby'  => 'user_login',
				'exclude'  => array( get_current_user_id() ),
				'role__in' => $this->options['general']['roles_can_approve'],
			);

			$users                = get_users( $user_query );
			$review_request_users = get_post_meta( $post_id, 'pending_review_status_users' );
			$user_response        = array();

			foreach ( $users as $user ) {
				$user_response[] = array(
					'ID'           => $user->ID,
					'display_name' => $user->display_name,
					'checked'      => in_array( (int) $user->ID, array_map( 'intval', $review_request_users ), true ),
				);
			}

			usort(
				$user_response,
				function ( $a, $b ) {
					if ( $a['checked'] && ! $b['checked'] ) {
						return -1;
					} elseif ( ! $a['checked'] && $b['checked'] ) {
						return 1;
					} else {
						return 0;
					}
				}
			);

			$response = array(
				'success' => true,
				'users'   => $user_response,
			);

			wp_send_json( $response );
		}

		/**
		 * Save content approval feedback via AJAX.
		 *
		 * This function handles the AJAX request to save feedback for a given post. It verifies the nonce,
		 * retrieves post ID and feedback from the AJAX request, validates the input, and inserts the feedback
		 * as a comment. It also sends email notifications for the added feedback and returns a JSON response
		 * with information about the feedback, total feedback count, and any potential warnings.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		public function save_feedback() {
			// Verify nonce.
			check_ajax_referer( 'caw_save_review_feedback', 'nonce' );

			// Retrieve post ID and feedback from AJAX request.
			$post_id  = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );
			$feedback = filter_input( INPUT_POST, 'feedback' );

			// Validate and handle empty feedback or post ID.
			if ( empty( $feedback ) || empty( $post_id ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Something went wrong.', 'content-approval-workflow' ) ) );
			}

			// Get current user information.
			$user = wp_get_current_user();

			// Prepare feedback data.
			$feedback_data = array(
				'comment_post_ID'      => (int) $post_id,
				'comment_author'       => sanitize_text_field( $user->display_name ),
				'comment_author_email' => sanitize_email( $user->user_email ),
				'comment_author_url'   => esc_url_raw( $user->user_url ),
				'comment_content'      => wp_kses_post( $feedback ),
				'comment_type'         => 'caw_feedback',
				'comment_parent'       => 0,
				'user_id'              => (int) $user->ID,
			);

			// Insert the feedback and handle success or failure.
			$feedback_id = wp_insert_comment( $feedback_data );

			if ( $feedback_id ) {
				$sent = $this->send_caw_feedback_emails( $post_id );

				$total_feedbacks = get_comments(
					array(
						'post_id' => $post_id,
						'status'  => 'approve',
						'type'    => 'caw_feedback',
					)
				);

				$total_feedbacks     = count( $total_feedbacks );
				$feedback_author_url = get_author_posts_url( $user->ID );
				$feedback_datetime   = get_comment_date( '', $feedback_id ) . ' ' . get_comment_time( '', false, true, $feedback_id );

				$data = array(
					'message'       => esc_html__( 'Feedback added successfully.', 'content-approval-workflow' ),
					'totalFeedback' => $total_feedbacks,
					'feedbackData'  => array(
						'feedback_author_avatar' => esc_url( get_avatar_url( $user->user_email ) ),
						'feedback_author'        => $user->display_name,
						'feedback_author_url'    => esc_url( $feedback_author_url ),
						'feedback_datetime'      => esc_attr( gmdate( 'j M h:i A', strtotime( $feedback_datetime ) ) ),
						'feedback_content'       => wp_kses_post( $feedback ),
						'feedback_edit_link'     => esc_attr( get_edit_comment_link( $feedback_id ) ),
					),
					'warning'       => $sent ? '' : esc_html__( 'Error sending feedback email.', 'content-approval-workflow' ),
				);

				wp_send_json_success( $data );
			} else {
				$data = array(
					'message'      => esc_html__( 'Something went wrong.', 'content-approval-workflow' ),
					'feedbackData' => $feedback_data,
				);

				wp_send_json_error( $data );
			}
		}

		/**
		 * Load more content approval feedbacks via AJAX.
		 *
		 * This static function handles the AJAX request to load more feedbacks for a given post.
		 * It verifies the nonce, retrieves post ID, page offset, and feedback offset from the AJAX
		 * request, validates the input, and retrieves additional approved feedbacks for the post.
		 * The response includes information about the loaded feedbacks, and whether there are more
		 * feedbacks available to load. The response is then sent back to the client as a JSON object.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @return void
		 */
		public static function load_more_feedbacks() {
			// Verify nonce.
			check_ajax_referer( 'caw_load_more_feedbacks', 'nonce' );

			// Retrieve post ID and feedback from AJAX request.
			$post_id         = filter_input( INPUT_POST, 'postID', FILTER_VALIDATE_INT );
			$page_offset     = filter_input( INPUT_POST, 'pageOffset', FILTER_VALIDATE_INT );
			$feedback_offset = filter_input( INPUT_POST, 'feedbackOffset', FILTER_VALIDATE_INT );

			if ( empty( $post_id ) || ! is_numeric( $page_offset ) || ! is_numeric( $feedback_offset ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid input parameters.', 'content-approval-workflow' ) ) );
			}

			// Gets content approval feedbacks.
			$caw_feedbacks = get_comments(
				array(
					'post_id' => (int) $post_id,
					'status'  => 'approve',
					'type'    => 'caw_feedback',
					'orderby' => 'comment_date',
					'order'   => 'DESC',
					'number'  => 10,
					'offset'  => 10 * $page_offset + $feedback_offset,
				)
			);

			$load_more = 10 === count( $caw_feedbacks ) ? true : false;

			foreach ( $caw_feedbacks as $feedback ) {
				$feedback_data[] = array(
					'feedback_author'        => sanitize_text_field( $feedback->comment_author ),
					'feedback_author_avatar' => esc_url( get_avatar_url( $feedback->comment_post_ID ) ),
					'feedback_author_url'    => esc_url( $feedback->comment_author_url ),
					'feedback_datetime'      => esc_attr( gmdate( 'j M h:i A', strtotime( $feedback->comment_date_gmt ) ) ),
					'feedback_content'       => esc_html( $feedback->comment_content ),
					'feedback_edit_link'     => esc_attr( get_edit_comment_link( $feedback->comment_post_ID ) ),
				);
			}

			$data = array(
				'loadMore'     => $load_more,
				'feedbackData' => $feedback_data,
			);

			wp_send_json_success( $data );
		}

		/**
		 * Load the history table data based on the provided filters and pagination parameters.
		 *
		 * This function handles the AJAX request to load data for the history table. It checks for a valid nonce,
		 * retrieves filters and pagination parameters from the AJAX request, validates and sanitizes the input,
		 * constructs conditions based on the filters, and queries the database for history table data. The response
		 * includes information about the queried data, success status, and total number of pages. The response is then
		 * sent back to the client as a JSON object.
		 *
		 * @since 1.1.0
		 *
		 * @access public
		 * @return void
		 */
		public function load_history_table() {
			check_ajax_referer( 'caw_load_history_table', 'nonce' );

			$request = array(
				'user_filter'     => filter_input( INPUT_POST, 'userFilter', FILTER_VALIDATE_INT, ),
				'post_filter'     => filter_input( INPUT_POST, 'postFilter', FILTER_VALIDATE_INT ),
				'assignee_filter' => filter_input( INPUT_POST, 'assigneeFilter', FILTER_VALIDATE_INT ),
				'limit'           => filter_input( INPUT_POST, 'itemsPerPage', FILTER_VALIDATE_INT ),
				'page'            => filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT ),
				'order_by'        => filter_input( INPUT_POST, 'orderBy', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
			);

			$result = $this->db->load_history_table_data( $request );
			wp_send_json( $result );
		}

		public function ignore_review_process() {
			check_ajax_referer( 'caw_ignore_review_process', 'nonce' );

			$post_id    = filter_input( INPUT_POST, 'postID', FILTER_VALIDATE_INT );
			$caw_action = filter_input( INPUT_POST, 'cawAction', FILTER_DEFAULT );
			$caw_action = htmlspecialchars( $caw_action );

			if ( 'hide' === $caw_action ) {
				update_post_meta( $post_id, 'review_process_status', 'ignore' );
				delete_post_meta( $post_id, 'pending_review_status_users' );
				delete_post_meta( $post_id, 'review_status', 'pending' );
				delete_post_meta( $post_id, 'review_request_users' );
			} else {
				update_post_meta( $post_id, 'review_process_status', 'show' );
			}

			wp_send_json_success(
				array(
					'message' => 'success',
				)
			);
		}

		public function get_approval_status() {
			check_ajax_referer( 'caw_get_approval_status', 'nonce' );

			$post_id         = filter_input( INPUT_POST, 'postID', FILTER_VALIDATE_INT );
			$old_post_status = filter_input( INPUT_POST, 'originalPostStatus', FILTER_DEFAULT );
			$post            = get_post( $post_id );

			if (
				'publish' !== $old_post_status
				&& 'no' === $this->options['general']['publish_without_approval']
				&& in_array( $post->post_type, $this->options['general']['post_types'], true )
				&& 'ignore' !== get_post_meta( $post_id, 'review_process_status', true )
			) {
				$min_required_reviews = $this->options['general']['min_required_reviews'];
				$min_required_reviews = ! empty( $min_required_reviews ) ? (int) $min_required_reviews : 0;
				$approved_reviews     = count( get_post_meta( $post_id, 'approval_review_status_users' ) );
				$remaining_reviews    = $min_required_reviews - $approved_reviews;

				if ( 0 < $remaining_reviews ) {
					wp_send_json_success(
						array(
							'status'            => 'unapproved',
							'remaining_reviews' => $remaining_reviews,
							'message'           => esc_html__( 'Not enough reviews for approval. Saved as draft.' ),
						)
					);
				} else {
					wp_send_json_success(
						array(
							'status' => 'approved',
						)
					);
				}
			}
		}
	}
}
