<!-- Visual The Process Of Meta Box -->
<div id="caw-step-wizard-container">
	<div class="caw-step-wizard" role="navigation">
		<div class="caw-progress">
			<div class="caw-progressbar empty"></div>
			<div id="caw-prog" class="caw-progressbar"></div>
		</div>
		<ul>
			<li class='caw-active' >
				<button>
				<div class='caw-step'><?php esc_html_e( '1', 'content-approval-workflow' ); ?></div>
				<div class='caw-title'><?php esc_html_e( 'Content Created', 'content-approval-workflow' ); ?></div>
				</button>
			</li>
			<li class='<?php echo esc_attr( ! empty( $value ) ? 'caw-active' : '' ); ?>' id='caw-wizard-step2'>
				<button>
				<div class='caw-step'><?php esc_html_e( '2', 'content-approval-workflow' ); ?></div>
				<div class='caw-title'><?php esc_html_e( 'Asked for Approval', 'content-approval-workflow' ); ?></div>
				</button>
			</li>
			<li class='<?php echo 'ready' === $remaining_reviews ? 'caw-active' : ''; ?>' id='caw-wizard-step3'>
				<button>
				<div class='caw-step'><?php esc_html_e( '3', 'content-approval-workflow' ); ?></div>
				<div class='caw-title'><?php esc_html_e( 'Approved', 'content-approval-workflow' ); ?></div>
				</button>
			</li>
		</ul>
	</div>
</div>
<?php if ( 0 < count( $user_can_approve ) ) : ?>
	<?php if ( $user_assignee_id ) : ?>
		<?php if ( in_array( (int) $current_user_id, $pending_review_users, true ) ) : ?>
			<p class="caw-assigned-message">
				<?php echo esc_html( ucwords( get_userdata( $user_assignee_id )->display_name ) ) . ' ' . esc_html__( 'has asked you to approve this ', 'content-approval-workflow' ) . ' ' . esc_html( $post->post_type ) . '.'; ?>
			</p>
				<button id="approve-button" class="button-primary">
					<span>
						<?php esc_html_e( 'Approve', 'content-approval-workflow' ); ?>
					</span>
				</button>
				<button id="caw-request-feedback-btn" class="button-primary">
					<span>
						<?php esc_html_e( 'Cancel Review Request', 'content-approval-workflow' ); ?>
					</span>
				</button>
		<?php endif; ?>
	<?php endif; ?>

	<input type="hidden" id="assignee_ID" value="<?php echo esc_attr( $user_assignee_id ); ?>">
	<div id="caw-cancel-alert-message"></div>
<?php endif; ?>
<?php if ( 0 < count( $user_can_request ) ) : ?>
	<div id="assigned-user-metabox" class="category-div">
		<div id="assigned-user-metabox" class="caw-category-div">
			<div class="caw-ask-approval">
				<h2 class="caw-approval-heading">
					<?php esc_html_e( 'Ask for Approval', 'content-approval-workflow' ); ?>
				</h2>
				<button class="caw-minimize-button">
					<span class="caw-minimize-icon"><?php echo in_array( (int) $current_user_id, $pending_review_users, true ) ? '+' : '-'; ?></span>
				</button>
			</div>
			<div id="caw-assigned-user-metabox" class='<?php echo in_array( (int) $current_user_id, $pending_review_users, true ) ? 'minimized' : null; ?>'>
				<?php if ( 0 < $min_required_reviews ) : ?>
					<p class="min_required_reviews">
						<?php echo esc_html__( 'Minimum reviews required for final approval: ', 'content-approval-workflow' ) . esc_html( $min_required_reviews ); ?>
					</p>
					<?php if ( 'ready' === $remaining_reviews ) : ?>
						<p class="caw-remaining_reviews">
							<?php echo esc_html__( 'Ready for final approval. ', 'content-approval-workflow' ); ?>
						</p>
					<?php elseif ( 0 === $remaining_reviews || empty( $remaining_reviews ) ) : ?>
						<p class="caw-remaining_reviews" style="display: none;">
							<?php echo esc_html__( 'Not Assigned', 'content-approval-workflow' ) . esc_html( $remaining_reviews ); ?>
						</p>
					<?php else : ?>
						<p class="caw-remaining_reviews">
							<?php echo esc_html__( 'Remaining reviews for final approval: ', 'content-approval-workflow' ) . esc_html( $remaining_reviews ); ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

				<input type="text" class="caw-user-search"
					placeholder="<?php esc_html_e( 'Search users', 'content-approval-workflow' ); ?>">
				<div id="caw-user-list-container">
					<ul class="caw-user-list"></ul>
				</div>
				<button id="ask-for-review-button" class="button-primary">
					<span>
						<?php esc_html_e( 'Ask for Review', 'content-approval-workflow' ); ?>
					</span>
				</button>

				<?php if ( ! empty( $user_reviews ) ) : ?>
					<h2 class="caw-approval-heading caw-success-message">
						<?php esc_html_e( 'Users Who Approved:', 'content-approval-workflow' ); ?>
					</h2>
					<ul class="caw-reviewed-users-list">
						<?php foreach ( $user_reviews as $reviewer ) : ?>
							<li>
								<?php echo esc_html( $reviewer ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<div id="caw-alert-message"></div>
	</div>
<?php endif; ?>
