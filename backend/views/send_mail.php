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

global $current_user;

?>

<div class="wrap">

	<div class="card" id="sendmail-testform">
		<h3><?php echo esc_html__( 'Send A test Mail', CF7_SMTP_TEXTDOMAIN ); ?></h3>

		<form action="">
			<label for="name-from">Name: </label>
			<input type="text" name="name-from" id="name-from" placeholder="<?php echo $this->options['advanced'] ? esc_html( $this->options['from_name'] ) : esc_html( wp_get_current_user()->display_name ); ?>" >

			<label for="subject">Subject:</label>
			<input type="text" id="subject" name="subject" placeholder="<?php esc_html_e( 'Add here something like: "this is a test mail!"', CF7_SMTP_TEXTDOMAIN ); ?>">

			<label for="email-from">From: </label>
			<input type="email" name="email-from" id="email-from" placeholder="<?php echo $this->options['advanced'] ? esc_html( $this->options['from_mail'] ) : esc_html( wp_get_current_user()->user_email ); ?>" >

			<label for="email">To*: </label>
			<input type="email" name="email" id="email" value="<?php echo esc_html( get_option( 'admin_email' ) ); ?>" required >

			<label for="body">Message:</label>
			<textarea id="body" name="body" rows="6" placeholder="<?php esc_html_e( 'Add here your custom mail body for the test mail otherwise a default body will be used', CF7_SMTP_TEXTDOMAIN ); ?>"></textarea>

			<div class="button-wrap">
				<button value="Send" class="button button-primary">Submit</button>
			</div>
		</form>

		<div id="sendmail-response">
			<pre></pre>
		</div>
	</div>

</div>
