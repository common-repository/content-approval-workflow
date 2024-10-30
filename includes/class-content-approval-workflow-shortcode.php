<?php
/**
 * Content Approval Workflow Shortcode Class.
 *
 * Handles the shortcode for the Content Approval Workflow plugin.
 *
 * This class provides functionality to display the content approval history log using a shortcode.
 *
 * @package ContentApprovalWorkflow
 * @since   1.1.0
 */

if ( ! class_exists( 'Content_Approval_Workflow_Shortcode' ) ) {
	/**
	 * Class Content_Approval_Workflow_Shortcode
	 *
	 * Handles the shortcode for Content Approval Workflow plugin.
	 *
	 * This class provides functionality to display the content approval history log using a shortcode.
	 *
	 * @package ContentApprovalWorkflow
	 * @since   1.1.0
	 */

	class Content_Approval_Workflow_Shortcode extends Content_Approval_Workflow_Common {

		/**
		 * Constructor for the Content_Approval_Workflow_Shortcode class.
		 * Registers the shortcode callback when the class is instantiated.
		 */
		/**
		 * The current version of the plugin.
		 * 
		 * @access protected
		 * @var    string    $version    The current version of the plugin.
		 */
		protected $version;

		public function __construct() {

			if ( defined( 'CONTENT_APPROVAL_WORKFLOW_VERSION' ) ) {
				$this->version = CONTENT_APPROVAL_WORKFLOW_VERSION;
			} else {
				$this->version = '1.1.0';
			}

			parent::__construct();
			add_shortcode( 'caw_history_log', array( $this, 'history_log_shortcode' ) );
		}

		/**
		 * Shortcode callback to display the content approval history log.
		 *
		 * This function is registered as the callback for the 'caw_history_log' shortcode. It enqueues the necessary script,
		 * retrieves distinct values from the database, and includes the template files to render the content approval history log.
		 *
		 * @param array $atts Shortcode attributes.
		 *                   - 'table' (string) Table name for script enqueue. Default is 'history-log'.
		 *                   - 'page'  (string) Page type. Default is 'post'.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function history_log_shortcode( $atts ) {

			/**
			 * Shortcode attributes.
			 *
			 * @var array $atts {
			 *     Shortcode attributes.
			 *
			 *     @type string $table Table name.
			 *     @type string $page  Page type.
			 * }
			 */
			$atts = shortcode_atts(
				array(
					'table' => 'history-log',
					'page'  => 'post',
				),
				$atts,
				'caw_history_log'
			);

			$table = sanitize_key( $atts['table'] );
			// Check if the style is already registered
			if ( ! wp_style_is( $table, 'enqueued' ) ) {
				wp_enqueue_style( $table, CAW_PLUGIN_URL . 'dist/admin/css/settings.bundle.css', array(), $this->version, 'all' );
			}
			// Check if the script is already registered
			if ( ! wp_script_is( $table, 'enqueued' ) ) {
				// Enqueue script for the specified table.
				wp_enqueue_script( $table, CAW_PLUGIN_URL . 'dist/admin/js/' . $table . '-table.bundle.js', array( 'jquery' ), $this->version, false );
			}

			$nonce = array(
				'ajax_url'           => esc_url( admin_url( 'admin-ajax.php' ) ),
				'load_history_table' => esc_attr( wp_create_nonce( 'caw_load_history_table' ) ),
			);
			wp_localize_script(
				$table,
				'cawHistoryTable',
				$nonce,
			);
			wp_enqueue_script( 'wp-util' );

			// Get distinct values for users, posts, and assignees from the database.
			$distinct_users     = $this->db->get_distinct_column( 'assigned_user_id' );
			$distinct_posts     = $this->db->get_distinct_column( 'post_id' );
			$distinct_assignees = $this->db->get_distinct_column( 'assignee_id' );

			// Include template files for history tab and content approval history table.
			include_once CAW_PLUGIN_PATH . 'admin/partials/history-tab-template.php';
			include_once CAW_PLUGIN_PATH . 'admin/partials/tmpl-content-approval-history-table.php';
		}
	}
}
