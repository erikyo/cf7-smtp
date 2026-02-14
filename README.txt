=== SMTP for Contact Form 7 ===
Contributors: codekraft, gardenboi
Tags: smtp, mail, wp mail, mail template, contact form 7
Requires PHP: 7.1
Requires at least: 5.5
Tested up to: 6.9
Stable tag: 1.0.0
Requires plugins: Contact Form 7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free SMTP plugin for Contact Form 7 that allows the smtp server configuration of wp_mail() powered by automated report and custom mail templates.

== Description ==

WordPress uses PHPMailer to send mail from with your local mail server, but it can happen that your mail were not accepted by mail providers...
This can happen for several reasons, sometimes because the mail server is not configured or sometimes because the records DKIM, DMARC and SPF of the domain been set up correctly and so on...
Anyway you can avoid any problems by using an external SMTP server and sending mail with it!

= Additional features =

✅ **Live testing:** a module for testing e-mail settings with the Rest-Api (that avoid to reload the page for this kind of test). The entire output of the php mailer will be captured, which will be useful in case of configuration errors or to get the wrong parameter when is possible.
✅ **Customised template:** wrap cf7 emails with a template, so your emails will have a less textual and a little prettier format! The template can be customised for each form and internationalized.
✅ **Automated Reports:** choose when and what email you want to receive the report and I will send you a summary of sent and failed emails

This plugin is ads free and I don't want to try to sell you any pro version! If you want to contribute, there are many ways to do so, from simple suggestions and bug reports to translating and contributing code. See below how to do it!

== SMTP ==
SMTP stands for 'Simple Mail Transfer Protocol'. It is a connection-oriented, text-based network protocol of the Internet protocol family and as such is on the seventh layer of the ISO/OSI model, the application layer.
Like any other network protocol, it contains the rules for proper communication between networked computers. SMTP is specifically responsible for sending and forwarding e-mails from a sender to a recipient.
Since its release in 1982 as the successor to the 'Mail Box Protocol' in Arpanet, SMTP has become the standard protocol for sending e-mails. However, the SMTP procedure remains largely invisible to the normal consumer, as it is executed in the background by the e-mail programme used.
Only if the software, the webmail application on the browser or the mobile e-mail application does not automatically determine the SMTP protocol when creating an account, does it have to be set manually to ensure smooth e-mail traffic.

= SMTP presets =
1. Aruba
2. Gmail (tls and ssl)
3. Yahoo (tls and ssl)
4. Outlook (tls and ssl)
4. Office365 (tls)

= OAuth2 Setup (Google Gmail) =

To use Gmail with OAuth2, you need to create a Google Cloud Project:

1. Go to **Google Cloud Console** (console.cloud.google.com).
2. Create a new project.
3. Go to **APIs & Services > Credentials** and click **Create Credentials > OAuth client ID**.
4. Application type: **Web application**.
5. **Authorized redirect URIs**: Copy the URL from the plugin settings (e.g., `https://your-site.com/wp-admin/admin.php?page=cf7-smtp&oauth2_callback=1`).
6. Copy the **Client ID** and **Client Secret** into the plugin settings.
7. Important: Go to **OAuth consent screen > Test users** and add your email address if the app is in "Testing" mode.
8. Click **Connect with Gmail** in the plugin settings.

Would you like to find more presets (that you think are useful to other users)? Open a request in the support form and provide the necessary connection data (auth, server address and port). In the next cf7-smtp version you will find the required configuration among the presets.

= Security =
it's warmly advised to store at least the password into config.php as a constant. And in addition, it's also very easy! It needs only to add

``define( 'CF7_SMTP_USER_PASS', 'mySecr3tp4ssWord' );
``

into your `config.php` just before

``/* That's all, stop editing! Happy publishing. */
``

All passwords will be stored encrypted, but still it is not good practice to put it into database!

= Quick setup =
as with the user password other constants can also be defined. Available constant are CF7_SMTP_HOST, CF7_SMTP_PORT, CF7_SMTP_AUTH, CF7_SMTP_USER_NAME, CF7_SMTP_USER_PASS, CF7_SMTP_FROM_MAIL, CF7_SMTP_FROM_NAME

But, to quickly set up the plugin there is one constant that wraps all the others, so in case you manage multiple websites this will be very convenient!

``define(
    'CF7_SMTP_SETTINGS',
    array(
      'host'      => string,
      'port'      => number,
      'auth'      => ''|'tls'|'ssl',
      'user_name' => string,
      'user_pass' => string,
      'replyTo'   => true|false,
      'insecure'  => true|false,
      'from_mail' => email,
      'from_name' => string,
    ));
``

= Template =
Wouldn't it be better to have a small container to make our mail a little prettier? Well we have it!
Furthermore, if you prefer to use your own template for mail, simply create it by following these steps:
1. Create a folder named "cf7-smtp/" in your template folder.
2. Copy what you find [here](https://github.com/erikyo/cf7-smtp/blob/main/templates/default.html) into it
3. Name it `default.html` (or `default-{{CONTACT-FORM-ID}}-{{LANGUAGE}}.html` depends on your needs)
4. (Optional) You can, customize logo, website link and other template parts. checkout the filter documentation on GitHub/wiki

==Support==
Community support: via the [support forums](https://wordpress.org/support/plugin/cf7-smtp/) on wordpress.org
Bug reporting (preferred): file an issue on [GitHub](https://github.com/erikyo/cf7-smtp)

= Contribute =
We love your input! We want to make contributing to this project as easy and transparent as possible, whether it's:

* Reporting a bug
* Testing the plugin with different user agent and report fingerprinting failures
* Discussing the current state, features, improvements
* Submitting a fix or a new feature

We use GitHub to host code, to track issues and feature requests, as well as accept pull requests.
By contributing, you agree that your contributions will be licensed under its GPLv2 License.

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

= 1.0.0 =
* Cleaner code, updated dependencies
* @DAnn2012 has contributed fixin a bug in a translation string

= 0.0.2 =
* The configuration panel has been integrated with Contact Form 7 forms
* The widget which shows sent and unsent emails is now in the WordPress dashboard
* Fix an issue about password being reset when saving the plugin options

= 0.0.1 =
* First Release

== Screenshot ==
1. Plugin options (1/1)

= Resources =
* [Wordpress Plugin boilerplate](https://github.com/WPBP/WordPress-Plugin-Boilerplate-Powered)
* Contact Form 7 © 2021 Takayuki Miyoshi,[LGPLv3 or later](https://it.wordpress.org/plugins/contact-form-7/)
* chart.js https://www.chartjs.org/, © 2021 Chart.js [contributors](https://github.com/chartjs/Chart.js/graphs/contributors), [MIT](https://github.com/chartjs/Chart.js/blob/master/LICENSE.md)
* Banner image - Ejiri in Suruga Province (Sunshū Ejiri), from the series Thirty-six Views of Mount Fuji (Fugaku sanjūrokkei) Artist: Katsushika Hokusai (Japanese, Tokyo (Edo) 1760–1849 Tokyo (Edo))
