/**
 * global window.smtp_settings, window.smtp_settings.nonce
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { enableAdvanced } from './utils';
import { fetchAndRetry } from './mailFetch';
import { appendOutput, appendOutputMultiline, cleanOutput } from './output';

apiFetch.use(apiFetch.createNonceMiddleware(window.smtp_settings.nonce));

/**
 *  Email Response box
 *
 *  @member {HTMLElement} formElem - the Email form used to test email functionalities
 *  @member {HTMLElement} responseBox - the wrapper for the smtp server messages
 */
export const formElem = document.querySelector('#sendmail-testform form');
export const responseBox = document.querySelector('#sendmail-response pre');

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

	/* Initialize the response box and show a welcome message */
	cleanOutput(
		responseBox,
		'<code>' +
			__('Mail Server initialization completed!', 'cf7-smtp') +
			'</code>'
	);

	/**
	 *  Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
	 */
	formElem.addEventListener('submit', (e) => {
		e.preventDefault();
		/* The form inputs data */
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
			)} <span class="mail-init animation-start">✉️</span></code>`
		);

		apiFetch({
			path: '/cf7-smtp/v1/sendmail',
			method: 'POST',
			data,
		})
			.then((r) => {
				if (r.status === 'success') {
					appendOutputMultiline(responseBox, r.message, true);
				}
				return r;
			})
			.then((mailResp) => {
				return fetchAndRetry(mailResp, 500, 5);
			})
			.catch((errMsg) => {
				appendOutput(
					responseBox,
					__('OOOPS something went wrong!', 'cf7-smtp')
				);
				console.log(errMsg);
			});
	});
}

window.onload = smtpAdmin();
