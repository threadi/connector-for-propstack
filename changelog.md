# Changelog

## [Unreleased]

### Changed

- Automatic unlock import after 1 hour

### Fixed

- Fixed a double action in V2 import

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
