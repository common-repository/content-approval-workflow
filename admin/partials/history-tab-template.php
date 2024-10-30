<div class="caw-container">
	<div id="caw-filter-container" class="filter-container">
		<div>
			<label for="caw-user-filter" class="filter-label">
				<?php esc_html_e( 'User Filter:', 'content-approval-workflow' ); ?>
			</label>
			<select id="caw-user-filter" class="filter-dropdown">
				<option value="">
					<?php esc_html_e( 'All', 'content-approval-workflow' ); ?>
				</option>

				<?php foreach ( $distinct_users as $user ) : ?>
					<option value="<?php echo esc_attr( $user['meta_value'] ); ?>">
						<?php echo esc_html( get_user_by( 'id', $user['meta_value'] )->display_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( 'settings' === $atts['page'] ) : ?>
			<div>
				<label for="caw-post-filter" class="filter-label">
					<?php esc_html_e( 'Post Filter:', 'content-approval-workflow' ); ?>
				</label>
				<select id="caw-post-filter" class="filter-dropdown">
					<option value="">
						<?php esc_html_e( 'All', 'content-approval-workflow' ); ?>
					</option>

					<?php foreach ( $distinct_posts as $caw_post ) : ?>
						<option value="<?php echo esc_attr( $caw_post['meta_value'] ); ?>">
							<?php echo esc_html( get_the_title( $caw_post['meta_value'] ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php elseif ( 'post' === $atts['page'] ) : ?>
			<select id="caw-post-filter" class="filter-dropdown" style="display: none;">
				<option value="<?php echo esc_attr( get_the_ID() ); ?>" selected></option>
			</select>
		<?php endif; ?>

		<div>
			<label for="caw-assignee-filter" class="filter-label">
				<?php esc_html_e( 'Assignee Filter:', 'content-approval-workflow' ); ?>
			</label>
			<select id="caw-assignee-filter" class="filter-dropdown">
				<option value="">
					<?php esc_html_e( 'All', 'content-approval-workflow' ); ?>
				</option>

				<?php foreach ( $distinct_assignees as $assignee ) : ?>
					<option value="<?php echo esc_attr( $assignee['meta_value'] ); ?>">
						<?php echo esc_html( get_user_by( 'id', $assignee['meta_value'] )->display_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="caw-table-container">
		<table id="caw-history-table" class="wp-list-table widefat fixed striped table-view-list pages">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-title column-primary sorted asc" aria-sort="ascending">
						<span class="order-col" data-id="user_name">
							<span>
								<?php esc_html_e( 'User Name', 'content-approval-workflow' ); ?>
							</span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</span>
					</th>
					<?php if ( 'settings' === $atts['page'] ) : ?>
					<th scope="col" class="manage-column column-title column-primary sorted" aria-sort="ascending">
						<span class="order-col" data-id="post_title">
							<span>
								<?php esc_html_e( 'Post Title', 'content-approval-workflow' ); ?>
							</span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</span>
					</th>
					<?php endif; ?>
					<th scope="col" class="manage-column column-title column-primary sorted" aria-sort="ascending">
						<span class="order-col" data-id="assigne_name">
							<span>
								<?php esc_html_e( 'Assignee Name', 'content-approval-workflow' ); ?>
							</span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</span>
					</th>
					<th scope="col" class="manage-column column-title column-primary sorted" aria-sort="ascending">
						<span class="order-col" data-id="status">
							<span>
								<?php esc_html_e( 'Status', 'content-approval-workflow' ); ?>
							</span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</span>
					</th>
					<th scope="col" class="manage-column column-title column-primary sorted" aria-sort="ascending">
						<span class="order-col" data-id="create_at">
							<span>
								<?php esc_html_e( 'Created At', 'content-approval-workflow' ); ?>
							</span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</span>
					</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="caw-table-pagination"></div>
</div>
