// Import utility functions and CSS.
import '../scss/settings.scss';

/**
 * Document ready function for handling user interactions related to content approval workflow settings.
 *
 * @param {Object} $ - jQuery object.
 * @return {void}
 * @since   1.1.0
 */
jQuery( ( $ ) => {
	const $tabWrapper    = $( '.nav-tab-wrapper' );
	const $emailTemplate = $( '#caw_email_template' );

	/**
	 * Switches the active tab based on the provided tab ID.
	 *
	 * @param {string} tabId - The ID of the tab to switch to.
	 * @return {void}
	 */
	const switchTab = ( tabId ) => {
		$tabWrapper.find( 'button' ).removeClass( 'nav-tab-active' );
		$( `.nav-tab-wrapper button[data-id="${ tabId }"]` ).addClass(
			'nav-tab-active',
		);
		$( '.caw-tab-content' ).hide();
		$( tabId ).show();

		sessionStorage.setItem( 'caw_active_tab', tabId );
	};

	/**
	 * Shows the email template based on the provided ID and updates the title.
	 *
	 * @param {string} id    - The ID of the email template.
	 * @param {string} title - The title of the email template.
	 * @return {void}
	 */
	const showEmailTemplate = ( id, title ) => {
		$( '[data-template-id="feedback_author"]' )
			.closest( 'p' )
			.toggle( 'feedback' === id );

		$( '#caw-template-title' ).text( title + ' Template' );
		$( '#caw-email' ).find( 'tr' ).hide();
		$( '#caw_' + id + '_subject' )
			.closest( 'tr' )
			.show();
		$( '#wp-caw_' + id + '_message-wrap' )
			.closest( 'tr' )
			.show();
	};

	// Event listener for tab buttons
	$tabWrapper.find( 'button' ).click( ( event ) => {
		event.preventDefault();
		const tabId = $( event.target ).data( 'id' );

		if ( tabId.concat( '-tab' ) !== window.location.hash ) {
			switchTab( tabId );
			window.location.hash = tabId.replace( '#', '' ).concat( '-tab' );
		}
	} );

	// Retrieve active tab from session storage
	const activeTab = sessionStorage.getItem( 'caw_active_tab' );

	// Event listener for email template dropdown
	$emailTemplate
		.on( 'change', ( e ) => {
			showEmailTemplate(
				$( e.target ).val(),
				$( e.target ).find( 'option:selected' ).text(),
			);
			sessionStorage.setItem( 'caw_email_template', $( e.target ).val() );
		} )
		.val( sessionStorage.getItem( 'caw_email_template' ) || 'ask_for_review' )
		.trigger( 'change' );

	/**
	 * Attach an 'input' event listener to dynamically filter checkboxes based on search input.
	 *
	 * This function sets up an event listener for the 'input' event on checkboxes with the class
	 * 'caw-search' within an element with the class 'caw-checkbox-field'. It filters the checkboxes
	 * based on the input value, hiding or showing them accordingly.
	 *
	 * @param {Event} e - The input event object.
	 * @return {void}
	 */
	$( document ).on( 'input', '.caw-checkbox-field input.caw-search', ( e ) => {
		// Traverse the DOM to find the checkboxes and filter them based on the search value.
		$( e.target )
			.parent()
			.find( '.caw-checkbox-options label' )
			.each( ( index, item ) => {
				$( item )
					.closest( 'li' )
					.toggle(
						$( item )
							.text()
							.toLowerCase()
							.includes( $( e.target ).val().toLowerCase() ),
					);
			} );
	} );

	// Set initial tab based on hash
	if ( 'settings_page_content-approval-workflow-settings' === pagenow ) {
		if ( activeTab ) {
			switchTab( activeTab );
		} else {
			const hasTabs    = [
				'#caw-notification-tab',
				'#caw-email-tab',
				'#caw-history-tab',
			];
			const defaultTab = hasTabs.find(
				( hasTab ) => window.location.hash === hasTab,
			);
			switchTab( defaultTab || '#caw-general' );
		}
	}
} );
