# Tests with live data from Propstack

## Overview

`composer test` checks the plugin with fixed test data from _tests/Data/_ and never sends a request to Propstack. Whether the import works with the **real** data of a Propstack account is checked by `composer test-live`: the tests load the data via the real Propstack API, import it into the database of the WordPress tests and check the flows you would otherwise click through by hand.

The live tests **only read** from Propstack. Nothing is created, changed or deleted in Propstack. Everything is imported into the test database, which the test framework resets on every run. Images are stored shortly in the upload directory of the test installation and removed after each test.

The functions of _Connector for Propstack Pro_ (projects, property groups, webhooks …) have their own live tests in the Pro plugin. Both run independently, but use the same environment variables.

## Requirements

- The test environment as for `composer test`: a MySQL or MariaDB database and `composer test-install` once (installs WordPress and the test library in _/tmp_).
- `composer install` has been run. The autoloader is `classmap-authoritative`: after adding new test files, run `composer dump-autoload`, otherwise PHPUnit does not find the classes.
- The machine reaches `api.propstack.de` and the hosts of the images (e.g. `images.propstack.de`). WordPress rejects image URLs whose host cannot be resolved via DNS.
- An API key of a Propstack account **with objects**.

## Setup

1. Copy _tests/live-config.example.php_ to _tests/live-config.php_.
2. Enter at least the API key in _tests/live-config.php_.
3. Run `composer test-live` (or `task test-live`).

_tests/live-config.php_ is listed in _.gitignore_. Instead of the file, each value can also be set as environment variable, which wins over the file.

| Setting | Environment variable | Default | Meaning |
|---|---|---|---|
| `api_key` | `CFPROP_LIVE_API_KEY` | – | The API key. Without it, all live tests are skipped. |
| `api_versions` | `CFPROP_LIVE_API_VERSIONS` | `v1,v2` | The API versions to test, comma-separated. |
| `language` | `CFPROP_LIVE_LANGUAGE` | `de` | The language of the import. |
| `object_id` | `CFPROP_LIVE_OBJECT_ID` | empty | Optional: the Propstack-ID of an object which must be delivered and imported. |
| `max_objects` | `CFPROP_LIVE_MAX_OBJECTS` | `0` | Optional: import only the first X objects (`0` = all). |
| – | `CFPROP_LIVE_REQUIRE` | off | `1`: fail instead of skip if no key is set (e.g. in CI). |
| – | `CFPROP_LIVE_CONFIG` | _tests/live-config.php_ | Path to another configuration file. |

## What to watch out for in the configuration

**Never put the API key into the repository.** Only in _tests/live-config.php_ (ignored) or as environment variable or CI secret. Not in _composer.json_, _phpunit-live.xml.dist_ or any other versioned file. The log of the plugin masks the key, the messages of the tests never contain it.

**The permissions of the key must match the API versions.** They differ between v1 and v2:

- API v1 and v2: Objects (Read), Object states (Read)
- additionally for API v2: Properties_options (Read), Properties_statuses (Read), Brokers (Read)

If the key has the permissions for one version only, set `api_versions` to this version. Otherwise the tests of the other version fail with HTTP 401 or 403, which is not a bug in the plugin.

**The test database is not the website.** `composer test-install` creates the database `wordpress_test`, and the test framework empties it on every run. Never enter the database of a real website as test database.

**The tests run with the default settings of the plugin.** Restrictions from the import settings of a website (e.g. only certain states or marketing types) do not apply in the tests, so the amount of imported objects can differ from the website. Objects the plugin never imports (e.g. object types which are not supported, with API v2 everything not in state "Vermarktung") are counted as "prevented by restrictions" - that is expected.

**The language must fit.** `language` must be a language the plugin supports and in which Propstack delivers the objects. In the tests, it is also the language of the frontend.

**Choose `object_id` with care.** Ideally an object in marketing, of a supported object type and with images. It is used for the check of an object (like `wp cfprop check_object`) and for the import of images. Without it, the tests use the first imported object or the object with the fewest images.

**`max_objects` saves time, not requests.** All pages of the list of objects are still requested, only fewer objects are imported. The configured `object_id` is always imported in addition. The check "all delivered objects are imported" says more without a limit.

**Duration.** Depending on the account, a run takes a few minutes: per API version the objects are imported twice (first and second import), plus the images of one object. The process timeout of Composer is disabled for `test-live`. The timeout of a single request is the setting of the plugin (default 30 seconds).

**A real account, real load.** The tests send the same requests as an import on a website. A test account is ideal; test with the account of a customer only after agreement and do not repeat the run in short intervals.

## What is tested

| Test class | Check |
|---|---|
| `Connection` | The key is accepted (like in the setup and the settings), a wrong key is rejected and not saved. All pages of the list of objects arrive without errors. The configured object is delivered and not prevented by a restriction. API v2 only: states, options of selection fields and brokers are delivered. |
| `ImportFlowV1`, `ImportFlowV2` | The complete import runs without errors, each request is answered with HTTP 200, the lock of the import is released. Each delivered object is imported or prevented by a restriction (lost objects are detected). No object exists twice. Each object has its Propstack-ID, language, object type and the saved API response. The fields of all objects could be read with and without HTML, the single view of all objects and the list of objects could be rendered. A second import keeps the same post-IDs. The check of an object finds the imported object. The images of an object are imported. Deleting all objects removes their images too. |

PHP warnings, notices and deprecations end in a failure. While reading the fields and rendering the views, they are reported for the object they occur with.

## Reading the results

During the run the tests write lines with `[live]` to the console, e.g.:

```
[live] API v2: 128 objects delivered, 121 imported, 7 prevented by restrictions, 5 requests, 2 runs, 41.3 s.
```

- **Skipped (S):** no API key, API version not configured, no `object_id` set or no object with images. Use `-v` to see the reason.
- **Failed (F):** the message names the affected objects and, where it helps, all requests to Propstack with their HTTP status. HTTP 401 or 403 nearly always means: the key misses a permission.

Run single tests:

```bash
vendor/bin/phpunit -c phpunit-live.xml.dist --filter ImportFlowV2
vendor/bin/phpunit -c phpunit-live.xml.dist --filter test_fields_of_all_objects_could_be_read -v
```

Only one API version or another account, without changing the file:

```bash
CFPROP_LIVE_API_VERSIONS=v1 composer test-live
CFPROP_LIVE_API_KEY=xyz CFPROP_LIVE_MAX_OBJECTS=20 composer test-live
```

## In CI (optional)

Live tests need a secret and access to the internet. They do not belong to the normal tests of a pull request (pull requests from forks get no secrets). Use a separate workflow, started by hand or on a schedule, e.g. with:

```yaml
- name: Run live tests
  run: composer test-live
  env:
    CFPROP_LIVE_API_KEY: ${{ secrets.PROPSTACK_LIVE_API_KEY }}
    CFPROP_LIVE_REQUIRE: 1
```

`CFPROP_LIVE_REQUIRE: 1` makes a missing secret visible instead of skipping all tests silently.

## Adding live tests

- Test classes belong in _tests/Live/_ and extend `ConnectorForPropstack\Tests\LiveTestCase` (or `LiveImportFlowTestCase` for checks on the imported objects).
- Create data which all tests of a class need in `set_up_live_class()` - not in `set_up_before_class()`: the test framework reconnects to the database afterwards, and everything before would be lost from the second test class on.
- Only reading requests to Propstack. Never create or change anything in Propstack.
- Write messages which are understandable without a debugger: the affected object, its Propstack-ID and, for problems with the API, `get_api_requests_as_text()`.
- Run `composer dump-autoload` afterwards.
