import './styles/settings.scss';
import apiFetch from '@wordpress/api-fetch';

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

	/**
	 *  Email Response box
	 *
	 *  @member {HTMLElement} formElem - the Email form used to test email functionalities
	 *  @member {HTMLElement} responseBox - the wrapper for the smtp server messages
	 */
	const formElem = document.querySelector('#sendmail-testform form');
	const responseBox = document.querySelector('#sendmail-response pre');

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
		if (msg.length) {
			msg.forEach((line) => {
				/* will add the lines "softly" */
				setTimeout(() => {
					outputContainer.insertAdjacentHTML(
						'beforeend',
						`<code>${line}</code>`
					);
				}, 100 + Math.random() * 100);

				// TODO: regex here to search for errors
			});
		}
		// if the mailSent flag is set (true or false) update the container class
		if (mailSent) {
			outputContainer.classList.remove('error');
			outputContainer.classList.add('ok');
		} else if (mailSent === false) {
			outputContainer.classList.remove('ok');
			outputContainer.classList.add('error');
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

		/* clean the previous results*/
		cleanOutput(responseBox);

		apiFetch({
			path: '/cf7-smtp/v1/sendmail',
			method: 'POST',
			data,
		})
			.then((r) => {
				OutputMessage(
					responseBox,
					'Waiting for server response... ⏰',
					true
				);

				console.log(r);

				OutputMessage(
					responseBox,
					r.message + '\r\n** END **\r\n',
					r.status === 'sent'
				);
			})
			.catch((err) => {
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
								OutputMessage(
									responseBox,
									errorMessage +
										'\r\n' +
										err.message +
										'\r\n** END **',
									false
								);
							}
						})
						.catch((errMsg) => console.log(errMsg));
				}, 2000);
			});
	});

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
};

window.onload = smtpAdmin();
