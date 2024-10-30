// Import utility functions and CSS
import {
	makeAjaxRequest,
	showLoadingSpinner,
	hideLoadingSpinner,
	setAlertMessage,
	sanitizeInteger,
} from './util';
import '../scss/post.scss';

/**
 * Document ready function for handling user interactions related to content approval workflow.
 *
 * @param {Object} $ - jQuery object.
 * @return {void}
 *
 * @since   1.0.0
 */
jQuery( ( $ ) => {
	// DOM elements
	const searchInput              = $( '.caw-user-search' );
	const postID                   = parseInt( $( '#post_ID' ).val(), 10 );
	const originalPostStatus       = $( '#original_post_status' ).val();
	const askReviewButton          = $( '#ask-for-review-button' );
	const approveButton            = $( '#approve-button' );
	const cancelRequestFeedbackBtn = $( '#caw-request-feedback-btn' );
	const feedbackSubmitButton     = $( '#caw-feedback-submit-btn' );
	const loadMoreFeedbacksButton  = $( '#load-more-feedbacks' );
	const ignorereview             = $( '.cancel-review' );

	// State variables
	let loading            = false;
	let feedbackPageOffset = 0;
	let newFeedbackOffset  = 0;

	/**
	 * Sets classes for the active and completed steps, and adjusts the width of the progress bar.
	 * @param {number} index - The index of the current active step.
	 * @param {number} steps - The total number of steps in the wizard.
	 */
	function setClasses( index, steps ) {
		// Calculate the width of the progress bar based on the active step
		const p = ( index - 1 ) * ( ( 100 / steps ) - 1 );
		$( '#caw-prog' ).width( p + '%' );
	}

	// Check the status of the second and third steps
	const secondStepStatus = $( '#caw-wizard-step2' ).hasClass( 'caw-active' );
	const thirdStepStatus  = $( '#caw-wizard-step3' ).hasClass( 'caw-active' );

	// Set classes and adjust progress bar width based on step status
	if ( thirdStepStatus ) {
		setClasses( 3, $( '.caw-step-wizard ul li' ).length );
	} else if ( secondStepStatus ) {
		setClasses( 2, $( '.caw-step-wizard ul li' ).length );
	} else {
		setClasses( 1, $( '.caw-step-wizard ul li' ).length );
	}

	ignorereview.on( 'change', function( e ) {
		const isChecked = ignorereview.prop( 'checked' );
		const cawAction = isChecked ? 'show' : 'hide';

		e.preventDefault();
		$.ajax( {
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'ignore_review_process',
				cawAction,
				postID,
				nonce: cawNonceAjaxObject.ignore_review_process,
			},
			success( response ) {
				if ( ! response.success ) {
					return;
				}
				if ( isChecked ) {
					$( '#caw-main-content' ).show();
				} else {
					$( '#caw-main-content' ).hide();
				}
			},
		} );
	} );

	// Trigger the change event on page load to set the initial state
	ignorereview.trigger( 'change' );

	/**
	 * Function to toggle the visibility of the caw-assigned-user-metabox div and update the minimize button icon.
	 */
	function toggleAssignedMetabox() {
		const categoryDiv    = document.querySelector(
			'#caw-assigned-user-metabox',
		);
		const minimizeButton = document.querySelector( '.caw-minimize-button' );

		if ( categoryDiv.classList.contains( 'minimized' ) ) {
			categoryDiv.classList.remove( 'minimized' );
			minimizeButton.querySelector( '.caw-minimize-icon' ).innerText = '-';
		} else {
			categoryDiv.classList.add( 'minimized' );
			minimizeButton.querySelector( '.caw-minimize-icon' ).innerText = '+';
		}
	}

	const minimizeButton = document.querySelector( '.caw-minimize-button' );
	minimizeButton.addEventListener( 'click', toggleAssignedMetabox );
	/**
	 * Asynchronously loads more users based on the current offset and search term.
	 *
	 * Triggers an AJAX request to load additional users and appends them to the user list.
	 *
	 * @since 1.0.0
	 */
	const loadUsers = async () => {
		if ( loading ) {
			return;
		}
		loading = true;

		try {
			const response = await makeAjaxRequest( {
				action: 'caw_load_more_users',
				postID,
				nonce: cawNonceAjaxObject.load_more_users,
			} );

			if ( response.success ) {
				if ( 0 < response.users.length ) {
					response.users.forEach( ( user ) => {
						const isChecked = user.checked
							? ' checked="checked"'
							: '';

						$( '.caw-user-list' ).append( `
							<li id="${ user.ID }">
								<label>
									<input type="checkbox" name="assigned_users_meta_box[]" id="in-${ user.ID }"${ isChecked } value="${ user.ID }" />
									${ user.display_name }
								</label>
							</li>
						` );
					} );
				} else {
					// No more users to load.
					$( '#caw-user-list-container' ).append(
						'<p>There are no other users who can approve, add users first.</p>',
					);
				}
			}
		} finally {
			loading = false;
		}
	};

	// Event listener for user search input
	searchInput.on( 'input', () => {
		$( '.caw-user-list li' ).each( ( index, element ) => {
			$( element ).toggle(
				$( element )
					.find( 'label' )
					.text()
					.toLowerCase()
					.includes( searchInput.val().toLowerCase() ),
			);
		} );
	} );

	// Load users if not on the settings page
	if ( 'settings_page_content-approval-workflow-settings' !== pagenow ) {
		loadUsers();
	}

	// Click event for "Ask for Review" button.
	askReviewButton.on( 'click', async () => {
		const selectedUsers = [];
		showLoadingSpinner( askReviewButton );

		$( '#assigned-user-metabox input[type="checkbox"]:checked' ).each(
			( index, element ) => {
				selectedUsers.push( $( element ).val() );
			},
		);

		try {
			const response = await makeAjaxRequest( {
				action: 'caw_save_review_request',
				post_id: postID,
				selected_users: selectedUsers,
				nonce: cawNonceAjaxObject.save_review_request,
			} );

			setAlertMessage(
				response.success,
				response.data.message,
				response.data.warning,
			);

			if ( response.success ) {
				$( '#caw-wizard-step3' ).removeClass( 'caw-active' );
				$( '#caw-wizard-step2' ).addClass( 'caw-active' );
				setClasses( 2, $( '.caw-step-wizard ul li' ).length );
			}

			if ( 'ready' === response.data.remaining_review ) {
				$( '.caw-remaining_reviews' )
					.text( response.data.reviews_massges[ 0 ] )
					.show();
			} else if ( 0 !== response.data.remaining_review ) {
				$( '.caw-remaining_reviews' )
					.text(
						response.data.reviews_massges[ 1 ] +
							response.data.remaining_review,
					)
					.show();
			}
		} finally {
			hideLoadingSpinner( askReviewButton );
		}
	} );

	const itemsPerPage      = 10;
	const currentPage       = 1;
	const $historyTable     = $( '#caw-history-table' );
	const $userFilter       = $( '#caw-user-filter' );
	const $postFilter       = $( '#caw-post-filter' );
	const $assigneeFilter   = $( '#caw-assignee-filter' );
	const $historyTableBody = $historyTable.find( 'tbody' );

	const loadTable = async ( order ) => {
		if ( loading ) {
			return;
		}

		loading = true;
		try {
			// Ajax request to load history table
			const response = await makeAjaxRequest( {
				action: 'caw_load_history_table',
				page: currentPage,
				itemsPerPage,
				userFilter: sanitizeInteger( $userFilter.val() ),
				postFilter: sanitizeInteger( $postFilter.val() ),
				assigneeFilter: sanitizeInteger( $assigneeFilter.val() ),
				orderBy: order,
				nonce: cawHistoryTable.load_history_table,
			},
			cawHistoryTable.ajax_url,
			);

			// Process the response
			if ( response.success ) {
				const data = response.data;
				if ( Array.isArray( data ) && 0 < data.length ) {
					// Clear and populate the history table body
					$historyTableBody.empty();
					data.forEach( ( item ) => {
						const template  = wp.template(
							'content-approval-history-table',
						);
						const $template = $( template( item ) );
						$historyTableBody.append( $template );
					} );

					if ( $( '#post_ID' ).val() ) {
						$( '.caw-table-post-title' ).remove();
					}
				}
			} else {
				// Display an error message in the table body
				$historyTableBody
					.empty()
					.append(
						`<td colspan="4" style="text-align: center;">${ response.message }</td>`,
					);
			}
		} finally {
			loading = false;
		}
	};
	// Click event for "Approve" button.
	approveButton.on( 'click', async () => {
		showLoadingSpinner( approveButton );

		try {
			const response = await makeAjaxRequest( {
				action: 'caw_approve_review',
				post_id: postID,
				assignee_user_id: $( '#assignee_ID' ).val(),
				nonce: cawNonceAjaxObject.approve_review,
			} );

			if ( response.success ) {
				approveButton.remove();
				cancelRequestFeedbackBtn.remove();
			} else {
				approveButton.attr( 'disabled', false );
			}

			setAlertMessage(
				response.success,
				response.data.message,
				response.data.warning,
			);

			if ( 'ready' === response.data.final_approve_message ) {
				$( '#caw-wizard-step3' ).addClass( 'caw-active' );
				setClasses( 3, $( '.caw-step-wizard ul li' ).length );
			}
			if ( 'ready' === response.data.remaining_reviews ) {
				$( '.caw-remaining_reviews' ).text(
					response.data.reviews_massges[ 0 ],
				);
			} else if ( 0 !== response.data.remaining_reviews ) {
				$( '.caw-remaining_reviews' ).text(
					response.data.reviews_massges[ 1 ] +
						response.data.remaining_reviews,
				);
			}
			loadTable();
		} finally {
			hideLoadingSpinner( approveButton );
		}
	} );

	/**
	 * Handle the submission of content approval feedback.
	 *
	 * This script is triggered when the '#caw-feedback-submit-btn' button is clicked.
	 *
	 * @since 1.0.0
	 */
	feedbackSubmitButton.on( 'click', () => {
		saveFeedback( feedbackSubmitButton, 'caw_feedback' );
	} );

	// Click event for "Cancel Request" button.
	cancelRequestFeedbackBtn.on( 'click', async () => {
		const isConfirmed = confirm(
			'Are you sure you want to cancel the review request?',
		);

		if ( isConfirmed ) {
			showLoadingSpinner( cancelRequestFeedbackBtn );

			try {
				const response = await makeAjaxRequest( {
					action: 'caw_cancel_review_request',
					post_id: postID,
					assignee_user_id: $( '#assignee_ID' ).val(),
					nonce: cawNonceAjaxObject.cancel_review_request,
				} );

				if ( response.success ) {
					approveButton.remove();
					cancelRequestFeedbackBtn.remove();
				} else {
					cancelRequestFeedbackBtn.attr( 'disabled', false );
				}

				setAlertMessage(
					response.success,
					response.data.message,
					response.data.warning,
					$( '#caw-cancel-alert-message' ),
				);
			} finally {
				hideLoadingSpinner( cancelRequestFeedbackBtn );
			}
		}
	} );

	// Click event for "Load More Feedbacks" button.
	loadMoreFeedbacksButton.on( 'click', async () => {
		feedbackPageOffset = feedbackPageOffset + 1;

		showLoadingSpinner( loadMoreFeedbacksButton );
		try {
			const response = await makeAjaxRequest( {
				action: 'caw_load_more_feedbacks',
				postID,
				pageOffset: feedbackPageOffset,
				feedbackOffset: newFeedbackOffset,
				nonce: cawNonceAjaxObject.load_more_feedbacks,
			} );

			if ( response.success ) {
				if ( ! response.data.loadMore ) {
					loadMoreFeedbacksButton.fadeOut();
				}

				response.data.feedbackData.forEach( ( element ) => {
					const template  = wp.template( 'content-approval-feedback' );
					const $template = $( template( element ) );

					$template.insertBefore( loadMoreFeedbacksButton );
				} );
			}
		} finally {
			hideLoadingSpinner( loadMoreFeedbacksButton );
		}
	} );

	/**
	 * Save content approval feedback.
	 *
	 * @param {jQuery} button       - The button triggering the feedback submission.
	 * @param {string} feedbackType - The type of feedback (e.g., 'feedback').
	 * @return {Promise<void>}
	 */
	const saveFeedback = async ( button, feedbackType ) => {
		let feedbackAlertTimeout;
		// Disable the submit button to prevent multiple submissions.
		button.prop( 'disabled', true );

		// Get the feedback from the input fields.
		const feedback = $( '#caw-feedback-form-textarea' ).val();

		// Clear any existing feedback alert timeout.
		clearTimeout( feedbackAlertTimeout );

		// Validate if the feedback is empty.
		if ( ! feedback ) {
			button.prop( 'disabled', false );
			$( '.caw-feedback-form .alert-message' )
				.addClass( 'error-message' )
				.removeClass( 'success-message' )
				.text( feedbackRespnose.error_message )
				.fadeIn();

			// Hide the error message after 5 seconds.
			feedbackAlertTimeout = setTimeout(
				() => $( '.caw-feedback-form .alert-message' ).fadeOut(),
				5000,
			);

			return;
		}

		// Perform AJAX request to save the content approval feedback.
		try {
			showLoadingSpinner( button );
			const response = await makeAjaxRequest( {
				action: 'caw_save_feedback',
				post_id: postID,
				feedback,
				feedback_type: feedbackType,
				nonce: cawNonceAjaxObject.save_review_feedback,
			} );

			// Enable the submit button after AJAX request completion.
			button.prop( 'disabled', false );

			if ( ! response.success ) {
				// Display error message on failure.
				$( '.caw-feedback-form .alert-message' )
					.addClass( 'error-message' )
					.removeClass( 'success-message' )
					.text( response.data.message )
					.fadeIn();

				feedbackAlertTimeout = setTimeout(
					() => $( '.caw-feedback-form .alert-message' ).fadeOut(),
					5000,
				);
			} else {
				// Reset feedback input and display success message on success.
				$( '#caw-feedback-form-textarea' ).val( '' );
				$( '.caw-feedback-form .alert-message' )
					.addClass( 'success-message' )
					.removeClass( 'errors-message' )
					.text( feedbackRespnose.added_success_message )
					.fadeIn();
				$( '.caw-warning-message' ).remove();
				$(
					'<div class="caw-warning-message">' +
						response.data.warning +
						'<div>',
				).insertBefore( '#caw-feedback-submit-btn' );

				feedbackAlertTimeout = setTimeout(
					() =>
						$(
							'.caw-feedback-form .alert-message, .caw-feedback-form .caw-warning-message',
						).fadeOut(),
					5000,
				);

				// Prepend the new feedback to the feedback container.
				const template    = wp.template( 'content-approval-feedback' );
				const html        = template( response.data.feedbackData );
				newFeedbackOffset = newFeedbackOffset + 1;

				$( '#caw-feedbacks-container' ).prepend( html );
				$( '#caw-feedback-count' ).text( response.data.totalFeedback );
			}
		} catch ( error ) {
			// Display a generic error message.
			$( '.caw-feedback-form .alert-message' )
				.addClass( 'error-message' )
				.removeClass( 'success-message' )
				.text( 'Something went wrong.' )
				.fadeIn();
			$( '#caw-feedback-submit-btn' ).prop( 'disabled', false );

			feedbackAlertTimeout = setTimeout(
				() => $( '.caw-feedback-form .alert-message' ).fadeOut(),
				5000,
			);

			throw error;
		} finally {
			hideLoadingSpinner( button );
		}
	};

	$( document ).on( 'click', '.editor-post-publish-button', async function() {
		const response = await makeAjaxRequest( {
			action: 'caw_get_approval_status',
			postID,
			originalPostStatus,
			nonce: cawNonceAjaxObject.get_approval_status,
		} );

		if ( response.success && 'unapproved' === response.data.status ) {
			const tmpl = wp.template( 'unapproved-publishing-failed' );
			$( '.components-editor-notices__dismissible' ).append( tmpl );
		}
	} );

	$( document ).on(
		'click',
		'#unapproved-publishing-failed-container .components-notice__dismiss',
		function() {
			$( '#unapproved-publishing-failed-container' ).remove();
		},
	);
} );
