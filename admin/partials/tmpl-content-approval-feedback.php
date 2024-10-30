<?php
/**
 * Template for displaying a single feedback comment.
 *
 * This template is used to display a single feedback comment when inserted into the DOM.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package Content_Approval_Workflow
 */

?>

<script type="text/html" id="tmpl-content-approval-feedback">
	<div class="caw-feedback">
		<div class="caw-avatar">
			<img alt="{{{data.feedback_author}}} Avatar" src="{{{data.feedback_author_avatar}}}" class="avatar-image" height="50" width="50" style="border-radius:20px;" loading="lazy">
		</div>

		<div>
			<div class="caw-feedback-author">
				<a href="{{{data.feedback_author_url}}}" class="caw-feedback-author-name">{{{data.feedback_author}}}</a>
				<span class="caw-feedback-date">{{{data.feedback_datetime}}}</span>
			</div>

			<div class="caw-feedback-content">{{{data.feedback_content}}}</div>
		</div>
	</div>
</script>
