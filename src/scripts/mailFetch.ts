import apiFetch from '@wordpress/api-fetch';
import { appendOutput, appendOutputMultiline } from './output';
import { __ } from '@wordpress/i18n';
import { responseBox } from './admin';
import { delay } from './utils';

interface ApiResponse {
	status: string;
	message: string | string[];
	nonce?: string;
}

interface MailResponse {
	status: string;
	message: string;
	nonce: string;
}

/**
 * Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
 *
 * @param {Object} res       the nonce to get the next request
 * @param          res.nonce
 */
export async function getSmtpLog( res: {
	nonce: string;
} ): Promise< ApiResponse > {
	const result = await apiFetch( {
		path: '/cf7-smtp/v1/get_log',
		method: 'POST',
		data: {
			nonce: res.nonce,
		},
	} );
	return result as ApiResponse;
}

/**
 * "If the getSmtpLog function fails, wait a bit and try again."
 *
 * The function takes three parameters:
 *
 * mailResp: The response from the mail server.
 * waitTime: The amount of time to wait before retrying.
 * attempts: The number of times we've tried to get the log.
 * The function returns a promise that resolves to the log
 *
 * @param {Object} mailResp - The response from the mailer.
 * @param {number} waitTime - The amount of time to wait between attempts.
 * @param {number} attempts - The number of times we've tried to fetch the log.
 *
 * @return {Promise} A promise that resolves to the smtp log for the given mail response.
 */
export async function fetchAndRetry(
	mailResp: MailResponse,
	waitTime: number,
	attempts: number
): Promise< void > {
	/**
	 * If the fetch fails, wait for a while and try again and try to get the error message if available
	 */
	function retry(): Promise< void > | undefined {
		if ( attempts > 0 ) {
			return delay( waitTime * ( attempts + attempts ) ).then( () =>
				fetchAndRetry( mailResp, waitTime, --attempts )
			);
		}
	}

	const logResp = await getSmtpLog( mailResp );
	// Error
	if ( logResp.status === 'error' ) {
		appendOutput(
			responseBox,
			`<code>${ __( '🆘 Failed!', 'cf7-smtp' ) }</code>`
		);

		if ( Array.isArray( logResp.message ) && logResp.message.length ) {
			const errorLog = logResp.message.join();
			// then append the server response
			return appendOutputMultiline( responseBox, errorLog );
		}
		return appendOutput( responseBox, `<code>${ logResp.message }</code>` );
	}
	// Quit
	if (
		typeof logResp.message === 'string' &&
		logResp.message.match( /CLIENT -> SERVER: QUIT/g )
	) {
		return appendOutput(
			responseBox,
			`<code>${ __(
				'💻 server has closed the connection!',
				'cf7-smtp'
			) }</code>`
		);
	}
	// Success
	if ( logResp.status === 'success' ) {
		return appendOutput(
			responseBox,
			`<code class="">${ __( '✅ Success!', 'cf7-smtp' ) }</code>`
		);
	}
	// Log retrieved
	if ( logResp.status === 'log' ) {
		return appendOutput( responseBox, `<code>${ logResp.message }</code>` );
	}
	// otherwise if nothing match output the server response
	appendOutput( responseBox, `<code>${ logResp.message }</code>` );
	return retry();
}
