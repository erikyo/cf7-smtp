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
	 *	This set of conditionals checks if we are in the integration page
	 *	if yes then set a flag in localStorage to "remember" that we are in the current page
	 *	on each page reload we will remove the transitions but set the max-width(maxWidth) to 1000
	 *	when we leave the page we will remove the flag
	 *	this code should not be changed unless Contact Form 7 will submit some major updates for the integration URLs
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
			} );
	} );
}

/**
 * Resize on submit=action in the integration panel from CF7
 */

window.onload = smtpAdmin();
