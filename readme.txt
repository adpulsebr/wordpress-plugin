=== AdPulse sGTM ===
Contributors: adpulse, adpulse-team
Tags: google tag manager, gtm, analytics, tracking, sgtm, server-side, first-party, privacy, gdpr
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate WordPress sites with AdPulse server-side Google Tag Manager using first-party tracking for improved privacy and reliability.

== Description ==

AdPulse sGTM brings server-side Google Tag Manager to WordPress with first-party tracking. Unlike traditional GTM implementations that load from googletagmanager.com, AdPulse routes all tracking through your own domain, giving you:

* **First-Party Tracking**: All GTM scripts load from your domain, not third-party
* **Improved Privacy**: Cookies are set as first-party (SameSite=Lax), avoiding ITP restrictions
* **Better Reliability**: No browser blocking of third-party scripts
* **Full GTM Support**: All standard GTM features work seamlessly
* **Easy Setup**: Get your Container ID from AdPulse dashboard and paste it in

= How It Works =

1. Create a container in your AdPulse dashboard
2. Copy the Container ID
3. Enter the ID in plugin settings
4. Enable the plugin
5. Your GTM container loads from your domain (e.g., yourdomain.com/c/gtm.js)

All tracking data is processed server-side through AdPulse's sGTM infrastructure, while your WordPress site serves the scripts as first-party content.

= Features =

* First-party GTM script serving
* Server-side cookie rewriting (first-party cookies)
* Automatic data layer population (PHP)
* User agent detection (server-side)
* IP address inclusion with consent
* No JavaScript for user agent parsing
* Compatible with all standard GTM features
* GDPR/CCPA compliant when used with consent manager

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* An active AdPulse account with sGTM container

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in your WordPress admin
2. Search for "AdPulse sGTM"
3. Click "Install Now"
4. Activate the plugin
5. Go to Settings > AdPulse to configure

= Manual Installation =

1. Download the plugin ZIP file from [WordPress.org](https://wordpress.org/plugins/adpulse/)
2. Go to Plugins > Add New > Upload Plugin
3. Select the ZIP file and click "Install Now"
4. Activate the plugin
5. Configure in Settings > AdPulse

= Configuration =

1. Log in to your AdPulse dashboard at https://dashboard.adpulse.com.br
2. Create a new container
3. Copy the numeric Container ID
4. Go to WordPress Admin > Settings > AdPulse
5. Enter the Container ID
6. Enable the plugin
7. Save settings

Your GTM container will now load from your own domain!

== Frequently Asked Questions ==

= What is server-side GTM? =

Server-side GTM (sGTM) moves tracking logic from the user's browser to your own server. This improves privacy, reliability, and control over your data.

= Why use first-party tracking? =

First-party tracking loads scripts and sets cookies from your own domain instead of third-party domains. This:

* Avoids browser restrictions (ITP, ETP)
* Improves reliability and accuracy
* Provides better privacy control
* Reduces ad blocking impact

= Do I need a Google GTM account? =

No. AdPulse provides its own sGTM infrastructure. You only need an AdPulse account.

= What about existing GTM containers? =

If you have existing Google GTM containers, you can migrate them to AdPulse sGTM. Contact our support team for assistance.

= Is this GDPR/CCPA compliant? =

When used with a proper consent manager, yes. The plugin includes IP address inclusion only when consent is granted.

= Does this work with all GTM tags? =

Yes! All standard GTM features work with AdPulse sGTM, including Google Analytics 4, Google Ads, Facebook Pixel, and more.

== Screenshots ==

1. Settings page with container configuration
2. Status indicators showing active/inactive state
3. Documentation links and quick access

== Changelog ==

= 1.0.0 =
* Initial release
* First-party GTM script serving
* Server-side cookie rewriting
* Automatic data layer population
* User agent detection (PHP)
* IP address with consent support
* Admin settings page

== Upgrade Notice ==

= 1.0.0 =
Initial release of AdPulse sGTM for WordPress.

== Credits ==

Developed by [AdPulse](https://adpulse.com.br)

== License ==

This plugin is licensed under the GPLv2 or later.

== Support ==

* Documentation: https://docs.adpulse.com.br
* Dashboard: https://dashboard.adpulse.com.br
* Support: support@adpulse.com.br

== Donate ==

Love this plugin? Consider [donating to support development](https://adpulse.com.br/donate).
