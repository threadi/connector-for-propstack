# Shortcodes

Die folgenden Shortcodes können verwendet werden, um in das WordPress-Projekt importierte Objekte in der Webseite auszugeben.

Die allermeisten Parameter sind optional. Wenn ein Parameter am Shortcode nicht angegeben wird, gelten die Standard-Werte.

## Liste von Objekten

Aufbau des Shortcodes:

`[cfprop_widget_archive]`

### Parameter

#### listing_template

* Angabe des Namens des Templates für die Liste
* Standard: default

#### templates

* Angabe der Inhalte und ihrer Reihenfolge pro Objekt im Listing
* kommagetrennte Liste mit zulässigen Namen
* Standard: 'thumbnail','location_object_type','title','values','detail_link'
* verfügbare Namen:
  * thumbnail => Beitragsbild
  * location_object_type => Objekt Typ
  * title => Titel
  * values => Liste mit Werten des Objektes
  * detail_link => Link zur Single-Ansicht

### Beispiel:

`[cfprop_widget_archive listing_templates="default"]`

## Detailansicht eines Objekts

Aufbau des Shortcodes:

`[cfprop_widget_single]`

### Parameter

#### template

* Angabe des Namens des Templates für die Ausgabe
* Standard: default

#### templates

* Angabe der Inhalte und ihrer Reihenfolge pro Objekt im Listing
* kommagetrennte Liste mit zulässigen Namen
* Standard: 'thumbnail', 'marketing_type', 'key_facts', 'property_details', '2column_content', 'gallery',
* verfügbare Namen:
  * thumbnail => Beitragsbild
  * marketing_type => Marketing Typ
  * key_facts => wichtige Fakten
  * property_details => Objekt Details
  * 2column_content => 2spaltiger Inhalt (u.a. Beschreibungstexte & Kontaktmöglichkeit)
  * gallery => Galerie

#### object_id

* Pflichtangabe der Objekt-ID, die dargestellt werden soll
* Standard: leer (keine Ausgabe)

### Beispiel

`[cfprop_widget_single object_id="42" template="default"]`

### Filter

Aufbau des Shortcodes:

`[cfprop_widget_filter]`

### Parameter

#### filters

* Angabe der anzuzeigenden Filter-Optionen
* kommagetrennte Liste mit zulässigen Namen
* Standard: 'cities', 'object_id'
* verfügbare Namen:
  * cities => Auswahlfeld oder Eingabefeld für Orte
  * object_id => Eingabefeld für die Objekt ID
  * rooms (nur Pro) => Anzahl Räume
  * living_space (nur Pro) => Quadratmeter
  * taxonomies (nur Pro) => Ausgabe der Begriffe einer Taxonomie des Plugins, z.B. Objekt Typ

### Beispiel

`[cfprop_widget_filter filters="cities"]`

## Feld

`[cfprop_widget_field]`

### Parameter

#### field_name

* Angabe des Feldes, dessen Wert ausgegeben werden soll
* Standard: leer (d.h. es wird nichts ausgegeben)
* verfügbare Felder: siehe Einstellungen > Connector for Propstack > Felder > Spalte "Interner Name"

### Beispiel

`[cfprop_widget_field field_name="address"]`

## Objektdaten

`[cfprop_widget_object_data]`

### Parameter

#### object_data

* Angabe der Felder, deren Wert ausgegeben werden soll
* kommagetrennte Liste mit zulässigen Namen
* Standard: leer (d.h. es wird nichts ausgegeben)
* verfügbare Felder: siehe Einstellungen > Connector for Propstack > Felder > Spalte "Interner Name"

### Beispiel

`[cfprop_widget_object_data object_data="address"]`

## Maklerfeld

`[cfprop_widget_broker_field]`

### Parameter

#### field_name

* Angabe des Makler-Feldes, dessen Wert ausgegeben werden soll
* Standard: leer (d.h. es wird nichts ausgegeben)
* verfügbare Felder: siehe Einstellungen > Connector for Propstack > Felder > Makler > Spalte "Interner Name"

### Beispiel

`[cfprop_widget_field field_name="address"]`

## Beschreibung

Aufbau des Shortcodes:

`[cfprop_widget_description]`

### Parameter

#### description_type

* Angabe des auszugebenden Beschreibungs-Feldes
* Standard: description_note
* verfügbare Felder: siehe Einstellungen > Connector for Propstack > Felder > Spalte "Interner Name"

### Hinweis

Der Shortcode für Felder kann Alternativ dazu genutzt werden.

`[cfprop_widget_description description_type="description_note"]`

## Galerie

Aufbau des Shortcodes:

`[cfprop_widget_gallery]`

Gibt die Galerie eines Objektes aus.

Dieser Shortcode verfügt über keine weiteren Parameter.
