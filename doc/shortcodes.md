# Shortcodes

The following shortcodes can be used to display objects imported into the WordPress project on the website.

All parameters are optional. If a parameter is not specified on the shortcode, the default values are used.

## List of properties

Shortcode structure:

`[cfprop_widget_archive]`

### Parameters

#### listing_template

* Specifies the name of the template for the list
* Default: default

#### templates

* Specifies the content and order for each property in the list
* Comma-separated list of valid names
* Default: 'thumbnail','location_object_type','title','values','detail_link'
* Available names:
  * thumbnail => Featured image
  * location_object_type => Property type
  * title => Title
  * values => List of property values
  * detail_link => Link to the single view

### Example:

`[cfprop_widget_archive listing_templates="default"]`

## Single property view

Shortcode structure:

`[cfprop_widget_single]`

### Parameters

#### template

* Specifies the name of the template for the output
* Default: default

#### templates

* Specifies the content and order for the property display
* Comma-separated list of valid names
* Default: 'thumbnail', 'marketing_type', 'key_facts', 'property_details', '2column_content', 'gallery',
* Available names:
  * thumbnail => Featured image
  * marketing_type => Marketing type
  * key_facts => Key facts
  * property_details => Property details
  * 2column_content => 2-column content (e.g., description texts & contact options)
  * gallery => Gallery

#### object_id

* Mandatory specification of the object ID to be displayed
* Default: empty (no output)

### Example

`[cfprop_widget_single object_id="42" template="default"]`

### Filters

Shortcode structure:

`[cfprop_widget_filter]`

### Parameters

#### filters

* Specifies the filter options to be displayed
* Comma-separated list of valid names
* Default: 'cities', 'object_id'
* Available names:
  * cities => Selection field or input field for locations
  * object_id => Input field for the property ID ID
  * rooms (Pro only) => Number of rooms
  * living_space (Pro only) => Square meters
  * taxonomies (Pro only) => Output of terms from a plugin taxonomy, e.g., Property Type

### Example

`[cfprop_widget_filter filters="cities"]`

## Field

`[cfprop_widget_field]`

### Parameters

#### field_name

* Specifies the field whose value is to be output
* Default: empty (i.e., nothing is output)
* Available fields: see Settings > Connector for Propstack > Fields > "Internal Name" column

### Example

`[cfprop_widget_field field_name="address"]`

## Property Data

`[cfprop_widget_object_data]`

### Parameters

#### object_data

* Specifies the fields whose values ​​are to be output
* Comma-separated list of valid names
* Default: empty (i.e., nothing is output)
* Available fields: see Settings > Connector for Propstack > Fields > "Internal Name" column

### Example

`[cfprop_widget_object_data object_data="address"]`

## Broker Field

`[cfprop_widget_broker_field]`

### Parameters

#### field_name

* Specifies the broker field whose value is to be output
* Default: empty (i.e., nothing is output)
* Available fields: see Settings > Connector for Propstack > Fields > Broker > "Internal Name" column

### Example

`[cfprop_widget_field field_name="address"]`

## Description

Shortcode structure:

`[cfprop_widget_description]`

### Parameters

#### description_type

* Specifies the description field to be output
* Default: description_note
* Available fields: see Settings > Connector for Propstack > Fields > "Internal Name" column

### Note

The field shortcode can be used as an alternative. `[cfprop_widget_description description_type="description_note"]`

## Gallery

Shortcode structure:

`[cfprop_widget_gallery]`

Displays the gallery of a property.

This shortcode has no additional parameters.
