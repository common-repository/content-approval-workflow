/**
 * Makes an asynchronous Ajax request using jQuery.
 *
 * @param {Object} data - The data object for the Ajax request.
 * @param {string} url  - ajax url
 * @return {Promise}    - A Promise that resolves with the Ajax response.
 */
export const makeAjaxRequest = async (
	data,
	url = cawNonceAjaxObject.ajax_url,
) => {
	return await jQuery.ajax( {
		type: 'POST',
		url,
		data,
	} );
};

/**
 * Show a loading spinner on a button.
 *
 * @param {Object} button - The button element to show the spinner on.
 * @return {void}
 *
 * @since 1.0.0
 */
export const showLoadingSpinner = ( button ) => {
	if ( button instanceof jQuery ) {
		button.attr( 'disabled', true );
		const spinner = jQuery( '<div class="caw-spinner"></div>' )
			.append( '<div class="caw-dot caw-dot1"></div>' )
			.append( '<div class="caw-dot caw-dot2"></div>' )
			.append( '<div class="caw-dot caw-dot3"></div>' );
		button.prepend( spinner );
		button.find( 'span' ).hide();
		spinner.show();
	}
};

/**
 * Hide the loading spinner on a button.
 *
 * @param {Object} button - The button element to hide the spinner on.
 * @return {void}
 *
 * @since 1.0.0
 */
export const hideLoadingSpinner = ( button ) => {
	if ( button instanceof jQuery ) {
		button.attr( 'disabled', false );
		button.find( '.caw-spinner' ).remove();
		button.find( 'span' ).show();
	}
};

/**
 * Set an alert message on the page.
 *
 * @param {boolean} status     - Success status.
 * @param {string}  message    - Message to display.
 * @param {string}  warning    - Optional warning message.
 * @param {Object}  messageBox - The jQuery object representing the message box.
 * @return {void}
 *
 * @since 1.0.0
 */
export const setAlertMessage = (
	status,
	message,
	warning = false,
	messageBox = jQuery( '#caw-alert-message' ),
) => {
	const className      = status ? 'caw-success-message' : 'caw-error-message';
	const warningMessage = warning
		? `<div class="caw-warning-message">${ warning }</div>`
		: '';

	messageBox
		.removeClass( 'caw-error-message caw-success-message' )
		.addClass( className )
		.html( `${ message }${ warningMessage }` )
		.show();
};

/**
 * Sanitize integer field value.
 *
 * @param {string} value - The integer field value to sanitize.
 * @return {number|null} - The sanitized integer value or null if not a valid integer.
 */
export const sanitizeInteger = ( value ) => {
	const sanitizedValue = parseInt( value, 10 );
	return isNaN( sanitizedValue ) ? null : sanitizedValue;
};
