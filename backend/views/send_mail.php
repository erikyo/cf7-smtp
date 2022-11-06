<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */
?>

<div class="wrap">

	<div class="card" id="sendmail-testform">
		<h3><?php echo esc_html__( 'Send A test Mail', C_TEXTDOMAIN ) ?></h3>

		<form action="">
			<label for="name-from">Name: </label>
			<input type="text" name="name-from" id="name-from" value="name">

			<label for="subject">Subject:</label>
			<input type="text" id="subject" name="subject" value="subject" required>

			<label for="email-from">From: </label>
			<input type="email" name="email-from" id="email-from" value="me@asdsd.com">

			<label for="email">To: </label>
			<input type="text" name="email" id="email" value="you@asdsd.com" required>

			<label for="message">Message:</label>
			<textarea id="message" name="message" rows="6" required >message</textarea>

			<div class="button-wrap">
				<button type="submit" value="Send" class="button button-primary">Submit</button>
			</div>
		</form>

		<div id="sendmail-response">
			<pre><code>Mail Server Init</code></pre>
		</div>
	</div>

</div>
