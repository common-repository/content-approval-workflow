<?php
/**
 * Fired during plugin deactivation
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/includes
 */

if ( ! class_exists( 'Content_Approval_Workflow_Deactivator' ) ) {
	/**
	 * Class Content_Approval_Workflow_Deactivator
	 *
	 * Fired during plugin deactivation.
	 *
	 * This class defines all code necessary to run during the plugin's deactivation.
	 *
	 * @since      1.0.0
	 * @author     Qrolic Technologies <info@qrolic.com>
	 * @package    Content_Approval_Workflow
	 * @subpackage Content_Approval_Workflow/includes
	 */
	class Content_Approval_Workflow_Deactivator {

		/**
		 * Deactivate the plugin.
		 *
		 * Clear the permalinks to remove our post type's rules from the database.
		 *
		 * @since 1.0.0
		 */
		public static function deactivate() {
			// Clear the permalinks to remove our post type's rules from the database.
			flush_rewrite_rules();
		}
	}
}
