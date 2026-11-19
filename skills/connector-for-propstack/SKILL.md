---
name: connector-for-propstack
description: Help someone who runs WordPress with the plugin Connector for Propstack set it up, import and show their real estate objects from Propstack, and find out why an object, an image or a field is missing. Not for developing the plugin codebase.
---

# Connector for Propstack (site operators)

Guidance for AI assistants helping **someone who runs WordPress with the plugin Connector for Propstack** (setup, import, display, troubleshooting). Typical users are real estate agents and the agencies building their websites.
This is **not** for developing the plugin codebase (see `AGENTS.md` in the repository for that).

If the site also uses **Connector for Propstack Pro**, use the skill `connector-for-propstack-pro` in addition.

Plugin page: https://wordpress.org/plugins/connector-for-propstack/

## What the plugin does

1. **Import**: it loads the objects (properties) of a Propstack account via the Propstack API with an API key and stores each as an entry in WordPress, with its fields, images and terms (object type, marketing type, property type, state, broker).
2. **Display**: a list of all objects and a detail page per object, via blocks (*List of objects*, *Filter*, *Single Object* …), block patterns, shortcodes or the templates of the theme.
3. **Update**: the import runs by hand or automatically in an interval. Objects which are no longer marketed in Propstack are removed with the next import.

## Mental model

```
Propstack account ──API key──► Import (API v1 or v2, one language)
                                   │
                    restrictions decide which objects are imported
                                   ▼
                    Objects in WordPress (one per object and language)
                       ├── fields, terms
                       └── images: afterwards via the queue
                                   ▼
                    List and detail page on the website
```

Key facts:

- The **images are imported after the objects** via a queue, not together with them. Right after an import, images are still missing - that is normal.
- **Without a restriction for the states, only objects in the state "Vermarktung" are imported.**
- An object is only imported if it has a **title** in Propstack (can be allowed under *Advanced settings > For objects*).
- The texts are imported in **one language** (*Settings > API*). Only texts maintained in Propstack in this language arrive.
- **Free version limits**: the list shows up to **10 objects** without pagination, and only the object types **apartment, house and garage** are imported. Everything else (plots, offices, stores …), pagination, more fields and widgets for page builders need Connector for Propstack Pro. Mention this neutrally when it explains the problem; do not push the upgrade otherwise.

## Where things are in the backend

All settings are under **Settings > Connector for Propstack**:

| Tab | What is there |
|---|---|
| API | API key, language of the import, API version (v1; v2 is beta) |
| Imports > Objects | *Import now*, *Delete objects*, automatic import and its interval |
| Imports > Images | Import all images now, delete imported files, image size |
| Objects | Restrictions (states, brokers, marketing types, object types, property types), filters, sorting |
| Fields | Which fields are shown, per object type; custom fields from Propstack |
| Maps | Map service (OpenStreetMap or Google Maps), consent, objects with hidden address |
| Queue | The images waiting for their import |
| Advanced settings | For objects (limit per run, max. share of objects to delete, objects without title, preserve files), for images, plugin, miscellaneous (timeout, check of schedules in frontend), abilities, debug mode |
| Logs | What happened during imports, with the reason for every object or image which was not imported |

The plugin also has a **knowledge center** (help entries linked from messages in the backend). Point the user to it; its answers match the installed version.

## Troubleshooting

**"An object is missing on the website"**

1. Is it delivered by Propstack at all? `wp cfprop check_object <Propstack-ID>` (WP-CLI) checks it with the same request as the import and says whether a restriction prevents it. Without WP-CLI: look in the log after an import.
2. Restrictions under *Objects > Restrictions* (state, broker, marketing type, object type, property type). Without a state restriction only "Vermarktung" is imported.
3. Object type not supported in the free version (only apartment, house, garage).
4. No title in Propstack.
5. Free version: the list shows only 10 objects - the object may exist but not be listed.
6. After changing restrictions, the import must run again (*Imports > Objects*).

**"Images are missing"** - they come via the queue after the import. Wait a few runs, or start *Imports > Images* by hand. Check *Settings > Queue* (must be enabled; each file is tried up to 3 times) and the log (failed download, file too large, no write permission in the upload directory). Images marked as private or "not for exposee" in Propstack are never imported.

**"Object pages show a 404 error"** - open *Settings > Permalinks* and click *Save Changes* without changing anything. Check that the detail view is not disabled (*Advanced settings > For objects*). Objects no longer marketed are removed with the next import.

**"The address is not shown"** - either it is hidden in Propstack (the plugin respects that) or the field *Address* is disabled for the object type under *Settings > Fields* (default for apartments, houses, garages).

**"The automatic import does not run"** - it uses WP-Cron, which needs visitors. With `DISABLE_WP_CRON` a server cron job must call WordPress. Alternative: a server cron job with `wp cfprop import_objects`.

**"The import stops or times out"** - reduce *Limit for objects* and *Limit for import of images* (*Advanced settings*), raise the *Default timeout* if the log says Propstack did not answer in time, check the log for a memory error. `wp cfprop import_objects` runs without the time limits of the web server.

**"Error 401/403 / insufficient permissions"** - the API key misses permissions in Propstack (*Verwaltung > API-Schlüssel*). They differ by API version: v1 and v2 need *Objects (Read)* and *Object states (Read)*; v2 additionally *Properties_options*, *Properties_statuses* and *Brokers (Read)*.

**"Texts are in the wrong language / codes instead of texts"** - check the language under *Settings > API* and whether the texts exist in Propstack in this language; with API v2 the texts of selection fields come from Propstack's options, the log tells if they could not be loaded. Run the import again after a change.

**"Sold objects disappeared"** - intended: objects no longer marketed are removed, their images too unless *Preserve files* is on. Suggest redirects for their old URLs.

## WP-CLI

| Command | Purpose |
|---|---|
| `wp cfprop check_object <ID>` | Is this object delivered and would it be imported? Nothing is changed. `--field=unit_id`, `--full-scan` |
| `wp cfprop import_objects` | Run the complete import |
| `wp cfprop import_files` / `process_queue` | Import missing images / process the queue |
| `wp cfprop delete_objects` / `delete_imported_files` | Delete all objects / all imported files (ask first) |
| `wp cfprop clear_queue` | Empty the queue without processing it |
| `wp cfprop reset_plugin` | Reset the whole plugin (ask first) |

## AI / automation (if available on the site)

The plugin registers **WordPress abilities** (category `connector-for-propstack`), usable via the MCP Adapter. The site operator can switch them off under *Advanced settings > Abilities*.

| Ability | Use it to |
|---|---|
| `get-objects`, `get-object` | List objects (search, language, sorting) and read one with its fields |
| `get-fields`, `get-taxonomies` | Look up field names and the existing terms |
| `get-import-status` | Is an import running, how many objects exist - start here for "objects are missing" |
| `start-import` | Start the import (it runs in steps; follow it with `get-import-status`) |
| `get-sorting-settings`, `update-sorting-settings` | Sorting of the list |
| `get-map-settings`, `update-map-settings` | Map service, consent, hidden addresses |
| `get-builders`, `get-template-catalog`, `get-template`, `preview-template`, `save-template`, `reset-template` | Design the detail view and the list with the page builder |

Abilities which change something support `dry_run`: call them with `dry_run: true` first, show the user what would change, and only then save. Ask before disabling the consent for maps (visitors' IP addresses would go to the map service without it).

## When helping the user

**Do**

- Ask whether they mean the **backend** (list under the menu of the objects) or the **website**.
- Check the log and the restrictions before assuming a bug.
- Explain that images follow after the import.
- Use the labels of the plugin's backend and its knowledge center.

**Don't**

- Ask the user to paste the API key into the chat. It is entered in *Settings > API* only.
- Delete objects, files or reset the plugin without asking. Deleting objects also deletes their images (unless *Preserve files*). Name what would be removed and wait for agreement.
- Tell the user to edit the PHP files of the plugin - updates overwrite them. Templates can be overridden in the theme.
- Switch to API v2 lightly: it is beta, delivers fewer fields, and needs other permissions and a new import.
- Invent setting names, hooks or Propstack fields.

## Boundaries

- You help with **using and configuring** Connector for Propstack on a site.
- Propstack itself (CRM, data maintenance, API keys) is in scope only as far as the import needs it.
- Themes, page builders, caching and other plugins are in scope where they affect the display of objects.
