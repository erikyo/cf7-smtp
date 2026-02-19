import { __ } from '@wordpress/i18n';
import { extractData } from './utils';

/**
 * It takes a DOM element and a message, and sets the DOM element's innerHTML to the message, with a timestamp
 *
 * @param {HTMLElement} logWrap   - The element that will contain the log messages.
 * @param {?string}     [message] - The message to be displayed in the log.
 */
export function cleanOutput(
	logWrap: HTMLElement,
	message: string = ''
): void {
	const date = new Date();
	logWrap.innerHTML =
		`<code class="logdate alignright">${ __(
			'Logs has been started in',
			'cf7-smtp'
		) } ${ date }</code>` + message;
}

/**
 * Append a message to the output container.
 *
 * @param logWrap The element where the output will be displayed.
 * @param message The message to be displayed in the log.
 */
export function appendOutput(
	logWrap: HTMLElement,
	message: string = ''
): void {
	logWrap.insertAdjacentHTML( 'beforeend', message );
}

/**
 * It takes a message, splits it into lines, and then adds each line to the output container with a random delay
 *
 * @param {HTMLElement}  outputContainer - the element where the output will be displayed
 * @param {string}       msg             - the message to be displayed
 * @param {boolean|null} mailSent        if the mail was sent or not
 */
export function appendOutputMultiline(
	outputContainer: HTMLElement,
	msg: string,
	mailSent: boolean | null = null
): void {
	const lines = msg.split( /\n|<br\s*\/?>/ );
	let lastTimestamp = 0;

	if ( lines.length ) {
		lines.forEach( ( line, index ) => {
			const [ raw, date, text ] = extractData( line );
			const newDate = Number( date );

			/* will add the lines "softly" */
			if ( raw !== '' ) {
				setTimeout( () => {
					if ( ! text ) {
						appendOutput(
							outputContainer,
							`<code>${ raw }</code>`
						);
					} else if ( newDate === lastTimestamp ) {
						appendOutput(
							outputContainer,
							`<code>${ text }</code>`
						);
					} else {
						// refresh the timestamp
						lastTimestamp = newDate;
						appendOutput(
							outputContainer,
							`<span class="timestamp">${ date }</span><code>${ text }</code>`
						);
					}
				}, 50 * index );
			}
		} );
	}

	// if the mailSent flag is set (true or false) update the container class
	if ( mailSent ) {
		outputContainer.classList.add( 'ok' );
		outputContainer.classList.remove( 'error' );
	} else {
		outputContainer.classList.add( 'error' );
		outputContainer.classList.remove( 'ok' );
	}
}
