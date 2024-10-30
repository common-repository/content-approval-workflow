<?php
/**
 * Content Approval Workflow Common Class
 *
 * This file defines the Content_Approval_Workflow_Common class, which serves as a common base class for handling common functionality
 * and configurations used in the Content Approval Workflow plugin.
 *
 * @package ContentApprovalWorkflow
 * @since   1.1.0
 */

if ( ! class_exists( 'Content_Approval_Workflow_Common' ) ) {
	/**
	 * Class Content_Approval_Workflow_Common
	 *
	 * This class serves as a common base class for handling common functionality
	 * and configurations used in the Content Approval Workflow plugin.
	 *
	 * @package ContentApprovalWorkflow
	 * @since   1.1.0
	 */
	class Content_Approval_Workflow_Common {

		/**
		 * Array to store plugin options.
		 *
		 * @var array
		 */
		protected $options;

		/**
		 * Database instance for handling interactions with the database.
		 *
		 * @var Content_Approval_Workflow_DB
		 */
		protected $db;

		/**
		 * Nonce property for securing requests.
		 *
		 * @var string
		 */
		public $nonce;

		/**
		 * Content_Approval_Workflow_Common constructor.
		 *
		 * Initializes the options and database instance.
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

			$this->db = new Content_Approval_Workflow_DB();
		}

		/**
		 * Sends review request emails to selected users for a specific post.
		 *
		 * This function retrieves post-related information, such as title, author, and permalink,
		 * as well as email templates from plugin options. It then sends personalized review request
		 * emails to the selected users.
		 *
		 * @since 1.1.0
		 *
		 * @param int   $post_id        The ID of the post for which the review request is being sent.
		 * @param array $selected_users Array of selected user IDs to whom the review request emails will be sent.
		 *
		 * @return bool True on successful email sending to at least one user, false on failure or if no emails were sent.
		 *
		 * @access protected
		 */
		protected function send_review_request_emails( $post_id, $selected_users ) {
			// Get post-related information
			$post_title    = (string) get_the_title( $post_id );
			$permalink     = esc_url( get_permalink( $post_id ) );
			$author_id     = get_post_field( 'post_author', $post_id );
			$author_name   = ( $author_id ) ? get_the_author_meta( 'display_name', $author_id ) : '';
			$assignee_user = get_the_author_meta( 'display_name', get_current_user_id() );

			// Get email templates from plugin options
			$subject_option = $this->options['email']['ask_for_review_subject'];
			$message_option = $this->options['email']['ask_for_review_message'];

			// Sanitize and prepare data
			$selected_users = array_map( 'intval', $selected_users );
			$headers        = array( 'Content-Type: text/html; charset=UTF-8' );
			$response       = true;

			// Loop through selected users and send personalized emails
			foreach ( $selected_users as $user_id ) {
				if ( ! is_numeric( $user_id ) || 0 > $user_id ) {
					continue;
				}

				// Prepare replacement values for email templates
				$str_array = array(
					'{post_title}'  => $post_title,
					'{post_link}'   => $permalink,
					'{post_author}' => $author_name,
					'{assignee}'    => $assignee_user,
					'{recipient}'   => get_user_by( 'id', $user_id )->display_name,
				);

				// Prepare subject and message by replacing placeholders in email templates
				$subject    = wp_strip_all_tags( str_replace( array_keys( $str_array ), array_values( $str_array ), $subject_option ) );
				$message    = '<html><body>';
				$message   .= wp_kses_post( str_replace( array_keys( $str_array ), array_values( $str_array ), $message_option ) );
				$message   .= '</body></html>';
				$user_email = get_userdata( $user_id )->user_email;

				// Send email and update response
				if ( ! wp_mail( $user_email, $subject, $message, $headers ) ) {
					$response = false;
				}
			}

			// Return true if at least one email was sent, otherwise false
			return $response;
		}

		/**
		 * Sends feedback notification emails to relevant users after a post has been reviewed.
		 *
		 * This method gathers necessary information about the post, such as title, author, assignee, and
		 * related users from the post metadata. It then sends personalized feedback notification emails
		 * to the relevant users, excluding the current user who initiated the feedback.
		 *
		 * @since 1.1.0
		 *
		 * @param int $post_id The ID of the post for which the feedback notifications are being sent.
		 *
		 * @return bool True on successful email sending to at least one user, false on failure or if no emails were sent.
		 *
		 * @access protected
		 */
		protected function send_caw_feedback_emails( $post_id ) {
			// Get the ID of the current user initiating the feedback
			$current_user_id = get_current_user_id();

			// Get post-related information
			$post_title      = get_the_title( $post_id );
			$related_users   = json_decode( get_post_meta( $post_id, 'review_request_users', true ) ) ?? array();
			$email_users_id  = array_unique( array_map( 'intval', array_merge( $related_users, array( get_post_field( 'post_author', $post_id ) ) ) ) );
			$feedback_author = sanitize_text_field( get_user_by( 'id', $current_user_id )->display_name );
			$permalink       = esc_url_raw( get_permalink( $post_id ) );
			$author_id       = get_post_field( 'post_author', $post_id );
			$author_name     = ( $author_id ) ? get_the_author_meta( 'display_name', $author_id ) : '';
			$assignee_id     = get_post_meta( $post_id, 'assignee_user', true );
			$assignee_user   = get_the_author_meta( 'display_name', $assignee_id );

			// Compose the email content.
			$subject_option = $this->options['email']['feedback_subject'];
			$message_option = $this->options['email']['feedback_message'];
			$headers        = array( 'Content-Type: text/html; charset=UTF-8' );
			$response       = true;

			// Loop through selected users and send emails.
			foreach ( $email_users_id as $user_id ) {
				if ( ! is_numeric( $user_id ) || $current_user_id === $user_id ) {
					continue;
				}

				// Prepare replacement values for email templates
				$recipient  = sanitize_text_field( get_user_by( 'id', $user_id )->display_name );
				$str_array  = array(
					'{post_title}'      => esc_html( $post_title ),
					'{post_link}'       => esc_url( $permalink ),
					'{post_author}'     => esc_html( $author_name ),
					'{assignee}'        => esc_html( $assignee_user ),
					'{recipient}'       => esc_html( $recipient ),
					'{feedback_author}' => esc_html( $feedback_author ),
				);
				$user_email = sanitize_email( get_userdata( $user_id )->user_email );
				$subject    = wp_strip_all_tags( str_replace( array_keys( $str_array ), array_values( $str_array ), $subject_option ) );
				$message    = '<html><body>';
				$message   .= wp_kses_post( str_replace( array_keys( $str_array ), array_values( $str_array ), $message_option ) );
				$message   .= '</body></html>';

				if ( ! wp_mail( $user_email, $subject, $message, $headers ) ) {
					$response = false;
				}
			}

			// Return true if at least one email was sent, otherwise false
			return $response;
		}

		/**
		 * Callback function for rendering a checkbox field in the plugin settings page.
		 *
		 * This protected method serves as a callback for rendering checkbox fields in the plugin settings page.
		 * It displays a list of checkboxes based on the provided items, allowing users to select multiple options.
		 * Additionally, it provides a search functionality for larger lists to enhance user experience.
		 *
		 * @since 1.1.0
		 *
		 * @param string $field The name of the field corresponding to the checkbox group.
		 * @param string $tab   The current tab where the field belongs.
		 * @param array  $items An associative array of items where keys are values and values are labels.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function checkbox_field_callback( $field, $tab, $items ) {
			$this->options[ $tab ][ $field ] = ! empty( $this->options[ $tab ][ $field ] ) ? $this->options[ $tab ][ $field ] : array();

			$is_large_list = count( $items ) > 10;
			$placeholder   = $is_large_list ? ( 'post_types' === $field ? __( 'Search Post', 'content-approval-workflow' ) : __( 'Search Role', 'content-approval-workflow' ) ) : '';

			?>
			<div class="caw-checkbox-field">
				<?php if ( $is_large_list ) : ?>
					<input type="search" class="caw-search" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
				<?php endif; ?>

				<ul class="caw-checkbox-options">
					<?php foreach ( $items as $item_key => $item_value ) : ?>
						<li>
							<label>
								<input type="checkbox"
									name="caw_<?php echo esc_attr( $tab ); ?>_settings[<?php echo esc_attr( $field ); ?>][]"
									value="<?php echo esc_attr( $item_key ); ?>" <?php checked( in_array( $item_key, $this->options[ $tab ][ $field ], true ) ); ?> />
								<?php echo esc_html( is_object( $item_value ) ? $item_value->label : $item_value ); ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}

		/**
		 * Callback function for rendering a number field in the plugin settings page.
		 *
		 * This method is used as a callback for rendering number fields in the settings page.
		 * It displays an input field for entering numerical values, with an optional unit label.
		 *
		 * @since 1.1.0
		 *
		 * @param string $field The name of the field corresponding to the number input.
		 * @param string $tab   The name of the settings tab where the field is located.
		 * @param string $unit  (Optional) The unit label to display after the number input.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function number_field_callback( $field, $tab, $unit = '' ) {
			$value = (int) ( ! empty( $this->options[ $tab ][ $field ] ) ? $this->options[ $tab ][ $field ] : 0 );
			?>
			<div class="section">
				<input id="caw_<?php echo esc_attr( $field ); ?>"
					name="caw_<?php echo esc_attr( $tab ); ?>_settings[<?php echo esc_attr( $field ); ?>]" type="number" step="1"
					min="0" value="<?php echo esc_attr( $value ); ?>" class="small-text">
				<?php esc_html( $unit ); ?>
			</div>
			<?php
		}

		/**
		 * Callback function to generate radio field HTML markup for settings.
		 *
		 * This function is used as a callback for rendering radio fields in a settings page.
		 * It generates the HTML markup for a set of radio buttons based on the provided options.
		 *
		 * @param string $field   The name of the radio field.
		 * @param string $tab     The name of the tab or section where the radio field belongs.
		 * @param array  $options An associative array of radio options where keys are option values and values are option labels.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function radio_field_callback( $field, $tab, $options ) {
			$value = ! empty( $this->options[ $tab ][ $field ] ) ? $this->options[ $tab ][ $field ] : null;

			foreach ( $options as $key => $option ) {
				?>
				<label class="caw_radio_label">
					<input type="radio" class="caw_radio"
						name="caw_<?php echo esc_attr( $tab ); ?>_settings[<?php echo esc_attr( $field ); ?>]"
						value="<?php echo esc_attr( $key ); ?>" <?php checked( $key === $value ); ?> />
					<?php echo esc_html( $option ); ?>
				</label>
				<br />
				<?php
			}
		}

		/**
		 * Callback function for rendering a select field in the plugin settings page.
		 *
		 * This method is used as a callback for rendering select fields in the settings page.
		 * It displays a dropdown list with options based on the provided associative array of options.
		 *
		 * @since 1.1.0
		 *
		 * @param string $field   The name of the field corresponding to the select input.
		 * @param string $tab     The current tab where the field belongs.
		 * @param array  $options An associative array of options where keys are values and values are labels.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function select_field_callback( $field, $tab, $options ) {
			$value = ! empty( $this->options[ $tab ][ $field ] ) ? $this->options[ $tab ][ $field ] : 'none';
			?>
			<select id="caw_<?php echo esc_attr( $field ); ?>"
				name="caw_<?php echo esc_attr( $tab ); ?>_settings[<?php echo esc_attr( $field ); ?>]">
				<?php foreach ( $options as $option_key => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $value, $option_key ); ?>>
						<?php echo esc_html( $option_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/**
		 * Callback function for rendering a text field in the plugin settings page.
		 *
		 * This method is used as a callback for rendering text fields in the settings page.
		 * It displays an input field for entering and editing text values.
		 *
		 * @since 1.1.0
		 *
		 * @param string $field The name of the field corresponding to the text input.
		 * @param string $tab   The current tab where the field belongs.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function text_field_callback( $field, $tab ) {
			$value = ! empty( $this->options[ $tab ][ $field ] ) ? sanitize_text_field( $this->options[ $tab ][ $field ] ) : '';

			echo '<input type="text" name="caw_' . esc_attr( $tab ) . '_settings[' . esc_attr( $field ) . ']" id="caw_' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '">';
		}

		/**
		 * Callback function for rendering a rich text editor field in the plugin settings page.
		 *
		 * This method is used as a callback for rendering rich text editor fields in the settings page.
		 * It displays a WordPress editor for entering and editing rich text content.
		 *
		 * @since 1.1.0
		 *
		 * @param string $field The name of the field corresponding to the rich text editor input.
		 * @param string $tab   The current tab where the field belongs.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function editor_field_callback( $field, $tab ) {

			// Get the content for the rich text editor, defaulting to an empty string if not set
			$content = ! empty( $this->options[ $tab ][ $field ] ) ? wp_kses_post( $this->options[ $tab ][ $field ] ) : '';

			// Generate a unique editor ID based on the field name
			$editor_id = 'caw_' . $field;

			// Define settings for the rich text editor
			$settings = array(
				'textarea_name' => 'caw_' . esc_attr( $tab ) . '_settings[' . $field . ']',
				'editor_height' => 400,
				'wpautop'       => false,
			);

			// Render the rich text editor
			wp_editor( $content, $editor_id, $settings );
		}

		/**
		 * Display posts for review in a table format.
		 *
		 * This method retrieves and displays posts for review based on the specified user keys and
		 * status label. It generates an HTML table with columns for post name, status label, date, and status.
		 * The posts are filtered based on the current user's ID and the provided user keys.
		 *
		 * @since 1.1.0
		 *
		 * @param string $status_label      The label for the status column.
		 * @param string $current_user_key  The meta key for the current user in post meta.
		 * @param string $other_user_key    The meta key for the other user in post meta.
		 *
		 * @return void
		 *
		 * @access protected
		 */
		protected function display_posts( $status_label, $current_user_key, $other_user_key ) {
			// Get the ID of the current user
			$current_user_id = get_current_user_id();

			// Initialize variables
			$html     = '';
			$is_empty = true;

			// Define query arguments to retrieve posts for review
			$args = array(
				'post_type'   => $this->options['general']['post_types'],
				'post_status' => 'all',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => (string) $current_user_key,
						'value'   => (int) $current_user_id,
						'compare' => 'IN',
					),
				),
			);

			// Get posts based on the query arguments
			$posts = get_posts( $args );

			// Check if there are no posts to review
			if ( empty( $posts ) ) {
				esc_html_e( 'No posts to review.', 'content-approval-workflow' );
				return;
			}

			// Start building the HTML table
			$html .= '<table class="fixed striped table-view-list" width="100%">';
			$html .= sprintf(
				'<tr>
					<th>%s</th>
					<th>%s</th>
					<th>%s</th>
					<th>%s</th>
				</tr>',
				esc_html__( 'Post Name', 'content-approval-workflow' ),
				esc_html( $status_label ),
				esc_html__( 'Date', 'content-approval-workflow' ),
				esc_html__( 'Status', 'content-approval-workflow' ),
			);

			// Loop through retrieved posts and generate table rows
			foreach ( $posts as $post ) {
				$users = get_post_meta( $post->ID, $other_user_key );

				foreach ( $users as $user ) {
					if ( $current_user_id === $user ) {
						continue;
					}

					// Set flag to indicate non-empty result
					$is_empty = false;

					// Add row for each post-user combination
					$html .= sprintf(
						'<tr>
							<td><a href="%s">%s</a></td>
							<td><a href="%s">%s</a></td>
							<td>%s</td>
							<td class="center-align">%s</td>
						</tr>',
						esc_attr( get_edit_post_link( $post->ID ) ),
						esc_html( $post->post_title ),
						esc_url( get_edit_user_link( $user ) ),
						esc_html( get_userdata( $user )->display_name ),
						esc_html( gmdate( 'F j, Y', strtotime( get_post_meta( $post->ID, 'review_date', true ) ) ) ),
						esc_html__( 'Pending', 'content-approval-workflow' ),
					);
				}
			}

			// Close the HTML table
			$html .= '</table>';

			// Display the table if not empty, otherwise show a message
			if ( ! $is_empty ) {
				echo wp_kses_post( $html );
			} else {
				echo esc_html__( 'No Post to review', 'content-approval-workflow' );
			}
		}

		/**
		 * Register the 'Approved' column.
		 *
		 * Adds column header 'Approved' to all WordPress admin posts type pages.
		 *
		 * @param array $columns Existing columns.
		 * @return array Columns with 'Approved' column added.
		 */
		public function add_approved_column_header( $columns ) {
			if ( 'yes' === $this->options['general']['show_approve_column'] ) {
				$columns['caw_approved'] = 'Approved';
			}

			return $columns;
		}

		/**
		 * Populate 'Approved' column with content.
		 *
		 * @param string $column_name The name of the column to display.
		 * @param int $post_id The current post ID.
		 * @return void
		 */
		public function approved_column_content( $column_name, $post_id ) {
			if ( 'yes' === $this->options['general']['show_approve_column'] && 'caw_approved' === $column_name ) {
				$min_required_reviews = $this->options['general']['min_required_reviews'];
				$min_required_reviews = ! empty( $min_required_reviews ) ? (int) $min_required_reviews : 0;
				$aproved_reviews      = count( get_post_meta( $post_id, 'approval_review_status_users' ) );
				$remaining_reviews    = $min_required_reviews - $aproved_reviews;

				if ( 0 < $remaining_reviews ) {
					echo 'No';
				} else {
					echo 'Yes';
				}
			}
		}
	}
}
