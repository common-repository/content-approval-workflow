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

<script type="text/html" id="tmpl-content-approval-history-table">
<tr>
	<td><a href="{{{data.user_author_link}}}">{{{data.user_name}}}</a></td>
	<td class="caw-table-post-title"><a href="{{{data.post_edit_link}}}">{{{data.post_title}}}</a></td>
	<td><a href="{{{data.assigne_link}}}">{{{data.assigne_name}}}</a></td>
	<td>{{{data.status}}}</td>
	<td>{{{data.created_at}}}</td>
</tr>
</script>
