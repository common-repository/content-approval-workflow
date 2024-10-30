<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://qrolic.com/
 * @author            Qrolic Technologies <info@qrolic.com>
 * @since             1.0.0
 * @package           Content_Approval_Workflow
 * @copyright         2024 Qrolic Technologies
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       Content Approval Workflow
 * Plugin URI:        https://qrolic.com/
 * Description:       This plugin facilitates a content approval workflow in WordPress.
 * Version:           1.1.4
 * Author:            Qrolic Technologies
 * Author URI:        https://qrolic.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       content-approval-workflow
 * Domain Path:       /languages
 */

/*
Content Review is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Content Review is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Content Review Flow. If not, see {URI to Plugin License}.
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin paths.
if ( ! defined( 'CAW_PLUGIN_PATH' ) ) {
	define( 'CAW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CAW_PLUGIN_URL' ) ) {
	define( 'CAW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


define( 'CONTENT_APPROVAL_WORKFLOW_VERSION', '1.1.4' );

/**
 * Adds a settings link to the plugin on the Plugins page.
 *
 * @param array $links An array of plugin action links.
 *
 * @return array An array of updated plugin action links.
 */
if ( ! function_exists( 'caw_add_plugin_settings_link' ) ) {
	function caw_add_plugin_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url() . 'options-general.php?page=content-approval-workflow-settings">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
add_filter( 'plugin_action_links_content-approval-workflow/content-approval-workflow.php', 'caw_add_plugin_settings_link' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-content-approval-workflow-activator.php
 */
if ( ! function_exists( 'content_approval_workflow_activate' ) ) {
	function content_approval_workflow_activate() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-approval-workflow-activator.php';
		Content_Approval_Workflow_Activator::activate();
	}
}
register_activation_hook( __FILE__, 'content_approval_workflow_activate' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-content-approval-workflow-deactivator.php
 */
if ( ! function_exists( 'content_approval_workflow_deactivate' ) ) {
	function content_approval_workflow_deactivate() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-approval-workflow-deactivator.php';
		Content_Approval_Workflow_Deactivator::deactivate();
	}
}
register_deactivation_hook( __FILE__, 'content_approval_workflow_deactivate' );

function content_approval_workflow_check_for_updates() {
	// Check if the plugin needs an update
	$current_version = get_option( 'caw_plugin_version' );

	if ( version_compare( $current_version, CONTENT_APPROVAL_WORKFLOW_VERSION, '<' ) ) {
		content_approval_workflow_activate();
	}
}
add_action( 'admin_init', 'content_approval_workflow_check_for_updates' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-content-approval-workflow.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'content_approval_workflow_run' ) ) {
	function content_approval_workflow_run() {
		$plugin = new Content_Approval_Workflow();
		$plugin->run();
	}
}
content_approval_workflow_run();
