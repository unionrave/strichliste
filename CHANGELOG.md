# Changelog – Vereinstresen (SG Germania Binsfeld Tennis)

Digitale Strichliste für den Tennis-Vereinstresen, angelehnt an tinyTresen.
Mitglieder bestellen an der Theke, alles wird auf ihr Konto gebucht, und später
per Bar/Überweisung/SEPA abgerechnet.

## v1.0 – Grundgerüst
- Erste Version als einzelne HTML-Datei (Single Page App, kein Build-Prozess).
- Fünf Bereiche: **Theke** (Bestellungen buchen), **Übersicht** (Salden, Zahlungen
  erfassen), **Mitglieder**, **Produkte**, **Verlauf** (Buchungshistorie).
- Saldo je Mitglied wird aus der Buchungshistorie berechnet (Summe Bestellungen
  minus Summe Zahlungen), nicht als eigenständiger, potenziell inkonsistenter Wert
  gespeichert.
- Tally-Mark-Symbole (Strichgruppen à 5) im Verlauf als Reminiszenz an die
  Papier-Strichliste.

## v1.1 – Zugriffsschutz (zweistufig)
- **Theke-PIN**: nötig, um die App überhaupt zu öffnen.
- **Admin-PIN**: zusätzlich nötig für Übersicht (Zahlungen erfassen), Mitglieder
  und Produkte.
- Einmalige Ersteinrichtung beim ersten Start, PINs im Nachhinein änderbar.
- "Abmelden"-Funktion, um ein gemeinsam genutztes Gerät (z. B. Theken-Tablet)
  wieder zu sperren.

## v1.2 – Eigenständiger Betrieb auf eigenem Webspace (Strato)
- `index.html` + `api.php` + `config.php`: PHP/MySQL-Backend als Ersatz für den
  Claude-internen Speicher, damit die App auch außerhalb von Claude.ai läuft.
- Einfacher Key-Value-Speicher (`kv_store`-Tabelle) statt eigenem Tabellenschema
  pro Datentyp – Tabelle wird beim ersten Aufruf automatisch angelegt.

## v1.3 – MariaDB-Kompatibilität
- Klargestellt und dokumentiert: MariaDB ist wire-kompatibel zu MySQL, der
  bestehende PHP-Code (PDO mit `mysql:`-DSN) funktioniert unverändert mit beiden.
  Kommentare in `api.php`/`config.php` entsprechend präzisiert.

## v1.4 – Vereinsfarben & Logo
- Farbpalette auf Schwarz/Gold umgestellt, exakt aus dem Vereinswappen
  abgetastet (`#F5CD4B`).
- Vereinslogo eingebettet (Base64, keine externe Bilddatei nötig), im Header und
  auf dem PIN-Sperrbildschirm, mit freigestelltem (transparentem) Hintergrund.
- Standard-Vereinsname auf „SG Germania Binsfeld Tennis" gesetzt.

## v1.5 – Logo-Sichtbarkeit im Header
- Logo im Header vergrößert (44px → 72px) und in einen hellen, abgerundeten
  Hintergrund-Badge gesetzt, damit die überwiegend schwarzen Logo-Linien auf dem
  dunklen Header-Hintergrund sichtbar bleiben.

## v1.6 – Hintergrund-Wasserzeichen
- Großes, dezentes Logo (7 % Deckkraft) als fixer Seitenhintergrund ergänzt,
  ohne die Lesbarkeit von Karten/Tabellen zu beeinträchtigen.

## v1.7 – Credit-Zeile
- Feste Fußzeile: „Erstellt von Marco Weber für SG Germania Binsfeld Tennis".

## v1.8 – Lokaler Betrieb (XAMPP)
- Anleitung für den Betrieb auf einem lokalen Rechner mit XAMPP
  (Apache + PHP + MariaDB gebündelt), inkl. Einrichtung von Datenbank,
  `config.php`-Werten und Zugriff aus dem lokalen Netzwerk.

## v1.9 – Betrieb auf Unraid (Docker)
- `docker-compose.yml` (MariaDB-Container + PHP/Apache-Container) und
  `web/Dockerfile` (PHP-Image mit `pdo_mysql`-Erweiterung, die im offiziellen
  Image standardmäßig fehlt).
- App-Ordner als Docker-Volume eingebunden, sodass Updates ohne Container-Neubau
  möglich sind.

## v1.10 – Zugangsdaten über .env-Datei
- Zugangsdaten (DB-Passwörter etc.) aus `docker-compose.yml` in eine separate
  `.env`-Datei ausgelagert, per `${...}`-Platzhaltern eingebunden.
- `config.php` liest die Werte seitdem über `getenv()` aus den
  Container-Umgebungsvariablen statt sie hart zu kodieren – nur noch eine
  Stelle mit Klartext-Zugangsdaten.

## v1.11 – Öffentlicher Zugriff über Nginx Proxy Manager
- Fehlerbehebung 502 Bad Gateway: Ursache war die macvlan-Einschränkung von
  Unraid (NPM-Container mit eigener br0-IP kann den Host nicht direkt
  erreichen). Behoben über **Settings → Docker → „Host access to custom
  networks"**.

## v1.12 – Produkte bearbeiten
- Bestehende Produkte (Name, Kategorie, Preis) lassen sich jetzt nachträglich
  bearbeiten, nicht nur anlegen/löschen.
- Bereits gebuchte Bestellungen speichern Preis und Bezeichnung zum
  Buchungszeitpunkt fest in der Transaktion – spätere Preisänderungen wirken
  sich **nur auf neue Buchungen** aus, vergangene Salden bleiben unverändert.

## v1.13 – Fire-HD-10-/Kiosk-Optimierung
- Web-App-Manifest (Vollbild, Querformat) für „Zum Startbildschirm hinzufügen".
- Vollbild-Button im Header (Fullscreen-API).
- Breiteres Layout ab 1100 px im Querformat, größere Touch-Ziele.
- Kein versehentliches Zoomen/Text-Markieren mehr beim schnellen Antippen.

## v1.14 – Ersteller-geschütztes Löschen des Buchungsverlaufs
- Neue dritte PIN-Ebene: **Ersteller-PIN**, unabhängig von Theke- und Admin-PIN.
- „Verlauf löschen" (setzt alle Salden zurück, Mitglieder/Produkte bleiben
  erhalten) ist ausschließlich mit diesem PIN möglich – auch Admin-PIN-Inhaber
  haben keinen Zugriff darauf.
- PIN wird beim ersten Nutzungsversuch einmalig festgelegt und danach nur zur
  Bestätigung abgefragt.

## v1.15 – Touch-Optimierung & responsives Layout
- Alle Buttons, Eingabefelder und Tabellen-Icons auf durchgängig touch-taugliche
  Mindestgrößen gebracht (Buttons min. 46px hoch, Icon-Buttons 40×40px statt
  vorher ~20px).
- Schriftgrößen in Tabellen, Kacheln, Tabs und Formularen insgesamt angehoben.
- Drei statt einer Breakpoint-Stufe: kleine Handys (<520px), Tablets im
  Hochformat/kleine Fenster (ab 700px), große Tablets quer & Desktop
  (ab 1100px) – statt nur der ursprünglichen Fire-HD-10-Sonderregel.
- Eingabefelder auf 16px Schriftgröße gesetzt, damit iOS-Browser beim
  Fokussieren nicht automatisch hineinzoomen.

## v1.16 – Feste Kopf-/Fußzeile ohne Seiten-Scroll
- Layout auf ein App-Shell-Prinzip umgestellt: Header und Fußzeile bleiben
  fest sichtbar, nur der mittlere Inhaltsbereich (Mitgliederliste,
  Produkttabelle, Verlauf) scrollt bei Bedarf für sich.
- Verhindert, dass die Fußzeile durch langes Scrollen der Gesamtseite aus dem
  sichtbaren Bereich verschwindet, unabhängig von der Menge an Inhalt.

## v1.17 – Statischer, zweifarbiger Vereinsname im Header
- Der Vereinsname ist nicht mehr als Eingabefeld bearbeitbar, sondern ein
  fester Schriftzug – verhindert versehentliche oder unautorisierte
  Änderungen.
- Zweifarbige Gestaltung (letztes Wort in Vereins-Gold hervorgehoben) für
  einen wortmarkenartigen Look, inklusive dezentem Schlagschatten.

## v1.17.1 – Mitglieder bearbeiten
- Bestehende Mitglieder lassen sich jetzt nachträglich umbenennen, analog zur
  Produktbearbeitung (Bearbeiten-Symbol ✎ in der Mitgliederliste).
- Bereits gebuchte Beträge im Verlauf sind davon unberührt.

## v1.18 – Sortierung, Produkt-Status, Ersteller-Rechte & Bestell-Overlay
- **Mitglieder & Produkte frei sortierbar**: Auf/Ab-Buttons je Zeile für beliebige
  eigene Reihenfolge (bei Produkten innerhalb der jeweiligen Kategorie), plus
  ein „A–Z sortieren"-Button für einmaliges alphabetisches Neuordnen. Die
  gespeicherte Reihenfolge gilt konsistent überall – auch an der Theke.
- **Produkte deaktivierbar**: analog zu Mitgliedern jetzt mit Aktiv/Inaktiv-
  Umschalter; deaktivierte Produkte verschwinden aus der Bestellmaske,
  bleiben aber in der Verwaltung erhalten (z. B. für Saisonartikel).
- **Ersteller-PIN hat zusätzlich alle Admin-Rechte**: überall, wo das
  Admin-PIN verlangt wird (Übersicht, Mitglieder, Produkte), wird jetzt auch
  der Ersteller-PIN akzeptiert - inklusive direktem Login am Sperrbildschirm
  mit vollem Admin-Zugriff.
- **Bestellung an der Theke als Overlay**: Klick auf ein Mitglied öffnet die
  Bestellmaske jetzt als großflächiges Overlay statt als Inline-Karte unter
  der Mitgliederliste. Nutzt den Bildschirm besser aus, die Buchen-Leiste mit
  Warenkorb-Summe bleibt dabei immer sichtbar am unteren Rand, ganz ohne
  Scrollen.
- Enthält außerdem die vorherige v1.17.1-Ergänzung: Mitglieder bearbeiten
  sowie den Scroll-Fix an der Theke (Fully Kiosk Browser / Fire HD 10).

## v1.18.1 – Größere Kacheln, verlässliches 3-Spalten-Layout & getrennte Namen
- Mitglieder- und Produktkacheln an der Theke sowie im Bestell-Overlay deutlich
  vergrößert (Schrift, Innenabstand, Mindesthöhe) für bessere Touch-Bedienung.
- Im Hochformat auf Tablets (z. B. Fire HD 10) wird jetzt zuverlässig eine
  feste 3-Spalten-Ansicht erzwungen - an `orientation:portrait` gekoppelt statt
  an eine feste Pixelbreite, weil der Fully Kiosk Browser die Viewport-Breite
  abweichend meldet und eine reine Breiten-Regel dadurch nicht griff.
- Mitglieder haben jetzt getrennte **Vorname-/Nachname-Felder** statt eines
  gemeinsamen Namensfelds, inklusive zweier eigener Sortier-Buttons
  („Vorname A–Z" / „Nachname A–Z") zusätzlich zur bestehenden freien
  ▲▼-Sortierung.
- Bestehende Mitgliederdatensätze werden beim Laden automatisch von einem
  einzelnen Namensfeld auf die neuen getrennten Felder migriert - keine
  manuelle Nacharbeit nötig.

## v1.18.2 – Korrekturbuchungen, Barcode-Anmeldung & Verlauf-Filter
- **Negative Zahlungen/Korrekturbuchungen**: Der Dialog „Zahlung erfassen"
  akzeptiert jetzt auch negative Beträge, um bereits erfasste Zahlungen oder
  Guthaben zu korrigieren, ohne die ursprüngliche Buchung löschen zu müssen.
  Neue Zahlweise „Korrektur" zur Auswahl. Der Verlauf zeigt Vorzeichen und
  Farbe jetzt passend zur tatsächlichen Saldo-Auswirkung an.
- **Barcode-/QR-Scan per Kamera** (über die eingebettete ZXing-Bibliothek,
  läuft vollständig im Browser):
  - An der Theke: Mitglied per Kamera-Scan direkt auswählen, statt zu tippen.
  - Am Sperrbildschirm: PIN per Scan eines Codes eingeben, der den PIN-Wert
    enthält.
  - Bei Mitgliedern (Anlegen & Bearbeiten): ein Barcode kann einmalig per
    Kamera erfasst und dem Mitglied zugeordnet werden; doppelte Zuordnungen
    werden verhindert.
  - Voraussetzung: Zugriff über HTTPS (oder localhost) sowie erlaubter
    Kamerazugriff im verwendeten Browser/Kiosk-Modus.
  - Kleinere Korrektur: das PIN-Feld am Sperrbildschirm schrumpft jetzt
    korrekt, damit der Kamera-Button daneben nicht abgeschnitten wird.
- **Versionsanzeige im Footer**: Die aktuell installierte Version steht ab
  sofort dauerhaft in der Fußzeile der App, sichtbar in jedem Bereich
  inklusive Sperrbildschirm.
- **Filterleiste im Verlauf**: Buchungen lassen sich jetzt nach Mitglied, Art
  (Bestellung/Zahlung/Korrektur) sowie Zeitraum (von/bis) filtern, beliebig
  kombinierbar, mit einem Klick zurücksetzbar. „Verlauf löschen" bleibt davon
  unabhängig und setzt weiterhin immer den kompletten Verlauf zurück.

## v1.18.3 – Sicherheitslücke bei den PINs behoben
- **Kritischer Fix**: Bisher lieferte der Server auf Anfrage den kompletten
  Auth-Datensatz (Theke-, Admin- **und** Ersteller-PIN im Klartext) aus -
  abrufbar von jedem mit Zugriff auf die Browser-Konsole, unabhängig davon,
  welchen PIN die Person selbst kannte. Das hebelte den gesamten PIN-Schutz
  aus.
- PIN-Prüfung läuft jetzt ausschließlich serverseitig über drei neue,
  dedizierte Aktionen in `api.php` (`auth_status`, `verify_pin`, `set_auth`).
  Der Server beantwortet Anfragen nur noch mit "welche Berechtigungsstufe"
  bzw. "ja/nein", nie mit den PIN-Werten selbst. Der Eintrag `auth` ist für
  den normalen Lese-/Schreibzugriff (GET sowie generisches POST) komplett
  gesperrt.
- Ändern bestehender PINs verlangt jetzt eine serverseitig geprüfte
  Bestätigung mit dem aktuell gültigen Admin- oder Ersteller-PIN - nicht mehr
  nur eine Prüfung im Browser.
- PIN-Vergleiche laufen serverseitig über eine zeitsichere Vergleichsfunktion
  (`hash_equals`) statt einfacher Gleichheitsprüfung.
- Betrifft ausschließlich die selbst gehostete Version (`index.html` +
  `api.php`). Die Vorschauversion hier im Chat (`tresen.html`, nutzt Claudes
  eigenen Speicher statt einer eigenen Datenbank) ist von diesem Fix nicht
  betroffen, da es dafür keine vergleichbare serverseitige Komponente gibt -
  sie ist aber ohnehin nicht die produktiv genutzte App.

## v1.19.0 – Buchungshistorie: von JSON-Block auf echte Tabelle umgestellt
- **Performance-Fix gegen wachsende Verlangsamung**: Bisher lag die komplette
  Buchungshistorie als ein einzelner JSON-Text unter einem Schlüssel in der
  Datenbank - jede neue Buchung musste diesen kompletten Block neu schreiben.
  Mit wachsender Vereinsgeschichte (mehrere tausend Buchungen über Jahre)
  wäre jede einzelne neue Buchung spürbar langsamer geworden.
- Buchungen liegen jetzt in einer **eigenen Datenbanktabelle** (`transactions`,
  eine Zeile pro Buchung, indiziert nach Mitglied/Zeitstempel/Art). Eine neue
  Buchung ist unabhängig von der Historiengröße immer gleich schnell.
- **Salden werden per SQL-Summe berechnet** statt im Browser aus der
  kompletten Historie zusammengerechnet - beim Laden der App wird nur noch
  ein kleines Salden-Objekt übertragen, nicht mehr jede einzelne Buchung.
- **Verlauf lädt seitenweise** (100 Einträge, „Weitere laden"-Button) und
  filtert jetzt direkt in der Datenbank statt im Browser - macht sich vor
  allem bei sehr langer Historie bemerkbar.
- **Automatische Einmal-Migration**: Bestehende Installationen übernehmen die
  bisherige Historie beim ersten Laden nach dem Update automatisch in die
  neue Tabelle. Der alte Datenblock wird dabei nicht gelöscht, sondern als
  Sicherheitsnetz unter einem Backup-Schlüssel aufbewahrt.
- „Verlauf löschen" verlangt jetzt zusätzlich eine serverseitig geprüfte
  Bestätigung mit dem Ersteller-PIN (bisher nur clientseitig geprüft),
  konsistent mit dem PIN-Sicherheitsfix aus v1.18.3.

---

## Architektur-Überblick (Stand heute)

| Baustein | Zweck |
|---|---|
| `index.html` | komplette Anwendung (UI + Logik), eine einzelne Datei |
| `api.php` | Backend über MySQL/MariaDB: Key-Value-Speicher (Mitglieder, Produkte, Einstellungen, PINs) + eigene `transactions`-Tabelle für Buchungen |
| `config.php` | Datenbank-Zugangsdaten (lokal) bzw. liest ENV-Variablen (Docker) |
| `docker-compose.yml` + `web/Dockerfile` | Betrieb als zwei Container (App + DB) auf Unraid |
| `.env` | Zugangsdaten für den Docker-Compose-Stack |

## Drei-Ebenen-PIN-Modell

1. **Theke-PIN** – Zugang zur App allgemein (Bestellungen buchen, Verlauf ansehen)
2. **Admin-PIN** – zusätzlich: Übersicht/Zahlungen, Mitglieder, Produkte verwalten
3. **Ersteller-PIN** – ausschließlich: kompletten Buchungsverlauf löschen

PIN-Prüfung läuft seit v1.18.3 ausschließlich serverseitig (`api.php`); die
PIN-Werte selbst verlassen die Datenbank nie in Richtung Browser.
