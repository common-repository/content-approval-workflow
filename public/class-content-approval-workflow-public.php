<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/public
 * @author     Qrolic Technologies <info@qrolic.com>
 */
class Content_Approval_Workflow_Public {


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Content_Approval_Workflow_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Content_Approval_Workflow_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, CAW_PLUGIN_URL . 'dist/public/css/content-approval-workflow-public.bundle.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Content_Approval_Workflow_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Content_Approval_Workflow_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, CAW_PLUGIN_URL . 'dist/public/js/content-approval-workflow-public.bundle.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Filters plugin comments from existing comments.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $clauses An array of clauses for the comment query.
	 * @return array An array of filtered clauses.
	 */
	public function filter_comments_by_type( $clauses ) {
		global $wpdb;

		if ( is_admin() ) {
			return $clauses;
		}

		$clauses['where'] .= " AND {$wpdb->comments}.comment_type NOT IN ( 'caw_feedback' )";

		return $clauses;
	}

	public function custome_comment_numbers( $count, $post_id ) {
		if ( is_admin() ) {
			return $count;
		}

		$total_comments = get_comments(
			array(
				'post_id' => $post_id,
				'status'  => 'approve',
			)
		);

		$count = count( $total_comments );

		return $count;
	}
}
