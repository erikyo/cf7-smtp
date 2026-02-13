/**
 * global window.smtp_settings, window.smtp_settings.nonce
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { enableAdvanced } from './utils';
import { fetchAndRetry } from './mailFetch';
import { appendOutput, appendOutputMultiline, cleanOutput } from './output';

apiFetch.use( apiFetch.createNonceMiddleware( window.smtp_settings.nonce ) );

/**
 *  Email Response box
 *
 *  @member {HTMLElement} formElem - the Email form used to test email functionalities
 *  @member {HTMLElement} responseBox - the wrapper for the smtp server messages
 */
export const formElem = document.querySelector( '#sendmail-testform form' );
export const responseBox = document.querySelector( '#sendmail-response pre' );

export function smtpAdmin() {
	/**
	 *	JS logic to manipulate card transition
	 */

	const urlParams = new URLSearchParams( window.location.search );
	const page = urlParams.get( 'page' );
	const service = urlParams.get( 'service' );
	const action = urlParams.get( 'action' );

	const cards = document.querySelectorAll( '.card' );

	function disableTransition() {
		cards.forEach( ( card ) => {
			card.style.transition = 'none';
		} );
	}

	function enableTransition() {
		cards.forEach( ( card ) => {
			card.style.transition = 'max-width 1s ease';
			card.style.maxWidth = '1000px';
		} );
	}

	if (
		page === 'wpcf7-integration' &&
		service === 'cf7-smtp' &&
		action === 'setup'
	) {
		if ( sessionStorage.getItem( 'disableTransition' ) === 'true' ) {
			disableTransition();
			cards.forEach( ( card ) => {
				card.style.maxWidth = '1000px';
			} );
		} else {
			enableTransition();
			sessionStorage.setItem( 'disableTransition', 'true' );
		}
	} else {
		sessionStorage.setItem( 'disableTransition', 'false' );
		disableTransition();
	}

	/**
	 *  Variables needed to set SMTP connetion
	 *
	 *  @member {HTMLElement} formSelectDefault - cf7_smtp_preset
	 *  @member {HTMLElement} formSelectHost - cf7_smtp_host
	 *  @member {HTMLElement} formSelectPort - cf7_smtp_port
	 */
	const formSelectDefault = document.getElementById( 'cf7_smtp_preset' );
	const formSelectHost = document.getElementById( 'cf7_smtp_host' );
	const formSelectPort = document.getElementById( 'cf7_smtp_port' );

	/**
	 * Sets the values of the SMTP connection according to the value selected by the user.
	 */
	if ( !! formSelectDefault ) {
		formSelectDefault.addEventListener( 'change', ( e ) => {
			const selectedEl = e.target[ e.target.selectedIndex ];
			if ( selectedEl ) {
				const authRadio = document.querySelector(
					'.auth-' + selectedEl.dataset.auth
				);
				authRadio.checked = true;
				formSelectHost.value = selectedEl.dataset.host;
				formSelectPort.value = selectedEl.dataset.port;
			}
		} );
	}

	/**
	 * Enables the SMTP settings
	 */
	const smtpEnabled = document.querySelector( '#cf7_smtp_enabled' );
	const formSmtpSection = document.querySelector(
		'#cf7-smtp-settings .form-table:first-of-type'
	);
	if ( !! smtpEnabled ) {
		enableAdvanced(
			[ 2, 3, 4, 5, 6, 7 ],
			formSmtpSection,
			smtpEnabled.checked
		);

		smtpEnabled.addEventListener( 'click', () => {
			enableAdvanced(
				[ 2, 3, 4, 5, 6, 7 ],
				formSmtpSection,
				smtpEnabled.checked
			);
		} );
	}
	/* Initialize the response box and show a welcome message */
	if ( !! responseBox ) {
		cleanOutput(
			responseBox,
			'<code>' +
				__( 'Mail Server initialization completed!', 'cf7-smtp' ) +
				'</code>'
		);
	}

	/**
	 *  Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
	 */
	if ( !! formElem ) {
		formElem.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			/* The form inputs data */
			const formData = new FormData( e.target );

			const data = {};
			data.nonce = window.smtp_settings.nonce;

			for ( const [ key, value ] of formData.entries() ) {
				data[ key ] = value;
			}

			/* clean the previous results*/
			cleanOutput( responseBox );

			appendOutput(
				responseBox,
				`<code>${ __(
					"Let's start a new server connection…",
					'cf7-smtp'
				) } <span class="mail-init animation-start">✉️</span></code>`
			);

			apiFetch( {
				path: '/cf7-smtp/v1/sendmail',
				method: 'POST',
				data,
			} )
				.then( ( r ) => {
					if ( r.status === 'success' ) {
						appendOutputMultiline( responseBox, r.message, true );
					}
					return r;
				} )
				.then( ( mailResp ) => {
					return fetchAndRetry( mailResp, 500, 5 );
				} )
				.catch( ( /*errMsg*/ ) => {
					appendOutput(
						responseBox,
						`<code>${ __(
							'OOOPS something went wrong!',
							'cf7-smtp'
						) }`
					);
				} );
		} );
	}

	const flushLogs = document.getElementById( 'cf7_smtp_flush_logs' );
	flushLogs?.addEventListener( 'click', () => {
		apiFetch( {
			path: '/cf7-smtp/v1/flush-logs',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
			},
		} )
			.then( ( r ) => {
				if ( r.status === 'success' ) {
					alert( r.message );
				}
				return r;
			} )
			.catch( ( /*errMsg*/ ) => {
				appendOutput(
					responseBox,
					`<code>${ __( 'OOOPS something went wrong!', 'cf7-smtp' ) }`
				);
			} );
	} );

	const reportNow = document.getElementById( 'cf7_smtp_report_now' );
	reportNow?.addEventListener( 'click', () => {
		apiFetch( {
			path: '/cf7-smtp/v1/report',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
			},
		} )
			.then( ( r ) => {
				if ( r.status === 'success' ) {
					alert( r.message );
				}
				return r;
			} )
			.catch( ( /*errMsg*/ ) => {
				appendOutput(
					responseBox,
					`<code>${ __( 'OOOPS something went wrong!', 'cf7-smtp' ) }`
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
