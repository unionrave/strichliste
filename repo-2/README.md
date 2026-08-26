# Vereinstresen – SG Germania Binsfeld Tennis

Digitale Strichliste für den Tennis-Vereinstresen (inspiriert von tinyTresen).
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
index.html          Die komplette App (UI + Logik) als einzelne Datei
api.php              Backend: einfacher Key-Value-Speicher über MySQL/MariaDB
config.php            Datenbank-Zugangsdaten
docker-compose.yml    Betrieb als zwei Container (App + MariaDB), z. B. auf Unraid
web/Dockerfile        PHP/Apache-Image mit pdo_mysql-Erweiterung
.env                  Zugangsdaten für den Docker-Compose-Stack
```

## Betrieb – drei Wege

### 1. Eigener Webspace (z. B. Strato)
`index.html`, `api.php`, `config.php` per FTP hochladen, in `config.php` die
Datenbank-Zugangsdaten eintragen, Seite aufrufen – die Tabelle wird beim ersten
Laden automatisch angelegt.

### 2. Lokal (XAMPP)
XAMPP installieren, Apache + MySQL/MariaDB starten, leere Datenbank per
phpMyAdmin anlegen, Dateien nach `htdocs/tresen/` kopieren, `config.php`
anpassen (`host: localhost`, `user: root`, `password: ''`).

### 3. Unraid (Docker Compose)
`docker-compose.yml`, `web/Dockerfile` und die App-Dateien wie im Repo
strukturiert ablegen, `.env` mit echten Passwörtern befüllen, per Docker
Compose Manager (oder `docker compose up -d`) starten.

## Sicherheit

Der PIN-Schutz ist ein pragmatischer, clientseitiger Schutz für den
Vereinskontext – ausreichend, um zufällige Mitglieder von der Verwaltung
fernzuhalten, aber keine kryptographische Absicherung gegen gezielte Angriffe
auf Quellcode/Netzwerkverkehr.

---

Erstellt von Marco Weber für SG Germania Binsfeld Tennis.
