<?php
/**
 * This File contains DB funtions
 *
 * @link  https://qrolic.com/
 * @since 1.1.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/includes
 */

if ( ! class_exists( 'Content_Approval_Workflow_DB' ) ) {
	/**
	 * The core database class for the Content Approval Workflow plugin.
	 *
	 * This class handles various database operations related to the Content Approval Workflow plugin.
	 *
	 * @since      1.1.0
	 * @package    Content_Approval_Workflow
	 * @subpackage Content_Approval_Workflow/includes
	 * @author     Qrolic Technologies <info@qrolic.com>
	 */
	class Content_Approval_Workflow_DB {

		/**
		 * Retrieves distinct column values from the caw_log_entry table.
		 *
		 * This function queries the WordPress database to retrieve distinct values for a specified column
		 * in the caw_log_entry table.
		 *
		 * @since 1.1.0
		 *
		 * @param string $col_name The name of the column for which distinct values are retrieved.
		 *
		 * @return array An array of distinct values for the specified column.
		 */
		public function get_distinct_column( $col_name ) {
			global $wpdb;

			$allowed_columns = array( 'assigned_user_id', 'post_id', 'assignee_id' );

			// Check if the provided column is allowed.
			if ( ! in_array( $col_name, $allowed_columns, true ) ) {
				return array();
			}

			// Query to retrieve distinct values for the specified column.
			$distinct_values = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT wp_postmeta.meta_value
					FROM {$wpdb->posts} AS wp_posts
					INNER JOIN {$wpdb->postmeta} AS wp_postmeta ON (wp_posts.ID = wp_postmeta.post_id)
					WHERE 1=1
						AND wp_postmeta.meta_key = %s
						AND wp_posts.post_type = %s
					",
					esc_sql( $col_name ),
					esc_sql( 'caw_log_entry' ),
				),
				ARRAY_A,
			);

			return $distinct_values;
		}

		/**
		 * Load history table data from the database based on the provided filters and pagination parameters.
		 *
		 * This method retrieves data for the history table from the database based on the provided filters and
		 * pagination parameters. It constructs conditions for the meta query based on the filters, queries the
		 * database using WP_Query, and prepares a response with information about the queried data, success status,
		 * and total number of pages.
		 *
		 * @since 1.1.0
		 *
		 * @param array $request An array containing filters and pagination parameters.
		 *
		 * @return array The response containing information about the queried data, success status, and total number of pages.
		 */
		public function load_history_table_data( $request ) {
			$conditions = array();
			$result     = array();

			// Validate and sanitize input
			$user_filter     = filter_var( $request['user_filter'], FILTER_VALIDATE_INT );
			$post_filter     = filter_var( $request['post_filter'], FILTER_VALIDATE_INT );
			$assignee_filter = filter_var( $request['assignee_filter'], FILTER_VALIDATE_INT );
			$limit           = filter_var( $request['limit'], FILTER_VALIDATE_INT );
			$page            = filter_var( $request['page'], FILTER_VALIDATE_INT );

			// Construct conditions for the meta query based on filters.
			if ( ! empty( $user_filter ) ) {
				$conditions[] = array(
					'key'     => 'assigned_user_id',
					'value'   => $user_filter,
					'compare' => '=',
				);
			}

			if ( ! empty( $post_filter ) ) {
				$conditions[] = array(
					'key'     => 'post_id',
					'value'   => $post_filter,
					'compare' => '=',
				);
			}

			if ( ! empty( $assignee_filter ) ) {
				$conditions[] = array(
					'key'     => 'assignee_id',
					'value'   => $assignee_filter,
					'compare' => '=',
				);
			}

			// Set up arguments for WP_Query.
			$args = array(
				'post_type'      => 'caw_log_entry',
				'posts_per_page' => $limit,
				'offset'         => ( $page - 1 ) * $limit,
				'meta_query'     => $conditions,
				'orderby'        => ! empty( $request['order_by'] ) ? sanitize_key( $request['order_by'][0] ) : 'date',
				'order'          => ! empty( $request['order_by'] ) ? sanitize_key( $request['order_by'][1] ) : 'DESC',
				'no_found_rows'  => false,
			);

			// Retrieve data from the database using WP_Query.
			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {
				// Prepare the response with successful data retrieval.
				$result['success']    = true;
				$result['data']       = array_map( array( $this, 'map_post_to_history_data' ), $query->posts );
				$result['totalPages'] = $query->max_num_pages;
			} else {
				// Prepare the response with no data found.
				$result['success']    = false;
				$result['message']    = esc_html__( 'No Data Found', 'content-approval-workflow' );
				$result['totalPages'] = 0;
			}

			return $result;
		}

		/**
		 * Maps a caw_log_entry post object to an array of data for display.
		 *
		 * This function takes a caw_log_entry post object and extracts relevant information to create
		 * an array of data for display purposes.
		 *
		 * @since 1.1.0
		 *
		 * @param WP_Post $post The caw_log_entry post object.
		 *
		 * @return array An array of data for display.
		 */
		public function map_post_to_history_data( $post ) {
			$meta_values = get_post_custom( $post->ID );

			return array(
				'status'           => esc_html( $meta_values['status'][0] ),
				'created_at'       => esc_html( gmdate( 'F j, Y g:i a', get_post_time( 'U', false, $post, true ) ) ),
				'user_name'        => esc_html( get_the_author_meta( 'user_login', $post->post_author ) ),
				'post_title'       => esc_html( $post->post_title ),
				'assigne_name'     => esc_html( get_the_author_meta( 'user_login', $meta_values['assignee_id'][0] ) ),
				'user_author_link' => esc_url( get_author_posts_url( $post->post_author ) ),
				'assigne_link'     => esc_url( get_author_posts_url( $meta_values['assignee_id'][0] ) ),
				'post_edit_link'   => esc_url( get_edit_post_link( $meta_values['post_id'][0] ) ),
			);
		}

		/**
		 * Inserts a new record into the caw_log_entry table.
		 *
		 * This function inserts a new record into the caw_log_entry table with the provided data.
		 * It validates and sanitizes the input data, checks for required fields, and creates a new
		 * post in the 'caw_log_entry' post type.
		 *
		 * @since 1.1.0
		 *
		 * @param array $post_data An array of data for the new record.
		 *
		 * @return int|false The ID of the newly inserted record or false on failure.
		 */
		public function caw_db_insert( $post_data ) {
			if ( empty( $post_data ) || ! is_array( $post_data ) ) {
				return false;
			}

			// Sanitize and validate data
			$post_id     = isset( $post_data['post_id'] ) ? absint( $post_data['post_id'] ) : 0;
			$assignee_id = isset( $post_data['assignee_id'] ) ? absint( $post_data['assignee_id'] ) : 0;
			$assigned_id = isset( $post_data['assigned_id'] ) ? absint( $post_data['assigned_id'] ) : 0;
			$status      = isset( $post_data['status'] ) ? sanitize_text_field( $post_data['status'] ) : '';

			// Validate data
			if ( empty( $post_id ) || empty( $assignee_id ) || empty( $assigned_id ) || empty( $status ) ) {
				return false;
			}

			// Insert new post
			$new_post_id = wp_insert_post(
				array(
					'post_type'    => 'caw_log_entry',
					'post_title'   => get_the_title( $post_id ),
					'post_content' => '',
					'post_status'  => 'publish',
				),
			);

			if ( ! is_wp_error( $new_post_id ) ) {
				// Update post meta
				update_post_meta( $new_post_id, 'assignee_id', $assignee_id );
				update_post_meta( $new_post_id, 'post_id', $post_id );
				update_post_meta( $new_post_id, 'assigned_user_id', $assigned_id );
				update_post_meta( $new_post_id, 'status', $status );
			}

			return $new_post_id;
		}
	}
}
