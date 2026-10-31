# Privacy text for readme.txt

Copy this into the `== Privacy ==` (or `== Third-party / external services ==`) section of your plugin's `readme.txt`, replacing the bracketed parts.

```
== Support requests ==

This plugin includes a Support screen (provided by the mammothwp/support library) in wp-admin. It never contacts any server and never sends data by itself: no tracking, no analytics, no update checks and no remote assets.

When an administrator fills in the form and presses Send, the site sends ONE email with wp_mail() to [support@example.com]. The email contains: the message, subject, name and reply-to address the administrator typed, the name and version of this plugin, and a reference code.

Only if the administrator ticks "Include site report" (unticked by default), the email also contains a compact report and a JSON copy of it as an attachment: site address (scheme and host only), WordPress, PHP and database versions, locale, timezone, debug and memory settings, active theme and its version, names and versions of active plugins, must-use plugins and drop-ins, and, if WooCommerce is active, its version, currency, base country and the ids of enabled payment and shipping methods. "Preview what will be sent" shows the exact text before sending. If the administrator also ticks "Include the last 50 lines of debug.log", those lines are added after emails, IP addresses and tokens are redacted.

The report never contains database credentials, salts or keys, passwords, API keys or tokens, user lists, customer or order data, other users' email addresses or absolute server paths.

The plugin keeps a short log on the site (option mmwp_support_log: time, plugin, reference code and result of the last 20 requests, never their contents). It is removed when the option is deleted.
```

Because the data goes to the plugin vendor's own mailbox through the site's mail configuration, no third-party service is involved. If a site owner configures an SMTP or transactional-mail plugin, that service delivers the email on the site owner's behalf.
