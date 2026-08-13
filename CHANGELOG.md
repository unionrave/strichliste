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

---

## Architektur-Überblick (Stand heute)

| Baustein | Zweck |
|---|---|
| `index.html` | komplette Anwendung (UI + Logik), eine einzelne Datei |
| `api.php` | Key-Value-Backend (GET/POST) über MySQL/MariaDB |
| `config.php` | Datenbank-Zugangsdaten (lokal) bzw. liest ENV-Variablen (Docker) |
| `docker-compose.yml` + `web/Dockerfile` | Betrieb als zwei Container (App + DB) auf Unraid |
| `.env` | Zugangsdaten für den Docker-Compose-Stack |

## Drei-Ebenen-PIN-Modell

1. **Theke-PIN** – Zugang zur App allgemein (Bestellungen buchen, Verlauf ansehen)
2. **Admin-PIN** – zusätzlich: Übersicht/Zahlungen, Mitglieder, Produkte verwalten
3. **Ersteller-PIN** – ausschließlich: kompletten Buchungsverlauf löschen
