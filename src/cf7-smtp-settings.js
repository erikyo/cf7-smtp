/* global smtpReportData */

/** the style */
import './styles/settings.scss';

/** js deps */
import apiFetch from '@wordpress/api-fetch';
import Chart from 'chart.js/auto';

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
	 * @param {Array}       elements - an array of "nth" input
	 * @param {HTMLElement} formElem - the form row
	 * @param {boolean}     enabled  - show or hide the form row
	 */
	function enableAdvanced(elements, formElem, enabled) {
		if (formElem) {
			elements.forEach((el) => {
				formElem.querySelector(`tr:nth-child(${el})`).style.display =
					enabled ? 'table-row' : 'none';
			});
		} else {
			console.log(
				`Cannot find form element ${elements} of ${toString(formElem)}`
			);
		}
	}

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
	 * It takes a line of text and returns an array of two elements, the first being the date and time, and the second being
	 * the rest of the line
	 *
	 * @param {string} line - The line of text to extract the data from.
	 * @return {Array} An array with the date and message.
	 */
	function extractData(line) {
		const regex = /(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (.*)/g;
		return regex.exec(line);
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

				const [string, date, text] = extractData(line);

				/* will add the lines "softly" */
				setTimeout(() => {
					if (date === lastTimestamp) {
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
				}, 50 * index + Math.random() * 200);
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
				responseBox.insertAdjacentHTML(
					'beforeend',
					`<code>${'Waiting for server response... ⏰'}</code>`
				);

				OutputMessage(
					responseBox,
					r.message + '\r\n** END **\r\n',
					r.status === 'success'
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

function mailCharts() {
	console.log(smtpReportData);

	if (typeof smtpReportData !== 'undefined') {
		const cf7aCharts = {};
		smtpReportData.lineData = {
			success: {},
			failed: {},
		};
		smtpReportData.pieData = {
			success: 0,
			failed: 0,
		};

		for (const timestamp in smtpReportData.storage) {
			const day = new Date(timestamp * 1000)
				.setHours(0, 0, 0, 0)
				.valueOf();

			if (typeof smtpReportData.lineData.failed[day] === 'undefined') {
				smtpReportData.lineData.failed[day] = 0;
			}
			if (typeof smtpReportData.lineData.success[day] === 'undefined') {
				smtpReportData.lineData.success[day] = 0;
			}

			if (smtpReportData.storage[timestamp].mail_sent === true) {
				smtpReportData.lineData.success[day]++;
				smtpReportData.pieData.success++;
			} else {
				smtpReportData.lineData.failed[day]++;
				smtpReportData.pieData.failed++;
			}
		}

		const lineConfig = {
			type: 'line',
			data: {
				datasets: [
					{
						label: 'Failed',
						data: Object.values(smtpReportData.lineData.failed),
						fill: false,
						borderColor: 'rgb(255, 99, 132)',
						tension: 0.1,
					},
					{
						label: 'Success',
						data: Object.values(smtpReportData.lineData.success),
						fill: false,
						borderColor: 'rgb(54, 162, 235)',
						tension: 0.1,
					},
				],
				labels: Object.keys(smtpReportData.lineData.failed).map(
					(label) => new Date().toDateString(label)
				),
			},
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
				},
				scales: {
					y: {
						ticks: {
							min: 0,
							precision: 0,
						},
					},
				},
			},
		};

		console.log(lineConfig);

		const PieConfig = {
			type: 'pie',
			data: {
				labels: Object.keys(smtpReportData.pieData),
				datasets: [
					{
						label: 'Total count',
						data: Object.values(smtpReportData.pieData),
						backgroundColor: [
							'rgb(54, 162, 235)',
							'rgb(255, 99, 132)',
						],
						hoverOffset: 4,
					},
				],
				options: {
					responsive: true,
					plugins: {
						legend: { display: false },
					},
				},
			},
		};

		cf7aCharts.lineChart = new Chart(
			document.getElementById('line-chart'),
			lineConfig
		);

		cf7aCharts.pieChart = new Chart(
			document.getElementById('pie-chart'),
			PieConfig
		);

		return cf7aCharts;
	}
}

window.onload = mailCharts();
