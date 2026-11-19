# Tests mit Live-Daten aus Propstack

## Überblick

`composer test` prüft das Plugin mit festen Testdaten aus _tests/Data/_ und schickt nie eine Anfrage an Propstack. Ob der Import mit den **echten** Daten eines Propstack-Kontos funktioniert, prüft `composer test-live`: Die Tests holen die Daten über die echte Propstack-API, importieren sie in die Datenbank der WordPress-Tests und prüfen die Abläufe, die man sonst von Hand durchklicken würde.

Die Live-Tests **lesen nur** aus Propstack. In Propstack wird nichts angelegt, geändert oder gelöscht. Alles wird in die Testdatenbank importiert, die das Test-Framework bei jedem Lauf zurücksetzt. Bilder landen kurz im Upload-Verzeichnis der Test-Installation und werden nach jedem Test wieder entfernt.

Für die Funktionen von _Connector for Propstack Pro_ (Projekte, Merkmale, Webhooks …) gibt es eigene Live-Tests im Pro-Plugin. Beide laufen unabhängig voneinander, nutzen aber dieselben Umgebungsvariablen.

## Voraussetzungen

- Die Testumgebung wie für `composer test`: eine MySQL- oder MariaDB-Datenbank und einmalig `composer test-install` (installiert WordPress und die Test-Bibliothek nach _/tmp_).
- `composer install` wurde ausgeführt. Die Autoloader-Konfiguration ist `classmap-authoritative`: Wer neue Testdateien anlegt, muss danach `composer dump-autoload` ausführen, sonst findet PHPUnit die Klassen nicht.
- Der Rechner erreicht `api.propstack.de` und die Hosts der Bilder (z. B. `images.propstack.de`). WordPress lehnt Bild-URLs ab, deren Host sich nicht per DNS auflösen lässt.
- Ein API-Schlüssel eines Propstack-Kontos **mit Objekten**.

## Einrichtung

1. _tests/live-config.example.php_ nach _tests/live-config.php_ kopieren.
2. In _tests/live-config.php_ mindestens den API-Schlüssel eintragen.
3. `composer test-live` ausführen (oder `task test-live`).

_tests/live-config.php_ steht in der _.gitignore_. Statt der Datei können alle Werte auch als Umgebungsvariablen gesetzt werden; diese haben Vorrang vor der Datei.

| Einstellung | Umgebungsvariable | Standard | Bedeutung |
|---|---|---|---|
| `api_key` | `CFPROP_LIVE_API_KEY` | – | API-Schlüssel. Ohne ihn werden alle Live-Tests übersprungen. |
| `api_versions` | `CFPROP_LIVE_API_VERSIONS` | `v1,v2` | Die zu testenden API-Versionen, kommagetrennt. |
| `language` | `CFPROP_LIVE_LANGUAGE` | `de` | Die Sprache des Imports. |
| `object_id` | `CFPROP_LIVE_OBJECT_ID` | leer | Optional: Propstack-ID eines Objekts, das geliefert und importiert werden muss. |
| `max_objects` | `CFPROP_LIVE_MAX_OBJECTS` | `0` | Optional: nur die ersten X Objekte importieren (`0` = alle). |
| – | `CFPROP_LIVE_REQUIRE` | aus | `1`: fehlschlagen statt überspringen, wenn kein Schlüssel gesetzt ist (z. B. in CI). |
| – | `CFPROP_LIVE_CONFIG` | _tests/live-config.php_ | Pfad zu einer anderen Konfigurationsdatei. |

## Worauf man bei der Konfiguration achten muss

**Den API-Schlüssel nie ins Repository.** Nur in _tests/live-config.php_ (ist ignoriert) oder als Umgebungsvariable bzw. CI-Secret. Nicht in _composer.json_, _phpunit-live.xml.dist_ oder eine andere versionierte Datei schreiben. Im Log des Plugins wird der Schlüssel unkenntlich gemacht, in Fehlermeldungen der Tests taucht er nicht auf.

**Die Berechtigungen des Schlüssels müssen zu den API-Versionen passen.** Sie unterscheiden sich zwischen v1 und v2:

- API v1 und v2: Objekte (Lesen), Objekt-Status (Lesen)
- zusätzlich für API v2: Properties_options (Lesen), Properties_statuses (Lesen), Brokers (Lesen)

Hat der Schlüssel nur die Rechte für eine Version, `api_versions` auf diese Version setzen. Sonst schlagen die Tests der anderen Version mit HTTP 401 oder 403 fehl. Das ist dann kein Fehler im Plugin.

**Die Testdatenbank ist nicht die Website.** `composer test-install` legt die Datenbank `wordpress_test` an und das Test-Framework leert sie bei jedem Lauf. Niemals die Datenbank einer echten Website als Testdatenbank eintragen.

**Die Tests laufen mit den Standardeinstellungen des Plugins.** Einschränkungen aus den Import-Einstellungen einer Website (z. B. nur bestimmte Status oder Vermarktungsarten) gelten im Test nicht. Die Zahl der importierten Objekte kann deshalb von der Website abweichen. Objekte, die das Plugin grundsätzlich nicht importiert (z. B. nicht unterstützte Objektarten, unter API v2 alles außer „Vermarktung“), zählen als „durch Einschränkungen verhindert“ – das ist erwartet.

**Die Sprache muss passen.** `language` muss eine Sprache sein, die das Plugin unterstützt und in der Propstack die Objekte liefert. Sie gilt im Test auch als Sprache des Frontends.

**`object_id` sorgfältig wählen.** Am besten ein Objekt in Vermarktung, mit einer unterstützten Objektart und mit Bildern. Es wird für die Objektprüfung (wie `wp cfprop check_object`) und für den Bild-Import verwendet. Ist es nicht gesetzt, nehmen die Tests das erste importierte Objekt bzw. das Objekt mit den wenigsten Bildern.

**`max_objects` spart Zeit, nicht Anfragen.** Es werden trotzdem alle Seiten der Objektliste abgefragt, nur weniger Objekte importiert. Das konfigurierte `object_id` wird immer zusätzlich importiert. Für die Prüfung „alle gelieferten Objekte importiert“ ist ein Lauf ohne Begrenzung aussagekräftiger.

**Laufzeit.** Je nach Konto dauert ein Lauf einige Minuten: Pro API-Version wird zweimal komplett importiert (erster und zweiter Import), dazu kommen die Bilder eines Objekts. Das Zeitlimit von Composer ist für `test-live` abgeschaltet. Den Timeout einzelner Anfragen bestimmt die Plugin-Einstellung (Standard 30 Sekunden).

**Ein echtes Konto, echte Last.** Die Tests stellen dieselben Anfragen wie ein Import auf der Website. Ein Test-Konto ist ideal; mit einem Kundenkonto nur nach Absprache testen und nicht in kurzen Abständen wiederholen.

## Was geprüft wird

| Testklasse | Prüfung |
|---|---|
| `Connection` | Der Schlüssel wird akzeptiert (wie im Setup und in den Einstellungen), ein falscher Schlüssel wird abgelehnt und nicht gespeichert. Alle Seiten der Objektliste kommen ohne Fehler an. Das konfigurierte Objekt wird geliefert und nicht durch eine Einschränkung verhindert. Nur API v2: Status, Auswahl-Optionen und Makler werden geliefert. |
| `ImportFlowV1`, `ImportFlowV2` | Der komplette Import läuft ohne Fehler, alle Anfragen werden mit HTTP 200 beantwortet, die Import-Sperre wird freigegeben. Jedes gelieferte Objekt ist importiert oder durch eine Einschränkung verhindert (verlorene Objekte fallen auf). Kein Objekt ist doppelt vorhanden. Jedes Objekt hat Propstack-ID, Sprache, Objektart und die gespeicherte API-Antwort. Die Felder aller Objekte lassen sich mit und ohne HTML lesen, die Einzelansicht aller Objekte und die Objektliste lassen sich darstellen. Ein zweiter Import behält dieselben Beitrags-IDs. Die Objektprüfung findet das importierte Objekt. Die Bilder eines Objekts werden importiert. Das Löschen aller Objekte entfernt auch deren Bilder. |

PHP-Warnungen, Hinweise und Deprecations führen zu einem Fehler. Beim Lesen der Felder und beim Darstellen der Ansichten werden sie dem Objekt zugeordnet, bei dem sie auftreten.

## Ergebnisse lesen

Während des Laufs schreiben die Tests Zeilen mit `[live]` in die Konsole, z. B.:

```
[live] API v2: 128 objects delivered, 121 imported, 7 prevented by restrictions, 5 requests, 2 runs, 41.3 s.
```

- **Übersprungen (S):** Kein API-Schlüssel, API-Version nicht konfiguriert, kein `object_id` gesetzt oder kein Objekt mit Bildern. Der Grund steht mit `-v` in der Ausgabe.
- **Fehlgeschlagen (F):** Die Meldung nennt die betroffenen Objekte und, wo es passt, alle Anfragen an Propstack mit ihrem HTTP-Status. HTTP 401 oder 403 heißt fast immer: dem Schlüssel fehlt eine Berechtigung.

Einzelne Tests ausführen:

```bash
vendor/bin/phpunit -c phpunit-live.xml.dist --filter ImportFlowV2
vendor/bin/phpunit -c phpunit-live.xml.dist --filter test_fields_of_all_objects_could_be_read -v
```

Nur eine API-Version oder ein anderes Konto, ohne die Datei zu ändern:

```bash
CFPROP_LIVE_API_VERSIONS=v1 composer test-live
CFPROP_LIVE_API_KEY=xyz CFPROP_LIVE_MAX_OBJECTS=20 composer test-live
```

## In CI (optional)

Live-Tests brauchen ein Secret und Zugang zum Internet. Sie gehören deshalb nicht in die normalen Tests eines Pull Requests (Pull Requests aus Forks bekommen keine Secrets). Geeignet ist ein eigener, manuell oder zeitgesteuert gestarteter Workflow, z. B. mit:

```yaml
- name: Run live tests
  run: composer test-live
  env:
    CFPROP_LIVE_API_KEY: ${{ secrets.PROPSTACK_LIVE_API_KEY }}
    CFPROP_LIVE_REQUIRE: 1
```

`CFPROP_LIVE_REQUIRE: 1` sorgt dafür, dass ein fehlendes Secret auffällt, statt dass alle Tests still übersprungen werden.

## Neue Live-Tests ergänzen

- Testklassen gehören nach _tests/Live/_ und erben von `ConnectorForPropstack\Tests\LiveTestCase` (oder `LiveImportFlowTestCase` für Prüfungen am importierten Bestand).
- Daten, die alle Tests einer Klasse brauchen, in `set_up_live_class()` anlegen – nicht in `set_up_before_class()`: Das Test-Framework verbindet sich danach neu mit der Datenbank, und alles davor ginge ab der zweiten Testklasse verloren.
- Nur lesende Anfragen an Propstack. Nichts in Propstack anlegen oder ändern.
- Fehlermeldungen so schreiben, dass sie ohne Debugger verständlich sind: betroffenes Objekt, Propstack-ID und bei API-Problemen `get_api_requests_as_text()`.
- Danach `composer dump-autoload`.
