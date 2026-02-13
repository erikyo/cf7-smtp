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
	 *	JS logic to manipulate card transition
	 */

	const urlParams = new URLSearchParams(window.location.search);
	const page = urlParams.get('page');
	const service = urlParams.get('service');
	const action = urlParams.get('action');

	const cards = document.querySelectorAll('.card');

	function disableTransition() {
		cards.forEach((card) => {
			card.style.transition = 'none';
		});
	}

	function enableTransition() {
		cards.forEach((card) => {
			card.style.transition = 'max-width 1s ease';
			card.style.maxWidth = '1000px';
		});
	}

	if (
		page === 'wpcf7-integration' &&
		service === 'cf7-smtp' &&
		action === 'setup'
	) {
		if (sessionStorage.getItem('disableTransition') === 'true') {
			disableTransition();
			cards.forEach((card) => {
				card.style.maxWidth = '1000px';
			});
		} else {
			enableTransition();
			sessionStorage.setItem('disableTransition', 'true');
		}
	} else {
		sessionStorage.setItem('disableTransition', 'false');
		disableTransition();
	}

	/**
	 *  Auth Method Selection Logic
	 */
	const authMethodInputs = document.querySelectorAll('input[name="cf7-smtp-options[auth_method]"]');
	const wpWarning = document.getElementById('cf7-smtp-wp-warning');

	// Helpers to find rows by input ID
	const getRow = (id) => {
		const el = document.getElementById(id);
		return el ? el.closest('tr') : null;
	};

	const classicFields = [
		'cf7_smtp_preset',
		'cf7-smtp-auth', // This is a div, need to find the row
		'cf7_smtp_host',
		'cf7_smtp_port',
		'cf7_smtp_user_name',
		'cf7_smtp_user_pass',
	].map(id => {
		if (id === 'cf7-smtp-auth') return document.getElementById(id)?.closest('tr');
		return getRow(id);
	}).filter(Boolean);

	const oauthFields = document.querySelectorAll('.cf7-smtp-oauth-row');

	const advancedSection = document.querySelector('.smtp-settings-options h2:nth-of-type(2)'); // "Advanced Options" header if exists? 
	// Actually advanced options are in a section.

	const updateUI = (method) => {
		// Update cards visual state
		document.querySelectorAll('.cf7-smtp-auth-card').forEach(card => {
			const input = card.querySelector('input');
			if (input.value === method) {
				card.classList.add('selected');
			} else {
				card.classList.remove('selected');
			}
		});

		// OAuth Section Header and Description handling
		const oauthDesc = document.getElementById('cf7_smtp_oauth2_section_desc');
		// Find H2 by assuming it's the preceding element (common WP structure: H2 then callback output)
		// Or verify structure. `do_settings_sections` outputs `<h2>...</h2>` then `call_user_func(...)`
		// So `<h2>` should be previous sibling of the description container.
		let oauthHeader = null;
		if (oauthDesc) {
			const prev = oauthDesc.previousElementSibling;
			if (prev && prev.tagName === 'H2') {
				oauthHeader = prev;
			}
		}

		if (method === 'wp') {
			classicFields.forEach(row => row.style.display = 'none');
			oauthFields.forEach(row => row.style.display = 'none');
			if (wpWarning) wpWarning.style.display = 'block';

			if (oauthDesc) oauthDesc.style.display = 'none';
			if (oauthHeader) oauthHeader.style.display = 'none';

		} else if (method === 'smtp') {
			classicFields.forEach(row => row.style.display = 'table-row');
			oauthFields.forEach(row => row.style.display = 'none');
			if (wpWarning) wpWarning.style.display = 'none';

			if (oauthDesc) oauthDesc.style.display = 'none';
			if (oauthHeader) oauthHeader.style.display = 'none';

		} else if (method === 'gmail' || method === 'outlook') {
			classicFields.forEach(row => row.style.display = 'none');
			oauthFields.forEach(row => row.style.display = 'table-row');
			if (wpWarning) wpWarning.style.display = 'none';

			if (oauthDesc) oauthDesc.style.display = 'block';
			if (oauthHeader) oauthHeader.style.display = 'block';

			// Auto-select provider in the hidden select if needed
			const providerSelect = document.getElementById('cf7_smtp_oauth2_provider');
			if (providerSelect) {
				providerSelect.value = method === 'gmail' ? 'gmail' : 'office365';
				// hide the provider select row itself since it's implied? 
				// The user asked to hide unnecessary fields. 
				// "Display a single, prominent button... Hide the Host, Port..."
				// If we auto-select, we can hide the provider select row.
				const providerRow = getRow('cf7_smtp_oauth2_provider');
				if (providerRow) providerRow.style.display = 'none';
			}
		}
	};

	authMethodInputs.forEach(input => {
		input.addEventListener('change', (e) => {
			updateUI(e.target.value);
		});
		// Init
		if (input.checked) {
			updateUI(input.value);
		}
	});


	/**
	 *  Variables needed to set SMTP connetion
	 */
	const formSelectDefault = document.getElementById('cf7_smtp_preset');
	const formSelectHost = document.getElementById('cf7_smtp_host');
	const formSelectPort = document.getElementById('cf7_smtp_port');

	/**
	 * Sets the values of the SMTP connection according to the value selected by the user.
	 */
	if (!!formSelectDefault) {
		formSelectDefault.addEventListener('change', (e) => {
			const selectedEl = e.target[e.target.selectedIndex];
			if (selectedEl) {
				const authRadio = document.querySelector(
					'.auth-' + selectedEl.dataset.auth
				);
				if (authRadio) authRadio.checked = true;
				formSelectHost.value = selectedEl.dataset.host;
				formSelectPort.value = selectedEl.dataset.port;
			}
		});
	}

	// Logic for "Connect" button in OAuth
	const connectBtn = document.getElementById('cf7_smtp_oauth2_connect');
	if (connectBtn) {
		connectBtn.addEventListener('click', (e) => {
			e.preventDefault();
			const providerSelect = document.getElementById('cf7_smtp_oauth2_provider');

			// If provider select is hidden or we rely on the auth cards, we derive the provider
			// But current implementation relies on the select value which should be set by the cards
			let provider = providerSelect ? providerSelect.value : '';

			// Fallback if select is missing but cards are used? 
			// But updateUI sets the select value.

			if (!provider) {
				// Try to find checked card
				const checked = document.querySelector('input[name="cf7-smtp-options[auth_method]"]:checked');
				if (checked) {
					if (checked.value === 'gmail') provider = 'gmail';
					if (checked.value === 'outlook') provider = 'office365'; // check value mapping
				}
			}

			if (!provider) {
				alert(__('Please select a provider (Gmail/Outlook) via the icons above.', 'cf7-smtp'));
				return;
			}

			// Show loading?
			connectBtn.disabled = true;
			connectBtn.innerText = __('Connecting...', 'cf7-smtp');

			apiFetch({
				path: '/cf7-smtp/v1/oauth2/authorize/',
				method: 'POST',
				data: {
					nonce: window.smtp_settings.nonce,
					provider: provider
				},
			})
				.then((r) => {
					if (r.status === 'success' && r.authorization_url) {
						window.location.href = r.authorization_url;
					} else {
						alert(r.message || __('Failed to get authorization URL.', 'cf7-smtp'));
						connectBtn.disabled = false;
						connectBtn.innerText = __('Connect', 'cf7-smtp');
					}
				})
				.catch((err) => {
					console.error(err);
					alert(__('An error occurred. Please try again.', 'cf7-smtp'));
					connectBtn.disabled = false;
					connectBtn.innerText = __('Connect', 'cf7-smtp');
				});
		});
	}

	const disconnectBtn = document.getElementById('cf7_smtp_oauth2_disconnect');
	if (disconnectBtn) {
		disconnectBtn.addEventListener('click', (e) => {
			e.preventDefault();
			if (!confirm(__('Are you sure you want to disconnect?', 'cf7-smtp'))) return;

			disconnectBtn.disabled = true;
			disconnectBtn.innerText = __('Disconnecting...', 'cf7-smtp');

			apiFetch({
				path: '/cf7-smtp/v1/oauth2/disconnect/',
				method: 'POST',
				data: {
					nonce: window.smtp_settings.nonce,
				},
			})
				.then((r) => {
					if (r.status === 'success') {
						window.location.reload();
					} else {
						alert(r.message || __('Failed to disconnect.', 'cf7-smtp'));
						disconnectBtn.disabled = false;
						disconnectBtn.innerText = __('Disconnect', 'cf7-smtp');
					}
				})
				.catch((err) => {
					console.error(err);
					alert(__('An error occurred.', 'cf7-smtp'));
					disconnectBtn.disabled = false;
					disconnectBtn.innerText = __('Disconnect', 'cf7-smtp');
				});
		});
	}

	/* Initialize the response box and show a welcome message */
	if (!!responseBox) {
		cleanOutput(
			responseBox,
			'<code>' +
			__('Mail Server initialization completed!', 'cf7-smtp') +
			'</code>'
		);
	}

	/**
	 *  Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
	 */
	if (!!formElem) {
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
				.catch(( /*errMsg*/) => {
					appendOutput(
						responseBox,
						`<code>${__(
							'OOOPS something went wrong!',
							'cf7-smtp'
						)}`
					);
				});
		});
	}

	const flushLogs = document.getElementById('cf7_smtp_flush_logs');
	flushLogs?.addEventListener('click', () => {
		apiFetch({
			path: '/cf7-smtp/v1/flush-logs',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
			},
		})
			.then((r) => {
				if (r.status === 'success') {
					alert(r.message);
				}
				return r;
			})
			.catch(( /*errMsg*/) => {
				appendOutput(
					responseBox,
					`<code>${__('OOOPS something went wrong!', 'cf7-smtp')}`
				);
			});
	});

	const reportNow = document.getElementById('cf7_smtp_report_now');
	reportNow?.addEventListener('click', () => {
		apiFetch({
			path: '/cf7-smtp/v1/report',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
			},
		})
			.then((r) => {
				if (r.status === 'success') {
					alert(r.message);
				}
				return r;
			})
			.catch(( /*errMsg*/) => {
				appendOutput(
					responseBox,
					`<code>${__('OOOPS something went wrong!', 'cf7-smtp')}`
				);
			});
	});

	// DNS Check Logic
	const checkDnsBtn = document.getElementById('cf7_smtp_check_dns');
	const fromMailInput = document.getElementById('cf7_smtp_from_mail');
	const dnsResultBox = document.getElementById('cf7_smtp_dns_result');

	const triggerDnsCheck = () => {
		const email = fromMailInput.value;
		const host = document.getElementById('cf7_smtp_host')?.value || '';

		if (!email) {
			return;
		}

		if (checkDnsBtn) checkDnsBtn.disabled = true;
		if (dnsResultBox) dnsResultBox.innerHTML = `<code>${__('Checking DNS...', 'cf7-smtp')}</code>`;

		apiFetch({
			path: '/cf7-smtp/v1/check-dns/',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
				email: email,
				host: host
			},
		})
			.then((r) => {
				if (checkDnsBtn) checkDnsBtn.disabled = false;
				if (r.status === 'success') {
					const data = r.data;
					let boxClass = 'notice ';
					if (data.risk === 'high') boxClass += 'notice-error';
					else if (data.risk === 'medium') boxClass += 'notice-warning';
					else boxClass += 'notice-success';

					let detailsHtml = '';
					if (data.details && data.details.length) {
						detailsHtml = '<ul>' + data.details.map(d => `<li>${d}</li>`).join('') + '</ul>';
					}

					if (dnsResultBox) {
						dnsResultBox.innerHTML = `
							<div class="${boxClass} inline" style="margin-top: 10px; padding: 10px;">
								<p><strong>${r.message}</strong></p>
								${detailsHtml}
							</div>
						`;
					}
				} else {
					if (dnsResultBox) dnsResultBox.innerHTML = `<div class="notice notice-error inline"><p>${r.message}</p></div>`;
				}
			})
			.catch((err) => {
				console.error(err);
				if (checkDnsBtn) checkDnsBtn.disabled = false;
				if (dnsResultBox) dnsResultBox.innerHTML = `<div class="notice notice-error inline"><p>${__('Error checking DNS.', 'cf7-smtp')}</p></div>`;
			});
	};

	// Debounce utility
	const debounce = (func, wait) => {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	};

	// Simple email validation regex
	const isValidEmail = (email) => {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	};

	if (checkDnsBtn) {
		checkDnsBtn.addEventListener('click', (e) => {
			e.preventDefault();
			triggerDnsCheck();
			lastCheckedEmail = fromMailInput ? fromMailInput.value : '';
		});
	}

	let lastCheckedEmail = '';
	if (fromMailInput) {
		// Initialize with current value
		lastCheckedEmail = fromMailInput.value;

		const debouncedCheck = debounce(() => {
			const currentVal = fromMailInput.value;
			if (currentVal !== lastCheckedEmail && isValidEmail(currentVal)) {
				lastCheckedEmail = currentVal;
				triggerDnsCheck();
			}
		}, 1000); // 1 second debounce

		fromMailInput.addEventListener('keyup', debouncedCheck);

		fromMailInput.addEventListener('blur', () => {
			const currentVal = fromMailInput.value;
			if (currentVal !== lastCheckedEmail && isValidEmail(currentVal)) {
				lastCheckedEmail = currentVal;
				triggerDnsCheck();
			}
		});
	}
}

/**
 * Resize on submit=action in the integration panel from CF7
 */

window.onload = smtpAdmin();
