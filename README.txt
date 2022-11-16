=== SMTP for Contact From 7 ===
Contributors: codekraft
Tags: smtp, mail, wp mail smtp, mail template, phpmailer, contact form 7
Requires PHP: 7.1
Requires at least: 5.5
Tested up to: 6.1
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple SMTP plugin for Contact Form 7 with automated report and mail templates. Free and without ads

== Description ==

WordPress uses PHPMailer to send mail from with your local mail server, but it can happen that your mail were not accepted by mail providers...
This can happen for several reasons, sometimes because the mail server is not configured or sometimes because the records DKIM, DMARC and SPF of the domain have to be configured and so on... Anyway you can avoid any problems by using an external SMTP server and sending mail with it!

There is a module for testing the sending of 'live' e-mails (with the API rest without reloading the page) and the entire output of the php mailer will be captured, which will be useful in case of configuration errors (by even indicating which parameter is wrong in some cases).

And last but not least there is the possibility of using a customised template to send your e-mails in a less textual and slightly prettier format! The template can be customised for each form and internationalized.

This plugin is ads free and I don't want to try to sell you any pro version! If you want to contribute, there are many ways to do so, from simple suggestions and bug reports to translating and contributing code. See below how to do it!

== SMTP ==
SMTP stands for 'Simple Mail Transfer Protocol'. It is a connection-oriented, text-based network protocol of the Internet protocol family and as such is on the seventh layer of the ISO/OSI model, the application layer.
Like any other network protocol, it contains the rules for proper communication between networked computers. SMTP is specifically responsible for sending and forwarding e-mails from a sender to a recipient.
Since its release in 1982 as the successor to the 'Mail Box Protocol' in Arpanet, SMTP has become the standard protocol for sending e-mails. However, the SMTP procedure remains largely invisible to the normal consumer, as it is executed in the background by the e-mail programme used.
Only if the software, the webmail application on the browser or the mobile e-mail application does not automatically determine the SMTP protocol when creating an account, does it have to be set manually to ensure smooth e-mail traffic.

= SMTP presets  =
1. Aruba
2. Gmail (tls and ssl)
3. Yahoo (tls and ssl)
4. Outlook (tls and ssl)

Would you like to find more presets that you think are useful to other users? Open a request in the support form and provide the necessary connection data (auth, server address and port). In the following version of the plugin you will find them among the presets.

= Security =
it's warmly advised to store at least the password into config.php as a constant. And in addition, it's also very easy! It needs only to add
`define( 'CF7_SMTP_USER_PASS', 'mySecr3tp4ssWord' );`
into your config.php just before `/* That's all, stop editing! Happy publishing. */`
that passwords will be stored encrypted, but still it is not good practice to put it into database!
Available constant are CF7_SMTP_HOST, CF7_SMTP_PORT, CF7_SMTP_AUTH, CF7_SMTP_USER_NAME, CF7_SMTP_USER_PASS, CF7_SMTP_FROM_MAIL, CF7_SMTP_FROM_NAME

But, To quickly configure multiple websites there is one constant that wraps all the others, so in case you manage many websites this will be very convenient!
to add it to your website follow the same instructions as for a "single" value constant.

```
define(
    'CF7_SMTP_SETTINGS',
    array(
        'host'      => '',
        'port'      => '',
        'auth'      => '',
        'user_name' => '',
        'user_pass' => '',
        'from_mail' => '',
        'from_name' => '',
    )
);
```

= Template =
Wouldn't it be better to have a small container to make our mail a little prettier? Well we have it!
Furthermore, if you prefer to use your own template for mail, simply create it by following these steps:
1. Create a folder named "cf7-smtp/" in your template folder.
2. Copy what you find [here](https://github.com/erikyo/cf7-smtp/blob/main/templates/default.html) into it
3. Name it `default.html` (or `default-{{CONTACT-FORM-ID}}-{{LANGUAGE}}.html` depends on your needs)
4. (Optional) You can, customize logo, website link and other template parts. checkout the filter documentation on GitHub/wiki

= How this plugin works  =

I use a filter bundled with WordPress to configure the smtp server, modifying the normal behaviour of wp_mail.
During this process I can take the body of the e-mail in simple html and wrap it inside a html template (customizable)

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

= Resources =
* [Wordpress Plugin boilerplate](https://github.com/WPBP/WordPress-Plugin-Boilerplate-Powered)
* Contact Form 7 © 2021 Takayuki Miyoshi,[LGPLv3 or later](https://it.wordpress.org/plugins/contact-form-7/)
* chart.js https://www.chartjs.org/, © 2021 Chart.js [contributors](https://github.com/chartjs/Chart.js/graphs/contributors), [MIT](https://github.com/chartjs/Chart.js/blob/master/LICENSE.md)
* Banner image - Ejiri in Suruga Province (Sunshū Ejiri), from the series Thirty-six Views of Mount Fuji (Fugaku sanjūrokkei) Artist: Katsushika Hokusai (Japanese, Tokyo (Edo) 1760–1849 Tokyo (Edo))
