=== SMTP for Contact From 7 ===
Contributors: codekraft
Tags: smtp, mail, wp mail, mail template, phpmailer, contact form 7
Requires PHP: 7.1
Requires at least: 5.4
Tested up to: 6.1
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy SMTP plugin for Contact Form 7. Simple and useful.

== Description ==

WordPress uses PHPMailer to send mail from with your local mail server, but it can happen that your mail were not accepted by mail providers...
This can happen for several reasons, sometimes because the mail server is not configured or sometimes because the records DKIM, DMARC and SPF of the domain have to be configured and so on... Anyway you can avoid any problems by using an external SMTP server and sending mail with it!

There is a module for testing the sending of 'live' e-mails (with the API rest without reloading the page) and the entire output of the php mailer will be captured, which will be useful in case of configuration errors (by even indicating which parameter is wrong in some cases).

And last but not least there is the possibility of using a customised template to send your e-mails in a less textual and slightly prettier format! The template can be customised for each form and internationalized.

== SMTP ==
SMTP stands for 'Simple Mail Transfer Protocol'. It is a connection-oriented, text-based network protocol of the Internet protocol family and as such is on the seventh layer of the ISO/OSI model, the application layer. Like any other network protocol, it contains the rules for proper communication between networked computers. SMTP is specifically responsible for sending and forwarding e-mails from a sender to a recipient.
Since its release in 1982 as the successor to the 'Mail Box Protocol' in Arpanet, SMTP has become the standard protocol for sending e-mails. However, the SMTP procedure remains largely invisible to the normal consumer, as it is executed in the background by the e-mail programme used. Only if the software, the webmail application on the browser or the mobile e-mail application does not automatically determine the SMTP protocol when creating an account, does it have to be set manually to ensure smooth e-mail traffic.

= How this plugin works  =

I use a filter bundled with WordPress to configure the smtp server, modifying the normal behaviour of wp_mail.
During this process I can take the body of the e-mail in simple html and wrap it inside a html template (customizable)

= How add a custom template? =

1. Into your template folder create a directory "templates"
2. download the default template from [here](https://github.com/erikyo/cf7-smtp/blob/main/templates/default.html) and name it default-(*CONTACTFORMID*)-(*LANGUAGE*).html (replace *CONTACTFORMID* and *LANGUAGE* with the right references)
3. You can, in addition, customize logo, website link and other template parts. wiki/GitHub

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'cf7-smtp'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `cf7-smtp.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `cf7-smtp.zip`
2. Extract the `cf7-smtp` directory to your computer
3. Upload the `cf7-smtp` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Changelog ==

= 0.0.1 =
* First Release