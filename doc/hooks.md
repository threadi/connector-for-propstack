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

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 562](Plugin/Setup.php#L562-L567)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 147](Propstack/Imports/v1/Objects.php#L147-L154)

### `cfprop_import_object`

*Run additional tasks for a single language-specific object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$object` | `array<string,mixed>` | The object data from API.
`$post_id` | `int` | The post-ID of the object.
`$language_code` | `string` | The used language.
`$is_new_object` | `bool` | Marker for new objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 650](Propstack/Imports/v1/Objects.php#L650-L660)

### `cfprop_import_language`

*Run additional tasks for importing objects in a given language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`(string) $language_code` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 851](Propstack/Imports/v1/Objects.php#L851-L858)

### `cfprop_import_object_errors`

*Run additional tasks if any error occurred during import of objects.*

…

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 871](Propstack/Imports/v1/Objects.php#L871-L875)

### `cfprop_import_object_empty`

*Run additional tasks if the import did not deliver any object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.1.0` | Available since 1.1.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 880](Propstack/Imports/v1/Objects.php#L880-L887)

### `cfprop_import_object_success`

*Run additional tasks after successful import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v1\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 892](Propstack/Imports/v1/Objects.php#L892-L899)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 928](Propstack/Imports/v1/Objects.php#L928-L935)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 1033](Propstack/Imports/v1/Objects.php#L1033-L1039)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 1053](Propstack/Imports/v1/Objects.php#L1053-L1059)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 1073](Propstack/Imports/v1/Objects.php#L1073-L1079)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 147](Propstack/Imports/v2/Objects.php#L147-L154)

### `cfprop_import_object`

*Run additional tasks for a single language-specific object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$object` | `array<string,mixed>` | The object data from API.
`$post_id` | `int` | The post-ID of the object.
`$language_code` | `string` | The used language.
`$is_new_object` | `bool` | Marker for new objects.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 644](Propstack/Imports/v2/Objects.php#L644-L654)

### `cfprop_import_language`

*Run additional tasks for importing objects in a given language.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`(string) $language_code` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 836](Propstack/Imports/v2/Objects.php#L836-L843)

### `cfprop_import_object_errors`

*Run additional tasks if any error occurred during import of objects.*

…

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 856](Propstack/Imports/v2/Objects.php#L856-L860)

### `cfprop_import_object_empty`

*Run additional tasks if the import did not deliver any object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.1.0` | Available since 1.1.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 865](Propstack/Imports/v2/Objects.php#L865-L872)

### `cfprop_import_object_success`

*Run additional tasks after successful import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Imports\v2\Objects` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 877](Propstack/Imports/v2/Objects.php#L877-L884)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 913](Propstack/Imports/v2/Objects.php#L913-L920)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 1055](Propstack/Imports/v2/Objects.php#L1055-L1061)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 1075](Propstack/Imports/v2/Objects.php#L1075-L1081)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 1095](Propstack/Imports/v2/Objects.php#L1095-L1101)

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

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1095](Propstack/Fields.php#L1095-L1105)

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

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1108](Propstack/Fields.php#L1108-L1117)

### `cfprop_import_object_after`

*Run additional tasks after any import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$instance` | `\ConnectorForPropstack\Propstack\Import_Base` | The import object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Import_Base.php](Propstack/Import_Base.php), [line 1542](Propstack/Import_Base.php#L1542-L1549)

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

Source: [app/Propstack/Widgets/Field.php](Propstack/Widgets/Field.php), [line 137](Propstack/Widgets/Field.php#L137-L143)

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

Source: [app/Propstack/Widgets/Description.php](Propstack/Widgets/Description.php), [line 106](Propstack/Widgets/Description.php#L106-L112)

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

Source: [app/Propstack/Widgets/Filter.php](Propstack/Widgets/Filter.php), [line 133](Propstack/Widgets/Filter.php#L133-L139)

### `cfprop_get_template_before`

*Run custom actions before the output of the archive listing.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array` | List of attributes.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/Widgets/Energy_Scale.php](Propstack/Widgets/Energy_Scale.php), [line 107](Propstack/Widgets/Energy_Scale.php#L107-L113)

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

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 141](Propstack/Widgets/Object_Data.php#L141-L147)

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

Source: [app/Propstack/Widgets/Single.php](Propstack/Widgets/Single.php), [line 124](Propstack/Widgets/Single.php#L124-L130)

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

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 145](Propstack/Widgets/Archive.php#L145-L151)

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

Source: [app/Propstack/Widgets/Broker_Field.php](Propstack/Widgets/Broker_Field.php), [line 125](Propstack/Widgets/Broker_Field.php#L125-L131)

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

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 471](Propstack/Queue.php#L471-L477)

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

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 575](Propstack/Queue.php#L575-L583)

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

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 598](Propstack/Queue.php#L598-L605)

### `cfprop_queue_after_processing`

*Run additional tasks after the queue has been processed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 612](Propstack/Queue.php#L612-L617)

### `cfprop_restriction_value_changed`

*Run tasks if one restriction setting has been changed.*


**Changelog**

Version | Description
------- | -----------
`1.0.0` | Availability 1.0.0.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 2285](Propstack/ImmoObjects.php#L2285-L2290)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 606](Propstack/Files.php#L606-L614)

### `cfprop_files_deleted`

*Run additional tasks after all files for objects have been deleted.*


Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1021](Propstack/Files.php#L1021-L1024)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1518](Propstack/Files.php#L1518-L1524)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1591](Propstack/Files.php#L1591-L1597)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1608](Propstack/Files.php#L1608-L1616)

### `cfprop_files_before_import`

*Run additional tasks before the files are imported during object import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$files_to_import` | `array<int,array<string,mixed>>` | The list of files.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1678](Propstack/Files.php#L1678-L1684)

## Filters

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

Source: [app/PageBuilder/Gutenberg.php](PageBuilder/Gutenberg.php), [line 122](PageBuilder/Gutenberg.php#L122-L128)

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

Source: [app/PageBuilder/Gutenberg.php](PageBuilder/Gutenberg.php), [line 148](PageBuilder/Gutenberg.php#L148-L154)

### `cfprop_block_help_url`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::get_plugin_support_url()` |  | 

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 124](PageBuilder/Gutenberg/Blocks_Basis.php#L124-L124)

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

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 147](PageBuilder/Gutenberg/Blocks_Basis.php#L147-L154)

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

Source: [app/PageBuilder/Gutenberg/Blocks_Basis.php](PageBuilder/Gutenberg/Blocks_Basis.php), [line 165](PageBuilder/Gutenberg/Blocks_Basis.php#L165-L172)

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

Source: [app/PageBuilder/Gutenberg/Templates.php](PageBuilder/Gutenberg/Templates.php), [line 222](PageBuilder/Gutenberg/Templates.php#L222-L229)

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

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 263](Plugin/Languages.php#L263-L270)

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

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 283](Plugin/Languages.php#L283-L290)

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

Source: [app/Plugin/Languages.php](Plugin/Languages.php), [line 311](Plugin/Languages.php#L311-L318)

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 90](Plugin/Templates.php#L90-L97)

### `cfprop_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$directory` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 134](Plugin/Templates.php#L134-L134)

### `cfprop_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$directory` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 184](Plugin/Templates.php#L184-L184)

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 400](Plugin/Templates.php#L400-L409)

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 451](Plugin/Templates.php#L451-L460)

### `cfprop_extend_post_kses_globally`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$extend_post_kses` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 508](Plugin/Templates.php#L508-L508)

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

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 513](Plugin/Templates.php#L513-L522)

### `cfprop_allowed_filter_html`

*Filter the allowed HTML for the output of the filter form.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$allowed_tags` | `array<string,mixed>` | List of allowed tags and their attributes.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 622](Plugin/Templates.php#L622-L628)

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

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 156](Plugin/Setup.php#L156-L162)

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

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 235](Plugin/Setup.php#L235-L242)

### `cfprop_transient_title`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::get_plugin_name()` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 252](Plugin/Setup.php#L252-L252)

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

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 308](Plugin/Setup.php#L308-L314)

### `cfprop_setup_process_completed_text`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$completed_text` |  | 
`$config_name` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 541](Plugin/Setup.php#L541-L541)

### `cfprop_setup_import_limit`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` |  | 

Source: [app/Plugin/Setup.php](Plugin/Setup.php), [line 695](Plugin/Setup.php#L695-L695)

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

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 137](Plugin/Helper.php#L137-L144)

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

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 158](Plugin/Helper.php#L158-L165)

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

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 234](Plugin/Helper.php#L234-L242)

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

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 489](Plugin/Helper.php#L489-L495)

### `cfprop_process_lock_timeout`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$hour` |  | 
`$option_name` |  | 

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 687](Plugin/Helper.php#L687-L687)

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

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 132](Plugin/Schedules.php#L132-L139)

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

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 157](Plugin/Schedules.php#L157-L165)

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

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 280](Plugin/Schedules.php#L280-L289)

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

Source: [app/Plugin/Init.php](Plugin/Init.php), [line 127](Plugin/Init.php#L127-L133)

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

Source: [app/Plugin/Init.php](Plugin/Init.php), [line 173](Plugin/Init.php#L173-L179)

### `cfprop_log_without_debug`

*Filter whether a log entry should be written when debug mode is disabled.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$should_log` | `bool` | Whether the entry should be logged.
`$category` | `string` | The log entry category.
`$state` | `string` | The log entry state.
`$log` | `string` | The log message.
`$md5` | `string` | The unique marker.

**Changelog**

Version | Description
------- | -----------
`1.1.0` | Available since 1.1.0

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 111](Plugin/Log.php#L111-L122)

### `cfprop_log_with_debug`

*Filter whether a log entry should be written when debug mode is enabled.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$should_log` | `bool` | Whether the entry should be logged.
`$category` | `string` | The log entry category.
`$state` | `string` | The log entry state.
`$log` | `string` | The log message.
`$md5` | `string` | The unique marker.

**Changelog**

Version | Description
------- | -----------
`1.1.0` | Available since 1.1.0.

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 141](Plugin/Log.php#L141-L152)

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

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 243](Plugin/Log.php#L243-L250)

### `cfprop_log_limit`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` |  | 

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 289](Plugin/Log.php#L289-L289)

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

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 350](Plugin/Log.php#L350-L356)

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

Source: [app/Plugin/Log.php](Plugin/Log.php), [line 361](Plugin/Log.php#L361-L367)

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

### `cfprop_hide_intro`

*Hide intro via hook.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Return true to hide the intro.

**Changelog**

Version | Description
------- | -----------
`3.0.0` | Available since 3.0.0

Source: [app/Plugin/Intro.php](Plugin/Intro.php), [line 76](Plugin/Intro.php#L76-L84)

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

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 94](Plugin/Schedules_Base.php#L94-L101)

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

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 232](Plugin/Schedules_Base.php#L232-L242)

### `cfprop_run_compatibility_checks`

*Filter whether the compatibility-checks should be run (false) or not (true)*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | True to prevent compatibility-checks.

**Changelog**

Version | Description
------- | -----------
`1.0.5` | Available since 1.0.5.

Source: [app/Plugin/Compatibilities.php](Plugin/Compatibilities.php), [line 89](Plugin/Compatibilities.php#L89-L98)

### `cfprop_compatibility_checks_interval`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$interval` |  | 

Source: [app/Plugin/Compatibilities.php](Plugin/Compatibilities.php), [line 120](Plugin/Compatibilities.php#L120-L120)

### `cfprop_compatibility_checks`

*Filter the list of compatibilities.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `string[]` | List of compatibility-checks.

**Changelog**

Version | Description
------- | -----------
`1.0.5` | Available since 1.0.5.

Source: [app/Plugin/Compatibilities.php](Plugin/Compatibilities.php), [line 187](Plugin/Compatibilities.php#L187-L194)

### `cfprop_users_for_settings_query`

*Filter the query to get the users for the settings (e.g., the author of imported objects).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query` | `array<string,mixed>` | The query for get_users().

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Plugin/Users.php](Plugin/Users.php), [line 161](Plugin/Users.php#L161-L167)

### `cfprop_process_values_max_age`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_age` |  | 

Source: [app/Plugin/ProcessHandler.php](Plugin/ProcessHandler.php), [line 218](Plugin/ProcessHandler.php#L218-L218)

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

Source: [app/Plugin/Log_Table.php](Plugin/Log_Table.php), [line 308](Plugin/Log_Table.php#L308-L314)

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

Source: [app/Plugin/Log_Table.php](Plugin/Log_Table.php), [line 349](Plugin/Log_Table.php#L349-L354)

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

Source: [app/Plugin/Admin/Admin.php](Plugin/Admin/Admin.php), [line 184](Plugin/Admin/Admin.php#L184-L191)

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

Source: [app/Plugin/Admin/Admin.php](Plugin/Admin/Admin.php), [line 310](Plugin/Admin/Admin.php#L310-L319)

### `cfprop_ability_import_time_budget`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time_budget` |  | 

Source: [app/Propstack/Abilities.php](Propstack/Abilities.php), [line 885](Propstack/Abilities.php#L885-L885)

### `cfprop_ability_import_max_chunks`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_chunks` |  | 

Source: [app/Propstack/Abilities.php](Propstack/Abilities.php), [line 894](Propstack/Abilities.php#L894-L894)

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

Source: [app/Propstack/Widgets.php](Propstack/Widgets.php), [line 87](Propstack/Widgets.php#L87-L93)

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

Source: [app/Propstack/Taxonomies.php](Propstack/Taxonomies.php), [line 221](Propstack/Taxonomies.php#L221-L228)

### `cfprop_schedule_import_time_budget`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time_budget` |  | 

Source: [app/Propstack/Schedules/Objects.php](Propstack/Schedules/Objects.php), [line 158](Propstack/Schedules/Objects.php#L158-L158)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 128](Propstack/Imports/v1/Objects.php#L128-L134)

### `cfprop_object_import_limit`

*Filter the limit of objects to import per request.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` | `int` | The limit.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 189](Propstack/Imports/v1/Objects.php#L189-L195)

### `cfprop_object_import_block_size`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$block_size` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 224](Propstack/Imports/v1/Objects.php#L224-L224)

### `cfprop_object_import_response`

*Filter the response data from Propstack.*

Hint: this filter now receives a single page instead of all objects.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$page_objects` | `array<int,mixed>` | The objects of this page.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 243](Propstack/Imports/v1/Objects.php#L243-L251)

### `cfprop_prevent_import_of_object`

*Process a single chunk of the import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`false` |  | 
`$object` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 74](Propstack/Imports/v1/Objects.php#L74-L260)

### `cfprop_object_import_time_budget`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time_budget` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 398](Propstack/Imports/v1/Objects.php#L398-L398)

### `cfprop_force_delete_obsolete_object`

*Filter whether an obsolete object is deleted permanently.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`true` |  | 
`$post_id` | `int` | The post-ID of the object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 425](Propstack/Imports/v1/Objects.php#L425-L432)

### `cfprop_object_import_time_budget`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time_budget` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 502](Propstack/Imports/v1/Objects.php#L502-L502)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 565](Propstack/Imports/v1/Objects.php#L565-L575)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 618](Propstack/Imports/v1/Objects.php#L618-L626)

### `cfprop_import_max_cleanup_ratio`

*Filter the max. ratio of existing objects which could be removed by the cleanup after an import.*

If more objects would be removed, the cleanup is skipped as a safeguard against incomplete
data from the API. Use 1 to allow the removal of all objects.
The default value comes from the setting "Max. percentage of objects to delete".

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_ratio` | `float` | The ratio between 0 and 1.
`$obsolete_count` | `int` | The amount of objects which would be removed.
`$existing_count` | `int` | The amount of existing objects.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 804](Propstack/Imports/v1/Objects.php#L804-L815)

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

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 973](Propstack/Imports/v1/Objects.php#L973-L979)

### `cfprop_import_per_page`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_per_value` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 1100](Propstack/Imports/v1/Objects.php#L1100-L1100)

### `cfprop_import_max_pages`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_pages` |  | 

Source: [app/Propstack/Imports/v1/Objects.php](Propstack/Imports/v1/Objects.php), [line 1109](Propstack/Imports/v1/Objects.php#L1109-L1109)

### `cfprop_object_import_limit`

*Filter the limit of objects to import per request.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` | `int` | The limit.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 195](Propstack/Imports/v2/Objects.php#L195-L201)

### `cfprop_object_import_block_size`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$block_size` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 230](Propstack/Imports/v2/Objects.php#L230-L230)

### `cfprop_object_import_response`

*Filter the response data from Propstack.*

Hint: this filter now receives a single page instead of all objects.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$page_objects` | `array<int,mixed>` | The objects of this page.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 247](Propstack/Imports/v2/Objects.php#L247-L255)

### `cfprop_prevent_import_of_object`

*Process a single chunk of the import of objects.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`false` |  | 
`$object` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 89](Propstack/Imports/v2/Objects.php#L89-L267)

### `cfprop_object_import_time_budget`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time_budget` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 395](Propstack/Imports/v2/Objects.php#L395-L395)

### `cfprop_force_delete_obsolete_object`

*Filter whether an obsolete object is deleted permanently.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`true` |  | 
`$post_id` | `int` | The post-ID of the object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 422](Propstack/Imports/v2/Objects.php#L422-L429)

### `cfprop_object_import_time_budget`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time_budget` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 498](Propstack/Imports/v2/Objects.php#L498-L498)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 561](Propstack/Imports/v2/Objects.php#L561-L571)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 614](Propstack/Imports/v2/Objects.php#L614-L622)

### `cfprop_import_max_cleanup_ratio`

*Filter the max. ratio of existing objects which could be removed by the cleanup after an import.*

If more objects would be removed, the cleanup is skipped as a safeguard against incomplete
data from the API. Use 1 to allow the removal of all objects.
The default value comes from the setting "Max. percentage of objects to delete".

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_ratio` | `float` | The ratio between 0 and 1.
`$obsolete_count` | `int` | The amount of objects which would be removed.
`$existing_count` | `int` | The amount of existing objects.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 789](Propstack/Imports/v2/Objects.php#L789-L800)

### `cfprop_api_v2_prepared_object`

*Filter a single object from Propstack API v2 after it has been prepared for the import.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$immo_object` | `array<string,mixed>` | The object data.
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 962](Propstack/Imports/v2/Objects.php#L962-L969)

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

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 995](Propstack/Imports/v2/Objects.php#L995-L1001)

### `cfprop_import_per_page`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_per_value` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 1122](Propstack/Imports/v2/Objects.php#L1122-L1122)

### `cfprop_import_max_pages`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_pages` |  | 

Source: [app/Propstack/Imports/v2/Objects.php](Propstack/Imports/v2/Objects.php), [line 1131](Propstack/Imports/v2/Objects.php#L1131-L1131)

### `cfprop_api_v2_options`

*Filter the options of object fields from Propstack API v2.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$options` | `array<string,mixed>` | The options: the name of the field in the API => list of codes with their texts.
`$language_code` | `string` | The used language.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Imports/v2/Options.php](Propstack/Imports/v2/Options.php), [line 103](Propstack/Imports/v2/Options.php#L103-L110)

### `cfprop_api_v2_untranslated_fields`

*Filter the names of the fields in the API v2 whose codes must not be replaced by their texts.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,string>` | The names of the fields in the API.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Imports/v2/Options.php](Propstack/Imports/v2/Options.php), [line 171](Propstack/Imports/v2/Options.php#L171-L177)

### `cfprop_api_object_options_url`

*Filter the URL of the API to get the options of object fields from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$url` | `string` | The URL.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Imports/v2/Options.php](Propstack/Imports/v2/Options.php), [line 352](Propstack/Imports/v2/Options.php#L352-L358)

### `cfprop_api_v2_brokers`

*Filter the brokers from Propstack API v2.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$brokers` | `array<int,array<string,mixed>>` | The brokers, indexed by their ID.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Imports/v2/Brokers.php](Propstack/Imports/v2/Brokers.php), [line 188](Propstack/Imports/v2/Brokers.php#L188-L194)

### `cfprop_api_v2_broker_url`

*Filter the URL of the API to get the brokers from Propstack via API v2.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$url` | `string` | The URL.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Imports/v2/Brokers.php](Propstack/Imports/v2/Brokers.php), [line 256](Propstack/Imports/v2/Brokers.php#L256-L262)

### `cfprop_import_object_states_languages`

*Filter the languages to import object states from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$languages` | `array<string,int>` | The languages to import.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/States.php](Propstack/Imports/v2/States.php), [line 93](Propstack/Imports/v2/States.php#L93-L99)

### `cfprop_api_object_states_url`

*Filter the URL of the API to import object states from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$url` | `string` | The URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Imports/v2/States.php](Propstack/Imports/v2/States.php), [line 278](Propstack/Imports/v2/States.php#L278-L284)

### `cfprop_template_names`

*Filter the list of allowed template names for the archive and single widgets.*

Each name is used to run the action "cfprop_template_<name>".

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of template names.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/Templates.php](Propstack/Templates.php), [line 188](Propstack/Templates.php#L188-L196)

### `cfprop_show_empty_placeholders`

*Filter whether placeholders for empty values are shown in widgets.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`Helper::is_rest_request()` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Widget_Base.php](Propstack/Widget_Base.php), [line 201](Propstack/Widget_Base.php#L201-L207)

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

Source: [app/Propstack/Tables/Queue.php](Propstack/Tables/Queue.php), [line 134](Propstack/Tables/Queue.php#L134-L143)

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

Source: [app/Propstack/Tables/Queue.php](Propstack/Tables/Queue.php), [line 256](Propstack/Tables/Queue.php#L256-L262)

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

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 661](Propstack/Fields.php#L661-L667)

### `cfprop_api_v2_object_fields`

*Filter the names of the fields the API v2 delivers for an object.*

Fields of objects, which are not in this list, are hidden if the API v2 is used.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,string>` | The names of the fields in the API.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 864](Propstack/Fields.php#L864-L872)

### `cfprop_field_available_in_api`

*Filter whether the used API version delivers a field of an object.*

This filter is only used if the API v2 is used. Fields which are not delivered are hidden.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$available` | `bool` | True if the field is delivered.
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field object.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 942](Propstack/Fields.php#L942-L951)

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

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1027](Propstack/Fields.php#L1027-L1035)

### `cfprop_reserved_meta_keys`

*Filter the list of meta keys which could not be used by custom fields from Propstack.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<string,bool>` | List of meta keys as keys.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1146](Propstack/Fields.php#L1146-L1152)

### `cfprop_field_type`

*Filter the detected field-type of a single field.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field_type` | `\ConnectorForPropstack\Propstack\FieldType_Base\|false` | The field type.
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1256](Propstack/Fields.php#L1256-L1263)

### `cfprop_show_not_set_hint`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$true` |  | 

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1304](Propstack/Fields.php#L1304-L1304)

### `cfprop_show_not_set_hint`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$true` |  | 

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1319](Propstack/Fields.php#L1319-L1319)

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

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1385](Propstack/Fields.php#L1385-L1392)

### `cfprop_respect_hide_address`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$respect` |  | 
`$immo_object` |  | 

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1622](Propstack/Fields.php#L1622-L1622)

### `cfprop_exact_address_fields`

*Filter the list of fields which are hidden if the address of an object should be hidden.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list` | `array<int,string>` | List of field names.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1641](Propstack/Fields.php#L1641-L1647)

### `cfprop_show_field_in_frontend`

*Filter whether a field is shown in the frontend.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$show_field` | `bool` | The result.
`$field` | `\ConnectorForPropstack\Propstack\Field_Base` | The field.
`$immo_object` | `\ConnectorForPropstack\Propstack\ImmoObject` | The object.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1699](Propstack/Fields.php#L1699-L1710)

### `cfprop_field_values_batch_size`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$batch_size` |  | 

Source: [app/Propstack/Fields.php](Propstack/Fields.php), [line 1809](Propstack/Fields.php#L1809-L1809)

### `cfprop_field_types`

*Filter the list of available field formats.*

Hint: this hook has been used for field formats and field types in the past. It is still
used here for backward compatibility. Entries which are not field formats are ignored.
Use "cfprop_field_formats" instead.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field_formats` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldFormats.php](Propstack/FieldFormats.php), [line 67](Propstack/FieldFormats.php#L67-L77)

### `cfprop_field_formats`

*Filter the list of available field formats.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field_formats` | `array<int,string>` | List of field formats.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/FieldFormats.php](Propstack/FieldFormats.php), [line 79](Propstack/FieldFormats.php#L79-L85)

### `cfprop_template_ability_adapters`

*Filter the list of adapters, which make templates of page builders accessible for abilities.*

Each entry must be an object based on \ConnectorForPropstack\Propstack\Template_Adapter_Base.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$adapters` | `array<int,\ConnectorForPropstack\Propstack\Template_Adapter_Base>` | List of adapters.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/Template_Abilities.php](Propstack/Template_Abilities.php), [line 80](Propstack/Template_Abilities.php#L80-L88)

### `cfprop_media_object_info`

*Filter the information about the object of an image in the media library.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$html` | `string` | The HTML output.
`$immo_object` | `\ConnectorForPropstack\Propstack\ImmoObject` | The object.
`$attachment` | `\WP_Post` | The attachment.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/MediaLibrary.php](Propstack/MediaLibrary.php), [line 434](Propstack/MediaLibrary.php#L434-L442)

### `cfprop_import_object_languages`

*This filter is documented in app/Propstack/Imports/v1/Objects.php*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$languages` |  | 

Source: [app/Propstack/Import_Base.php](Propstack/Import_Base.php), [line 164](Propstack/Import_Base.php#L164-L165)

### `cfprop_import_prevented_hint`

*Filter the hint where the reasons for objects which have not been imported can be found.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$hint` | `string` | The hint, HTML is allowed.
`$prevented` | `int` | The amount of objects which have not been imported.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/Import_Base.php](Propstack/Import_Base.php), [line 682](Propstack/Import_Base.php#L682-L690)

### `cfprop_import_chunk_lock_ttl`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$ttl` |  | 

Source: [app/Propstack/Import_Base.php](Propstack/Import_Base.php), [line 883](Propstack/Import_Base.php#L883-L883)

### `cfprop_widget_filter_select_attributes`

*Filter the attributes for the select filter widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the archive widget.

Source: [app/Propstack/Widgets/Filter.php](Propstack/Widgets/Filter.php), [line 115](Propstack/Widgets/Filter.php#L115-L120)

### `cfprop_object_data_widget_fields`

*Filter the fields for the object data widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$fields` | `array<int,array<string,mixed>>` | The fields for the object data widget.
`$attributes` | `array<string,mixed>` | The attributes for the object data widget.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 120](Propstack/Widgets/Object_Data.php#L120-L127)

### `cfprop_object_data_widget_attributes`

*Filter the attributes for the object data widget.*

Hint: until 2.0.x this filter was also used for the list of fields, use "cfprop_object_data_widget_fields" for them.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the object data widget.

Source: [app/Propstack/Widgets/Object_Data.php](Propstack/Widgets/Object_Data.php), [line 129](Propstack/Widgets/Object_Data.php#L129-L136)

### `cfprop_widget_single_attributes`

*Filter the attributes for the single widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the single widget.

Source: [app/Propstack/Widgets/Single.php](Propstack/Widgets/Single.php), [line 99](Propstack/Widgets/Single.php#L99-L104)

### `cfprop_archive_posts_per_page`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$posts_per_page` |  | 

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 80](Propstack/Widgets/Archive.php#L80-L80)

### `cfprop_archive_query_params`

*Filter the archive query params, e.g., to filter the list.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$query_params` | `array<string,mixed>` | The additional query parameters for "WP_Query".
`$attributes` | `array<string,mixed>` | The attributes.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 99](Propstack/Widgets/Archive.php#L99-L105)

### `cfprop_widget_archive_attributes`

*Filter the attributes for the archive widget.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attributes` | `array<string,mixed>` | The attributes for the archive widget.

Source: [app/Propstack/Widgets/Archive.php](Propstack/Widgets/Archive.php), [line 132](Propstack/Widgets/Archive.php#L132-L137)

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

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 114](Propstack/Taxonomy.php#L114-L121)

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

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 286](Propstack/Taxonomy.php#L286-L295)

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

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 450](Propstack/Taxonomy.php#L450-L457)

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

Source: [app/Propstack/Taxonomy.php](Propstack/Taxonomy.php), [line 542](Propstack/Taxonomy.php#L542-L548)

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

Source: [app/Propstack/Filters/Cities.php](Propstack/Filters/Cities.php), [line 171](Propstack/Filters/Cities.php#L171-L181)

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

Source: [app/Propstack/Filters/Cities.php](Propstack/Filters/Cities.php), [line 248](Propstack/Filters/Cities.php#L248-L257)

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

### `cfprop_log_max_response_length`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`100000` |  | 

Source: [app/Propstack/ApiRequest.php](Propstack/ApiRequest.php), [line 198](Propstack/ApiRequest.php#L198-L198)

### `cfprop_log_mask_personal_data`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$mask` |  | 

Source: [app/Propstack/ApiRequest.php](Propstack/ApiRequest.php), [line 437](Propstack/ApiRequest.php#L437-L437)

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

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 363](Propstack/Queue.php#L363-L368)

### `cfprop_queue_failed_entries_lifetime`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$days` |  | 

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 720](Propstack/Queue.php#L720-L720)

### `cfprop_queue_max_attempts`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_attempts` |  | 

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 1006](Propstack/Queue.php#L1006-L1006)

### `cfprop_prevent_file_import`

*Filter whether a given file should not be imported.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | Return true to prevent the import.
`$file` |  | 
`absint($file['id'] ?? 0)` |  | 
`$url` | `string` | The URL to use for import.
`!empty($file['name']) && is_scalar($file['name']) ? (string) $file['name'] : Files::get_instance()->get_file_name_from_url($url)` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Queue.php](Propstack/Queue.php), [line 1050](Propstack/Queue.php#L1050-L1062)

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

Hint: this hook has been used for field categories and field category types in the past.
It is still used here for backward compatibility. Entries which are not field category types
are ignored. Use "cfprop_field_category_types" instead.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$category_types` | `array<int,string>` | List of field category types.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/FieldCategories.php](Propstack/FieldCategories.php), [line 172](Propstack/FieldCategories.php#L172-L182)

### `cfprop_field_category_types`

*Filter the list of available field category types.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$category_types` | `array<int,string>` | List of field category types.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/FieldCategories.php](Propstack/FieldCategories.php), [line 184](Propstack/FieldCategories.php#L184-L190)

### `cfprop_get_immo_obj`

*Filter the requested immo object.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$immo_object` | `\ConnectorForPropstack\Propstack\ImmoObject` | The object of the object.
`$language_code` | `string` | The requested language.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 163](Propstack/ImmoObjects.php#L163-L171)

### `cfprop_object_search_meta_keys`

*Filter the meta keys of objects which are used for the search in the backend.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$meta_keys` | `array<int,string>` | List of meta keys.

**Changelog**

Version | Description
------- | -----------
`2.1.0` | Available since 2.1.0.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 1255](Propstack/ImmoObjects.php#L1255-L1261)

### `cfprop_prevent_import_of_object_reasons`

*Filter the reasons why the import of an object is prevented.*

Use this to add the reason for an own check on the filter "cfprop_prevent_import_of_object".

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$reasons` | `array<int,string>` | The reasons, each as a complete sentence.
`$immo_object` | `array<string,mixed>` | The object data from API.

**Changelog**

Version | Description
------- | -----------
`2.0.1` | Available since 2.0.1.

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 1534](Propstack/ImmoObjects.php#L1534-L1544)

### `cfprop_prevent_import_of_object`

*This filter is documented in app/Propstack/Imports/v1/Objects.php*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`false` |  | 
`$result['object']` |  | 

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 1949](Propstack/ImmoObjects.php#L1949-L1950)

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

Source: [app/Propstack/ImmoObjects.php](Propstack/ImmoObjects.php), [line 2307](Propstack/ImmoObjects.php#L2307-L2315)

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

Source: [app/Propstack/Filters.php](Propstack/Filters.php), [line 426](Propstack/Filters.php#L426-L432)

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

Source: [app/Propstack/Filters.php](Propstack/Filters.php), [line 478](Propstack/Filters.php#L478-L484)

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

Source: [app/Propstack/FieldTypes.php](Propstack/FieldTypes.php), [line 68](Propstack/FieldTypes.php#L68-L74)

### `cfprop_energy_efficiency_clases`

*Filter the allowed energy efficiency classes.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$allowed_classes` | `array<int,string>` | List of allowed classes.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/Energy_Efficiency_Classes.php](Propstack/Energy_Efficiency_Classes.php), [line 66](Propstack/Energy_Efficiency_Classes.php#L66-L72)

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

Source: [app/Propstack/Taxonomies/ObjectType.php](Propstack/Taxonomies/ObjectType.php), [line 255](Propstack/Taxonomies/ObjectType.php#L255-L261)

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

Source: [app/Propstack/Taxonomies/ObjectType.php](Propstack/Taxonomies/ObjectType.php), [line 277](Propstack/Taxonomies/ObjectType.php#L277-L284)

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

Source: [app/Propstack/Taxonomies/ObjectTypes/Garage.php](Propstack/Taxonomies/ObjectTypes/Garage.php), [line 433](Propstack/Taxonomies/ObjectTypes/Garage.php#L433-L440)

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

Source: [app/Propstack/Taxonomies/ObjectTypes/House.php](Propstack/Taxonomies/ObjectTypes/House.php), [line 480](Propstack/Taxonomies/ObjectTypes/House.php#L480-L487)

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

Source: [app/Propstack/PostTypes/Queue.php](Propstack/PostTypes/Queue.php), [line 267](Propstack/PostTypes/Queue.php#L267-L274)

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

Source: [app/Propstack/PostTypes/ImmoObject.php](Propstack/PostTypes/ImmoObject.php), [line 504](Propstack/PostTypes/ImmoObject.php#L504-L515)

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

Source: [app/Propstack/PostTypes/ImmoObject.php](Propstack/PostTypes/ImmoObject.php), [line 530](Propstack/PostTypes/ImmoObject.php#L530-L540)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 495](Propstack/Files.php#L495-L507)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 537](Propstack/Files.php#L537-L543)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 549](Propstack/Files.php#L549-L555)

### `cfprop_files_import_lock_ttl`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$ttl` |  | 

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 719](Propstack/Files.php#L719-L719)

### `cfprop_file_download_timeout`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$timeout` |  | 
`$url` |  | 

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 755](Propstack/Files.php#L755-L755)

### `cfprop_max_file_size`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$max_size` |  | 
`$url` |  | 

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 765](Propstack/Files.php#L765-L765)

### `cfprop_allowed_file_hosts`

*Filter the list of hosts from which files may be downloaded.*

An empty list allows any public host. Subdomains of listed hosts are allowed, too.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$allowed_hosts` | `array<int,string>` | The list of allowed hosts.
`$url` | `string` | The URL to check.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 854](Propstack/Files.php#L854-L863)

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

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1090](Propstack/Files.php#L1090-L1096)

### `cfprop_files_import_limit`

*Filter the limit of files to import during object import.*

Hint: since 2.1.0 the list contains the files of the actual block of the list only.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$limit` | `int` | The limit.
`is_array($block) ? $block : array()` |  | 

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1399](Propstack/Files.php#L1399-L1408)

### `cfprop_files_import_block_size`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$block_size` |  | 

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1696](Propstack/Files.php#L1696-L1696)

### `cfprop_files_import_takeover_time`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$time` |  | 

Source: [app/Propstack/Files.php](Propstack/Files.php), [line 1803](Propstack/Files.php#L1803-L1803)


<p align="center"><a href="https://github.com/pronamic/wp-documentor"><img src="https://cdn.jsdelivr.net/gh/pronamic/wp-documentor@main/logos/pronamic-wp-documentor.svgo-min.svg" alt="Pronamic WordPress Documentor" width="32" height="32"></a><br><em>Generated by <a href="https://github.com/pronamic/wp-documentor">Pronamic WordPress Documentor</a> <code>1.2.0</code></em><p>

