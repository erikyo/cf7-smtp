/* global smtp_settings */
import apiFetch from '@wordpress/api-fetch';
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
		'<code>Mail Server initialization completed!</code>'
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
			`<code class="logdate alignright">Log start ${date}</code>` +
			message;
	}

	/**
	 * It takes a message, splits it into lines, and then adds each line to the output container with a random delay
	 *
	 * @param {HTMLElement}  outputContainer - the element where the output will be displayed
	 * @param {string}       msg             - the message to be displayed
	 * @param {boolean|null} mailSent        if the mail was sent or not
	 */
	function OutputMessage(outputContainer, msg, mailSent = null) {
		msg = msg.split(/\n/);
		let lastTimestamp = 0;

		if (msg.length) {
			msg.forEach((line, index) => {
				// TODO: regex here to search for errors

				const [raw, date, text] = extractData(line);

				/* will add the lines "softly" */
				setTimeout(() => {
					if (!text) {
						outputContainer.insertAdjacentHTML(
							'beforeend',
							`<code>${raw}</code>`
						);
					} else if (date === lastTimestamp) {
						outputContainer.insertAdjacentHTML(
							'beforeend',
							`<code>${text}</code>`
						);
					} else {
						// refresh the timestamp
						lastTimestamp = date;
						outputContainer.insertAdjacentHTML(
							'beforeend',
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

	const delay = (ms) => new Promise((r) => setTimeout(r, ms));

	/**
	 *  Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
	 *
	 * @param {Object} error api response with errors
	 */
	async function onApiError(error) {
		return apiFetch({
			path: '/cf7-smtp/v1/get_errors',
			method: 'POST',
			data: {
				nonce: error.nonce,
			},
		}).then((result) => {
			return result;
		});
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

		responseBox.insertAdjacentHTML(
			'beforeend',
			`<code>${"Let's start a new server connection... ✉️"}</code>`
		);

		apiFetch({
			path: '/cf7-smtp/v1/sendmail',
			method: 'POST',
			data,
		})
			.then((r) => {
				if (r.status === 'success') {
					OutputMessage(responseBox, r.message, true);
				} else {
					responseBox.insertAdjacentHTML(
						'beforeend',
						'<code>' +
							[r.status, r.protocol, r.message].join(' - ') +
							'</code>'
					);

					// try to get the error message if available
					let connectionClose = 'false';
					for (let attempt = 0; attempt <= 5; attempt++) {
						if (connectionClose === true) {
							break;
						}
						return new Promise((resolve) => {
							delay(1000 * attempt)
								.then(() => onApiError(r))
								.then((errorResponse) => {
									let errResponse = '';
									if (errorResponse.message.errors) {
										errResponse +=
											errorResponse.message.errors.wp_mail_failed.join();
									} else {
										errResponse += errorResponse.message;
									}
									responseBox.insertAdjacentHTML(
										'beforeend',
										`<code>${errResponse}</code>`
									);
									if (errorResponse.status === 'success') {
										connectionClose = true;
										resolve(connectionClose);
									}
								});
						});
					}
				}
			})
			.catch((errMsg) => errMsg);
	});
}

window.onload = smtpAdmin();
