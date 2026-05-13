# Hooks

- [Actions](#actions)
- [Filters](#filters)

## Actions

### `propstack_connector_setup_completed`

*Run additional tasks if the setup is marked as completed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 489](Plugin/Setup.php#L489-L494)

### `propstack_connector_object_field_metabox`

*Run additional tasks during the view of a single field in a metabox in the backend.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post` | `\WP_Post` | The post.
`$field` | `\PropstackConnector\Propstack\Field_Base` | The field.
`$value` | `mixed` | The value.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Field_Category_Base.php](Propstack/Field_Category_Base.php), [line 116](Propstack/Field_Category_Base.php#L116-L124)

### `propstack_connector_import_object_field`

*Run additional tasks for a single field on an object during the import of them.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field` | `\PropstackConnector\Propstack\Field_Base` | The field.
`$value` | `mixed` | The value.
`$post_id` | `int` | The post-ID of the object.
`$object_type_object` | `\PropstackConnector\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type.
`$immo_object` | `array<string,mixed>` | The data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 714](Propstack/Fields.php#L714-L724)

### `propstack_connector_import_object_fields`

*Run additional tasks for fields on an object during the import of them.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\PropstackConnector\Propstack\Field_Base>` | The list of fields.
`$post_id` | `int` | The post-ID of the object.
`$object_type_object` | `\PropstackConnector\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type.
`$immo_object` | `array<string,mixed>` | The data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 748](Propstack/Fields.php#L748-L757)

### `propstack_connector_import_object_before_start`

*Run additional tasks before starting the import of objects.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 111](Propstack/Imports/v2/Objects.php#L111-L116)

### `propstack_connector_import_object`

*Run additional tasks for a single language-specific object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$object` | `array<string,mixed>` | The object data from API.
`$post_id` | `int` | The post-ID of the object.
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 297](Propstack/Imports/v2/Objects.php#L297-L306)

### `propstack_connector_import_language`

*Run additional tasks for importing objects in a given language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 321](Propstack/Imports/v2/Objects.php#L321-L328)

### `propstack_connector_import_object_errors`

*Run additional tasks if any error occurred during import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\PropstackConnector\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 339](Propstack/Imports/v2/Objects.php#L339-L346)

### `propstack_connector_import_object_success`

*Run additional tasks after successfully import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\PropstackConnector\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 351](Propstack/Imports/v2/Objects.php#L351-L358)

### `propstack_connector_import_object_after`

*Run additional tasks after any import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\PropstackConnector\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 372](Propstack/Imports/v2/Objects.php#L372-L378)

### `propstack_connector_import_object_set_max_count`

*Run additional tasks after setting the max count.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$count` | `int` | The max count.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 457](Propstack/Imports/v2/Objects.php#L457-L463)

### `propstack_connector_import_object_set_count`

*Run additional tasks after setting the max count.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$count` | `int` | The max count.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 477](Propstack/Imports/v2/Objects.php#L477-L483)

### `propstack_connector_import_object_set_status`

*Run additional tasks after setting the new status.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$new_status` | `string` | The new status.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 497](Propstack/Imports/v2/Objects.php#L497-L503)

### `propstack_connector_import_object_before_start`

*Run additional tasks before starting the import of objects.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 109](Propstack/Imports/v1/Objects.php#L109-L114)

### `propstack_connector_import_object`

*Run additional tasks for a single language-specific object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$object` | `array<string,mixed>` | The object data from API.
`$post_id` | `int` | The post-ID of the object.
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 281](Propstack/Imports/v1/Objects.php#L281-L290)

### `propstack_connector_import_language`

*Run additional tasks for importing objects in a given language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 305](Propstack/Imports/v1/Objects.php#L305-L312)

### `propstack_connector_import_object_errors`

*Run additional tasks if any error occurred during import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\PropstackConnector\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 323](Propstack/Imports/v1/Objects.php#L323-L330)

### `propstack_connector_import_object_success`

*Run additional tasks after successfully import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\PropstackConnector\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 335](Propstack/Imports/v1/Objects.php#L335-L342)

### `propstack_connector_import_object_after`

*Run additional tasks after any import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\PropstackConnector\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 356](Propstack/Imports/v1/Objects.php#L356-L362)

### `propstack_connector_import_object_set_max_count`

*Run additional tasks after setting the max count.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$count` | `int` | The max count.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 441](Propstack/Imports/v1/Objects.php#L441-L447)

### `propstack_connector_import_object_set_count`

*Run additional tasks after setting the max count.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$count` | `int` | The max count.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 461](Propstack/Imports/v1/Objects.php#L461-L467)

### `propstack_connector_import_object_set_status`

*Run additional tasks after setting the new status.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$new_status` | `string` | The new status.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 481](Propstack/Imports/v1/Objects.php#L481-L487)

### `propstack_connector_file_imported`

*Run additional tasks after a file has been imported.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attachment_id` | `int` | The attachment ID.
`$id` | `int` | The Propstack file ID.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 458](Propstack/Files.php#L458-L465)

### `propstack_connector_files_deleted`

*Run additional tasks after all files for objects have been deleted.*


Source: [app/Propstack/Files.php](Propstack/Files.php), [line 579](Propstack/Files.php#L579-L582)

### `propstack_connector_files_before_import`

*Run additional tasks before the files are imported during object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$files_to_import` | `array<string,mixed>` | The list of files.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 823](Propstack/Files.php#L823-L829)

### `propstack_connector_import_object_set_status`

*Set the new state for the setup.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$new_state_text` | `string` | The new status.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 906](Propstack/Files.php#L906-L912)

### `propstack_connector_file_is_assigned`

*Run additional tasks after a file has been assigned to an object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attachment_id` |  | 
`$file` | `array<string,mixed>` | The file data from Propstack API.
`$immo_object_obj` | `\PropstackConnector\Propstack\ImmoObject` | The immo object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 927](Propstack/Files.php#L927-L935)

### `propstack_connector_files_for_object_imported_via_ajax`

*Run additional tasks after the files has been imported during the object import via AJAX.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post_id` | `int` | The post-ID of the object (optional).

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 985](Propstack/Files.php#L985-L991)

### `propstack_connector_queue_before_processing`

*Run additional tasks before the queue is processed.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`count($queue)` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 341](Propstack/Queue.php#L341-L347)

### `propstack_connector_file_is_assigned`

*Run additional tasks after a file has been assigned to an object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attachment_id` |  | 
`$file` | `array<string,mixed>` | The file data from Propstack API.
`$immo_object` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 403](Propstack/Queue.php#L403-L411)

### `propstack_connector_queue_processing`

*Run additional tasks after the queue has processed one entry.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`1` |  | 
`$attachment_id` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 420](Propstack/Queue.php#L420-L427)

### `propstack_connector_queue_after_processing`

*Run additional tasks after the queue has been processed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 434](Propstack/Queue.php#L434-L439)

## Filters

### `propstack_connector_supported_languages`

*Return the supported languages.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$languages` | `string[]` | List of supported languages.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 94](Plugin/Languages.php#L94-L101)

### `propstack_connector_fallback_language`

*Filter the fallback language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fallback_language` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 185](Plugin/Languages.php#L185-L192)

### `propstack_connector_current_language`

*Filter the resulting language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$wp_language` | `string` | The language-name (e.g., "en").

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 241](Plugin/Languages.php#L241-L248)

### `propstack_connector_language_mappings`

*Filter the possible mapping languages.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$mapping_languages` | `array` | List of language mappings.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 261](Plugin/Languages.php#L261-L268)

### `propstack_connector_language_mappings`

*Filter the possible mapping languages.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$mapping_languages` | `array` | List of language mappings.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 289](Plugin/Languages.php#L289-L296)

### `propstack_connector_schedule_interval`

*Filter the interval to a single schedule.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$interval` | `string` | The interval.
`$instance` | `\PropstackConnector\Plugin\Schedules_Base` | The schedule-object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 83](Plugin/Schedules_Base.php#L83-L90)

### `propstack_connector_schedule_enabling`

*Filter whether to activate this schedule.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | True if this object should NOT be enabled.
`$instance` | `\PropstackConnector\Plugin\Schedules_Base` | Actual object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 201](Plugin/Schedules_Base.php#L201-L211)

### `propstack_connector_templates_archive`

*Filter the list of available templates for archive listings.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$templates` | `array<string,string>` | List of templates (filename => label).

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 78](Plugin/Templates.php#L78-L85)

### `propstack_connector_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$directory` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 119](Plugin/Templates.php#L119-L119)

### `propstack_connector_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$directory` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 153](Plugin/Templates.php#L153-L153)

### `propstack_connector_load_single_template`

*Decide whether to use our own template (false) or not (true).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Return true if our own single template should not be used.
`$single_template` | `string` | The single template, which will be used instead.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 290](Plugin/Templates.php#L290-L299)

### `propstack_connector_load_archive_template`

*Decide whether to use our own archive template (false) or not (true).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Return true if our own archive template should not be used.
`$archive_template` | `string` | The archive template, which will be used instead.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 341](Plugin/Templates.php#L341-L350)

### `propstack_connector_add_kses_filter`

*Prevent filtering the HTML code via kses.*

We need this only for the filter-form.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | False if the filter should be run.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 368](Plugin/Templates.php#L368-L377)

### `propstack_connector_intervals`

*Filter the list of possible intervals.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of our interval objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Intervals.php](Plugin/Intervals.php), [line 78](Plugin/Intervals.php#L78-L84)

### `propstack_connector_setup_is_completed`

*Filter the setup complete marker.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$completed` | `bool` | True if setup has been completed.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 131](Plugin/Setup.php#L131-L137)

### `propstack_connector_setup`

*Filter the configured setup for this plugin.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$setup` | `array<int,array<string,mixed>>` | The setup-configuration.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 210](Plugin/Setup.php#L210-L217)

### `propstack_connector_transient_title`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::get_plugin_name()` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 227](Plugin/Setup.php#L227-L227)

### `propstack_connector_setup_config`

*Filter the setup configuration.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$config` | `array<string,array<int,mixed>\|string>` | List of configuration for the setup.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 283](Plugin/Setup.php#L283-L289)

### `propstack_connector_setup_process_completed_text`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$completed_text` |  | 
`$config_name` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 468](Plugin/Setup.php#L468-L468)

### `propstack_connector_log_table_filter`

*Filter the list before output.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,string>` | List of filter.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log_Table.php](Plugin/Log_Table.php), [line 273](Plugin/Log_Table.php#L273-L279)

### `propstack_connector_status_list`

*Filter the list of possible states in the log table.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log_Table.php](Plugin/Log_Table.php), [line 332](Plugin/Log_Table.php#L332-L337)

### `propstack_connector_objects_with_db_tables`

*Add additional objects for this plugin, which use custom tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$objects` | `array<int,string>` | List of objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Init.php](Plugin/Init.php), [line 115](Plugin/Init.php#L115-L121)

### `propstack_connector_objects_with_db_tables`

*Add additional objects for this plugin, which use custom tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$objects` | `array<int,string>` | List of objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Init.php](Plugin/Init.php), [line 154](Plugin/Init.php#L154-L160)

### `propstack_connector_log_categories`

*Filter the list of possible log categories.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,string>` | List of categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 152](Plugin/Log.php#L152-L159)

### `propstack_connector_log_limit`

*Filter limit to prevent possible errors on big tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` | `int` | The actual limit.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 185](Plugin/Log.php#L185-L191)

### `propstack_connector_log_category`

*Filter the used category.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$category` | `string` | The category to use.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 196](Plugin/Log.php#L196-L202)

### `propstack_connector_log_md5`

*Filter the used md5.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$md5` | `string` | The md5 to use.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 212](Plugin/Log.php#L212-L218)

### `propstack_connector_log_errors`

*Filter for errors.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$errors` | `int` | Should be 1 to filter only for errors.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 223](Plugin/Log.php#L223-L229)

### `propstack_connector_archive_slug`

*Change the archive slug.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$slug` | `string` | The archive slug.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since first release.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 110](Plugin/Helper.php#L110-L117)

### `propstack_connector_single_slug`

*Change the single slug.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$slug` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since first release.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 131](Plugin/Helper.php#L131-L138)

### `propstack_connector_file_version`

*Filter the used file version (for JS- and CSS-files, which get enqueued).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$plugin_version` | `string` | The plugin-version.
`$filepath` | `string` | The absolute path to the requested file.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 196](Plugin/Helper.php#L196-L204)

### `propstack_connector_current_url`

*Filter the resulting current URL.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$page_url` | `string` | The resulting current URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 422](Plugin/Helper.php#L422-L428)

### `propstack_connector_log_export_filename`

*Filter the filename for CSV-download.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$filename` | `string` | The generated filename for CSV-download.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Admin/Admin.php](Plugin/Admin/Admin.php), [line 157](Plugin/Admin/Admin.php#L157-L164)

### `propstack_connector_hide_pro_hints`

*Hide the additional buttons for reviews or pro-version.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Set true to hide the buttons.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Plugin/Admin/Admin.php](Plugin/Admin/Admin.php), [line 237](Plugin/Admin/Admin.php#L237-L244)

### `propstack_connector_schedule_our_events`

*Filter the list of our own events, e.g., to check if all, which are enabled in setting are active.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$our_events` | `array<string,array<string,mixed>>` | List of our own events in WP-cron.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 133](Plugin/Schedules.php#L133-L140)

### `propstack_connector_disable_cron_check`

*Disable the additional cron check.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | True if the check should be disabled.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 158](Plugin/Schedules.php#L158-L166)

### `propstack_connector_schedules`

*Add custom schedule-objects to use.*

They must be objects based on \PropstackConnector\Plugin\Schedules_Base.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list_of_schedules` | `array<int,string>` | List of additional schedules.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 263](Plugin/Schedules.php#L263-L272)

### `propstack_connector_gutenberg_pattern`

*Filter the list of pattern we provide for the Block Editor.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$patterns` | `array<string,mixed>` | List of patterns.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg/Patterns.php](PageBuilder/Gutenberg/Patterns.php), [line 109](PageBuilder/Gutenberg/Patterns.php#L109-L116)

### `propstack_connector_block_templates`

*Filter the list of available block templates.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$templates` | `array<string,array<string,string>>` | The list of templates.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg/Templates.php](PageBuilder/Gutenberg/Templates.php), [line 217](PageBuilder/Gutenberg/Templates.php#L217-L224)

### `propstack_connector_block_help_url`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::get_plugin_support_url()` |  | 

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 123](PageBuilder/Gutenberg/Blocks_Basis.php#L123-L123)

### `propstack_connector_gutenberg_block_{$name}_attributes`

*Filter the attributes for a Block.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$single_attributes` | `array<string,mixed>` | The settings as array.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 197](PageBuilder/Gutenberg/Blocks_Basis.php#L197-L204)

### `propstack_connector_gutenberg_block_{$name}_path`

*Filter the path of a Block.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$path` | `string` | The absolute path to the block.json.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 215](PageBuilder/Gutenberg/Blocks_Basis.php#L215-L222)

### `propstack_connector_block_language_path`

*Return the language path this plugin should use.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$language_path` | `string` | The path to the languages.
`$instance` | `\PropstackConnector\PageBuilder\Gutenberg\Blocks_Basis` | The Block object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 252](PageBuilder/Gutenberg/Blocks_Basis.php#L252-L262)

### `propstack_connector_pagebuilder`

*Filter the possible page builders.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `string[]` | List of the handler.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Page_Builders.php](PageBuilder/Page_Builders.php), [line 61](PageBuilder/Page_Builders.php#L61-L68)

### `propstack_connector_is_block_theme`

*Filter whether this theme is a block theme (true) or not (false).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$resulting_value` | `bool` | The resulting value.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg.php](PageBuilder/Gutenberg.php), [line 107](PageBuilder/Gutenberg.php#L107-L113)

### `propstack_connector_gutenberg_blocks`

*Filter the list of available Gutenberg blocks.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of blocks.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg.php](PageBuilder/Gutenberg.php), [line 132](PageBuilder/Gutenberg.php#L132-L138)

### `propstack_connector_taxonomy_{$taxonomy_slug}`

*Filter the settings for this taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$taxonomy_array` | `array<string,mixed>` | The taxonomy settings.
`$taxonomy_slug` | `string` | The slug of the taxonomy.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 122](Propstack/Taxonomy.php#L122-L129)

### `propstack_connector_rest_taxonomy_fields`

*Filter the available details-templates for REST API.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,array<string,mixed>>` | The fields.
`$instance` | `\PropstackConnector\Propstack\Taxonomy` | The taxonomy object.
`$request` | `\WP_REST_Request` | The REST API request object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 283](Propstack/Taxonomy.php#L283-L292)

### `propstack_connector_taxonomy_terms_query`

*Filter the query for terms on a single taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query.
`$instance` | `\PropstackConnector\Propstack\Taxonomy` | The taxonomy object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 423](Propstack/Taxonomy.php#L423-L430)

### `propstack_connector_taxonomy_terms_query`

*Filter the query to delete terms of one taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query parameter.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 556](Propstack/Taxonomy.php#L556-L562)

### `propstack_connector_fields`

*Filter the list of available fields.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,string>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 611](Propstack/Fields.php#L611-L617)

### `propstack_connector_rest_fields`

*Filter the available details-templates for REST API.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,mixed>` | The fields.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 948](Propstack/Fields.php#L948-L955)

### `propstack_connector_object_data_widget_attributes`

*Filter the fields for the object data widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,array<string,mixed>>` | The fields for the single widget.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 116](Propstack/Widgets/Object_Data.php#L116-L121)

### `propstack_connector_object_data_widget_attributes`

*Filter the attributes for the object data widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the single widget.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 123](Propstack/Widgets/Object_Data.php#L123-L128)

### `propstack_connector_archive_query_params`

*Filter the archive query params, e.g., to filter the list.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query_params` | `array<string,mixed>` | The additional query parameters for "WP_Query".
`$attributes` | `array<string,mixed>` | The attributes.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 70](Propstack/Widgets/Archive.php#L70-L76)

### `propstack_connector_widget_archive_attributes`

*Filter the attributes for the archive widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the archive widget.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 102](Propstack/Widgets/Archive.php#L102-L107)

### `propstack_connector_widget_filter_select_attributes`

*Filter the attributes for the select filter widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the archive widget.

Source: [app/Propstack/Widgets/Filter.php](Propstack/Widgets/Filter.php), [line 90](Propstack/Widgets/Filter.php#L90-L95)

### `propstack_connector_widget_single_attributes`

*Filter the attributes for the single widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the single widget.

Source: [app/Propstack/Widgets/Single.php](Propstack/Widgets/Single.php), [line 95](Propstack/Widgets/Single.php#L95-L100)

### `propstack_connector_filters`

*Filter the list of available immo object filters.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of filters.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filters.php](Propstack/Filters.php), [line 270](Propstack/Filters.php#L270-L276)

### `propstack_connector_filter_types`

*Filter the list of available immo object filter types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of filter types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filters.php](Propstack/Filters.php), [line 322](Propstack/Filters.php#L322-L328)

### `propstack_connector_property_type_default_terms`

*Filter the default terms of categories.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$terms` | `array<int,mixed>` | List of terms.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/PropertyType.php](Propstack/Taxonomies/PropertyType.php), [line 664](Propstack/Taxonomies/PropertyType.php#L664-L670)

### `propstack_connector_taxonomy_broker_fields`

*Filter the list of available broker fields.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\PropstackConnector\Propstack\Field_Base>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/Broker.php](Propstack/Taxonomies/Broker.php), [line 134](Propstack/Taxonomies/Broker.php#L134-L140)

### `propstack_connector_category_default_terms`

*Filter the default terms of categories.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$terms` | `array<int,mixed>` | List of terms.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/Category.php](Propstack/Taxonomies/Category.php), [line 109](Propstack/Taxonomies/Category.php#L109-L115)

### `propstack_connector_marketing_type_default_terms`

*Filter the default terms of marketing types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$terms` | `array<int,mixed>` | List of terms.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/MarketingType.php](Propstack/Taxonomies/MarketingType.php), [line 104](Propstack/Taxonomies/MarketingType.php#L104-L110)

### `propstack_connector_object_type_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\PropstackConnector\Propstack\Field_Base>` | List of fields.
`$instance` | `\PropstackConnector\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/Garage.php](Propstack/Taxonomies/ObjectTypes/Garage.php), [line 138](Propstack/Taxonomies/ObjectTypes/Garage.php#L138-L146)

### `propstack_connector_object_type_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\PropstackConnector\Propstack\Field_Base>` | List of fields.
`$instance` | `\PropstackConnector\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/House.php](Propstack/Taxonomies/ObjectTypes/House.php), [line 137](Propstack/Taxonomies/ObjectTypes/House.php#L137-L144)

### `propstack_connector_object_type_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\PropstackConnector\Propstack\Field_Base>` | List of fields.
`$instance` | `\PropstackConnector\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/Apartment.php](Propstack/Taxonomies/ObjectTypes/Apartment.php), [line 161](Propstack/Taxonomies/ObjectTypes/Apartment.php#L161-L168)

### `propstack_connector_object_type_default_disabled_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\PropstackConnector\Propstack\Field_Base>` | List of fields.
`$instance` | `\PropstackConnector\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/Apartment.php](Propstack/Taxonomies/ObjectTypes/Apartment.php), [line 184](Propstack/Taxonomies/ObjectTypes/Apartment.php#L184-L191)

### `propstack_connector_object_types`

*Filter the list of available object types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$object_types` | `array<int,string>` | List of object types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectType.php](Propstack/Taxonomies/ObjectType.php), [line 96](Propstack/Taxonomies/ObjectType.php#L96-L102)

### `propstack_connector_object_typ_default_terms`

*Filter the default terms of object types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$terms` | `array<int,mixed>` | List of terms.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectType.php](Propstack/Taxonomies/ObjectType.php), [line 211](Propstack/Taxonomies/ObjectType.php#L211-L217)

### `propstack_connector_taxonomy_terms_query`

*Filter the query for terms on a single taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query.
`$instance` | `\PropstackConnector\Propstack\Taxonomy` | The taxonomy object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectType.php](Propstack/Taxonomies/ObjectType.php), [line 233](Propstack/Taxonomies/ObjectType.php#L233-L240)

### `propstack_connector_filter_hide_field_by_value`

*Prevent the usage of this filter value.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Whether the filter value should be hidden.
`$md5` | `string` | The md5 hash of the field name and the value.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filters/Cities.php](Propstack/Filters/Cities.php), [line 162](Propstack/Filters/Cities.php#L162-L171)

### `propstack_connector_filter_hide_field_by_value`

*Prevent the usage of this filter value.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Whether the filter value should be hidden.
`$md5` | `string` | The md5 hash of the field name and the value.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filters/Cities.php](Propstack/Filters/Cities.php), [line 240](Propstack/Filters/Cities.php#L240-L249)

### `propstack_connector_request_header`

*Filter the headers for the request.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$headers` | `array<string,string>` | List of headers.
`$instance` | `\PropstackConnector\Propstack\ApiRequest` | The ApiRequest-object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/ApiRequest.php](Propstack/ApiRequest.php), [line 126](Propstack/ApiRequest.php#L126-L134)

### `propstack_connector_import_object_languages`

*Filter the languages to import object states from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$languages` | `array<string,int>` | The languages to import.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 103](Propstack/Imports/v2/Objects.php#L103-L109)

### `propstack_connector_object_import_response`

*Filter the response data from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$data['data']` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 173](Propstack/Imports/v2/Objects.php#L173-L179)

### `propstack_connector_prevent_import_of_object`

*Prevent import of this object under custom conditions.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$prevent_import` | `bool` | True to prevent the import.
`$object` | `array` | The object data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 212](Propstack/Imports/v2/Objects.php#L212-L222)

### `propstack_connector_new_object_query`

*Filter the query to add a new object during import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query.
`$object` | `array<string,mixed>` | The object data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 269](Propstack/Imports/v2/Objects.php#L269-L277)

### `propstack_connector_api_object_url`

*Filter the URL of the API to import objects from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$url` | `string` | The URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 411](Propstack/Imports/v2/Objects.php#L411-L417)

### `propstack_connector_import_object_languages`

*Filter the languages to import object states from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$languages` | `array<string,int>` | The languages to import.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 101](Propstack/Imports/v1/Objects.php#L101-L107)

### `propstack_connector_object_import_response`

*Filter the response data from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$data` | `array<string,mixed>` | The response data.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 162](Propstack/Imports/v1/Objects.php#L162-L168)

### `propstack_connector_prevent_import_of_object`

*Prevent import of this object under custom conditions.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$prevent_import` | `bool` | True to prevent the import.
`$object` | `array` | The object data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 202](Propstack/Imports/v1/Objects.php#L202-L212)

### `propstack_connector_new_object_query`

*Filter the query to add a new object during import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query.
`$object` | `array<string,mixed>` | The object data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 253](Propstack/Imports/v1/Objects.php#L253-L261)

### `propstack_connector_api_object_url`

*Filter the URL of the API to import objects from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$url` | `string` | The URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 395](Propstack/Imports/v1/Objects.php#L395-L401)

### `propstack_connector_file_import_array`

*Filter the query to upload a file in the media library.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$array` | `array<string,mixed>` | The parameter to use.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 417](Propstack/Files.php#L417-L423)

### `propstack_connector_file_import_post_array`

*Filter the post-query to upload a file in the media library.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post_array` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 429](Propstack/Files.php#L429-L435)

### `propstack_connector_files_query`

*Filter the query to get the list of files we imported from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array` | The query array.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 632](Propstack/Files.php#L632-L638)

### `propstack_connector_files_import_limit`

*Filter the limit of files to import during object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` | `int` | The limit.
`$files_to_import` | `array<string,mixed>` | The list of files.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 856](Propstack/Files.php#L856-L863)

### `propstack_connector_register_taxonomies`

*Filter the taxonomies.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$taxonomies` | `array<int,string>` | List of taxonomies.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies.php](Propstack/Taxonomies.php), [line 167](Propstack/Taxonomies.php#L167-L174)

### `propstack_connector_get_immo_obj`

*Filter the requested position object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$immo_object` | `\PropstackConnector\Propstack\ImmoObject` | The object of the object.
`$language_code` | `string` | The requested language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 136](Propstack/ImmoObjects.php#L136-L144)

### `propstack_connector_queue_table_columns`

*Filter the columns for the queue table before output.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$columns` | `array<string,string>` | List of columns.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Tables/Queue.php](Propstack/Tables/Queue.php), [line 35](Propstack/Tables/Queue.php#L35-L41)

### `propstack_connector_queue_table_column_content`

*Filter the content of a single column for the queue table.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$content` | `string` | The content of the column.
`$column_name` | `string` | The name of the column.
`$item` | `\WP_Post` | The item object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Tables/Queue.php](Propstack/Tables/Queue.php), [line 123](Propstack/Tables/Queue.php#L123-L132)

### `propstack_connector_queue_table_filter`

*Filter the list before output.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,string>` | List of filter.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Tables/Queue.php](Propstack/Tables/Queue.php), [line 245](Propstack/Tables/Queue.php#L245-L251)

### `propstack_connector_field_categories`

*Filter the list of available field categories.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$categories` | `array<int,string>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldCategories.php](Propstack/FieldCategories.php), [line 83](Propstack/FieldCategories.php#L83-L89)

### `propstack_connector_field_categories`

*Filter the list of available field category types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$category_types` | `array<int,string>` | List of field category types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldCategories.php](Propstack/FieldCategories.php), [line 170](Propstack/FieldCategories.php#L170-L176)

### `propstack_connector_register_post_type`

*Filter the post-types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post_types` | `array<int,string>` | List of post-types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Post_Types.php](Propstack/Post_Types.php), [line 95](Propstack/Post_Types.php#L95-L102)

### `propstack_connector_object_prevent_meta_box_remove`

*Prevent removing of all meta-boxes in the edit view of objects.*

Caution: the boxes will not be able to be saved.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Set true to prevent removing of each meta-box.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/PostTypes/ImmoObject.php](Propstack/PostTypes/ImmoObject.php), [line 411](Propstack/PostTypes/ImmoObject.php#L411-L422)

### `propstack_connector_object_do_not_hide_meta_box`

*Decide if we should not remove the support for this meta-box.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Return true to ignore this box.
`$box` | `array` | Settings of the meta-box.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/PostTypes/ImmoObject.php](Propstack/PostTypes/ImmoObject.php), [line 437](Propstack/PostTypes/ImmoObject.php#L437-L447)

### `propstack_connector_queue_fields`

*Filter the list of fields for the Propstack queue.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<string,array<string,mixed>>` | The list of fields.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/PostTypes/Queue.php](Propstack/PostTypes/Queue.php), [line 265](Propstack/PostTypes/Queue.php#L265-L272)

### `propstack_connector_queue_query`

*Filter the query to get the next entries for the processing of the queue.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 295](Propstack/Queue.php#L295-L300)

### `propstack_connector_widgets`

*Filter the list of available widgets.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$widgets` | `array<int,string>` | List of widgets.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets.php](Propstack/Widgets.php), [line 86](Propstack/Widgets.php#L86-L92)


<p align="center"><a href="https://github.com/pronamic/wp-documentor"><img src="https://cdn.jsdelivr.net/gh/pronamic/wp-documentor@main/logos/pronamic-wp-documentor.svgo-min.svg" alt="Pronamic WordPress Documentor" width="32" height="32"></a><br><em>Generated by <a href="https://github.com/pronamic/wp-documentor">Pronamic WordPress Documentor</a> <code>1.2.0</code></em><p>

