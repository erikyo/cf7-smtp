import './styles/settings.scss';
import apiFetch from '@wordpress/api-fetch';

apiFetch.use(apiFetch.createNonceMiddleware(window.smtp_settings.nonce));

const smtpAdmin = () => {
	console.log('smtp-mail init');

	/* This is the code that saves the settings when the user presses ctrl-s. */
	if (document.querySelector('#cf7-smtp-settings')) {
		// save on ctrl-s keypress
		document.addEventListener('keydown', (e) => {
			if (e.ctrlKey && e.key === 's') {
				e.preventDefault();
				document.querySelector('#cf7-smtp-settings #submit').click();
			}
		});
	}

	function enableAdvanced(formElem, enabled) {
		formElem.querySelector('tr:nth-child(3)').style.display = enabled ? 'table-row' : 'none';
		formElem.querySelector('tr:nth-child(4)').style.display = enabled ? 'table-row' : 'none';
	}

	const smtpAdvancedOptions = document.querySelector('#cf7_smtp_advanced');
	const formAdvancedSection = document.querySelector(
		'#cf7-smtp-settings .form-table:last-of-type'
	);

	enableAdvanced(formAdvancedSection, smtpAdvancedOptions.checked);

	smtpAdvancedOptions.addEventListener('click', () => {
		enableAdvanced(formAdvancedSection, smtpAdvancedOptions.checked);
	});

	const formElem = document.querySelector('#sendmail-testform form');
	const responseBox = document.querySelector('#sendmail-response code');

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
		}).then((r) => {
			if (r.message) {
				console.log(r.message);
				responseBox.innerHTML = r.message;
			} else {
				console.log(r);
			}
		});
	});
};

window.onload = smtpAdmin();
