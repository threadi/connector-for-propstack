=== Connector for Propstack ===
Contributors: laolaweb, threadi
Tags: propstack, real estate, immobilien, rental, property
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: @@VersionNumber@@

Import your [Propstack](https://www.propstack.de) properties into WordPress and show them as your own SEO-friendly pages. No iFrame, no duplicate data entry.

== Description ==

**Connector for Propstack connects your Propstack real estate CRM directly to your WordPress website.**

Your properties are imported into the WordPress database via the official Propstack API and displayed as regular WordPress content: a list, a filter and a detail page for every object - under your own domain, in the design of your own theme.

No iFrame is used. Every property gets its own URL, its own title tag and its own meta-description, so Google indexes your listings as your content, not somebody else's.

There is nothing to prepare on your hosting. Install the plugin, enter your Propstack API key and start the import — the setup wizard guides you through it in a few minutes.

[youtube https://www.youtube.com/watch?v=dhsQX7-3APY]

### Features

✅ Automatic or manual import of your properties from Propstack
✅ Import in German or in English
✅ List view showing up to 10 properties - unlimited in the Pro version
✅ Property types "Apartment", "House" and "Garage" with their respective fields
✅ Every property gets its own URL on your website — indexable by search engines
✅ Works with classic themes and block themes
✅ 7 blocks for the block editor plus [shortcodes](https://github.com/threadi/connector-for-propstack/blob/master/doc/shortcodes.md)
✅ Filter your property archive by city and object ID
✅ Privacy friendly: Propstack never receives any data about your visitors

### Requirements

🔌 A Propstack account with access to the API keys

### Advantages over iFrame embeds and the OpenImmo format

An embedded iFrame is quick to set up, but its content belongs to the source domain - your properties will never rank in Google under your own address. An OpenImmo export needs FTP credentials and a writable directory on your server, and it only transfers what the standard covers.

This plugin uses the Propstack API instead. A read-only API key is all you need: no FTP access, no export portal, no server configuration - and considerably more data fields than the OpenImmo standard provides.

### Pro version

The Pro version removes the limits of the free version and adds everything you need to build a complete real estate website:

➕ Import and display of all property types Propstack offers, such as "Plot", "Office" or "Store"
➕ Considerably more data fields for your Propstack properties
➕ Eight widgets and ready-made templates for page builders such as Avada, Bricks, Brizy, Divi 4, Elementor, GeneratePress Elements, Salient and WPBakery
➕ Send contact enquiries straight into Propstack using Avada Forms, Elementor Forms, Contact Form 7 or WPForms
➕ Additional blocks for the block editor, for example object status
➕ Personal support by the developers
➕ Up to 3 hours of setup service with the annual license
➕ Money-back guarantee: try it for 7 days without risk

🆙 [Get the Pro-version](https://laolaweb.com/en/plugins/propstack-wordpress-plugin/order-propstack-plugin-pro/)  — or [compare both versions](https://laolaweb.com/en/plugins/propstack-wordpress-plugin/) first.

### Who builds this plugin

Connector for Propstack is developed by [laOlaWeb](https://laolaweb.com), a WordPress agency based in Leipzig, Germany. We also build complete websites for real estate agents, including the Propstack integration.

### External services

This plugin connects to the Propstack API at [www.propstack.de](https://www.propstack.de) to retrieve the properties of your personal account.

This API service is provided by Propstack: [terms of service](https://www.propstack.de/nutzungsbedingungen/), [privacy policy](https://www.propstack.de/datenschutz/).

### Repository, documentation and reliability

The development repository is hosted on [GitHub](https://github.com/threadi/connector-for-propstack).

We also provide a set of [hooks](https://github.com/threadi/connector-for-propstack/blob/master/doc/hooks.md) for developers.

The Propstack logo contained in the provided icons is a trademark of [Propstack GmbH](https://www.propstack.de).

Every new release of this plugin is only published if it meets the following conditions:

✅ Compliance with WordPress Coding Standards.
✅ PHPStan checks for potential errors.
✅ No exceptions in the PHP Unit Tests.

---

== Installation ==

1. Upload "connector-for-propstack" to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. In Propstack, create an API key with read permissions for objects and object status.
4. Enter the API key in WordPress under "Settings > Connector for Propstack > API" and run the setup.
5. Place your properties on your site using one of the blocks or shortcodes.

== Frequently Asked Questions ==

= What is the difference between the free and the Pro version? =

The free version imports your properties of the types "Apartment", "House" and "Garage" and displays them on your website with filter, list view and detail view. The list view shows up to 10 properties. It is free to use, permanently.

The Pro version displays your properties without any limit and adds all remaining property types such as "Plot", "Office" and "Store", considerably more data fields, eight widgets with ready-made templates for page builders, additional blocks, contact forms that submit enquiries directly into Propstack, and personal support. The annual licence also includes up to 3 hours of setup service. You can find the current pricing on [our website](https://laolaweb.com/en/plugins/propstack-wordpress-plugin/).

= How do I create an API key in Propstack? =

Log in to your Propstack account and open the API key administration under Admin > API keys. Click "Add API key" and either choose the "WP ImmoMakler" template or set the permissions for "Objects" and "Object status" to "Read". Copy the key and paste it into WordPress under "Settings > Connector for Propstack > API".

A read-only key is sufficient. The plugin never needs write access to your CRM.

= Will my properties be found by Google? =

Yes. Every property becomes a regular WordPress post type entry with its own URL, its own title tag and its own meta-description. Your list and detail views are delivered as indexable HTML from your own domain, so search engines treat them as your content. SEO plugins such as Yoast SEO, Rank Math or SEOPress can be used on them like on any other page.

= Does this plugin use iFrames? =

No. No iFrame is used to embed any data. Your properties are stored in your own WordPress database and rendered by your own site.

= Can I use the plugin with Elementor, Bricks or Avada? =

The import works regardless of which page builder you use. Creating and editing property templates inside a page builder requires the Pro version, which ships widgets and prepared templates for Avada, Bricks, Brizy, Elementor, Salient and WPBakery.

In the free version you design your property views with the seven blocks of the block editor, with shortcodes and with CSS.

= How do I send contact enquiries from my website to Propstack? =

In the free version, build a form with the form plugin of your choice, receive the submissions by email and enter them into Propstack manually.

With Connector for Propstack Pro this transfer happens automatically: enquiries from Avada Forms, Elementor Forms, Contact Form 7 or WPForms are submitted directly into your Propstack account, with no manual step in between.

= Does Propstack receive data about my website visitors? =

No. The plugin communicates with the Propstack API only from your server, in order to import your own property data. Your visitors never connect to Propstack, so no visitor IP addresses or browser data are transferred. This is one of the practical differences to an embedded iFrame, which usually requires consent in your cookie banner.

= What happens to properties that are sold or archived? =

Properties that are no longer marketed in Propstack are removed from the import. Consider setting up redirects for their URLs so that existing links and search engine results do not end in a 404 error.

= Can I use the plugin without a Propstack account? =

The plugin can be installed without a Propstack account, but it cannot do anything useful without Propstack data. A Propstack account with API access is required.

= Where do I get support? =

For the free version, please use the [support forum](https://wordpress.org/support/plugin/connector-for-propstack/) here on WordPress.org. Pro customers get personal support directly from the developers, either through the "Questions? Ask here" function inside the plugin or by email.

== Screenshots ==

1. Setup leads you through the first steps
2. The Plugin settings
3. Configure which objects you want to import
4. Import the images of your objects
5. View your objects in the frontend

== Changelog ==

= @@VersionNumber@@ =
- Added option to switch to modern DataView for settings pages
- Added support for WP Consent API
- Set compatibility with WordPress 7.1
- Updated crypt lib to 3.0.0
- Updated dialog lib to 2.0.0
- Updated settings lib to 2.0.0
- Remove cpt support for our own objects in Brizy as they are not edited in WordPress
- Remove custom columns from some SEO plugins from our own cpt
- Optimized log statements

[older changes](https://github.com/threadi/connector-for-propstack/blob/master/changelog.md)
