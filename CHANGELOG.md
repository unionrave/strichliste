# Changelog – Vereinstresen (SG Germania Binsfeld)

Digitale Strichliste für den Vereinstresen (Tennis und Fußball), angelehnt an tinyTresen.
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

## v1.19.1 – Gzip-Kompression aktiviert
- **Performance bei langsamer Internetverbindung**: `index.html` (inzwischen
  rund 750 KB durch eingebettete Schriftarten und die Barcode-Bibliothek)
  sowie die JSON-Antworten von `api.php` werden jetzt komprimiert
  ausgeliefert. Gemessen: rund 50 % kleinere Übertragung für `index.html`
  (760 KB → 365 KB).
- **Docker/Unraid**: `web/Dockerfile` aktiviert das Apache-Modul `mod_deflate`
  und bindet die neue `web/deflate.conf` ein - automatisch beim nächsten
  Container-Neubau (`docker compose up -d --build`) aktiv.
- **Strato/XAMPP**: neue `.htaccess`-Datei im Repo-Hauptverzeichnis, die
  dieselbe Kompression ohne Zugriff auf die zentrale Server-Konfiguration
  aktiviert - einfach mit hochladen/kopieren.
- Mit einem echten Apache-Testserver verifiziert (Konfigurationstest plus
  tatsächliche `Content-Encoding: gzip`-Antwort und Größenvergleich).

## v1.19.2 – Automatische Datenbank-Backups
- **Neuer `db-backup`-Container** (nur Docker/Unraid): erstellt beim Start und
  danach täglich automatisch einen komprimierten Dump der kompletten
  Datenbank, unabhängig von einem Cronjob auf dem Host.
- Ältere Backups als 30 Tage werden automatisch entfernt (einstellbar über
  `RETENTION_DAYS`), damit der Speicherplatz nicht unbegrenzt wächst.
- Backups liegen in einem eigenen Ordner getrennt vom Datenbank-Datenverzeichnis
  selbst - ein Problem mit dem DB-Volume betrifft die Backups nicht.
- Sauberes Fehlverhalten bei einem fehlgeschlagenen Dump: es entsteht keine
  kaputte, aber als "erfolgreich" gemeldete Backup-Datei mehr (ursprünglicher
  Entwurf hatte hier eine Schwachstelle beim direkten Durchleiten in die
  Komprimierung, die einen fehlgeschlagenen Dump verschluckt hätte).
- **Kompatibilität mit neueren MariaDB-Images**: Das Dump-Werkzeug heißt dort
  nur noch `mariadb-dump` statt `mysqldump`. Das Skript erkennt beim Start
  automatisch, welches der beiden Kommandos vorhanden ist, und nutzt es
  entsprechend. Fehlt beides, bricht es mit einer klaren Fehlermeldung ab,
  statt kryptisch mit "not found" zu scheitern.
- **Fix für Unraid-User-Shares**: Datenbank-Dump und Komprimierung laufen
  vollständig im lokalen, ephemeren Dateisystem des Containers (`/tmp`). Auf
  den gemounteten Unraid-Share (FUSE-Dateisystem `shfs`) wird nur noch die
  bereits fertige, vollständige Backup-Datei einmalig kopiert - dort wird
  nichts mehr geschrieben und gleich wieder gelöscht. Ohne diesen Fix konnte
  ein kurz nacheinander geschriebenes-und-gelöschtes File dazu führen, dass
  Unraid es nicht sauber entfernt, sondern stattdessen als
  `.fuse_hiddenXXXXXXXX`-Datei ablegt - der Backup-Ordner blieb dadurch leer.
  Der Zielordner wird zusätzlich beim Start defensiv selbst angelegt
  (`mkdir -p`), falls er noch nicht existiert.
- README um Anleitung zum Wiederherstellen eines Backups sowie Empfehlungen
  für zusätzliche externe Sicherungen ergänzt.
- Betrifft ausschließlich den Docker/Unraid-Betrieb. Für Strato/XAMPP verweist
  die README auf die dort jeweils angebotenen Backup-Möglichkeiten.
- Alle Fälle (Erfolg, Fehlschlag, fehlender Zielordner, beide Dump-Kommando-
  Varianten) mit einem echten MariaDB-Server nachgestellt und verifiziert,
  bevor es ausgeliefert wurde.

## v1.19.3 – Kritischer Fix: Verlauf blieb nach einem Fehlschlag dauerhaft leer
- **Hauptursache gefunden und behoben**: Schlug das Laden des Verlaufs einmal
  fehl (z. B. durch einen kurzen Netzwerk- oder Serverfehler), merkte sich
  die App das fälschlich als „bereits erledigt" und versuchte es **nie
  wieder erneut** - der Verlauf blieb dadurch dauerhaft leer bzw. veraltet
  stehen, obwohl im Hintergrund weiter normal gebucht wurde. Betrifft nur die
  Anzeige, keine echten Datenverluste: alle Buchungen waren die ganze Zeit
  korrekt in der Datenbank gespeichert.
- Ein fehlgeschlagener Ladeversuch löst jetzt beim nächsten Öffnen des
  Verlaufs automatisch einen neuen Versuch aus; bleibt der Fehler bestehen,
  erscheint ein eigener Hinweis mit „Erneut versuchen"-Button statt einer
  stillen, dauerhaft leeren Ansicht.
- **`api.php` außerdem grundlegend abgesichert**: Alle Datenbank-Aktionen
  laufen jetzt in einer zentralen Fehlerbehandlung. Ein unerwarteter
  Datenbankfehler führt dadurch immer zu einer sauberen, für die App
  verständlichen Fehlermeldung (und einem nachvollziehbaren Eintrag im
  Server-Log) statt zu einer rohen PHP-Fehlerausgabe oder einer scheinbar
  erfolgreichen Antwort ohne tatsächlich gespeicherte Daten.
- Mit einem echten Server nachgestellt und verifiziert (provozierter
  Datenbankfehler, Wiederherstellung ohne Endlosschleife).

## v1.19.4 – Summenanzeige im Verlauf
- Über der Buchungsliste im Verlauf stehen jetzt drei Summen: **Bestellungen
  (Umsatz)**, **Zahlungen erhalten** und **Korrekturen** - berechnet direkt
  in der Datenbank passend zum aktuell gesetzten Filter (Mitglied, Art,
  Zeitraum), nicht nur über die gerade geladene Seite.
- Funktioniert mit „Weitere laden": die Summen bleiben korrekt, auch wenn
  noch nicht alle passenden Buchungen im Browser geladen sind.
- Mit echten Testdaten verifiziert, inklusive kombiniertem Mitglieds- und
  Zeitraumfilter.

## v1.19.5 – Regression behoben: Verlauf blieb nach Buchung veraltet stehen
- **Ursache**: Die "nicht endlos wiederholen"-Bremse aus v1.19.3 hat beim
  Invalidieren nach einer neuen Buchung nur eine von zwei internen Sperren
  zurückgesetzt. Dadurch hielt sich der Verlauf **innerhalb derselben
  Sitzung** fälschlich für aktuell und zeigte neue Buchungen erst nach einem
  kompletten Neuladen der Seite an - genau das war v1.19.3 eigentlich
  gedacht zu verhindern, hat es hier aber selbst ausgelöst.
- Beide Sperren werden nach einer Buchung/Zahlung jetzt korrekt
  zurückgesetzt, der Verlauf lädt sich beim nächsten Öffnen wieder wie
  vorgesehen automatisch neu.
- Mit einer gezielten Zustands-Simulation verifiziert (alter Code
  reproduzierbar fehlerhaft, neuer Code korrekt in allen drei Schritten:
  laden → buchen → erneut laden).

## v1.19.6 – Summenanzeige im Verlauf nur für Admin/Ersteller
- Die drei Summenkarten (Bestellungen, Zahlungen, Korrekturen) über der
  Verlaufsliste sind jetzt nur sichtbar, wenn mit Admin- oder Ersteller-PIN
  angemeldet - mit reinem Theke-PIN bleiben sie ausgeblendet.

## v1.20.0 – Modulverwaltung (nur Ersteller)
- **Neuer Bereich „Module"**, sichtbar und bedienbar ausschließlich mit dem
  Ersteller-PIN - erscheint nicht einmal in der Navigation für Admin- oder
  Theke-PIN-Nutzer. Dafür wird jetzt intern unterschieden, ob mit Admin- oder
  mit Ersteller-PIN angemeldet wurde (bisher gab es dafür keinen eigenen
  Status).
- Fünf Funktionen lassen sich dort global ein- oder ausschalten, wirkt sich
  sofort auf alle Nutzer:innen aus:
  - **Barcode-/Kamera-Anmeldung** (Theke, Sperrbildschirm, Mitglieder-Barcode)
  - **Korrekturbuchungen** (negative Beträge im Zahlungsdialog)
  - **Verlauf-Filter**
  - **Summenanzeige im Verlauf**
  - **Vollbildmodus-Button**
- Bestehende Installationen bekommen die fehlende Modul-Konfiguration beim
  ersten Laden automatisch mit Standardwerten (alles aktiviert) ergänzt -
  keine manuelle Nacharbeit nötig.
- Als Grundlage für künftige Funktionen gedacht: neue Features bekommen hier
  künftig einfach einen weiteren Schalter dazu.
- Mit einem echten Rendering-Test (jsdom) verifiziert: alle Module über die
  Benutzeroberfläche einzeln deaktiviert, anschließend fehlerfrei durch alle
  Bereiche der App geklickt; Theke-PIN-Login sieht den Modul-Bereich
  nachweislich nicht.

## v1.21.0 – Materialwirtschaft (neues Modul)
- **Neuer Bereich „Material"** für Admin/Ersteller: Gebinde je Produkt anlegen
  (z. B. „Kasten" mit 20 Flaschen), Start-Lagerbestand erfassen und eine
  Nachbestellschwelle festlegen.
- **Automatischer Abzug bei Verkauf**: Jede Bestellung an der Theke reduziert
  den Lagerbestand des verknüpften Produkts automatisch um die verkaufte
  Menge - ganz ohne manuellen Zusatzschritt.
- **Wareneingang erfassen**: Anzahl eingekaufter Gebinde eintragen, der
  Bestand wird automatisch in Einzeleinheiten umgerechnet und hochgezählt.
- **Bestandskorrektur**: manuelle Anpassung für Inventuren, Bruch oder Schwund.
- **Differenz-/Schwundkontrolle**: Jede Bestandskorrektur mit Abweichung wird
  protokolliert (Datum, Produkt, erwarteter vs. gezählter Bestand, Differenz,
  optionale Notiz). Zeigt konkret, ob weniger vorhanden war als laut
  gebuchten Verkäufen erwartet - also ob nicht alles gebucht wurde, was
  entnommen wurde. Inklusive kumulierter Gesamtsumme und geschätztem
  Gegenwert in Euro (fehlende Einheiten × Produktpreis).
- **Nachbestell-Hinweis**: Positionen unter der Schwelle werden rot als
  „Nachbestellen!" markiert; zusätzlich zeigt der „Material"-Tab in der
  Navigation ein kleines Zahlen-Badge mit der Anzahl betroffener Positionen.
- Als eigenes, abschaltbares Modul umgesetzt (Schalter in der
  Modulverwaltung, Standard: aktiviert) - lässt sich bei Bedarf über
  „Module" ausblenden.
- Mit einem vollständigen End-to-End-Test (jsdom) verifiziert: Gebinde
  anlegen → echten Verkauf über die Theke-Oberfläche buchen → korrekter
  automatischer Abzug → Wareneingang → manuelle Korrektur mit Differenz
  (60 → 50, Protokoll-Eintrag „−10", geschätzter Gegenwert korrekt berechnet)
  → Nachbestell-Status und Tab-Badge in beiden Fällen (OK/Nachbestellen)
  korrekt.

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
3. **Ersteller-PIN** – zusätzlich: kompletten Buchungsverlauf löschen, Modulverwaltung

PIN-Prüfung läuft seit v1.18.3 ausschließlich serverseitig (`api.php`); die
PIN-Werte selbst verlassen die Datenbank nie in Richtung Browser.

## v1.22.0 – Mehrere Vereine aus einem Repo (Tennis + Fußball)
- **Branding aus Konfiguration statt aus dem Code**: Vereinsname, Farben und
  Logo stehen nicht mehr fest in `index.html`, sondern in
  `clubs/<verein>/branding.json` und `clubs/<verein>/logo.png`. `index.html`
  ist damit für alle Vereine identisch.
- Neuer Verein **SG Germania Binsfeld Fußball** (`clubs/fussball/`), Farben
  identisch zum Tennisverein. Ohne `logo.png` läuft die App sauber ohne Logo
  (Header-Badge, Sperrbildschirm, Hintergrund-Wasserzeichen entfallen).
- Titel, Theme-Color, Footer und Web-App-Manifest werden zur Laufzeit aus dem
  Branding erzeugt. Der Vereinsname kommt immer aus `branding.json` (ein evtl.
  in der Datenbank gespeicherter alter Name wird überschrieben).
- `docker-compose.yml` ist parametrisiert (Projektname, Port, Datenordner,
  Branding-Ordner über `clubs/<verein>/.env`): pro Verein ein eigener Stack mit
  eigener Datenbank, eigenen PINs, eigenem Port und eigenem Backup-Ordner.
  Die App-Dateien werden jetzt einzeln aus dem Repo eingehängt (kein `app/`-
  Unterordner mehr nötig).
- Der Tennis-Stack behält seine Container-Namen (`tresen-db`, `tresen-web`,
  `tresen-db-backup`) und Datenpfade (`/mnt/user/appdata/tresen`).
- Neue README-Anleitung "Neuen Verein hinzufügen".

## v1.23.0 – Mitglieder-Reihenfolge speichern
- Im Tab **Mitglieder** lässt sich die aktuelle Reihenfolge mit
  „💾 Reihenfolge speichern“ merken. Nach dem Umsortieren (▲▼, Vorname/
  Nachname A–Z) bringt „↩ Gespeicherte Reihenfolge“ die Liste wieder in
  genau diesen Zustand zurück.
- Neu angelegte Mitglieder erscheinen danach am Ende, gelöschte werden
  übersprungen. Es gibt eine gespeicherte Reihenfolge pro Verein; erneutes
  Speichern ersetzt sie (nach Rückfrage).

## v1.23.1 – Reihenfolge speichern nur für Ersteller
- „💾 Reihenfolge speichern“ ist jetzt nur mit dem Ersteller-PIN sichtbar und
  bedienbar. „↩ Gespeicherte Reihenfolge“ steht weiterhin auch dem Admin zur
  Verfügung.

## v1.24.0 – Benutzergruppen mit einzeln vergebbaren Rechten
- Neuer Tab **Benutzer** (nur Ersteller): eigene **Gruppen** anlegen und jeder
  Gruppe einzelne Rechte geben, dazu **Benutzer** mit **persönlichem PIN** in
  eine Gruppe einordnen (anlegen, PIN neu vergeben, deaktivieren, löschen).
- Vergebbare Rechte: Übersicht & Zahlungen · Mitglieder & Produkte verwalten ·
  Summen im Verlauf sehen · Materialwirtschaft nutzen · Reihenfolge/Filter
  speichern · Module aktivieren/deaktivieren.
- Theke, Admin und Ersteller bleiben als feste Gruppen mit ihren gemeinsamen
  PINs bestehen (Theke: nur Buchen; Admin: Zahlungen, Verwaltung, Summen,
  Material; Ersteller: alles). Der Anmeldebildschirm akzeptiert jeden dieser PINs.
- Ein PIN darf nur einmal vergeben sein (auch nicht doppelt zu Theke/Admin/
  Ersteller); persönliche PINs haben mindestens 4 Zeichen.
- Gesperrte Tabs zeigen weiterhin das 🔒; der PIN-Dialog akzeptiert jetzt jeden
  PIN, dessen Gruppe das nötige Recht hat.
- Sicherheit: Gruppen und Benutzer lassen sich nur mit dem Ersteller-PIN
  ändern (serverseitig geprüft). Die persönlichen PINs sind wie die übrigen
  PINs nie über die API lesbar. Wie bisher gilt: Das Ausblenden von Funktionen
  in der Oberfläche ist ein pragmatischer Schutz, kein Ersatz für echte
  Benutzerkonten.
- Neuer Ersteller-Hinweis: Ohne Ersteller-PIN lässt sich der Tab nicht öffnen.

