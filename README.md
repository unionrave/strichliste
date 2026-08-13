# Vereinstresen – SG Germania Binsfeld Tennis

Digitale Strichliste für den Tennis-Vereinstresen (inspiriert von tinyTresen).
Mitglieder bestellen an der Theke, alles wird auf ihr Konto gebucht, und später
per Bar/Überweisung/SEPA abgerechnet – ohne Papierliste.

Die vollständige Entstehungsgeschichte und alle Änderungen stehen in
[`CHANGELOG.md`](./CHANGELOG.md).

## Funktionen

- **Theke** – Mitglied auswählen, Produkte antippen, auf Konto buchen
- **Übersicht** – alle Salden, Zahlungen (Bar/Überweisung/SEPA) erfassen
- **Mitglieder** – anlegen, bearbeiten, aktiv/inaktiv setzen
- **Produkte** – anlegen, bearbeiten (Name/Kategorie/Preis), löschen
- **Verlauf** – vollständige Buchungshistorie, inkl. geschütztem Zurücksetzen
- **Dreistufiger PIN-Schutz** – Theke / Admin / Ersteller (siehe Changelog)
- Vereinsfarben & -logo, für Tablet-Vollbildbetrieb (z. B. Fire HD 10) optimiert

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
