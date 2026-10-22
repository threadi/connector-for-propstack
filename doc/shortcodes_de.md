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

#### show_sorting

* Mit "0" wird die Auswahl der Sortierung über dieser Liste ausgeblendet
* Die Sortierung wird unter Einstellungen > Connector for Propstack > Sortierung verwaltet
* Standard: 1

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

#### object_id

* Angabe einer Object ID dessen Wert von dem angegebenen Feld angezeigt werden soll
* Standard: leer (d.h. es wird versucht as Objekt aus dem Aufruf der Seite zu erkennen)
* verfügbare Werte: alle Object IDs von in WordPress importierten Objekten

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

### Parameter

#### tags (nur Pro)

* Optional: nur Bilder mit mindestens einem dieser Tags aus Propstack anzeigen, durch Kommas getrennt
* Die Werte stehen unter Einstellungen > Connector für Propstack > Import > Tags
* Standard: leer (alle Bilder)

#### columns

* Optional: die Anzahl der Spalten
* Standard: 3

#### size

* Optional: die Bildgröße, z. B. thumbnail, medium, large oder full
* Standard: thumbnail

### Beispiel

`[cfprop_widget_gallery tags="innenansicht,floorplan" columns="4" size="medium"]`

## Karte

Zeigt den Standort eines Objekts auf einer Karte des Kartendienstes, der unter Einstellungen > Connector for Propstack > Karten gewählt ist (OpenStreetMap oder Google Maps). Die Karte wird erst nach Zustimmung des Besuchers geladen, sofern aktiviert.

Aufbau des Shortcodes:

`[cfprop_widget_map]`

### Parameter

#### object_id

* Die Propstack-ID des anzuzeigenden Objekts
* Nur außerhalb der Vorlage für die Detailansicht von Objekten nötig

#### zoom

* Zoomstufe von 1 (Welt) bis 19 (Straße)
* Standard: die Zoomstufe aus den Einstellungen

#### height

* Höhe der Karte mit CSS-Einheit, z. B. 400px oder 50vh
* Standard: die Höhe aus den Einstellungen

#### source

* "object" für das aktuelle Objekt, "list" für alle Objekte der Liste mit den aktiven Filtern (nur mit Connector for Propstack Pro)
* Standard: object

#### popup

* "1" zeigt bei Karten mit der Liste den Titel und einen Link im Popup, "0" blendet ihn aus
* Standard: 1

### Beispiel

`[cfprop_widget_map zoom="14" height="400px"]`

## Anfrage-Button

Zeigt einen Button für eine Anfrage zum aktuellen Objekt. Ohne Link-Ziel nutzt der Button die öffentliche E-Mail-Adresse des Maklers (mit Titel, ID und Link des Objekts in der E-Mail), sonst die öffentliche Telefonnummer des Maklers, sonst die E-Mail-Adresse dieser Website.

Aufbau des Shortcodes:

`[cfprop_widget_inquiry_button]`

### Parameter

#### url

* Optionales Link-Ziel, z. B. `#contact` für ein Formular mit dieser ID auf derselben Seite oder die URL deiner Kontaktseite
* Standard: leer (E-Mail oder Telefon des Maklers, sonst die E-Mail-Adresse dieser Website)

#### Gestaltung

Optional, ohne Wert nutzt der Button die Gestaltung des Themes:

* `text_color`, `background_color`, `text_color_hover`, `background_color_hover`, `border_color`: Farben, z. B. `#ffffff`, `rgb(0,0,0)` oder `var(--meine-farbe)`
* `font_family`: z. B. `Georgia, serif`
* `font_size`, `letter_spacing`, `padding`, `border_width`, `border_radius`: Größen, z. B. `16px`, `1em` oder `10px 20px` (Zahlen ohne Einheit sind Pixel)
* `line_height`: z. B. `1.4`
* `font_weight`: `100` bis `900`
* `font_style`: `normal` oder `italic`
* `text_transform`: `none`, `uppercase`, `lowercase` oder `capitalize`
* `text_decoration`: `none`, `underline` oder `line-through`
* `border_style`: `none`, `solid`, `dashed`, `dotted` oder `double`

### Beispiel

`[cfprop_widget_inquiry_button url="#contact" background_color="#333333" text_color="#ffffff" border_radius="4px"]`
