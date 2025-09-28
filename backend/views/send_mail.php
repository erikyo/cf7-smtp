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

use cf7_smtp\Core\Mailer as Mailer;

$cf7_smtp_mailer    = new Mailer();
$cf7_smtp_from_name = $cf7_smtp_mailer->cf7_smtp_get_setting_by_key( 'from_name', $this->options );
$cf7_smtp_from_mail = $cf7_smtp_mailer->cf7_smtp_get_setting_by_key( 'from_mail', $this->options );
?>

<div class="wrap">

	<div class="card" id="sendmail-testform">
		<h3><code class="alignright">SMTP: <?php echo $this->options['enabled'] ? esc_html__( 'on', 'cf7-smtp' ) : esc_html__( 'off', 'cf7-smtp' ); ?></code></h3>
		<h3><?php echo esc_html__( 'Send A test Mail', 'cf7-smtp' ); ?></h3>

		<form action="">
			<label for="subject">Subject:</label>
			<input type="text" id="subject" name="subject" placeholder="<?php echo esc_attr__( 'Add here something like: "this is a test mail!"', 'cf7-smtp' ); ?>">

			<label for="email">To*: </label>
			<input type="email" name="email" id="email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required>

			<label for="body">Message:</label>
			<textarea id="body" name="body" rows="6" placeholder="<?php echo esc_attr__( 'Add here your custom mail body for the test mail otherwise a default body will be used', 'cf7-smtp' ); ?>"></textarea>

			<div class="button-wrap">
				<button value="Send" class="button button-primary">Submit</button>
			</div>
		</form>

		<div id="sendmail-response">
			<pre></pre>
		</div>
	</div>

</div>
