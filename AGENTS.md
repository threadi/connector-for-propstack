# AGENTS.md

Instructions for AI coding agents working on **Connector for Propstack** (`threadi/connector-for-propstack`, the "light" plugin) - and on its add-on **Connector for Propstack Pro**, which has its own repository and its own `AGENTS.md`.

This file is for **plugin development**. It is not end-user documentation: assistants helping site operators use `skills/connector-for-propstack/SKILL.md`.

## The two plugins

| | Connector for Propstack (this repo) | Connector for Propstack Pro |
|---|---|---|
| Role | Imports objects from Propstack, shows them via blocks, shortcodes and templates | Add-on: projects, all object types, property groups, documents, videos, webhooks, inquiries, widgets for many page builders, own update and license server |
| Namespace | `ConnectorForPropstack\` | `ConnectorForPropstackPro\` |
| Text domain | `connector-for-propstack` | `connector-for-propstack-pro` |
| Hook prefix | `cfprop_` | `cfprop_pro_` (and `cfprop_` hooks of the light plugin) |
| Main file | `connector-for-propstack.php` (`CFPROP_PLUGIN`, `CFPROP_VERSION`) | `connector-for-propstack-pro.php` (`CFPROP_PRO_PLUGIN`) |
| Distribution | wordpress.org | laolaweb.com |

Pro **requires** the light plugin (`Requires Plugins: connector-for-propstack`) and only starts if the light version is at least `CFPROP_PRO_REQUIRED_LIGHT_VERSION` (Pro: `inc/constants.php`). Pro uses classes, singletons and hooks of the light plugin directly. Consequences:

- Renaming or removing a public method, class, option, meta key, term meta or hook here can break Pro. Search the Pro repository before such a change and keep a deprecated alias if Pro uses it.
- A new feature that Pro needs: add a hook or a public method here, then use it in Pro. Do not let Pro patch the behaviour of the light plugin in other ways.
- Restrictions of the light plugin (list shows up to 10 objects, only apartments, houses and garages are imported, hints for Pro) are lifted by Pro via filters (e.g. `cfprop_hide_pro_hints`). Keep them filterable.
- The Pro tests install the light plugin from GitHub `master` (or a local copy, `LIGHT_PLUGIN_SOURCE`). A change here that Pro relies on must be merged first.

## Requirements and setup

- PHP >= 8.2, WordPress >= 6.6, Composer, Node/npm for the blocks. [Task](https://taskfile.dev) is optional, Ant (`build/`) is the alternative.

```bash
composer install          # also runs Mozart (app/Dependencies) and dump-autoload
npm install && npm run build
# or: task prepare
```

For development add `define( 'WP_ENVIRONMENT_TYPE', 'local' ); define( 'WP_DEVELOPMENT_MODE', 'plugin' );` to _wp-config.php_. Some features (e.g. selecting the API v2 in the settings) are only offered in development mode (`Helper::is_development_mode()`).

## Commands

```bash
composer test-install     # once: WordPress test suite (drops and creates the database "wordpress_test")
composer test             # PHPUnit with fixed test data, never calls Propstack
composer test-live        # PHPUnit against the real Propstack API, see doc/live-tests.md
vendor/bin/phpstan analyse   # level 8
vendor/bin/phpcs .        # WordPress Coding Standards incl. VIP
vendor/bin/phpcbf .
npm run build             # blocks
npm run test:e2e          # Playwright against WordPress Playground (tests/e2e)
task docs                 # regenerate doc/hooks.md from the hook docblocks
task release -- 3.2.0     # all checks + release zip
```

The autoloader is `classmap-authoritative`: after adding a class (also in `tests/`), run `composer dump-autoload`.

## Layout

| Path | Role |
|---|---|
| `connector-for-propstack.php` | Bootstrap, constants |
| `app/Plugin/` | Init, installer, settings (library *easy-settings-for-wordpress*), setup, log, CLI (`wp cfprop …`), schedules, helper, crypt, compatibilities with form and page builder plugins |
| `app/Propstack/` | Everything about Propstack: API requests, imports, objects, fields, taxonomies, files, queue, widgets, abilities, knowledge center |
| `app/Propstack/Imports/v1`, `Imports/v2` | The imports per API version |
| `app/Propstack/Fields/`, `FieldTypes/`, `FieldFormats/`, `FieldCategories/` | One class per field of an object, its type, its output format and its category |
| `app/Propstack/KnowledgeCenter/` | Help entries shown in the backend and linked from messages |
| `app/PageBuilder/` | Gutenberg blocks and templates |
| `app/Dependencies/` | Mozart output - never edit by hand |
| `blocks/` | Block sources and builds |
| `templates/` | Frontend templates (overridable by themes) |
| `doc/` | Developer docs (`hooks.md` is generated) |
| `skills/` | Skill for assistants of site operators (not shipped) |
| `tests/Unit/` | PHPUnit with fixtures from `tests/Data/` |
| `tests/Live/` | PHPUnit against the live API (`composer test-live`) |
| `tests/e2e/` | Playwright |

The release zip contains only what `bin/build-release.sh collect` lists. `AGENTS.md`, `skills/`, `doc/`, `tests/` are not shipped - keep it that way.

## Domain model

- **Object**: post type `cfprop_object`, one post per Propstack object **and language**. Identified by the post meta `object_id` (Propstack-ID) plus `language_code`. Use `ImmoObjects::get_object_for_import()` (any status) in imports, `get_object_by_object_id()` for published objects.
- **Fields**: each field is a class (`Field_Base`) and is stored as post meta with its name. Read values through `Fields::get_field_value()` / `ImmoObject::get_fields()`, never raw meta in templates - formatting, HTML and hidden fields depend on it.
- **Taxonomies**: `cfprop_object_type`, `cfprop_object_marketing_type`, `cfprop_object_property_type`, `cfprop_object_status`, `cfprop_object_broker`, `cfprop_object_category`. Terms carry the API value in the term meta `api` and the language in `language_code`. Find them with `Taxonomy::get_term_id_by_api_value()`.
- **Files**: images are not imported with the object. `Files` and the queue (`cfprop_queue`) download them afterwards; the list of attachments of an object is the post meta `images`.

## Import (critical)

- `ImmoObjects::get_import_object()` chooses `Imports\v1\Objects` or `Imports\v2\Objects` by the option `propstack_connector_api_version` (v2 is beta). Both extend `Import_Base`.
- The import runs in **chunks**: the first run builds a work list (option `cfprop_objects_to_import` + blocks), each run processes up to the limit and sets `has_load_more()`. Callers loop until it is false (`Cli::import_objects()`, AJAX dialog, cron). Every chunk holds a chunk lock; `CFPROP_IMPORT_RUNNING` marks a running import. Do not add a second way to run it.
- The API v2 delivers another structure: `Imports\v2\Objects::prepare_object()` brings it into the v1 structure (texts of selection fields via `Options`, broker via `Brokers`). New v2 support goes there, not into the fields.
- Whether an object is imported is decided by the filter `cfprop_prevent_import_of_object`. Every check must also explain itself in `ImmoObjects::get_prevent_import_reasons()`, because prevented objects are logged and listed with their reason.
- Objects which are no longer delivered are deleted after the import, unless more than `propstack_connector_max_cleanup_percent` would go at once (protection against incomplete API answers). Keep this protection.
- `cfprop_import_object` runs for every imported object (fields, terms, title, broker image, queue). Hook in there instead of changing the import loop.

## Propstack API

- Every request goes through `Propstack\ApiRequest`: no redirects (they would forward the API key), the key is anonymized and personal data masked in the log, one retry on HTTP 429. Do not call `wp_remote_*` for the Propstack API directly.
- The API key (`propstack_connector_api_key`) is stored encrypted (`Plugin\Crypt`, read/save callbacks of the setting); `get_option()` returns it decrypted. Never log, echo or return it (also not in abilities or REST).
- Permissions of the key differ between v1 and v2 (`KnowledgeCenter\InsufficientPermissions`). A 401/403 must lead to a message that names the missing permission.
- The light plugin only reads from Propstack. Anything that writes to Propstack belongs to Pro.

## Abilities

`Abilities`, `Display_Abilities`, `Template_Abilities` register WordPress abilities (category `connector-for-propstack`, e.g. `get-objects`, `start-import`, `save-template`) for MCP clients. Site operators switch them off under *Advanced settings > Abilities* (*Provide abilities*, `Abilities_Settings`). Abilities which save settings or templates support `dry_run` (default: only show what would change). Keep this for new abilities which change something, and never return secrets (API keys) from an ability.

## Coding conventions

- WordPress Coding Standards (incl. VIP), PHPStan level 8. Run `phpcbf`, `phpcs`, `phpstan` before finishing.
- Singletons via `get_instance()`; ABSPATH guard in every PHP file.
- Every new hook gets a docblock with `@since` and `@param` (`task docs` generates `doc/hooks.md` from it). Prefix `cfprop_`.
- User-facing strings translatable with the text domain `connector-for-propstack`; messages in the backend link to a knowledge center entry where it helps.
- Comments describe the step (`// bail if …`, `// get the …`), like the existing code.
- `declare(strict_types=1);` is used in Pro everywhere, here only in single files: do not add it in bulk.

## Tests

- Unit tests (`tests/Unit/`) extend `ConnectorForPropstackTestCase`, which answers the Propstack API with the fixtures in `tests/Data/`. They must never reach the internet.
- Live tests (`tests/Live/`, `composer test-live`) use a real API key from `tests/live-config.php` (git-ignored) or `CFPROP_LIVE_*` variables. They only read from Propstack. Read `doc/live-tests.md` before changing them; class-wide data belongs in `set_up_live_class()`.
- Change of the import or a field: extend the unit tests and run the live tests once if an API key is available.
- E2E (`tests/e2e/`) run against WordPress Playground without access to Propstack.

## Do not

- Delete anything without asking first: files, branches, objects, terms, options, database tables or the test database (`composer test-install` drops it). Say what would be deleted and wait for the answer.
- Commit an API key, `tests/live-config.php` or `build/build.properties`.
- Send requests to Propstack which change data, in code of the light plugin or in tests.
- Bypass `ApiRequest`, the chunked import, `cfprop_prevent_import_of_object` or the cleanup protection.
- Edit `app/Dependencies/` (Mozart output) or the generated `doc/hooks.md` by hand.
- Change a public API that Pro uses without checking the Pro repository.
- Invent Propstack API fields from memory: check the fixtures in `tests/Data/`, the API docs (https://docs.propstack.de, v2: https://api.propstack.de/docs/index.html) or a live response.

## Human docs

Plugin page: https://wordpress.org/plugins/connector-for-propstack/ · Setup and release: `readme.md` · Hooks: `doc/hooks.md` · CLI: `doc/cli.md` · Live tests: `doc/live-tests.md`.
