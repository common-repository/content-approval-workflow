<select name="caw_email_template" id="caw_email_template">
	<option value="ask_for_review">
		<?php esc_html_e( 'Ask For Review', 'content-approval-workflow' ); ?>
	</option>
	<option value="approve_review">
		<?php esc_html_e( 'Approve Review', 'content-approval-workflow' ); ?>
	</option>
	<option value="feedback">
		<?php esc_html_e( 'Feedback', 'content-approval-workflow' ); ?>
	</option>
</select>
<div class="content-approval">
	<p>
		<?php esc_html_e( 'To enter Post Title', 'content-approval-workflow' ); ?><span><b>{post_title}</b></span>
	</p>
	<p>
		<?php esc_html_e( 'To enter Post Link', 'content-approval-workflow' ); ?><span><b>{post_link}</b></span>
	</p>
	<p>
		<?php esc_html_e( 'To enter Post Author', 'content-approval-workflow' ); ?><span><b>{post_author}</b></span>
	</p>
	<p>
		<?php esc_html_e( 'To enter Assignee', 'content-approval-workflow' ); ?><span><b>{assignee}</b></span>
	</p>
	<p>
		<?php esc_html_e( 'To enter Recipient', 'content-approval-workflow' ); ?><span><b>{recipient}</b></span>
	</p>
	<p data-template-id="feedback_author">
		<?php esc_html_e( 'To enter Feedback Author', 'content-approval-workflow' ); ?><span><b>{feedback_author}</b></span>
	</p>
</div>
<h2 id="caw-template-title"></h2>
