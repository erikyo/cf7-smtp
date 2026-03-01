/**
 * global window.smtp_settings, window.smtp_settings.nonce
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { fetchAndRetry } from './mailFetch';
import { appendOutput, appendOutputMultiline, cleanOutput } from './output';

interface SmtpSettings {
	nonce: string;
}

interface ApiResponse {
	status: string;
	message?: string;
	authorization_url?: string;
	data?: {
		risk: string;
		details: string[];
	};
}

interface MailData {
	[ key: string ]: string;
}

declare global {
	interface Window {
		smtp_settings: SmtpSettings;
	}
}

apiFetch.use( apiFetch.createNonceMiddleware( window.smtp_settings.nonce ) );

/**
 *  Email Response box
 *
 *  @member {HTMLElement} formElem - the Email form used to test email functionalities
 *  @member {HTMLElement} responseBox - the wrapper for the smtp server messages
 */
export const formElem = document.querySelector(
	'#sendmail-testform form'
) as HTMLFormElement;
export const responseBox = document.querySelector(
	'#sendmail-response pre'
) as HTMLElement;

export function smtpAdmin(): void {
	/**
	 *	JS logic to manipulate card transition
	 */

	const urlParams = new URLSearchParams( window.location.search );
	const page = urlParams.get( 'page' );
	const service = urlParams.get( 'service' );
	const action = urlParams.get( 'action' );

	const cards = document.querySelectorAll( '.card' );

	/**
	 * Disable the transition for the cards.
	 */
	function disableTransition(): void {
		cards.forEach( ( card ) => {
			( card as HTMLElement ).style.transition = 'none';
		} );
	}

	/**
	 * Enable the transition for the cards.
	 */
	function enableTransition(): void {
		cards.forEach( ( card ) => {
			( card as HTMLElement ).style.transition = 'max-width 1s ease';
			( card as HTMLElement ).style.maxWidth = '1000px';
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
				( card as HTMLElement ).style.maxWidth = '1000px';
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
	 *  Auth Method Selection Logic
	 */
	const authMethodInputs = document.querySelectorAll(
		'input[name="cf7-smtp-options[auth_method]"]'
	) as NodeListOf< HTMLInputElement >;
	const wpWarning = document.getElementById( 'cf7-smtp-wp-warning' );

	// Helpers to find rows by input ID
	const getRow = ( id: string ): HTMLElement | null => {
		const el = document.getElementById( id );
		return el ? ( el.closest( 'tr' ) as HTMLElement ) : null;
	};

	const classicFields = [
		'cf7_smtp_preset',
		'cf7-smtp-auth', // This is a div, need to find the row
		'cf7_smtp_host',
		'cf7_smtp_port',
		'cf7_smtp_user_name',
		'cf7_smtp_user_pass',
	]
		.map( ( id ) => {
			if ( id === 'cf7-smtp-auth' ) {
				return document
					.getElementById( id )
					?.closest( 'tr' ) as HTMLElement;
			}
			return getRow( id );
		} )
		.filter( Boolean ) as HTMLElement[];

	const oauthFields = document.querySelectorAll(
		'.cf7-smtp-oauth-row'
	) as NodeListOf< HTMLElement >;

	const advancedSection = document.querySelector(
		'.smtp-settings-options h2:nth-of-type(2)'
	);

	/**
	 * Update the UI based on the selected authentication method.
	 *
	 * @param method The selected authentication method.
	 */
	const updateUI = ( method: string ): void => {
		// Update cards visual state
		document
			.querySelectorAll( '.cf7-smtp-auth-card' )
			.forEach( ( card ) => {
				const input = card.querySelector( 'input' ) as HTMLInputElement;
				if ( input.value === method ) {
					card.classList.add( 'selected' );
				} else {
					card.classList.remove( 'selected' );
				}
			} );

		// OAuth Section Header and Description handling
		const oauthDesc = document.getElementById(
			'cf7_smtp_oauth2_section_desc'
		);
		let oauthHeader: HTMLElement | null = null;
		if ( oauthDesc ) {
			const prev = oauthDesc.previousElementSibling;
			if ( prev && prev.tagName === 'H2' ) {
				oauthHeader = prev as HTMLElement;
			}
		}

		if ( method === 'wp' ) {
			classicFields.forEach( ( row ) => ( row.style.display = 'none' ) );
			oauthFields.forEach( ( row ) => ( row.style.display = 'none' ) );
			if ( wpWarning ) {
				wpWarning.style.display = 'block';
			}

			if ( oauthDesc ) {
				oauthDesc.style.display = 'none';
			}
			if ( oauthHeader ) {
				oauthHeader.style.display = 'none';
			}
		} else if ( method === 'smtp' ) {
			classicFields.forEach(
				( row ) => ( row.style.display = 'table-row' )
			);
			oauthFields.forEach( ( row ) => ( row.style.display = 'none' ) );
			if ( wpWarning ) {
				wpWarning.style.display = 'none';
			}

			if ( oauthDesc ) {
				oauthDesc.style.display = 'none';
			}
			if ( oauthHeader ) {
				oauthHeader.style.display = 'none';
			}
		} else if ( method === 'gmail' || method === 'outlook' ) {
			classicFields.forEach( ( row ) => ( row.style.display = 'none' ) );
			oauthFields.forEach(
				( row ) => ( row.style.display = 'table-row' )
			);
			if ( wpWarning ) {
				wpWarning.style.display = 'none';
			}

			if ( oauthDesc ) {
				oauthDesc.style.display = 'block';
			}
			if ( oauthHeader ) {
				oauthHeader.style.display = 'block';
			}

			// Auto-select provider in the hidden select if needed
			const providerSelect = document.getElementById(
				'cf7_smtp_oauth2_provider'
			) as HTMLSelectElement;
			if ( providerSelect ) {
				providerSelect.value =
					method === 'gmail' ? 'gmail' : 'office365';
				const providerRow = getRow( 'cf7_smtp_oauth2_provider' );
				if ( providerRow ) {
					providerRow.style.display = 'none';
				}
			}
		}
	};

	authMethodInputs.forEach( ( input ) => {
		input.addEventListener( 'change', ( e ) => {
			updateUI( ( e.target as HTMLInputElement ).value );
		} );
		// Init
		if ( input.checked ) {
			updateUI( input.value );
		}
	} );

	/**
	 *  Variables needed to set SMTP connetion
	 */
	const formSelectDefault = document.getElementById(
		'cf7_smtp_preset'
	) as HTMLSelectElement;
	const formSelectHost = document.getElementById(
		'cf7_smtp_host'
	) as HTMLInputElement;
	const formSelectPort = document.getElementById(
		'cf7_smtp_port'
	) as HTMLInputElement;

	/**
	 * Sets the values of the SMTP connection according to the value selected by the user.
	 */
	if ( !! formSelectDefault ) {
		formSelectDefault.addEventListener( 'change', ( e ) => {
			const target = e.target as HTMLSelectElement;
			const selectedEl = target[
				target.selectedIndex
			] as HTMLOptionElement;
			if ( selectedEl ) {
				const authRadio = document.querySelector(
					'.auth-' + selectedEl.dataset.auth
				) as HTMLInputElement;
				if ( authRadio ) {
					authRadio.checked = true;
				}
				formSelectHost.value = selectedEl.dataset.host || '';
				formSelectPort.value = selectedEl.dataset.port || '';
			}
		} );
	}

	// Logic for "Connect" button in OAuth
	const connectBtn = document.getElementById(
		'cf7_smtp_oauth2_connect'
	) as HTMLButtonElement;
	if ( connectBtn ) {
		connectBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const providerSelect = document.getElementById(
				'cf7_smtp_oauth2_provider'
			) as HTMLSelectElement;

			// If provider select is hidden or we rely on the auth cards, we derive the provider
			// But current implementation relies on the select value which should be set by the cards
			let provider = providerSelect ? providerSelect.value : '';

			// Fallback if select is missing but cards are used?
			// But updateUI sets the select value.

			if ( ! provider ) {
				// Try to find checked card
				const checked = document.querySelector(
					'input[name="cf7-smtp-options[auth_method]"]:checked'
				) as HTMLInputElement;
				if ( checked ) {
					if ( checked.value === 'gmail' ) {
						provider = 'gmail';
					}
					if ( checked.value === 'outlook' ) {
						provider = 'office365';
					} // check value mapping
				}
			}

			if ( ! provider ) {
				alert(
					__(
						'Please select a provider (Gmail/Outlook) via the icons above.',
						'cf7-smtp'
					)
				);
				return;
			}

			// Show loading?
			connectBtn.disabled = true;
			connectBtn.innerText = __( 'Connecting…', 'cf7-smtp' );

			apiFetch( {
				path: '/cf7-smtp/v1/oauth2/authorize/',
				method: 'POST',
				data: {
					nonce: window.smtp_settings.nonce,
					provider,
				},
			} )
				.then( ( r ) => {
					const response = r as ApiResponse;
					if (
						response.status === 'success' &&
						response.authorization_url
					) {
						window.location.href = response.authorization_url;
					} else {
						alert(
							response.message ||
								__(
									'Failed to get authorization URL.',
									'cf7-smtp'
								)
						);
						connectBtn.disabled = false;
						connectBtn.innerText = __( 'Connect', 'cf7-smtp' );
					}
				} )
				.catch( ( err ) => {
					console.error( err );
					alert(
						__( 'An error occurred. Please try again.', 'cf7-smtp' )
					);
					connectBtn.disabled = false;
					connectBtn.innerText = __( 'Connect', 'cf7-smtp' );
				} );
		} );
	}

	const disconnectBtn = document.getElementById(
		'cf7_smtp_oauth2_disconnect'
	) as HTMLButtonElement;
	if ( disconnectBtn ) {
		disconnectBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			if (
				! confirm(
					__( 'Are you sure you want to disconnect?', 'cf7-smtp' )
				)
			) {
				return;
			}

			disconnectBtn.disabled = true;
			disconnectBtn.innerText = __( 'Disconnecting…', 'cf7-smtp' );

			apiFetch( {
				path: '/cf7-smtp/v1/oauth2/disconnect/',
				method: 'POST',
				data: {
					nonce: window.smtp_settings.nonce,
				},
			} )
				.then( ( r ) => {
					const response = r as ApiResponse;
					if ( response.status === 'success' ) {
						window.location.reload();
					} else {
						alert(
							response.message ||
								__( 'Failed to disconnect.', 'cf7-smtp' )
						);
						disconnectBtn.disabled = false;
						disconnectBtn.innerText = __(
							'Disconnect',
							'cf7-smtp'
						);
					}
				} )
				.catch( ( err ) => {
					console.error( err );
					alert( __( 'An error occurred.', 'cf7-smtp' ) );
					disconnectBtn.disabled = false;
					disconnectBtn.innerText = __( 'Disconnect', 'cf7-smtp' );
				} );
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
			const formData = new FormData( e.target as HTMLFormElement );

			const data: MailData = {};
			data.nonce = window.smtp_settings.nonce;

			for ( const [ key, value ] of formData.entries() ) {
				data[ key ] = value as string;
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
					const response = r as ApiResponse;
					if ( response.status === 'success' ) {
						appendOutputMultiline(
							responseBox,
							response.message || '',
							true
						);
					}
					return response;
				} )
				.then( ( mailResp ) => {
					const mailResponse = mailResp as {
						status: string;
						message: string;
						nonce: string;
					};
					return fetchAndRetry( mailResponse, 500, 5 );
				} )
				.catch( ( /*errMsg*/ ) => {
					appendOutput(
						responseBox,
						`<code>${ __(
							'OOOPS something went wrong!',
							'cf7-smtp'
						) }</code>`
					);
				} );
		} );
	}

	const flushLogs = document.getElementById(
		'cf7_smtp_flush_logs'
	) as HTMLButtonElement;
	flushLogs?.addEventListener( 'click', () => {
		apiFetch( {
			path: '/cf7-smtp/v1/flush-logs',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
			},
		} )
			.then( ( r ) => {
				const response = r as ApiResponse;
				if ( response.status === 'success' ) {
					alert( response.message );
				}
				return r;
			} )
			.catch( ( /*errMsg*/ ) => {
				appendOutput(
					responseBox,
					`<code>${ __(
						'OOOPS something went wrong!',
						'cf7-smtp'
					) }</code>`
				);
			} );
	} );

	const reportNow = document.getElementById(
		'cf7_smtp_report_now'
	) as HTMLButtonElement;
	reportNow?.addEventListener( 'click', () => {
		apiFetch( {
			path: '/cf7-smtp/v1/report',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
			},
		} )
			.then( ( r ) => {
				const response = r as ApiResponse;
				if ( response.status === 'success' ) {
					alert( response.message );
				}
				return r;
			} )
			.catch( ( /*errMsg*/ ) => {
				appendOutput(
					responseBox,
					`<code>${ __(
						'OOOPS something went wrong!',
						'cf7-smtp'
					) }</code>`
				);
			} );
	} );

	// DNS Check Logic
	const checkDnsBtn = document.getElementById(
		'cf7_smtp_check_dns'
	) as HTMLButtonElement;
	const fromMailInput = document.getElementById(
		'cf7_smtp_from_mail'
	) as HTMLInputElement;
	const dnsResultBox = document.getElementById(
		'cf7_smtp_dns_result'
	) as HTMLElement;

	const triggerDnsCheck = (): void => {
		const email = fromMailInput.value;
		const hostInput = document.getElementById(
			'cf7_smtp_host'
		) as HTMLInputElement;
		const host = hostInput?.value || '';

		if ( ! email ) {
			return;
		}

		if ( checkDnsBtn ) {
			checkDnsBtn.disabled = true;
		}
		if ( dnsResultBox ) {
			dnsResultBox.innerHTML = `<code>${ __(
				'Checking DNS…',
				'cf7-smtp'
			) }</code>`;
		}

		apiFetch( {
			path: '/cf7-smtp/v1/check-dns/',
			method: 'POST',
			data: {
				nonce: window.smtp_settings.nonce,
				email,
				host,
			},
		} )
			.then( ( r ) => {
				const response = r as ApiResponse;
				if ( checkDnsBtn ) {
					checkDnsBtn.disabled = false;
				}
				if ( response.status === 'success' && response.data ) {
					const data = response.data;
					let boxClass = 'notice ';
					if ( data.risk === 'high' ) {
						boxClass += 'notice-error';
					} else if ( data.risk === 'medium' ) {
						boxClass += 'notice-warning';
					} else {
						boxClass += 'notice-success';
					}

					let detailsHtml = '';
					if ( data.details && data.details.length ) {
						detailsHtml =
							'<ul>' +
							data.details
								.map( ( d ) => `<li>${ d }</li>` )
								.join( '' ) +
							'</ul>';
					}

					if ( dnsResultBox ) {
						dnsResultBox.innerHTML = `
							<div class="${ boxClass } inline" style="margin-top: 10px; padding: 10px;">
								<p><strong>${ response.message }</strong></p>
								${ detailsHtml }
							</div>
						`;
					}
				} else if ( dnsResultBox ) {
					dnsResultBox.innerHTML = `<div class="notice notice-error inline"><p>${ response.message }</p></div>`;
				}
			} )
			.catch( ( err ) => {
				console.error( err );
				if ( checkDnsBtn ) {
					checkDnsBtn.disabled = false;
				}
				if ( dnsResultBox ) {
					dnsResultBox.innerHTML = `<div class="notice notice-error inline"><p>${ __(
						'Error checking DNS.',
						'cf7-smtp'
					) }</p></div>`;
				}
			} );
	};

	// Debounce utility
	const debounce = ( func: () => void, wait: number ) => {
		let timeout: ReturnType< typeof setTimeout >;
		return function executedFunction( ...args: any[] ) {
			const later = () => {
				clearTimeout( timeout );
				func();
			};
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );
		};
	};

	// Simple email validation regex
	const isValidEmail = ( email: string ): boolean => {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
	};

	if ( checkDnsBtn ) {
		checkDnsBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			triggerDnsCheck();
			lastCheckedEmail = fromMailInput ? fromMailInput.value : '';
		} );
	}

	let lastCheckedEmail = '';
	if ( fromMailInput ) {
		// Initialize with current value
		lastCheckedEmail = fromMailInput.value;

		const debouncedCheck = debounce( () => {
			const currentVal = fromMailInput.value;
			if (
				currentVal !== lastCheckedEmail &&
				isValidEmail( currentVal )
			) {
				lastCheckedEmail = currentVal;
				triggerDnsCheck();
			}
		}, 1000 ); // 1 second debounce

		fromMailInput.addEventListener( 'keyup', debouncedCheck );

		fromMailInput.addEventListener( 'blur', () => {
			const currentVal = fromMailInput.value;
			if (
				currentVal !== lastCheckedEmail &&
				isValidEmail( currentVal )
			) {
				lastCheckedEmail = currentVal;
				triggerDnsCheck();
			}
		} );
	}
}

/**
 * Resize on submit=action in the integration panel from CF7
 */
document.addEventListener( 'DOMContentLoaded', smtpAdmin );
