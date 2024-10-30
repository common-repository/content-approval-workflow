<?php
/**
 * Handles the cron related to Content Approval Workflow plugin.
 *
 * This class provides functionality to add and manage plugin cron.
 *
 * @package ContentApprovalWorkflow
 * @since   1.1.0
 */

if ( ! class_exists( 'Content_Approval_Workflow_Cron' ) ) {
	/**
	 * The Content_Approval_Workflow_Cron class.
	 *
	 * This class handles cron related to the Content Approval Workflow plugin.
	 * @since 1.1.0
	 */
	class Content_Approval_Workflow_Cron {

		/**
		 * Array to store plugin options.
		 *
		 * @var array
		 */
		private $options;

		/**
		 * Constructor for the Content_Approval_Workflow_Cron class.
		 *
		 * Initializes the options and triggers an action hook for extending the cron initialization.
		 *
		 * @since 1.1.0
		 */
		public function __construct() {
			$this->options = array(
				'general'      => get_option( 'caw_general_settings', array() ),
				'notification' => get_option( 'caw_notification_settings', array() ),
				'email'        => get_option( 'caw_email_settings', array() ),
				'history'      => get_option( 'caw_history_settings', array() ),
			);

			do_action( 'caw_extend_cron_init', $this );
		}

		/**
		 * Schedule cron events based on plugin settings.
		 *
		 * This method schedules various cron events based on the Content Approval Workflow plugin settings.
		 * It schedules events for notifying pending reviews, removing old logs, and frequent notifications if enabled.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 *
		 * @access public
		 */
		public function schedule_cron_events() {
			// Get the frequency of pending review email notifications
			$email_frequency = (int) ( $this->options['notification']['pending_review_frequency'] ?? 0 );

			// Schedule events for pending review notifications and removing old logs
			$this->schedule_cron( 'caw_review_pending_overdue_notification', DAY_IN_SECONDS );
			$this->schedule_cron( 'caw_remove_old_log', MONTH_IN_SECONDS );

			// Schedule frequent notifications if email frequency is not set to 'none'
			if ( ! empty( $email_frequency ) && 'none' !== $email_frequency ) {
				$this->schedule_cron( 'caw_frequently_notification', $this->get_interval_seconds( $email_frequency ) );
			}
		}

		/**
		 * Schedule a single cron event if not already scheduled.
		 *
		 * Checks if a specific cron event is scheduled and schedules it if not.
		 * Uses the WordPress scheduling system to schedule a single event to execute the specified hook
		 * after a specified interval of time.
		 *
		 * @since 1.1.0
		 *
		 * @param string $hook     The action hook to execute.
		 * @param string $interval The interval for the cron event.
		 *
		 * @access private
		 *
		 * @return void
		 */
		private function schedule_cron( $hook, $interval ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_single_event( time() + $interval, $hook );
			}
		}

		/**
		 * Get interval in seconds based on the provided frequency.
		 *
		 * Calculates the interval in seconds based on the provided frequency.
		 * Used to determine the interval for scheduling cron events, such as daily, weekly, or monthly.
		 *
		 * @since 1.1.0
		 *
		 * @param string $frequency The frequency of the event ('daily', 'weekly', 'monthly').
		 *
		 * @access private
		 *
		 * @return int The interval in seconds. Returns 0 for unsupported frequencies.
		 */
		private function get_interval_seconds( $frequency ) {
			switch ( $frequency ) {
				case 'daily':
					return DAY_IN_SECONDS;
				case 'weekly':
					return WEEK_IN_SECONDS;
				case 'monthly':
					return MONTH_IN_SECONDS;
				default:
					return 0;
			}
		}

		/**
		 * Get pending posts for review.
		 *
		 * This private method retrieves posts that are pending review based on plugin settings.
		 * It queries the posts with pending review status for any selected users.
		 *
		 * @since 1.1.0
		 *
		 * @access private
		 *
		 * @return array An array of post objects representing pending posts for review.
		 */
		private function get_pending_posts() {
			// Get the post types from plugin settings or default to an empty array
			$post_type = $this->options['general']['post_types'] ?? array();

			// Prepare arguments for querying pending posts
			$args = array(
				'post_type'   => is_array( $post_type ) ? array_map( 'sanitize_text_field', $post_type ) : array(),
				'post_status' => 'all',
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => 'pending_review_status_users',
						'compare' => 'EXIST',
					),
				),
			);

			// Retrieve and return the pending posts
			return get_posts( $args );
		}

		/**
		 * Send overdue review reminder emails.
		 *
		 * This public method retrieves pending posts with overdue review dates and sends reminder emails to
		 * corresponding users based on plugin settings. It aggregates the information and sends a single email
		 * to each user with details about their pending posts that are overdue.
		 *
		 * @since 1.1.0
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function send_overdue_review_email() {
			// Get the number of days before due date to send reminder emails
			$notification_days = (int) ( $this->options['notification']['due_date_days'] ?? 0 );

			// Return if notification days are not set or not a valid integer
			if ( ! $notification_days || ! is_int( $notification_days ) ) {
				return;
			}

			// Get pending posts with overdue review dates
			$posts = $this->get_pending_posts();

			// Initialize an array to store pending posts information
			$pending_posts_info = array();

			// Loop through pending posts and gather information for overdue posts
			foreach ( $posts as $post ) {
				$assign_review_date = get_post_meta( $post->ID, 'review_date', true );

				// Continue to the next post if no review date is assigned
				if ( ! $assign_review_date ) {
					continue;
				}

				$current_date = ( new DateTime() )->format( 'Y-m-d' );
				$review_date  = new DateTime( $assign_review_date );
				$due_date     = $review_date->modify( "+$notification_days days" )->format( 'Y-m-d' );

				// Continue to the next post if the due date is not overdue
				if ( $due_date > $current_date ) {
					continue;
				}

				$pending_users = get_post_meta( $post->ID, 'pending_review_status_users' );

				// Continue to the next post if no pending users
				if ( empty( $pending_users ) ) {
					continue;
				}

				foreach ( $pending_users as $user_id ) {
					$user_id = absint( $user_id );

					// Continue to the next user if user ID is not valid
					if ( ! $user_id ) {
						continue;
					}

					$user_email = get_user_by( 'id', $user_id )->user_email;

					// Store information about the overdue post for each user
					$pending_posts_info[ $user_email ][] = array(
						'post_name' => $post->post_title,
						'post_link' => get_edit_post_link( $post->ID ),
					);
				}
			}

			// Return if there are no pending posts with overdue review dates
			if ( empty( $pending_posts_info ) ) {
				return;
			}

			// Send reminder emails to users with aggregated information about their overdue posts
			foreach ( $pending_posts_info as $user_email => $posts_info ) {
				$message = sprintf(
					'<p>%s</p>',
					esc_html__( 'This is a reminder that the due date for the following posts is overdue. Please take immediate action:', 'content-approval-workflow' )
				);

				foreach ( $posts_info as $info ) {
					$message .= sprintf(
						'<p>- %s: <a href="%s">%s</a></p>',
						esc_html__( 'Post', 'content-approval-workflow' ),
						esc_url( $info['post_link'] ),
						esc_html( $info['post_name'] ),
					);
				}

				// Send a single email with the aggregated information to each user
				$to      = sanitize_email( $user_email );
				$subject = esc_html__( 'Overdue Reminder for Pending Review Posts', 'content-approval-workflow' );
				$message = wp_kses_post( $message );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $to, $subject, $message, $headers );
			}
		}

		/**
		 * Send frequent notification emails for pending reviews.
		 *
		 * This method is responsible for sending reminder emails about pending reviews to users based on
		 * the specified frequency (daily, weekly, or monthly) in plugin settings. It retrieves pending posts with
		 * their due dates and sends aggregated reminder emails to corresponding users.
		 *
		 * @since 1.1.0
		 * @access public
		 *
		 * @return void
		 */
		public function frequently_notification() {
			// Get the specified frequency for sending notification emails
			$email_frequency = $this->options['notification']['pending_review_frequency'] ?? 'none';

			// Define valid frequency options
			$valid_frequencies = array( 'none', 'daily', 'weekly', 'monthly' );

			// Validate and set the frequency, default to 'none' if invalid
			$email_frequency = in_array( $email_frequency, $valid_frequencies, true ) ? $email_frequency : 'none';

			// Clear the scheduled hook and return if frequency is 'none' or not set
			if ( ! $email_frequency || 'none' === $email_frequency ) {
				wp_clear_scheduled_hook( 'caw_frequently_notification' );

				return;
			}

			// Get the number of days before due date to send reminder emails
			$notification_days = (int) ( $this->options['notification']['pending_review_frequency'] ?? 0 );

			// Return if notification days are not set or not a valid integer
			if ( 0 >= $notification_days || ! is_int( $notification_days ) ) {
				return;
			}

			// Get pending posts with their due dates
			$posts = $this->get_pending_posts();

			// Initialize an array to store pending posts information
			$pending_posts_info = array();

			// Loop through pending posts and gather information for frequent notification emails
			foreach ( $posts as $post ) {
				$assign_review_date = get_post_meta( $post->ID, 'review_date', true );

				// Continue to the next post if no review date is assigned
				if ( ! $assign_review_date ) {
					continue;
				}

				$review_date   = new DateTime( $assign_review_date );
				$due_date      = $review_date->modify( "+$notification_days days" )->format( 'Y-m-d' );
				$pending_users = get_post_meta( $post->ID, 'pending_review_status_users' );

				// Continue to the next post if no pending users
				if ( empty( $pending_users ) ) {
					continue;
				}

				foreach ( $pending_users as $user_id ) {
					$user_id = absint( $user_id );

					// Continue to the next user if user ID is not valid
					if ( ! $user_id ) {
						continue;
					}

					$user_email = get_user_by( 'id', $user_id )->user_email;

					// Store information about the pending post for each user
					$pending_posts_info[ $user_email ][] = array(
						'post_name' => $post->post_title,
						'post_link' => get_edit_post_link( $post->ID ),
						'due_date'  => $due_date,
					);
				}
			}

			// Return if there are no pending posts for frequent notification emails
			if ( empty( $pending_posts_info ) ) {
				return;
			}

			// Send frequent notification emails to users with aggregated information about their pending posts
			foreach ( $pending_posts_info as $user_email => $posts_info ) {
				$message = sprintf(
					'<p>%s:</p>',
					esc_html__( 'This is a reminder for the following posts', 'content-approval-workflow' )
				);

				foreach ( $posts_info as $info ) {
					$message .= sprintf(
						'<p>- %s: <a href="%s">%s</a> ( %s: %s )</p>',
						esc_html__( 'Post', 'content-approval-workflow' ),
						esc_url( $info['post_link'] ),
						esc_html( $info['post_name'] ),
						esc_html__( 'Due Date', 'content-approval-workflow' ),
						esc_html( $info['due_date'] )
					);
				}

				$to      = sanitize_email( $user_email );
				$subject = esc_html__( 'Pending Review Reminder for Your Posts', 'content-approval-workflow' );
				$message = wp_kses_post( $message );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $to, $subject, $message, $headers );
			}
		}

		/**
		 * Remove old log entries based on the configured duration threshold.
		 *
		 * This public method is responsible for removing old log entries from the 'caw_log_entry' post type
		 * based on the specified duration threshold in the plugin settings. It retrieves log entries older than
		 * the threshold and deletes associated post metadata, log entries, and their revisions.
		 *
		 * @since 1.1.0
		 * @access public
		 *
		 * @return void
		 */
		public function remove_old_log() {
			// Get the configured duration threshold for removing old log entries
			$day_threshold = absint( $this->options['history']['duration_of_log'] ?? 0 );

			// Check if a valid duration threshold is set
			if ( 0 < $day_threshold ) {
				// Get the current time and calculate the timestamp threshold
				$current_time        = new DateTime();
				$timestamp_threshold = $current_time->modify( "-{$day_threshold} days" )->format( 'Y-m-d H:i:s' );

				// Define arguments to query old log entries
				$args = array(
					'post_type'      => 'caw_log_entry',
					'posts_per_page' => -1,
					'date_query'     => array(
						'before' => $timestamp_threshold,
					),
				);

				// Get old log entries based on the defined arguments
				$posts = get_posts( $args );

				// Loop through old log entries and perform deletion actions
				foreach ( $posts as $post ) {
					$post_id = absint( $post->ID );

					// Check if a valid post ID is obtained
					if ( 0 < $post_id ) {
						// Delete post metadata associated with the log entry
						delete_post_meta( $post_id, '', true );

						// Delete revisions associated with the log entry
						$revisions = wp_get_post_revisions( $post_id );

						foreach ( $revisions as $revision ) {
							wp_delete_post_revision( $revision->ID );
						}

						// Delete the log entry post itself
						wp_delete_post( $post_id, true );
					}
				}
			}
		}
	}
}
