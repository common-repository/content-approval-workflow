<?php
/**
 * Template for displaying warning notice .
 *
 * This template is used to display warning when publishing unapproved post.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package Content_Approval_Workflow
 */
?>
<script type="text/html" id="tmpl-unapproved-publishing-failed">
	<div class="components-notice is-warning is-dismissible" id="unapproved-publishing-failed-container">
		<div data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00"
			style="border: 0px; clip: rect(1px, 1px, 1px, 1px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0px; position: absolute; width: 1px; overflow-wrap: normal;">
			<?php esc_html_e( 'Warning notice', 'content-approval-workflow' ); ?>
		</div>

		<div class="components-notice__content">
			<?php esc_html_e( 'Publishing failed. Not enough reviews for approval. Saved as draft.', 'content-approval-workflow' ); ?>
			<div class="components-notice__actions"></div>
		</div>

		<button type="button" class="components-button components-notice__dismiss has-icon" aria-label="Close" aria-controls="unapproved-publishing-failed">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
				<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
			</svg>
		</button>
	</div>
</script>