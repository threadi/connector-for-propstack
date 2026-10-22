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

#### show_sorting

* Set to "0" to hide the selection for the sorting above this list
* The sorting is managed under Settings > Connector for Propstack > Sorting
* Default: 1

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

### Parameters

#### tags (Pro only)

* Optional: show only images with at least one of these tags from Propstack, separated by commas
* The values are listed under Settings > Connector for Propstack > Import > Tags
* Default: empty (all images)

#### columns

* Optional: the number of columns
* Default: 3

#### size

* Optional: the image size, e.g. thumbnail, medium, large or full
* Default: thumbnail

### Example

`[cfprop_widget_gallery tags="innenansicht,floorplan" columns="4" size="medium"]`

## Map

Shows the location of an object on a map of the map service chosen under Settings > Connector for Propstack > Maps (OpenStreetMap or Google Maps). The map is loaded only after the consent of the visitor, if enabled.

Shortcode structure:

`[cfprop_widget_map]`

### Parameters

#### object_id

* The Propstack ID of the object to show
* Only needed outside of the template for the detail view of objects

#### zoom

* Zoom level from 1 (world) to 19 (street)
* Default: the zoom level from the settings

#### height

* Height of the map with a CSS unit, e.g. 400px or 50vh
* Default: the height from the settings

#### source

* "object" for the actual object, "list" for all objects of the list with the active filters (only with Connector for Propstack Pro)
* Default: object

#### popup

* "1" to show the title and a link in a popup for maps with the list of objects, "0" to hide it
* Default: 1

### Example

`[cfprop_widget_map zoom="14" height="400px"]`

## Inquiry Button

Shows a button for an inquiry about the actual object. Without a link target the button uses the public email of the broker (with the title, the ID and the link of the object in the email), otherwise the public phone number of the broker, otherwise the email of this website.

Shortcode structure:

`[cfprop_widget_inquiry_button]`

### Parameters

#### url

* Optional link target, e.g. `#contact` for a form with this ID on the same page or the URL of your contact page
* Default: empty (email or phone of the broker, otherwise the email of this website)

#### Styles

Optional, without value the button uses the styles of the theme:

* `text_color`, `background_color`, `text_color_hover`, `background_color_hover`, `border_color`: colors, e.g. `#ffffff`, `rgb(0,0,0)` or `var(--my-color)`
* `font_family`: e.g. `Georgia, serif`
* `font_size`, `letter_spacing`, `padding`, `border_width`, `border_radius`: sizes, e.g. `16px`, `1em` or `10px 20px` (numbers without unit are pixels)
* `line_height`: e.g. `1.4`
* `font_weight`: `100` to `900`
* `font_style`: `normal` or `italic`
* `text_transform`: `none`, `uppercase`, `lowercase` or `capitalize`
* `text_decoration`: `none`, `underline` or `line-through`
* `border_style`: `none`, `solid`, `dashed`, `dotted` or `double`

### Example

`[cfprop_widget_inquiry_button url="#contact" background_color="#333333" text_color="#ffffff" border_radius="4px"]`
