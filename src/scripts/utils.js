/**
 * Toggle enabled advanced settings row
 *
 * @param {Array}       elements - an array of "nth" input
 * @param {HTMLElement} formElem - the form row
 * @param {boolean}     enabled  - show or hide the form row
 */
export function enableAdvanced( elements, formElem, enabled ) {
	if ( formElem ) {
		elements.forEach( ( el ) => {
			formElem.querySelector( `tr:nth-child(${ el })` ).style.display =
				enabled ? 'table-row' : 'none';
		} );
	} else {
		// eslint-disable-next-line no-console
		console.log(
			`Cannot find form element ${ elements } of ${ toString(
				formElem
			) }`
		);
	}
}

/**
 * It takes a line of text and returns an array of two elements, the first being the date and time, and the second being
 * the rest of the line
 *
 * @param {string} raw - The line of text to extract the data from.
 * @return {Array} An array with the date and message.
 */
export function extractData( raw ) {
	const regex = /^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (.*)|\w.+/g;
	return regex.exec( raw ) || [ raw, false, false ];
}

/**
 * Delay returns a promise that resolves after the given number of milliseconds.
 *
 * @param {number} ms - The number of milliseconds to delay.
 */
export const delay = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
