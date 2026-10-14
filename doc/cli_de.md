# Verwendung der WP CLI

## Überblick

Die WP Cli führt Wordpress-Kommandos an der Konsole aus. Diese sollte nur mit entsprechendem Vorwissen ausgeführt werden. Das Plugin stellt eine ganze Reihe an Kommandos für die schnelle Bearbeitung von Objekten in der Datenbank bereit.

# Main command

Liste der verfügbaren Befehle für dieses Plugin anzeigen:

`wp cfprop`

# Kommandos

`wp cfprop check_object <ID>`
=> prüft, ob die Propstack-API ein bestimmtes Objekt für den Import liefert, ohne etwas zu importieren. Zeigt außerdem, ob eine Ihrer Einschränkungen den Import verhindern würde. `<ID>` ist die Propstack-ID des Objekts; mit `--field=unit_id` oder `--field=exposee_id` wird nach einer anderen ID gesucht, mit `--full-scan` wird mit exakt den Abfragen des Imports geprüft

`wp cfprop clear_queue`
=> leer die Warteschlange one diese zu verarbeiten

`wp cfprop delete_imported_files`
=> löscht alle von Propstack importierten Dateien

`wp cfprop delete_objects`
=> löscht alle von Propstack importierten Objekte

`wp cfprop import_files`
=> importiert alle noch nicht importierten Dateien von Objekten

`wp cfprop import_objects`
=> importiert Objekte von Propstack

`wp cfprop process_queue`
=> verarbeitet Einträge der Warteschlange

`wp cfprop reset_plugin`
=> setzt das gesamte Plugin zurück

# weitere Kommandos im Pro-Plugin

`wp cfprop import_object_states`
`wp cfprop delete_object_states`
`wp cfprop import_broker`
`wp cfprop delete_broker`

# Hint

Abhängig von Ihrem Hosting-System müssen diese Befehle im Benutzerkontext Ihrer Website ausgeführt werden.
