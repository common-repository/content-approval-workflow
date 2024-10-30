<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/includes
 */

if ( ! class_exists( 'Content_Approval_Workflow_I18n' ) ) {
	/**
	 * Define the internationalization functionality.
	 *
	 * Loads and defines the internationalization files for this plugin
	 * so that it is ready for translation.
	 *
	 * @since      1.0.0
	 * @author     Qrolic Technologies <info@qrolic.com>
	 * @package    Content_Approval_Workflow
	 * @subpackage Content_Approval_Workflow/includes
	 */
	class Content_Approval_Workflow_I18n {

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since 1.0.0
		 */
		public function load_plugin_textdomain() {

			load_plugin_textdomain(
				'content-approval-workflow',
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);
		}
	}
}
