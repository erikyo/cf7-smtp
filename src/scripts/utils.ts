/**
 * Toggle enabled advanced settings row
 *
 * @param {Array}       elements - an array of "nth" input
 * @param {HTMLElement} formElem - the form row
 * @param {boolean}     enabled  - show or hide the form row
 */
export function enableAdvanced(
	elements: number[],
	formElem: HTMLElement | null,
	enabled: boolean
): void {
	if ( formElem ) {
		elements.forEach( ( el ) => {
			const row = formElem.querySelector(
				`tr:nth-child(${ el })`
			) as HTMLElement;
			if ( row ) {
				row.style.display = enabled ? 'table-row' : 'none';
			}
		} );
	} else {
		// eslint-disable-next-line no-console
		console.log( `The form has no element child number ${ elements }` );
	}
}

/**
 * It takes a line of text and returns an array of two elements, the first being the date and time, and the second being
 * the rest of the line
 *
 * @param {string} raw - The line of text to extract the data from.
 * @return {Array} An array with the date and message.
 */
export function extractData(
	raw: string
): [ string, string | false, string | false ] {
	const regex = /^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (.*)|\w.+/g;
	const result = regex.exec( raw );
	return result
		? [ result[ 0 ], result[ 1 ] || false, result[ 2 ] || false ]
		: [ raw, false, false ];
}

/**
 * Delay returns a promise that resolves after the given number of milliseconds.
 *
 * @param {number} ms - The number of milliseconds to delay.
 */
export const delay = ( ms: number ): Promise< void > =>
	new Promise( ( resolve ) => setTimeout( resolve, ms ) );
