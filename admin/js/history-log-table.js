// Import utility functions and CSS
import { makeAjaxRequest, sanitizeInteger } from './util';

/**
 * Document ready function for handling user interactions related to content approval workflow.
 *
 * @param {Object} $ - jQuery object.
 * @return {void}
 */
jQuery( ( $ ) => {
	// Configuration variables
	const itemsPerPage = 10;
	let currentPage    = 1;
	let orderBy        = [ 'user_name', 'asc' ];
	let loading        = false;

	// DOM elements
	const $document            = $( document );
	const $historyTable        = $( '#caw-history-table' );
	const $userFilter          = $( '#caw-user-filter' );
	const $postFilter          = $( '#caw-post-filter' );
	const $assigneeFilter      = $( '#caw-assignee-filter' );
	const $historyTableBody    = $historyTable.find( 'tbody' );
	const $paginationContainer = $( '#caw-table-pagination' );

	/**
	 * Asynchronously loads the history table content via Ajax.
	 *
	 * @param {Array} order - The sorting order (column, direction).
	 * @return {void}
	 */
	const loadTable = async ( order ) => {
		if ( loading ) {
			return;
		}

		loading = true;
		try {
			// Ajax request to load history table
			const response = await makeAjaxRequest(
				{
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

					updatePagination( response.totalPages );
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

	/**
	 * Updates the pagination controls based on the total number of pages.
	 *
	 * @param {number} totalPages - The total number of pages.
	 * @return {void}
	 */
	const updatePagination = ( totalPages ) => {
		$paginationContainer.empty();
		const maxVisiblePages = 4;
		// Add 'Prev' link
		$paginationContainer.append(
			1 === currentPage
				? $( '<a href="#" class="pagination-link disabled">Prev</a>' )
				: $( `
				<a href="#" class="pagination-link" data-page="${ currentPage - 1 }">Prev</a>
			` ),
		);

		// Add page number links
		for ( let i = 1; i <= totalPages; i++ ) {
			if (
				1 === i ||
				i === totalPages ||
				( i >= currentPage - Math.floor( maxVisiblePages / 2 ) &&
					i <= currentPage + Math.floor( maxVisiblePages / 2 ) )
			) {
				$paginationContainer.append(
					$( `<a href="#" class="pagination-link${
						currentPage === i ? ' active' : ''
					}" data-page="${ i }">${ i }</a>
				` ),
				);
			} else if ( 0 === $( '.pagination-ellipsis' ).length ) {
				const ellipsis = $(
					'<span class="pagination-ellipsis">...</span>',
				);
				$paginationContainer.append( ellipsis );
			}
		}

		// Add 'Next' link
		$paginationContainer.append(
			totalPages === currentPage
				? '<a href="#" class="pagination-link disabled">Next</a>'
				: '<a href="#" class="pagination-link" data-page="' +
						( currentPage + 1 ) +
						'">Next</a>',
		);

		// Attach click event listener to pagination links
		$( '.pagination-link' ).on( 'click', ( event ) => {
			event.preventDefault();
			const page = $( event.target ).data( 'page' );
			if ( page !== currentPage ) {
				currentPage = page;
				loadTable();
			}
		} );
	};

	// Event listeners
	$document
		.on(
			'change',
			'#caw-user-filter, #caw-post-filter, #caw-assignee-filter',
			() => {
				// Reset current page to 1 and reload the table
				currentPage = 1;
				loadTable( orderBy );
			},
		)
		.on( 'click', '#caw-history-table span.order-col', ( e ) => {
			e.preventDefault();
			// Handle column sorting
			const columnName = $( e.target ).closest( 'span.order-col' ).data( 'id' );
			const th         = $( e.target ).closest( 'th' );

			$( '#caw-history-table th' )
				.not( th )
				.removeClass( 'asc' )
				.removeClass( 'desc' );

			let order = 'asc';
			if ( th.hasClass( 'asc' ) ) {
				order = 'desc';
				th.removeClass( 'asc' ).addClass( 'desc' );
			} else {
				order = 'asc';
				th.removeClass( 'desc' ).addClass( 'asc' );
			}

			orderBy = [ columnName, order ];
			loadTable( orderBy );
		} );

	// Initial table load
	loadTable( orderBy );
} );
