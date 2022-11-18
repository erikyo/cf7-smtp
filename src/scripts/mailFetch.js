import apiFetch from '@wordpress/api-fetch';
import { appendOutput, appendOutputMultiline } from './output';
import { __ } from '@wordpress/i18n';
import { responseBox } from './admin';
import { delay } from './utils';

/**
 * Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
 *
 * @param {Object} res the nonce to get the next request
 */
export function getSmtpLog(res) {
	return apiFetch({
		path: '/cf7-smtp/v1/get_log',
		method: 'POST',
		data: {
			nonce: res.nonce,
		},
	}).then((result) => {
		return result;
	});
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
export function fetchAndRetry(mailResp, waitTime, attempts) {
	/**
	 * If the fetch fails, wait for a while and try again and try to get the error message if available
	 *
	 * @param {Error} err - The error that was thrown
	 * @return {Promise} A promise that will either resolve or reject.
	 */
	function retry(err) {
		if (attempts > 0) {
			return delay(waitTime * (attempts + attempts)).then(() =>
				fetchAndRetry(mailResp, waitTime, --attempts)
			);
		}
		throw err;
	}

	return getSmtpLog(mailResp)
		.then((logResp) => {
			// Error
			if (logResp.status === 'error') {
				appendOutput(
					responseBox,
					`<code>${__('🆘 Failed!', 'cf7-smtp')}</code>`
				);

				if (logResp.message.length) {
					const errorLog = logResp.message.join();
					// then append the server response
					return appendOutputMultiline(responseBox, errorLog);
				}
			}

			// Quit
			if (logResp.message.match(/CLIENT -> SERVER: QUIT/g)) {
				return appendOutput(
					responseBox,
					`<code>${__(
						'💻 server has closed the connection!',
						'cf7-smtp'
					)}</code>`
				);
			}

			// Success
			if (logResp.status === 'success') {
				return appendOutput(
					responseBox,
					`<code>${__('✅ Success!', 'cf7-smtp')}</code>`
				);
			}

			// otherwise if nothing match output the server response
			const resp = logResp.message;
			return appendOutput(responseBox, `<code>${resp}</code>`);
		})
		.then(retry);
}
