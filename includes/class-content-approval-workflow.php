<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/includes
 */

if ( ! class_exists( 'Content_Approval_Workflow' ) ) {
	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Content_Approval_Workflow
	 * @subpackage Content_Approval_Workflow/includes
	 * @author     Qrolic Technologies <info@qrolic.com>
	 */
	class Content_Approval_Workflow {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var    Content_Approval_Workflow_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var    string    $version    The current version of the plugin.
		 */
		protected $version;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( defined( 'CONTENT_APPROVAL_WORKFLOW_VERSION' ) ) {
				$this->version = CONTENT_APPROVAL_WORKFLOW_VERSION;
			} else {
				$this->version = '1.1.0';
			}

			$this->plugin_name = 'content-approval-workflow';

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * This method includes various files that make up the plugin:
		 * - Content_Approval_Workflow_Common: Orchestrates common functionalities.
		 * - Content_Approval_Workflow_DB: Manages database operations.
		 * - Content_Approval_Workflow_Shortcode: Handles custom shortcodes.
		 * - Content_Approval_Workflow_Loader: Orchestrates the hooks of the plugin.
		 * - Content_Approval_Workflow_i18n: Defines internationalization functionality.
		 * - Content_Approval_Workflow_Admin: Defines all hooks for the admin area.
		 * - Content_Approval_Workflow_Public: Defines all hooks for the public side of the site.
		 * - Content_Approval_Workflow_Post_Meta_Box: Manages post meta boxes.
		 * - Content_Approval_Workflow_Ajax: Handles AJAX functionalities.
		 * - Content_Approval_Workflow_Dashboard_Meta_Box: Manages dashboard meta boxes.
		 * - Content_Approval_Workflow_Setting: Handles plugin settings.
		 * - Content_Approval_Workflow_Cron: Manages cron jobs for the plugin.
		 *
		 * Additionally, it creates an instance of the loader (Content_Approval_Workflow_Loader)
		 * which will be used to register the hooks with WordPress.
		 *
		 * @since  1.0.0
		 * @access private
		 * @return void
		 */
		private function load_dependencies() {
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-common.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-db.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-shortcode.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-loader.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-i18n.php';
			require_once CAW_PLUGIN_PATH . 'admin/class-content-approval-workflow-admin.php';
			require_once CAW_PLUGIN_PATH . 'public/class-content-approval-workflow-public.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-post-meta-box.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-ajax.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-dashbord-meta-box.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-setting.php';
			require_once CAW_PLUGIN_PATH . 'includes/class-content-approval-workflow-cron.php';

			$this->loader = new Content_Approval_Workflow_Loader();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Content_Approval_Workflow_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function set_locale() {
			$plugin_i18n = new Content_Approval_Workflow_I18n();

			$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
		}

		/**
		 * Register all hooks related to the admin area functionality of the plugin.
		 *
		 * This method initializes and sets up various hooks for the admin area, including styles, scripts,
		 * meta boxes, dashboard widgets, admin menu, settings, cron events, and AJAX actions.
		 *
		 * @since  1.0.0
		 * @access private
		 * @return void
		 */
		private function define_admin_hooks() {
			$plugin_admin      = new Content_Approval_Workflow_Admin( $this->get_plugin_name(), $this->get_version() );
			$post_meta_box     = new Content_Approval_Workflow_Post_Meta_Box();
			$dashbord_meta_box = new Content_Approval_Workflow_Dashbord_Meta_Box();
			$setting_admin     = new Content_Approval_Workflow_Setting();
			$cron              = new Content_Approval_Workflow_Cron();
			$ajax              = new Content_Approval_Workflow_Ajax();
			$common            = new Content_Approval_Workflow_Common();
			$shortcode         = new Content_Approval_Workflow_Shortcode();
			$general_options   = get_option( 'caw_general_settings', array() );

			// Register Ajax functionality.
			$ajax->register();

			// Register dashboard meta box.
			$dashbord_meta_box->register();

			// Enqueue Styles and Scripts
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

			// Meta Boxes
			$this->loader->add_action( 'add_meta_boxes', $post_meta_box, 'add_meta_boxes' );

			// Hooks a function to check approval of the content.
			if ( ! empty( $general_options['post_types'] ) && 'no' === $general_options['publish_without_approval'] ) {
				foreach ( $general_options['post_types'] as $post_type ) {
					$this->loader->add_action( 'publish_' . $post_type, $post_meta_box, 'check_post_approval', 10, 3 );
				}
			}

			// Dashboard Widgets
			$this->loader->add_action( 'wp_dashboard_setup', $dashbord_meta_box, 'add_dashboard_widgets' );

			// Admin Menu and Settings
			$this->loader->add_action( 'admin_menu', $setting_admin, 'add_plugin_page' );
			$this->loader->add_action( 'admin_init', $setting_admin, 'initialize_settings' );

			// Cron Events
			$this->loader->add_action( 'init', $cron, 'schedule_cron_events' );
			$this->loader->add_action( 'caw_review_pending_overdue_notification', $cron, 'send_overdue_review_email' );
			$this->loader->add_action( 'caw_frequently_notification', $cron, 'frequently_notification' );
			$this->loader->add_action( 'caw_remove_old_log', $cron, 'remove_old_log' );

			// AJAX Actions
			$this->loader->add_action( 'wp_ajax_caw_save_review_request', $ajax, 'save_review_request' );
			$this->loader->add_action( 'wp_ajax_caw_approve_review', $ajax, 'approve_review' );
			$this->loader->add_action( 'wp_ajax_caw_load_more_users', $ajax, 'load_more_users' );
			$this->loader->add_action( 'wp_ajax_caw_save_feedback', $ajax, 'save_feedback' );
			$this->loader->add_action( 'wp_ajax_caw_load_more_feedbacks', $ajax, 'load_more_feedbacks' );
			$this->loader->add_action( 'wp_ajax_caw_cancel_review_request', $ajax, 'cancel_review_request' );
			$this->loader->add_action( 'wp_ajax_caw_load_history_table', $ajax, 'load_history_table' );
			$this->loader->add_action( 'wp_ajax_ignore_review_process', $ajax, 'ignore_review_process' );
			$this->loader->add_action( 'wp_ajax_caw_get_approval_status', $ajax, 'get_approval_status' );

			// Adds a custom column 'Approved' to all WordPress admin posts type and pages.
			if ( ! empty( $general_options['post_types'] ) ) {
				foreach ( $general_options['post_types'] as $post_type ) {
					$post_type_name = '';

					if ( 'post' === $post_type ) {
						$post_type_name = 'posts';
					} elseif ( 'page' === $post_type ) {
						$post_type_name = 'pages';
					} else {
						continue;
					}

					$this->loader->add_filter( 'manage_' . $post_type_name . '_columns', $common, 'add_approved_column_header' );
					$this->loader->add_action( 'manage_' . $post_type_name . '_custom_column', $common, 'approved_column_content', 10, 2 );
				}
			}
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function define_public_hooks() {
			$plugin_public = new Content_Approval_Workflow_Public( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_filter( 'comments_clauses', $plugin_public, 'filter_comments_by_type', 10, 1 );
			$this->loader->add_filter( 'get_comments_number', $plugin_public, 'custome_comment_numbers', 10, 2 );

			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since 1.0.0
		 */
		public function run() {
			$this->loader->run();
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since  1.0.0
		 * @return string    The name of the plugin.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since  1.0.0
		 * @return Content_Approval_Workflow_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since  1.0.0
		 * @return string    The version number of the plugin.
		 */
		public function get_version() {
			return $this->version;
		}
	}
}
