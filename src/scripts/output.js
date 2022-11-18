import { __ } from '@wordpress/i18n';
import { extractData } from './utils';

/**
 * It takes a DOM element and a message, and sets the DOM element's innerHTML to the message, with a timestamp
 *
 * @param {HTMLElement} logWrap   - The element that will contain the log messages.
 * @param {?string}     [message] - The message to be displayed in the log.
 */
export function cleanOutput(logWrap, message = '') {
	const date = new Date();
	logWrap.innerHTML =
		`<code class="logdate alignright">${__(
			'Logs has been started in',
			'cf7-smtp'
		)} ${date}</code>` + message;
}

export function appendOutput(logWrap, message = '') {
	logWrap.insertAdjacentHTML('beforeend', message);
}

/**
 * It takes a message, splits it into lines, and then adds each line to the output container with a random delay
 *
 * @param {HTMLElement}  outputContainer - the element where the output will be displayed
 * @param {string}       msg             - the message to be displayed
 * @param {boolean|null} mailSent        if the mail was sent or not
 */
export function appendOutputMultiline(outputContainer, msg, mailSent = null) {
	msg = msg.split(/\n|<br\s*\/?>/);
	let lastTimestamp = 0;

	if (msg.length) {
		msg.forEach((line, index) => {
			// TODO: regex here to search for errors

			const [raw, date, text] = extractData(line);

			/* will add the lines "softly" */
			if (raw !== '')
				setTimeout(() => {
					if (!text) {
						appendOutput(outputContainer, `<code>${raw}</code>`);
					} else if (date === lastTimestamp) {
						appendOutput(outputContainer, `<code>${text}</code>`);
					} else {
						// refresh the timestamp
						lastTimestamp = date;
						appendOutput(
							outputContainer,
							`<span class="timestamp">${date}</span><code>${text}</code>`
						);
					}
				}, 50 * index);
		});
	}

	// if the mailSent flag is set (true or false) update the container class
	if (mailSent) {
		outputContainer.classList.add('ok');
		outputContainer.classList.remove('error');
	} else {
		outputContainer.classList.add('error');
		outputContainer.classList.remove('ok');
	}
}
