# Hooks

- [Actions](#actions)
- [Filters](#filters)

## Actions

### `cfprop_setup_completed`

*Run additional tasks if the setup is marked as completed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 489](Plugin/Setup.php#L489-L494)

### `cfprop_object_field_metabox`

*Run additional tasks during the view of a single field in a metabox in the backend.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post` | `\WP_Post` | The post.
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field.
`$value` | `mixed` | The value.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Field_Category_Base.php](Propstack/Field_Category_Base.php), [line 127](Propstack/Field_Category_Base.php#L127-L135)

### `cfprop_import_object_field`

*Run additional tasks for a single field on an object during the import of them.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field.
`$value` | `mixed` | The value.
`$post_id` | `int` | The post-ID of the object.
`$object_type_object` | `\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type.
`$immo_object` | `array<string,mixed>` | The data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 685](Propstack/Fields.php#L685-L695)

### `cfprop_import_object_fields`

*Run additional tasks for fields on an object during the import of them.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\ConnectorForPropstack\Propstack\Field_Base>` | The list of fields.
`$post_id` | `int` | The post-ID of the object.
`$object_type_object` | `\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type.
`$immo_object` | `array<string,mixed>` | The data from API.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 719](Propstack/Fields.php#L719-L728)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 138](Propstack/Widgets/Object_Data.php#L138-L144)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 113](Propstack/Widgets/Archive.php#L113-L119)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Filter.php](Propstack/Widgets/Filter.php), [line 130](Propstack/Widgets/Filter.php#L130-L136)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Single.php](Propstack/Widgets/Single.php), [line 112](Propstack/Widgets/Single.php#L112-L118)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Description.php](Propstack/Widgets/Description.php), [line 108](Propstack/Widgets/Description.php#L108-L114)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Broker_Field.php](Propstack/Widgets/Broker_Field.php), [line 127](Propstack/Widgets/Broker_Field.php#L127-L133)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widgets/Field.php](Propstack/Widgets/Field.php), [line 163](Propstack/Widgets/Field.php#L163-L169)

### `cfprop_import_object_before_start`

*Run additional tasks before starting the import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$process_handler` | `\ConnectorForPropstack\Plugin\ProcessHandler` | The process handler.
`$instance` | `\ConnectorForPropstack\Propstack\Import_Base` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 104](Propstack/Imports/v2/Objects.php#L104-L111)

### `cfprop_import_object_before_start`

*Run additional tasks before starting the import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$process_handler` | `\ConnectorForPropstack\Plugin\ProcessHandler` | The process handler.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 113](Propstack/Imports/v2/Objects.php#L113-L119)

### `cfprop_import_object`

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

### `cfprop_import_language`

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

### `cfprop_import_object_errors`

*Run additional tasks if any error occurred during import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 339](Propstack/Imports/v2/Objects.php#L339-L346)

### `cfprop_import_object_success`

*Run additional tasks after successfully import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 351](Propstack/Imports/v2/Objects.php#L351-L358)

### `cfprop_import_object_after`

*Run additional tasks after any import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 372](Propstack/Imports/v2/Objects.php#L372-L378)

### `cfprop_import_object_set_max_count`

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

### `cfprop_import_object_set_count`

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

### `cfprop_import_object_set_status`

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

### `cfprop_import_object_before_start`

*Run additional tasks before starting the import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$process_handler` | `\ConnectorForPropstack\Plugin\ProcessHandler` | The process handler.
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 99](Propstack/Imports/v1/Objects.php#L99-L106)

### `cfprop_import_content_not_change`

*Run actions if objects in Propstack did not change.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$md5` | `string` | The md5-hash from the content of the response.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 215](Propstack/Imports/v1/Objects.php#L215-L222)

### `cfprop_import_object`

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 325](Propstack/Imports/v1/Objects.php#L325-L334)

### `cfprop_import_language`

*Run additional tasks for importing objects in a given language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 349](Propstack/Imports/v1/Objects.php#L349-L356)

### `cfprop_import_object_errors`

*Run additional tasks if any error occurred during import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 367](Propstack/Imports/v1/Objects.php#L367-L374)

### `cfprop_import_object_success`

*Run additional tasks after successfully import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 379](Propstack/Imports/v1/Objects.php#L379-L386)

### `cfprop_import_object_after`

*Run additional tasks after any import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 400](Propstack/Imports/v1/Objects.php#L400-L406)

### `cfprop_import_object_set_max_count`

*Run additional tasks after setting the max count.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$count` | `int` | The max count.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 486](Propstack/Imports/v1/Objects.php#L486-L492)

### `cfprop_import_object_set_count`

*Run additional tasks after setting the max count.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$count` | `int` | The max count.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 506](Propstack/Imports/v1/Objects.php#L506-L512)

### `cfprop_import_object_set_status`

*Run additional tasks after setting the new status.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$new_status` | `string` | The new status.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 526](Propstack/Imports/v1/Objects.php#L526-L532)

### `cfprop_file_imported`

*Run additional tasks after a file has been imported.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attachment_id` | `int` | The attachment ID.
`$id` | `int` | The Propstack file ID.
`$file_data` | `array<string,mixed>` | The file data from Propstack.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 481](Propstack/Files.php#L481-L489)

### `cfprop_files_deleted`

*Run additional tasks after all files for objects have been deleted.*


Source: [app/Propstack/Files.php](Propstack/Files.php), [line 603](Propstack/Files.php#L603-L606)

### `cfprop_files_before_import`

*Run additional tasks before the files are imported during object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$files_to_import` | `array<string,mixed>` | The list of files.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 857](Propstack/Files.php#L857-L863)

### `cfprop_import_object_set_status`

*Set the new state for the setup.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$new_state_text` | `string` | The new status.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 944](Propstack/Files.php#L944-L950)

### `cfprop_file_is_assigned`

*Run additional tasks after a file has been assigned to an object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attachment_id` |  | 
`$file` | `array<string,mixed>` | The file data from Propstack API.
`$immo_object_obj` | `\ConnectorForPropstack\Propstack\ImmoObject` | The immo object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 965](Propstack/Files.php#L965-L973)

### `cfprop_files_for_object_imported_via_ajax`

*Run additional tasks after the files has been imported during the object import via AJAX.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post_id` | `int` | The post-ID of the object (optional).

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1024](Propstack/Files.php#L1024-L1030)

### `cfprop_restriction_value_changed`

*Run tasks if one restriction setting has been changed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Availability 1.0.0.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 1654](Propstack/ImmoObjects.php#L1654-L1659)

### `cfprop_queue_before_processing`

*Run additional tasks before the queue is processed.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`count($queue)` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 342](Propstack/Queue.php#L342-L348)

### `cfprop_file_is_assigned`

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

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 404](Propstack/Queue.php#L404-L412)

### `cfprop_queue_processing`

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

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 421](Propstack/Queue.php#L421-L428)

### `cfprop_queue_after_processing`

*Run additional tasks after the queue has been processed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 435](Propstack/Queue.php#L435-L440)

## Filters

### `cfprop_supported_languages`

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

### `cfprop_fallback_language`

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

### `cfprop_current_language`

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

### `cfprop_language_mappings`

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

### `cfprop_language_mappings`

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

### `cfprop_schedule_interval`

*Filter the interval to a single schedule.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$interval` | `string` | The interval.
`$instance` | `\ConnectorForPropstack\Plugin\Schedules_Base` | The schedule-object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 83](Plugin/Schedules_Base.php#L83-L90)

### `cfprop_schedule_enabling`

*Filter whether to activate this schedule.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | True if this object should NOT be enabled.
`$instance` | `\ConnectorForPropstack\Plugin\Schedules_Base` | Actual object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 201](Plugin/Schedules_Base.php#L201-L211)

### `cfprop_templates_archive`

*Filter the list of available templates for archive listings.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$templates` | `array<string,string>` | List of templates (filename => label).

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 81](Plugin/Templates.php#L81-L88)

### `cfprop_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$directory` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 122](Plugin/Templates.php#L122-L122)

### `cfprop_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$directory` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 156](Plugin/Templates.php#L156-L156)

### `cfprop_load_single_template`

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 293](Plugin/Templates.php#L293-L302)

### `cfprop_load_archive_template`

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 344](Plugin/Templates.php#L344-L353)

### `cfprop_add_kses_filter`

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 371](Plugin/Templates.php#L371-L380)

### `cfprop_intervals`

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

### `cfprop_setup_is_completed`

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

### `cfprop_setup`

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

### `cfprop_transient_title`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::get_plugin_name()` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 227](Plugin/Setup.php#L227-L227)

### `cfprop_setup_config`

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

### `cfprop_setup_process_completed_text`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$completed_text` |  | 
`$config_name` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 468](Plugin/Setup.php#L468-L468)

### `cfprop_log_table_filter`

*Filter the list before output.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,string>` | List of filter.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log_Table.php](Plugin/Log_Table.php), [line 266](Plugin/Log_Table.php#L266-L272)

### `cfprop_status_list`

*Filter the list of possible states in the log table.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log_Table.php](Plugin/Log_Table.php), [line 307](Plugin/Log_Table.php#L307-L312)

### `cfprop_objects_with_db_tables`

*Add additional objects for this plugin, which use custom tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$objects` | `array<int,string>` | List of objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Init.php](Plugin/Init.php), [line 118](Plugin/Init.php#L118-L124)

### `cfprop_objects_with_db_tables`

*Add additional objects for this plugin, which use custom tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$objects` | `array<int,string>` | List of objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Init.php](Plugin/Init.php), [line 157](Plugin/Init.php#L157-L163)

### `cfprop_log_categories`

*Filter the list of possible log categories.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,string>` | List of categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 145](Plugin/Log.php#L145-L152)

### `cfprop_log_limit`

*Filter limit to prevent possible errors on big tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` | `int` | The actual limit.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 181](Plugin/Log.php#L181-L187)

### `cfprop_log_category`

*Filter the used category.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$category` | `string` | The category to use.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 192](Plugin/Log.php#L192-L198)

### `cfprop_log_errors`

*Filter for errors.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$errors` | `int` | Should be 1 to filter only for errors.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 203](Plugin/Log.php#L203-L209)

### `cfprop_archive_slug`

*Change the archive slug.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$slug` | `string` | The archive slug.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since first release.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 114](Plugin/Helper.php#L114-L121)

### `cfprop_single_slug`

*Change the single slug.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$slug` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since first release.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 135](Plugin/Helper.php#L135-L142)

### `cfprop_file_version`

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

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 200](Plugin/Helper.php#L200-L208)

### `cfprop_current_url`

*Filter the resulting current URL.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$page_url` | `string` | The resulting current URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 453](Plugin/Helper.php#L453-L459)

### `cfprop_log_export_filename`

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

### `cfprop_hide_pro_hints`

*Hide the additional buttons for reviews or pro-version.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Set true to hide the buttons.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Plugin/Admin/Admin.php](Plugin/Admin/Admin.php), [line 248](Plugin/Admin/Admin.php#L248-L257)

### `cfprop_schedule_our_events`

*Filter the list of our own events, e.g., to check if all which are enabled in setting are active.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$our_events` | `array<string,array<string,mixed>>` | List of our own events in WP-cron.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 133](Plugin/Schedules.php#L133-L140)

### `cfprop_disable_cron_check`

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

### `cfprop_schedules`

*Add custom schedule-objects to use.*

They must be objects based on \ConnectorForPropstack\Plugin\Schedules_Base.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list_of_schedules` | `array<int,string>` | List of additional schedules.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 263](Plugin/Schedules.php#L263-L272)

### `cfprop_block_templates`

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

### `cfprop_block_help_url`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::get_plugin_support_url()` |  | 

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 123](PageBuilder/Gutenberg/Blocks_Basis.php#L123-L123)

### `cfprop_gutenberg_block_{$name}_attributes`

*Filter the attributes for a Block.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$single_attributes` | `array<string,mixed>` | The settings as an array.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 138](PageBuilder/Gutenberg/Blocks_Basis.php#L138-L145)

### `cfprop_gutenberg_block_{$name}_path`

*Filter the path of a Block.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$path` | `string` | The absolute path to the block.json.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 156](PageBuilder/Gutenberg/Blocks_Basis.php#L156-L163)

### `cfprop_pagebuilder`

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

### `cfprop_is_block_theme`

*Filter whether this theme is a block theme (true) or not (false).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$resulting_value` | `bool` | The resulting value.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg.php](PageBuilder/Gutenberg.php), [line 101](PageBuilder/Gutenberg.php#L101-L107)

### `cfprop_gutenberg_blocks`

*Filter the list of available Gutenberg blocks.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of blocks.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/PageBuilder/Gutenberg.php](PageBuilder/Gutenberg.php), [line 126](PageBuilder/Gutenberg.php#L126-L132)

### `cfprop_taxonomy_{$taxonomy_slug}`

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

### `cfprop_rest_taxonomy_fields`

*Filter the available details-templates for REST API.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,array<string,mixed>>` | The fields.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomy` | The taxonomy object.
`$request` | `\WP_REST_Request` | The REST API request object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 283](Propstack/Taxonomy.php#L283-L292)

### `cfprop_taxonomy_terms_query`

*Filter the query for terms on a single taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomy` | The taxonomy object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 429](Propstack/Taxonomy.php#L429-L436)

### `cfprop_taxonomy_terms_query`

*Filter the query to delete terms of one taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query parameter.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 553](Propstack/Taxonomy.php#L553-L559)

### `cfprop_fields`

*Filter the list of available fields.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,string>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 572](Propstack/Fields.php#L572-L578)

### `cfprop_import_object_field_value`

*Filter the value of a single field on an immo object during import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$value` | `mixed` | The value.
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field.
`$post_id` | `int` | The post-ID of the object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 672](Propstack/Fields.php#L672-L680)

### `cfprop_field_type`

*Filter the detected field-type of single field.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field_type` | `\ConnectorForPropstack\Propstack\FieldType_Base\|false` | The field type.
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 789](Propstack/Fields.php#L789-L796)

### `cfprop_rest_fields`

*Filter the available details-templates for REST API.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,mixed>` | The fields.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 903](Propstack/Fields.php#L903-L910)

### `cfprop_object_data_widget_attributes`

*Filter the fields for the object data widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,array<string,mixed>>` | The fields for the single widget.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 121](Propstack/Widgets/Object_Data.php#L121-L126)

### `cfprop_object_data_widget_attributes`

*Filter the attributes for the object data widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the single widget.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 128](Propstack/Widgets/Object_Data.php#L128-L133)

### `cfprop_archive_query_params`

*Filter the archive query params, e.g., to filter the list.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query_params` | `array<string,mixed>` | The additional query parameters for "WP_Query".
`$attributes` | `array<string,mixed>` | The attributes.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 70](Propstack/Widgets/Archive.php#L70-L76)

### `cfprop_widget_archive_attributes`

*Filter the attributes for the archive widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the archive widget.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 103](Propstack/Widgets/Archive.php#L103-L108)

### `cfprop_widget_filter_select_attributes`

*Filter the attributes for the select filter widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the archive widget.

Source: [app/Propstack/Widgets/Filter.php](Propstack/Widgets/Filter.php), [line 112](Propstack/Widgets/Filter.php#L112-L117)

### `cfprop_widget_single_attributes`

*Filter the attributes for the single widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the single widget.

Source: [app/Propstack/Widgets/Single.php](Propstack/Widgets/Single.php), [line 96](Propstack/Widgets/Single.php#L96-L101)

### `cfprop_show_field_in_frontend`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$show_field` |  | 
`$field` |  | 
`$immo_object` |  | 

Source: [app/Propstack/Widgets/Field.php](Propstack/Widgets/Field.php), [line 131](Propstack/Widgets/Field.php#L131-L131)

### `cfprop_filters`

*Filter the list of available immo object filters.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of filters.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filters.php](Propstack/Filters.php), [line 265](Propstack/Filters.php#L265-L271)

### `cfprop_filter_types`

*Filter the list of available immo object filter types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of filter types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filters.php](Propstack/Filters.php), [line 317](Propstack/Filters.php#L317-L323)

### `cfprop_property_type_default_terms`

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

### `cfprop_taxonomy_broker_fields`

*Filter the list of available broker fields.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\ConnectorForPropstack\Propstack\Field_Base>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/Broker.php](Propstack/Taxonomies/Broker.php), [line 134](Propstack/Taxonomies/Broker.php#L134-L140)

### `cfprop_category_default_terms`

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

### `cfprop_marketing_type_default_terms`

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

### `cfprop_object_type_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\ConnectorForPropstack\Propstack\Field_Base>` | List of fields.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/Garage.php](Propstack/Taxonomies/ObjectTypes/Garage.php), [line 411](Propstack/Taxonomies/ObjectTypes/Garage.php#L411-L419)

### `cfprop_object_type_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\ConnectorForPropstack\Propstack\Field_Base>` | List of fields.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/House.php](Propstack/Taxonomies/ObjectTypes/House.php), [line 459](Propstack/Taxonomies/ObjectTypes/House.php#L459-L466)

### `cfprop_object_type_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\ConnectorForPropstack\Propstack\Field_Base>` | List of fields.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/Apartment.php](Propstack/Taxonomies/ObjectTypes/Apartment.php), [line 489](Propstack/Taxonomies/ObjectTypes/Apartment.php#L489-L496)

### `cfprop_object_type_default_disabled_fields`

*Filter the list of files in this object type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,\ConnectorForPropstack\Propstack\Field_Base>` | List of fields.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base` | The object type object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectTypes/Apartment.php](Propstack/Taxonomies/ObjectTypes/Apartment.php), [line 512](Propstack/Taxonomies/ObjectTypes/Apartment.php#L512-L519)

### `cfprop_object_types`

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

### `cfprop_object_typ_default_terms`

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

### `cfprop_taxonomy_terms_query`

*Filter the query for terms on a single taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query.
`$instance` | `\ConnectorForPropstack\Propstack\Taxonomy` | The taxonomy object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies/ObjectType.php](Propstack/Taxonomies/ObjectType.php), [line 233](Propstack/Taxonomies/ObjectType.php#L233-L240)

### `cfprop_filter_hide_field_by_value`

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

Source: [app/Propstack/Filters/Cities.php](Propstack/Filters/Cities.php), [line 162](Propstack/Filters/Cities.php#L162-L172)

### `cfprop_filter_hide_field_by_value`

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

Source: [app/Propstack/Filters/Cities.php](Propstack/Filters/Cities.php), [line 252](Propstack/Filters/Cities.php#L252-L261)

### `cfprop_field_types`

*Filter the list of available field types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field_types` | `array<int,string>` | List of field types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldTypes.php](Propstack/FieldTypes.php), [line 66](Propstack/FieldTypes.php#L66-L72)

### `cfprop_request_header`

*Filter the headers for the request.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$headers` | `array<string,string>` | List of headers.
`$instance` | `\ConnectorForPropstack\Propstack\ApiRequest` | The ApiRequest-object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/ApiRequest.php](Propstack/ApiRequest.php), [line 126](Propstack/ApiRequest.php#L126-L134)

### `cfprop_object_import_response`

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

### `cfprop_prevent_import_of_object`

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

### `cfprop_new_object_query`

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

### `cfprop_api_object_url`

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

### `cfprop_import_object_languages`

*Filter the languages to import object states from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$languages` | `array<string,int>` | The languages to import.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 90](Propstack/Imports/v1/Objects.php#L90-L96)

### `cfprop_object_import_response`

*Filter the response data from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$data` | `array<string,mixed>` | The response data.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 198](Propstack/Imports/v1/Objects.php#L198-L204)

### `cfprop_prevent_import_of_object`

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 246](Propstack/Imports/v1/Objects.php#L246-L256)

### `cfprop_new_object_query`

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 297](Propstack/Imports/v1/Objects.php#L297-L305)

### `cfprop_api_object_url`

*Filter the URL of the API to import objects from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$url` | `string` | The URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 440](Propstack/Imports/v1/Objects.php#L440-L446)

### `cfprop_prevent_file_import`

*Filter whether a given file should not be imported.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Return true to prevent the import.
`$file_data` | `array<string,mixed>` | The file data from Propstack.
`$id` | `int` | The file ID.
`$url` | `string` | The URL to use for import.
`$filename` | `string` | The file name.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 385](Propstack/Files.php#L385-L397)

### `cfprop_file_import_array`

*Filter the query to upload a file in the media library.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$array` | `array<string,mixed>` | The parameter to use.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 437](Propstack/Files.php#L437-L443)

### `cfprop_file_import_post_array`

*Filter the post-query to upload a file in the media library.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$post_array` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 449](Propstack/Files.php#L449-L455)

### `cfprop_files_query`

*Filter the query to get the list of files we imported from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array` | The query array.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 656](Propstack/Files.php#L656-L662)

### `cfprop_files_import_limit`

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 890](Propstack/Files.php#L890-L897)

### `cfprop_register_taxonomies`

*Filter the taxonomies.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$taxonomies` | `array<int,string>` | List of taxonomies.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Taxonomies.php](Propstack/Taxonomies.php), [line 170](Propstack/Taxonomies.php#L170-L177)

### `cfprop_get_immo_obj`

*Filter the requested position object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$immo_object` | `\ConnectorForPropstack\Propstack\ImmoObject` | The object of the object.
`$language_code` | `string` | The requested language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 144](Propstack/ImmoObjects.php#L144-L152)

### `cfprop_hide_pro_hints`

*Hide the additional buttons for reviews or pro-version.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Set true to hide the buttons.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 1676](Propstack/ImmoObjects.php#L1676-L1684)

### `cfprop_queue_table_columns`

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

### `cfprop_queue_table_column_content`

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

### `cfprop_queue_table_filter`

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

### `cfprop_field_types`

*Filter the list of available field formats.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field_formats` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldFormats.php](Propstack/FieldFormats.php), [line 66](Propstack/FieldFormats.php#L66-L72)

### `cfprop_field_categories`

*Filter the list of available field categories.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$categories` | `array<int,string>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldCategories.php](Propstack/FieldCategories.php), [line 84](Propstack/FieldCategories.php#L84-L90)

### `cfprop_field_categories`

*Filter the list of available field category types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$category_types` | `array<int,string>` | List of field category types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldCategories.php](Propstack/FieldCategories.php), [line 171](Propstack/FieldCategories.php#L171-L177)

### `cfprop_register_post_type`

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

### `cfprop_filter_is_hidden`

*Filter whether an object filter should be hidden.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$hidden` | `bool` | True if the filter should be hidden.
`$instance` | `\ConnectorForPropstack\Propstack\Filter_Base` | The filter object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Filter_Base.php](Propstack/Filter_Base.php), [line 121](Propstack/Filter_Base.php#L121-L130)

### `cfprop_knowledge_center_entries`

*Filter the list of available knowledge center entries.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,string>` | List of field categories.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/KnowledgeCenter.php](Propstack/KnowledgeCenter.php), [line 74](Propstack/KnowledgeCenter.php#L74-L80)

### `cfprop_show_help`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$allowed` |  | 
`$screen` |  | 

Source: [app/Propstack/KnowledgeCenter.php](Propstack/KnowledgeCenter.php), [line 150](Propstack/KnowledgeCenter.php#L150-L150)

### `cfprop_help_tabs`

*Filter the list of help tabs with its contents.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,mixed>` | List of help tabs.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/KnowledgeCenter.php](Propstack/KnowledgeCenter.php), [line 179](Propstack/KnowledgeCenter.php#L179-L185)

### `cfprop_help_sidebar_content`

*Filter the sidebar content.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$sidebar_content` | `string` | The content.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/KnowledgeCenter.php](Propstack/KnowledgeCenter.php), [line 199](Propstack/KnowledgeCenter.php#L199-L205)

### `cfprop_object_prevent_meta_box_remove`

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

Source: [app/Propstack/PostTypes/ImmoObject.php](Propstack/PostTypes/ImmoObject.php), [line 436](Propstack/PostTypes/ImmoObject.php#L436-L447)

### `cfprop_object_do_not_hide_meta_box`

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

Source: [app/Propstack/PostTypes/ImmoObject.php](Propstack/PostTypes/ImmoObject.php), [line 462](Propstack/PostTypes/ImmoObject.php#L462-L472)

### `cfprop_queue_fields`

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

### `cfprop_queue_query`

*Filter the query to get the next entries for the processing of the queue.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 296](Propstack/Queue.php#L296-L301)

### `cfprop_widgets`

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

