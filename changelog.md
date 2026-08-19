# Changelog

## [Unreleased]

### Changed

- Optimized the log cleanup

### Fixed

- Fixed the statement to get our own Block templates from the database

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
