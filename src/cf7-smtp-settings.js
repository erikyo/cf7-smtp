import './styles/settings.scss';
import apiFetch from '@wordpress/api-fetch';
import { error } from '../../../../wp-includes/js/dist/redux-routine';

apiFetch.use(apiFetch.createNonceMiddleware(window.smtp_settings.nonce));

const smtpAdmin = () => {
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
	 * Toggle enabled advanced settings row
	 *
	 * @param {HTMLElement} formElem - the form row
	 * @param {boolean}     enabled  - show or hide the form row
	 */
	function enableAdvanced(formElem, enabled) {
		formElem.querySelector('tr:nth-child(3)').style.display = enabled
			? 'table-row'
			: 'none';
		formElem.querySelector('tr:nth-child(4)').style.display = enabled
			? 'table-row'
			: 'none';
	}

	const smtpAdvancedOptions = document.querySelector('#cf7_smtp_advanced');
	const formAdvancedSection = document.querySelector(
		'#cf7-smtp-settings .form-table:last-of-type'
	);
	enableAdvanced(formAdvancedSection, smtpAdvancedOptions.checked);

	/* Adding an event listener to the smtpAdvancedOptions element. */
	smtpAdvancedOptions.addEventListener('click', () => {
		enableAdvanced(formAdvancedSection, smtpAdvancedOptions.checked);
	});

	const formElem = document.querySelector('#sendmail-testform form');
	const responseBox = document.querySelector('#sendmail-response code');

	/**
	 * It takes a message, splits it into lines, and then adds each line to the output container with a random delay
	 *
	 * @param {HTMLElement} outputContainer - the element where the output will be displayed
	 * @param {string}      msg             - the message to be displayed
	 * @param {boolean}     mailSent        if the mail was sent or not
	 */
	function OutputMessage(outputContainer, msg, mailSent = false) {
		msg = msg.split(/\n/);
		if (msg.length) {
			outputContainer.innerHTML = '';
			msg.forEach((line) => {
				/* will add the lines "softly" */
				setTimeout(() => {
					outputContainer.innerHTML += line;
				}, 200 * (Math.random() * 200));

				// TODO: regex here to search for errors
			});
		}
		if (mailSent) {
			outputContainer.classList.add('error');
			outputContainer.classList.remove('ok');
		} else {
			outputContainer.classList.add('ok');
			outputContainer.classList.remove('error');
		}
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

		apiFetch({
			path: '/cf7-smtp/v1/sendmail',
			method: 'POST',
			data,
		})
			.then((r) => {
				if (r.message) {
					console.log(r);
					OutputMessage(responseBox, r.message, true);
				}
			})
			.catch((err) => {
				OutputMessage(responseBox, err.message, false);

				setTimeout(function () {
					apiFetch({
						path: '/cf7-smtp/v1/get_errors',
						method: 'POST',
						data: {
							nonce: err.nonce,
						},
					})
						.then((resp) => {
							console.log(resp.message);
							if (resp.message.errors) {
								const errorMessage =
									resp.message.errors.wp_mail_failed.join(
										'\r\n'
									);
								OutputMessage(responseBox, errorMessage, false);
							}
						})
						.catch((err) => console.log(err));
				}, 1000);
			});
	});

	/* variables needed to set via js the connection parameters */
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
};

window.onload = smtpAdmin();
