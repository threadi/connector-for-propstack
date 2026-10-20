# Changelog

## [Unreleased]

### Added

- Added hint for administrators on the page of an object, if its map could not be shown (e.g. without geo coordinates)
- Added sorting of the list of objects: a selection above the list (archive widget, block and shortcode) offers only sortings which are useful for the objects of the actual list, works with filters and pagination, objects without value are shown at the end
- Added sorting by price (purchase price for objects to buy, base rent for objects to rent; in mixed lists the objects to buy are shown first, then the objects to rent)
- Added settings tab "Sorting" to enable the sorting and hide single sortings (the default sorting could be chosen with Connector for Propstack Pro)
- Added attribute "show_sorting" to the shortcode [cfprop_widget_archive]
- Added widget, block and shortcode "Map" to show the location of an object with OpenStreetMap (Leaflet) or Google Maps
- Added settings tab "Maps" for the map service, the API key for Google Maps, the tile server, consent before loading, zoom level, height and the handling of objects with hidden address
- Maps are loaded only after the consent of the visitor by default; the exact location of objects with hidden address is never sent to the browser
- Added abilities to read and change the settings of the sorting and the maps (get-sorting-settings, update-sorting-settings, get-map-settings, update-map-settings)
- Added parameter "sort" to the ability get-objects and the location of the object to the ability get-object
- Added the map block and hints for the sorting to the catalog of the template abilities
- Added help entries for the sorting and the maps
- Added multiple hooks for sortings and maps
- Added hint for the required API key permissions of API v1 and API v2 in the settings with a link to the help
- Added help entries for showing objects on the website, editing their views, import problems, missing images, API v2, automatic import, hidden addresses, sold objects, languages and support
- Help entries for displaying objects adapt to the used theme and detected page builders
- Added links from log entries and dialogs to the matching help entries
- Added entry "Edit object view" in the admin bar for administrators on the detail view and the list of objects, which shows the used template and links to it (can be disabled in the settings)
- Added block patterns for a list of objects with filter, the newest objects and the contact with the broker
- Show the reason why an object has not been imported
- Show the reason why an image of an object has not been imported (e.g. as it is marked as private in Propstack) in the log, if debug mode for imports is enabled
- Images which have already been imported and are marked as private or as not intended for the exposee in Propstack afterwards are removed from the object and the media library during the next import
- Added button "Import images" to the images of an object in the backend, also if it already has images, with a hint if images from Propstack are not imported yet
- Added hooks cfprop_prevent_file_import_reasons and cfprop_widget_archive_params
- Added hooks cfprop_widget_archive_output, cfprop_widget_map_output and cfprop_widget_filter_params (e.g. for the update of the list via AJAX in Connector for Propstack Pro)
- Added the attributes of the shortcode [cfprop_widget_filter] to its description
- The sorting above the list also works for lists which are loaded later, e.g. via AJAX
- Added WP CLI command to check whether the Propstack API delivers a specific object
- Added multiple hooks
- Added option --network to the WP CLI command reset_plugin to reset all sites of a multisite network
- Added Playwright tests as part of the release process
- Added setting for the max. percentage of objects which could be deleted after an import (Advanced settings > For objects)
- Added setting to allow the import of objects without title (Advanced settings > For objects)
- Added list of the custom fields of objects: their type is detected by their values, their label is created from their internal name (no additional permission of the API key needed); custom fields which are no longer used by any object are removed after a complete import
- Added hook to load the labels, types, units and options of custom fields from Propstack before each import (used by Connector for Propstack Pro for API v2)
- Added list of all custom fields in Settings > Fields > Custom fields
- Added help entry for custom fields from Propstack
- Added hooks for custom fields, e.g. to enable their output in the frontend (used by Connector for Propstack Pro)
- Added the custom fields, which are enabled for the frontend, to the catalog of the template abilities
- Added hooks to add own blocks to the catalog of the template abilities for block templates and to check them
- Columns of custom fields in the list of objects are hidden until they are enabled in the screen options, also for new custom fields

### Changed

- Objects without title are no longer imported by default; this is noted in the log (API v1 objects with an empty title have been imported before)
- Minimum required WordPress version is now 6.6
- The "hide address" setting from Propstack is now respected: street, house number, address and geo coordinates are not shown for such objects
- Renter, total commission and valuation prices are no longer shown in the frontend
- The address of houses and garages is now hidden by default, like for apartments
- Disabled fields are respected in all widgets, blocks, templates, the REST API and the abilities
- Abilities only return published objects to users without the plugin settings capability and return field values as plain text
- Form elements are no longer allowed globally in post content; only the filter output of this plugin may contain them (old behavior stays active if the Pro plugin or a theme override of the filter templates is detected)
- HTML in descriptions from Propstack is filtered during import like regular post content
- Numbers and prices show decimals if they have any and use the format of the site language
- Date fields (e.g. "free from") are shown in the date format of the site
- Scheduled imports now run within a time budget and continue automatically in a following run
- Imports started via abilities run in limited steps per cron request
- Images and queue entries of objects removed by the import are deleted, too (unless files should be preserved)
- Queue entries which failed repeatedly are removed after 30 days
- The queue list and the log view in the backend only load the entries of the current page
- The log export now runs in chunks
- Old log entries are cleaned up at most once per hour; the minimum age of log entries is 1 day
- Email addresses and phone numbers in logged API responses are masked
- Database errors and exception traces in the log no longer contain complete statements or server paths
- Texts from the Propstack API are escaped in log entries, status texts and dialogs
- The timeout for requests to the Propstack API is limited to 5–120 seconds
- The API key is never returned via the REST API and is excluded from the settings export
- If the API key could not be encrypted, the previous value is kept and an error is shown
- Encryption and settings errors are logged at most once per day
- Schedule and compatibility checks no longer run on AJAX requests; compatibility checks run at most every 12 hours or after plugin or theme changes
- The check for outdated templates in child themes runs much less often and without remote filesystem access
- Schedule checks and plugin updates are protected against parallel requests
- The progress of processes is saved per process
- The admin script of the plugin is only loaded for users with access to the plugin
- The list of selectable authors is limited to users who can edit posts
- The backend search for objects searches the title and the most important fields and is much faster
- Faster object list in the backend
- The saved debug API response is deleted when debug mode is disabled and is no longer autoloaded
- The WP CLI commands `import_files` and `process_queue` wait for a running import of files and end with an error if it is still running
- The uninstallation removes all options, transients and settings of the plugin, also on archived or deactivated sites of a multisite network
- Completed the "Getting started" guide for abilities with example prompts
- The description of external services in readme.txt now explains which data is sent and when
- Hardened the GitHub release workflow (pinned actions, minimal permissions, tests and smoke test before the release)
- Removed developer files from release packages and git exports
- Updated the bundled library for notices (dismissing requires the capability to see the notice)
- Changed the log category for changed field values from system to import
- Now fully support Propstack API v2 as far as Propstack does deliver data atm
  -> over 120 fields are missing
  -> still only usable for developers
- Encrypted settings are now bound to the setting they belong to: copied into another field of
  the database, they can no longer be decrypted there. Existing values are converted
  automatically during the update
- Replaced custom TextInfo object with the new one from settings lib
- The API key field is now a password field which restricts the visibility of your API key
- Updated the process to check any changed and create the release on GitHub
- Updated hints how to use MCP with our abilities
- Updated dependencies
- More PHP Unit Tests
- Custom fields are saved with the prefix "cfprop_cf_" to prevent collisions with other fields; the values in the previous format are converted during the next import of each object
- Values of custom fields are now saved and shown in a uniform format (e.g. the names of chosen options instead of their IDs with API v1, dates and numbers in the format of the website)
- Custom fields, which are no longer delivered by Propstack, are removed during the import
- Country fields show the name of each country instead of its code

### Fixed

- The validation of the interval of the automatic import no longer requires functions of the backend (e.g. if saved via WP-CLI)
- Fixed misaligned prices and areas in the list of objects, e.g. in block themes (empty values are no longer output and the styles of the costs on the detail page no longer apply to the list)
- Fixed title of existing objects not being updated by the import if it has been changed in Propstack (the URL of the object stays the same)
- Fixed import of images via API v2
- Fixed wrong JS-function to import settings for the plugin
- Remove our schedules on plugin deactivation
- Fixed missing logging of errors if debug mode is enabled with restriction for one or more categories
- Fixed REST API for objects returning all fields, including internal data, to every logged-in user
- Fixed private email of a broker being used for the inquiry button in the classic single template
- Fixed post meta of objects being registered for all post types
- Fixed users without the needed capability being able to dismiss notices of this plugin
- Fixed network activation on multisite running on all sites and ending on the wrong site
- Fixed resetting the plugin via settings or WP CLI affecting all sites of a multisite network
- Fixed duplicate images if the queue and the import of images ran at the same time
- Fixed queue stopping forever if a single image crashed the request
- Fixed scheduled imports of large accounts never finishing because of PHP time limits
- Fixed import restarting from the beginning after a fatal error; the object which caused it is now skipped and the import is aborted after 3 fatal errors without progress
- Fixed import of images never finishing on hosts with a size-limited object cache
- Fixed import cleanup deleting objects which were not created by the import
- Fixed import being blocked for an hour if a task before the start of the import reported an error
- Fixed timeouts during the cleanup of obsolete objects
- Fixed object states with the same name overwriting each other with API v2
- Fixed file names containing the query string of the image URL
- Fixed raw JSON output if the import of images was started without JavaScript
- Fixed success messages for the import of files and the queue if another import was running
- Fixed brokers no longer used by any object never being removed
- Fixed custom fields from Propstack being able to overwrite fields of the plugin
- Fixed object type restriction not working with API v1
- Fixed scheduled and WP CLI imports not updating hidden internal fields (e.g. unit ID, exposé ID)
- Fixed thumbnails not being assigned to remaining objects if one object had no usable image
- Fixed "delete all objects" not removing drafts, private and trashed objects
- Fixed filter block having no effect on the object archive in block themes
- Fixed paginated object archive pages resulting in a 404 error
- Fixed single object block template using the header and footer of a specific theme, a wrong distance field, a wrongly mapped "Hausgeld" row and a button without target
- Fixed styles of blocks not being applied; they are now validated before use
- Fixed invalid template names in shortcodes resulting in an error
- Fixed unavailable listing template not falling back to the default listing
- Fixed possible PHP errors with unexpected values in REST API requests and block attributes
- Fixed hidden broker fields being offered in the block editor
- Fixed resetting a template via abilities removing its CSS without the capability to edit CSS
- Fixed CSS text appearing above galleries in classic themes without HTML5 support
- Fixed object selection in the "Single Object" block not showing the selected object
- Fixed filter block being labelled "Field" in the block inserter
- Fixed block previews in the block inserter
- Fixed missing block styles in classic themes
- Fixed "Location" and "Features" templates for object fields not being available
- Fixed boolean fields without value being shown as "no"
- Fixed negative numbers losing their sign and room counts like 2.5 being shown as 2
- Fixed email, phone and list fields not being escaped
- Fixed invalid dates being shown as 01.01.1970
- Fixed error when adding a term to a taxonomy of this plugin via AJAX
- Fixed missing permalink and "View" link on the edit screen of objects
- Fixed log entries being deleted completely if the max. age for log entries was 0
- Fixed time zone used for deleting old log entries
- Fixed "Every 20 minutes" interval running every 15 minutes
- Fixed errors in settings never being written to the log
- Fixed cached administrator used as fallback author not being updated after user or role changes
- Fixed network-wide activated plugins not being detected by the compatibility checks
- Fixed import dialog retrying endlessly or failing silently on server errors
- Fixed possible error in the frontend if the plugin name is requested
- Fixed possible fatal error in grouped notices
- Fixed WPML configuration using wrong names for post types and taxonomies
- Fixed release builds missing the energy scale block and the files for the intro
- Fixed a fatal error if custom fields are using an array

## [2.0.0] - 30.09.2026

### Added

- Added new widget for show the energy scale of a single object, usable as Block and via shortcode
- Added new field type for energy efficiency classes with check against a whitelist for value A+ to H
- Added new field type for geo coordinates to output them with 4 digits
- Added internal names for each supported page builder
- Added multiple abilities to get object data, start imports or create templates for and with objects via AI or your preferred MCP plugin
- Added the Propstack logo as icon in the new WordPress Icon API
- Added compatibility check for Avia, Beaver Builder, Divi 5 and SiteOrigin
- Added an intro after the setup to introduce the main functions of this plugin
- Added a filter in both media library views to show only images for objects
- Added an info box on every image from Propstack in media library
- Added some new hooks

### Changed

- Optimized the setup to onboarding the plugin
- Multiple optimizations for stability of the plugin
- Use $throwable::class instead of get_class() for the modern way of PHP
- Use configured language during the installation of default terms for our taxonomies
- Do not show error marker if setup has not been completed
- Resort some settings
- Updated settings lib
- Updated lightbox3 library

### Fixed

- Fixed check for Elementor Pro and PRO Elements
- Fixed missing DataView scripts in release build
- Fixed faulty check for the singular template to display the thumbnail
- Fixed faulty default value for daily import schedule
- Fixed wrong handling of block templates from third parties
- Fixed missing run of check for tasks to update after plugin update

### Removed

- Removed language files from repository

## [1.1.1] - 16.09.2026

### Changed

- Optimized deletion of objects for more speed
- Optimizations on import for more speed too
- Check for valid API key during first step in setup and show hints if there are any problems
- Show info about empty feed from Propstack in setup
- Better infos in import dialog about the start
- Prevent crypt error after installation
- Updates setup and settings lib

### Fixed

- Fixed 2 wrong textdomains in Settings.php
- Fixed missing usage of Lightbox for the gallery widget
- Fixed old interval name for 2 settings

## [1.1.0] - 14.09.2026

### Added

- Introducing paginated import for objects using API v1 and v2 in backend to prevent timeouts during manual imports
- Added error marker in menu and settings for any events where an error was logged
- Added option to debug only single topics, e.g., to get complete logs for import of objects but not for system-messages

### Changed

- Optimized import via API v1 and v2 if objects are prevented to be imported
- Optimized used memory and amount of database queries during the import for higher speed and to prevent memory_limit errors
- Optimized catch of fatal error during import via XML or API v2
- Use an extended logging for any API error
- Log if Propstack API is using an unexpected field type
- More PHP Unit Tests to reduce possible errors with the Propstack API
- Clearer hint for unsupported object types in log
- Optimized AJAX-driven progress dialog to be less flickering
- Optimized memory usage during import process
- Optimized styling of the logging table
- Optimized styling of the object table
- Optimized the process handler to remove older process information
- Prevent deletion of all objects if Propstack API does not response
- Updated crypt lib and settings lib
- Save the collapsible states of sections in the settings
- Extended the function to get settings URL
- Show hint if nothing has been imported

### Fixed

- Fixed wrong handling of taxonomy term slug during import, which results in missing imported objects if slugs are used multiple times
- Fixed missing dialog for the button to empty the log
- Fixed missing subfield definition for status terms, which results in PHP-warnings during imports
- Fixed missing value for custom fields (not the pretty value)
- Fixed missing cache using for file import

### Removed

- Removed now unused double function to check for an active WordPress development mode

## [1.0.5] - 07.09.2026

### Added

- Added option on the shortcode for fields to show the value of a specific field from a given object
- Added compatibility checks and hints
- Added some new hooks to allow developers to extend the plugin

### Changed

- Optimized the log cleanup
- Optimized button styling in the edit view
- Show the internal field name in the list of all fields in the settings
- Allow overriding some default values via shortcode
- Optimized styling of settings in both views
- Updated the settings and the dialog lib
- Moved the field "Heating Type" and "Firing Types" from Building to Firing Types
- Changed to get errors during creation and update of open positions from WordPress
- Only log the API response if debug mode is enabled
- Typos

### Fixed

- Fixed the loading of field values if they are used in other post types as our own
- Fixed the statement to get our own Block templates from the database
- Fixed the visibility of the save button in settings
- Fixed the wrong cronjob interval name for queue running
- Fixed check for development mode in WordPress < 6.3
- Fixed a wrong variable name for the "Single" block
- Fixed a wrong name for the hidden tab to load the hidden section for settings
- Fixed a wrong natural sorting of field lists by name
- Fixed the progressbar in the plugin setup
- Fixed faulty reading of object titles for log entries during the import

## [1.0.4] - 17.08.2026

### Added

- Added option to switch to modern DataView for settings pages
- Added support for WP Consent API

### Changed

- Set compatibility with WordPress 7.1
- Updated crypt lib to 3.0.0
- Updated dialog lib to 2.0.0
- Updated settings lib to 2.0.0
- Remove cpt support for our own objects in Brizy as they are not edited in WordPress
- Remove custom columns from some SEO plugins from our own cpt
- Optimized log statements

## [1.0.3] - 30.07.2026

### Added

- Added more PHP Unit Tests to check for main functions this plugin is using
- Added method to run updates after a plugin update

### Changed

- Now using paginated imports for V1 and V2, which allows the import of hundreds of Propstack objects in one rush
- Automatic unlock import after 1 hour
- Optimized the log definition and usage in debug mode
- Optimized the usage of all Blocks
- Import supported object states with API v2

### Fixed

- Fixed a double action in V2 import
- Fixed a missing action in V2 import

### Removed

- Removed some now unused code

## [1.0.2] - 20.07.2026

### Changed

- Optimized capability checks
- Optimized adding lines in CSV-export for logs
- Scheduled import for objects will now use API v2 if set

### Fixed

- Fixed a logical error during the check of the object type on any object to import
- Fixed a wrong filter for property types during import of objects with API v1
- Fixed the missing clearing of cache through a wrong internal name

## [1.0.1] - 13.07.2026

### Changed

- Optimized loading of object types
- Use global helper to nat sort arrays
- Updated crypt library to 2.0.1
- Updated dependencies

### Fixed

- Fixed an error in the default archive template
- Fixed a wrong field in Block Editor single template
- Fixed missing output of boolean fields in some cases

## [1.0.0] - 22.06.2026

- Initial release
