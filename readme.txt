=== Connector for Propstack ===
Contributors: laolaweb, threadi
Tags: propstack
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: @@VersionNumber@@

Import and display your objects from [Propstack](https://www.propstack.de) directly on your website. Get full control over how they are displayed.

== Description ==

Import and display your objects from [Propstack](https://www.propstack.de) directly on your website. Get full control over how they are displayed.

#### Features

- automatic import of marketing objects from Propstack in German or Englisch
- import of objects from type "Apartment", "House" and "Garage" with their respective fields
- objects are indexable for search engines (SEO)
- each object gets its own URL on your website
- data-protection compliant as Propstack did not get any data from your visitors
- 8 Blocks for Block Editor and [shortcodes](https://github.com/threadi/propstack-connector/blob/master/doc/shortcodes.md)
- support for classic as well as block themes
- filter for archive listings using City and Object ID

#### Requirements

- Propstack account with API credentials

#### External services

This plugin connects to the Propstack API via [www.propstack.de](https://www.propstack.de) to get the objects of your personal account there.

This API service is provided by Propstack: [terms and conditions](https://www.propstack.de/nutzungsbedingungen/), [privacy policy](https://www.propstack.de/datenschutz/).

== Repository, documentation and reliability ==

The development repository is on [GitHub](https://github.com/threadi/connector-for-propstack).

We also provide several [hooks](https://github.com/threadi/connector-for-propstack/blob/master/doc/hooks.md) as help for developers.

The Propstack logo as part of all distributed icons is a trademark of [Propstack GmbH](https://www.propstack.de).

Each release of this plugin will only be published if it fulfills the following conditions:

- Compliance with WordPress Coding Standards
- PHPStan check for possible bugs
- PHP Unit tests

---

== Installation ==

1. Upload "connector-for-propstack" to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Enter your API credentials in the settings.
4. Include one of the different output options for immo objects in your website.

== Frequently Asked Questions ==

= Can I use the plugin without a Propstack account? =

The plugin can be installed even without a Propstack account. However, it is unusable without Propstack data.

= Does this plugin use iframes? =

No, no iframe of any kind is used to embed data.

== Screenshots ==

1.

== Changelog ==

= @@VersionNumber@@ =
- Initial Release

[older changes](https://github.com/threadi/connector-for-propstack/blob/master/changelog.md)
