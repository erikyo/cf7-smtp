/**
 * global window.smtp_settings, window.smtp_settings.nonce
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { enableAdvanced, extractData } from './utils';

apiFetch.use(apiFetch.createNonceMiddleware(window.smtp_settings.nonce));

export function smtpAdmin() {
	/**
	 * This is the code that saves the settings when the user presses ctrl-s.
	 */
	if (document.querySelector('#cf7-smtp-settings')) {
		// Save on ctrl-s keypress
		document.addEventListener('keydown', (e) => {
			if (e.ctrlKey && e.key === 's') {
				e.preventDefault();
				document.querySelector('#cf7-smtp-settings #submit').click();
			}
		});
	}

	/**
	 *  Variables needed to set SMTP connetion
	 *
	 *  @member {HTMLElement} formSelectDefault - cf7_smtp_preset
	 *  @member {HTMLElement} formSelectHost - cf7_smtp_host
	 *  @member {HTMLElement} formSelectPort - cf7_smtp_port
	 */
	const formSelectDefault = document.getElementById('cf7_smtp_preset');
	const formSelectHost = document.getElementById('cf7_smtp_host');
	const formSelectPort = document.getElementById('cf7_smtp_port');

	/**
	 * Sets the values of the SMTP connection according to the value selected by the user.
	 */
	formSelectDefault.addEventListener('change', (e) => {
		const selectedEl = e.target[e.target.selectedIndex];
		if (selectedEl) {
			const authRadio = document.querySelector(
				'.auth-' + selectedEl.dataset.auth
			);
			authRadio.checked = true;
			formSelectHost.value = selectedEl.dataset.host;
			formSelectPort.value = selectedEl.dataset.port;
		}
	});

	/**
	 * Enables the SMTP settings
	 */
	const smtpEnabled = document.querySelector('#cf7_smtp_enabled');
	const formSmtpSection = document.querySelector(
		'#cf7-smtp-settings .form-table:first-of-type'
	);
	enableAdvanced([2, 3, 4, 5, 6, 7], formSmtpSection, smtpEnabled.checked);

	smtpEnabled.addEventListener('click', () => {
		enableAdvanced(
			[2, 3, 4, 5, 6, 7],
			formSmtpSection,
			smtpEnabled.checked
		);
	});

	/**
	 *  Email Response box
	 *
	 *  @member {HTMLElement} formElem - the Email form used to test email functionalities
	 *  @member {HTMLElement} responseBox - the wrapper for the smtp server messages
	 */
	const formElem = document.querySelector('#sendmail-testform form');
	const responseBox = document.querySelector('#sendmail-response pre');

	responseBox.classList.add('enabled');

	/* Initialize the response box and show a welcome message */
	cleanOutput(
		responseBox,
		'<code>' +
			__('Mail Server initialization completed!', 'cf7-smtp') +
			'</code>'
	);

	/**
	 * It takes a DOM element and a message, and sets the DOM element's innerHTML to the message, with a timestamp
	 *
	 * @param {HTMLElement} logWrap   - The element that will contain the log messages.
	 * @param {?string}     [message] - The message to be displayed in the log.
	 */
	function cleanOutput(logWrap, message = '') {
		const date = new Date();
		logWrap.innerHTML =
			`<code class="logdate alignright">${__(
				'Logs has been started in',
				'cf7-smtp'
			)} ${date}</code>` + message;
	}

	function appendOutput(logWrap, message = '') {
		logWrap.insertAdjacentHTML('beforeend', message);
	}

	/**
	 * It takes a message, splits it into lines, and then adds each line to the output container with a random delay
	 *
	 * @param {HTMLElement}  outputContainer - the element where the output will be displayed
	 * @param {string}       msg             - the message to be displayed
	 * @param {boolean|null} mailSent        if the mail was sent or not
	 */
	function OutputMessage(outputContainer, msg, mailSent = null) {
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
							appendOutput(
								outputContainer,
								`<code>${raw}</code>`
							);
						} else if (date === lastTimestamp) {
							appendOutput(
								outputContainer,
								`<code>${text}</code>`
							);
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

	/**
	 * Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
	 *
	 * @param {Object} res the nonce to get the next request
	 */
	function getSmtpLog(res) {
		return apiFetch({
			path: '/cf7-smtp/v1/get_log',
			method: 'POST',
			data: {
				nonce: res.nonce,
			},
		}).then((result) => {
			return result;
		});
	}

	/**
	 * Delay returns a promise that resolves after the given number of milliseconds.
	 *
	 * @param {number} ms - The number of milliseconds to delay.
	 */
	const delay = (ms) => new Promise((r) => setTimeout(r, ms));

	/**
	 * "If the getSmtpLog function fails, wait a bit and try again."
	 *
	 * The function takes three parameters:
	 *
	 * mailResp: The response from the mail server.
	 * waitTime: The amount of time to wait before retrying.
	 * attempts: The number of times we've tried to get the log.
	 * The function returns a promise that resolves to the log
	 *
	 * @param {Object} mailResp - The response from the mailer.
	 * @param {int}    waitTime - The amount of time to wait between attempts.
	 * @param {int}    attempts - The number of times we've tried to fetch the log.
	 *
	 * @return A promise that resolves to the smtp log for the given mail response.
	 */
	function fetchAndRetry(mailResp, waitTime, attempts) {
		// try to get the error message if available
		function retry(err) {
			if (attempts > 0) {
				throw err;
			}
			return delay(waitTime * (attempts + attempts)).then(() =>
				fetchAndRetry(mailResp, waitTime, ++attempts)
			);
		}

		return getSmtpLog(mailResp)
			.then((logResp) => {
				// Error
				if (logResp.status === 'error') {
					appendOutput(
						responseBox,
						`<code>${__('🆘 Failed!', 'cf7-smtp')}</code>`
					);

					if (logResp.message.length) {
						const errorLog = logResp.message.join();
						// then append the server response
						return OutputMessage(responseBox, errorLog);
					}
				}

				// Quit
				if (logResp.message.match(/CLIENT -> SERVER: QUIT/g)) {
					return appendOutput(
						responseBox,
						`<code>${__(
							'💻 server has closed the connection!',
							'cf7-smtp'
						)}</code>`
					);
				}

				// Success
				if (logResp.status === 'success') {
					return appendOutput(
						responseBox,
						`<code>${__('✅ Success!', 'cf7-smtp')}</code>`
					);
				}

				// otherwise if nothing match output the server response
				const resp = logResp.message;
				return appendOutput(responseBox, `<code>${resp}</code>`);
			})
			.then(retry);
	}

	/**
	 *  Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
	 */
	formElem.addEventListener('submit', (e) => {
		e.preventDefault();
		const formData = new FormData(e.target);
		const data = {};
		data.nonce = window.smtp_settings.nonce;

		for (const [key, value] of formData.entries()) {
			data[key] = value;
		}

		/* clean the previous results*/
		cleanOutput(responseBox);

		appendOutput(
			responseBox,
			`<code>${__(
				"Let's start a new server connection…",
				'cf7-smtp'
			)} <span class="mail-init">✉️</span></code>`
		);

		apiFetch({
			path: '/cf7-smtp/v1/sendmail',
			method: 'POST',
			data,
		})
			.then((r) => {
				if (r.status === 'success') {
					OutputMessage(responseBox, r.message, true);
					return r;
				}
				// is waiting
				appendOutput(
					responseBox,
					'<code>' +
						[r.status, r.protocol, r.message].join(' - ') +
						'</code>'
				);
			})
			.then((mailResp) => {
				fetchAndRetry(mailResp, 500, 5);
			})
			.catch((errMsg) => {
				console.log(errMsg);
			});
	});
}

window.onload = smtpAdmin();
