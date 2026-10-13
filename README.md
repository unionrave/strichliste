# Vereinstresen – SG Germania Binsfeld (Tennis & Fußball)

Digitale Strichliste für den Vereinstresen (je Verein eine eigene Instanz) (inspiriert von tinyTresen).
Mitglieder bestellen an der Theke, alles wird auf ihr Konto gebucht, und später
per Bar/Überweisung/SEPA abgerechnet – ohne Papierliste.

Die vollständige Entstehungsgeschichte und alle Änderungen stehen in
[`CHANGELOG.md`](./CHANGELOG.md).

## Funktionen

- **Theke** – Mitglied auswählen, Bestellung im großflächigen Overlay
  zusammenstellen, auf Konto buchen
- **Übersicht** – alle Salden, Zahlungen (Bar/Überweisung/SEPA) erfassen
- **Mitglieder** – anlegen (Vor-/Nachname getrennt), bearbeiten,
  aktiv/inaktiv setzen, frei sortierbar (▲▼ oder A–Z je Feld)
- **Produkte** – anlegen, bearbeiten (Name/Kategorie/Preis), aktiv/inaktiv
  setzen, frei sortierbar (▲▼ oder A–Z)
- **Verlauf** – vollständige Buchungshistorie, inkl. Ersteller-geschütztem
  Zurücksetzen
- **Dreistufiger PIN-Schutz** – Theke / Admin / Ersteller, wobei der
  Ersteller-PIN zusätzlich alle Admin-Rechte besitzt (siehe Changelog)
- Responsive & touch-optimiert (u. a. verlässliches 3-Spalten-Layout im
  Tablet-Hochformat), feste Kopf-/Fußzeile ohne Seiten-Scroll
- Vereinsfarben & -logo, für Tablet-Vollbildbetrieb (z. B. Fire HD 10 im
  Fully Kiosk Browser) optimiert
- **Barcode-/QR-Scan per Kamera** – Mitgliedsauswahl an der Theke und
  PIN-Anmeldung lassen sich per Kartenscan statt Tippen erledigen
  (benötigt HTTPS-Zugriff)

## Aufbau

```
index.html          Die komplette App (UI + Logik) als einzelne Datei – für alle Vereine gleich
clubs/<verein>/      Vereinsspezifisch: branding.json, logo.png, .env(.example)
api.php              Backend: einfacher Key-Value-Speicher über MySQL/MariaDB
config.php            Datenbank-Zugangsdaten
docker-compose.yml    Betrieb als drei Container (App + MariaDB + Backup), z. B. auf Unraid
web/Dockerfile        PHP/Apache-Image mit pdo_mysql-Erweiterung + Gzip
web/deflate.conf       Apache-Konfiguration für Gzip-Kompression (Docker)
.htaccess             Gzip-Kompression für Strato/XAMPP (ohne Docker)
backup.sh             Tägliches automatisches Datenbank-Backup (Docker)
clubs/<verein>/.env   Zugangsdaten/Port/Datenordner je Verein (nicht im Git)
```

## Betrieb – drei Wege

### 1. Eigener Webspace (z. B. Strato)
`index.html`, `api.php`, `config.php` **und `.htaccess`** per FTP hochladen,
in `config.php` die Datenbank-Zugangsdaten eintragen, Seite aufrufen – die
Tabellen werden beim ersten Laden automatisch angelegt. Die `.htaccess`
aktiviert Gzip-Kompression (kleinere Übertragung, spürbar bei langsamer
Internetverbindung).

### 2. Lokal (XAMPP)
XAMPP installieren, Apache + MySQL/MariaDB starten, leere Datenbank per
phpMyAdmin anlegen, Dateien (inkl. `.htaccess`) nach `htdocs/tresen/`
kopieren, `config.php` anpassen (`host: localhost`, `user: root`,
`password: ''`).

### 3. Unraid (Docker Compose) – ein Stack pro Verein
Repo auf den Server legen (z. B. `/mnt/user/appdata/tresen-repo`), dann je Verein:
```bash
cp clubs/tennis/.env.example clubs/tennis/.env     # Passwörter eintragen
docker compose -p tresen --env-file clubs/tennis/.env up -d --build

cp clubs/fussball/.env.example clubs/fussball/.env # andere Passwörter!
docker compose -p tresen-fussball --env-file clubs/fussball/.env up -d --build
```
Jeder Verein bekommt eigene Container, eigene Datenbank, eigene PINs, einen
eigenen Port (`WEB_PORT`, Tennis 8090 / Fußball 8091) und einen eigenen
Datenordner mit Backups. Gzip ist im Dockerfile bereits aktiviert.

## Neuen Verein hinzufügen
1. `clubs/tennis/` nach `clubs/<neuer-verein>/` kopieren.
2. In `branding.json` `clubName` (und ggf. Farben) anpassen, `logo.png` ersetzen
   (fehlt das Logo, läuft die App ohne).
3. `.env.example` → `.env` kopieren und anpassen: `CLUB=<neuer-verein>`,
   `COMPOSE_PROJECT_NAME`, **eigener `WEB_PORT`**, eigener `DATA_DIR`, neue
   Passwörter und eigener `MYSQL_DATABASE`-Name.
4. Starten: `docker compose -p <projektname> --env-file clubs/<neuer-verein>/.env up -d --build`
5. Im Nginx Proxy Manager einen Proxy Host auf `<Unraid-IP>:<WEB_PORT>` anlegen
   (eigene Subdomain je Verein). Beim ersten Aufruf die PINs einrichten.

Logo oder Farben später ändern: Datei im Vereinsordner austauschen und die
Seite neu laden – kein Neustart nötig. Bei Strato/XAMPP wird der Vereinsordner
als `branding/` neben `index.html` hochgelegt.

**Umstieg bestehender Tennis-Installation:** Container-Namen und Datenpfade
bleiben gleich; `clubs/tennis/.env` aus der bisherigen `.env` übernehmen und
`CLUB=tennis`, `COMPOSE_PROJECT_NAME=tresen`, `WEB_PORT=8090`,
`DATA_DIR=/mnt/user/appdata/tresen` ergänzen. Den Stack zuvor stoppen.

## Backups

Nur im Docker-Betrieb (Unraid): ein dritter Container (`db-backup`) erstellt
automatisch einmal täglich einen komprimierten Datenbank-Dump und legt ihn
unter `<DATA_DIR>/backups` ab (Tennis: `/mnt/user/appdata/tresen/backups`). Ältere Backups als 30 Tage
werden automatisch gelöscht (einstellbar über `RETENTION_DAYS` in
`docker-compose.yml`).

**Wiederherstellen** (Beispiel, Dateiname anpassen):
```bash
gunzip -c /mnt/user/appdata/tresen/backups/tresen_20260101_030000.sql.gz | \
  docker exec -i tresen-db mysql -u root -p"$MYSQL_ROOT_PASSWORD" tresen
```

**Empfehlung**: Die Backups liegen auf demselben Server wie die Datenbank
selbst - schützt vor einem kaputten Datenbank-Volume, aber nicht vor einem
Totalausfall des Servers (Diebstahl, Defekt, Feuer). Für zusätzliche
Sicherheit den `backups`-Ordner regelmäßig auf ein anderes Gerät oder einen
Cloud-Speicher kopieren, z. B. per Unraid-eigenem Backup-Plugin oder einem
einfachen `rsync`/`rclone`-Cronjob.

Wer die App auf einem eigenen Webspace (Strato) oder lokal (XAMPP) betreibt,
sollte Backups stattdessen über die dort angebotenen Mittel einrichten
(bei Strato z. B. den phpMyAdmin-Export oder ein im Kundenmenü verfügbares
automatisches Datenbank-Backup) - der `db-backup`-Container ist Docker-
spezifisch.

## Sicherheit

PINs werden ausschließlich serverseitig geprüft (`api.php`) - der Browser
bekommt nie einen der drei PIN-Werte selbst zu sehen, nur das Ergebnis der
Prüfung. Das ist trotzdem ein pragmatischer, kein kryptographisch gehärteter
Schutz für den Vereinskontext - ausreichend, um zufällige Mitglieder von der
Verwaltung fernzuhalten, aber kein Ersatz für echte Benutzerkonten.

---

Erstellt von Marco Weber für SG Germania Binsfeld.
