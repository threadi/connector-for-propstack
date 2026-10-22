---
name: Release-Checkliste
about: Vor jedem Release anlegen und abarbeiten (Connector for Propstack)
title: "Release X.Y.Z"
labels: release
---

<!--
Grundsatz: Die CI deckt Code-Qualität, PHPUnit (PHP 8.2–8.5), Playwright gegen einen API-Mock und den
Smoke-Test des Zips ab. Diese Liste prüft nur, was die CI NICHT sehen kann: echte Propstack-API,
echtes Cron, Update von der Vorgängerversion, echte Themes.

Teststand: Staging-Kopie einer Kundenseite (oder eigene Demo) mit echtem Propstack-Account,
WP_DEBUG + WP_DEBUG_LOG an. Nicht Playground.

Abschnitt B nur für Bereiche abarbeiten, die laut Changelog dieses Release angefasst wurden.
Nicht relevante Punkte mit ~~durchstreichen~~ statt löschen, damit sichtbar ist, was bewusst ausgelassen wurde.
-->

**Version:** X.Y.Z  ·  **Vorgänger:** X.Y.Z  ·  **Teststand:** _URL / PHP-Version / WP-Version_

## 0. Vorab (≈ 5 Min.)

- [ ] Workflows „Testing“ und „Build release zip“ (inkl. E2E) für den Release-Stand grün
- [ ] Plugin-Check-Ausgabe im Build-Lauf überflogen – keine neuen Fehler
- [ ] `changelog.md`: Abschnitt `## [X.Y.Z]` vorhanden, „Unreleased“ umbenannt
- [ ] `readme.txt`: „Tested up to“ und „Requires at least“ stimmen
- [ ] Bricht die Änderung etwas, worauf die Pro-Version aufsetzt (Hooks, Filter, Klassen)? → Pro-Release mitplanen

## A. Kern – bei jedem Release (≈ 30 Min.)

**Update-Pfad** – der Weg, den alle Bestandskunden gehen
- [ ] Vorgängerversion installiert, mit Daten → neue Version drüber installiert, keine Fehler
- [ ] Einstellungen erhalten, API-Key funktioniert noch (Verbindungstest), Update-Routinen sind gelaufen
- [ ] Frische Installation: Setup-Assistent führt bis zum ersten erfolgreichen Import

**Import mit echtem Account**
- [ ] Manueller Import: Objektanzahl stimmt mit Propstack überein, Log ohne Fehler
- [ ] Bilder-Queue läuft leer, keine doppelten Bilder in der Mediathek
- [ ] Ein Objekt in Propstack ändern (Titel, Preis, ein Bild entfernen) → nächster Import übernimmt es, URL bleibt gleich
- [ ] Automatischer Import per Cron läuft vollständig durch (`wp cron event run --due-now` oder Intervall abwarten)

**Frontend** – in einem Block-Theme UND einem klassischen Theme
- [ ] Liste: Filter, Sortierung und Seite 2 der Pagination funktionieren
- [ ] Detailseite vollständig, Karte erst nach Zustimmung
- [ ] Objekt mit versteckter Adresse: keine Straße und keine Koordinaten im Quelltext

**Abschluss**
- [ ] `debug.log` leer bzw. keine neuen Einträge des Plugins

## B. Module – nur wenn im Changelog betroffen

**Import / Queue / Aufräumen**
- [ ] API v2-Import eines Objekts inkl. Bilder
- [ ] Objekt in Propstack aus der Vermarktung nehmen → wird entfernt; manuell angelegte Beiträge bleiben unangetastet
- [ ] Grenze „max. Prozent gelöschter Objekte“ greift (z. B. API-Key temporär mit leerem Ergebnis)
- [ ] Abbruch mitten im Import (Seite neu laden / PHP-Timeout) → nächster Lauf setzt fort, Sperre wird freigegeben

**Einstellungen / Verschlüsselung**
- [ ] Einstellungen exportieren und auf anderer Seite importieren, API-Key ist nicht im Export
- [ ] API-Key ändern und wieder speichern

**Blöcke / Shortcodes / Templates**
- [ ] Alle Blöcke im Editor einfügbar, Vorschau nicht leer
- [ ] Template-Override im Child-Theme wird weiter verwendet

**Felder / Custom Fields**
- [ ] Neues Custom Field in Propstack → erscheint unter Einstellungen > Felder > Custom fields

**Abilities / MCP**
- [ ] `get-objects` und `get-object` über einen MCP-Client abrufen, nur veröffentlichte Objekte

**WP-CLI** – nur geänderte Befehle
- [ ] z. B. `wp cfprop import_objects`, `wp cfprop check_object`, `wp cfprop reset_plugin`

**Multisite**
- [ ] Netzwerkaktivierung, Import auf einer Unterseite, Reset betrifft nur diese Seite

**Deinstallation**
- [ ] Löschen entfernt Optionen, Transients, Cron-Events und Tabellen

## C. Nach dem Release (≈ 5 Min.)

- [ ] Zip aus dem GitHub-Release (nicht den lokalen Build) auf der Testseite installiert – läuft
- [ ] wordpress.org: neue Version auf der Plugin-Seite sichtbar, Update erscheint auf einer Bestandsseite

**Notizen / Auffälligkeiten:**
