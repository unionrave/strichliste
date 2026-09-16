#!/bin/sh
# Läuft dauerhaft im db-backup-Container: erstellt beim Start sofort einen
# Dump und danach alle 24 Stunden erneut. Alte Backups werden automatisch
# nach RETENTION_DAYS gelöscht, damit der Speicherplatz nicht unbegrenzt
# wächst. Das Passwort steht bewusst in der Umgebungsvariable MYSQL_PWD statt
# als Kommandozeilen-Argument, damit es nicht in der Prozessliste auftaucht.
#
# WICHTIG: Dump und Komprimierung laufen bewusst im lokalen, ephemeren
# Container-Dateisystem (/tmp) statt direkt auf dem gemounteten Unraid-Share.
# Auf Unraid-User-Shares (FUSE-Dateisystem "shfs") kann das Löschen einer
# Datei kurz nachdem sie geschrieben wurde dazu führen, dass Unraid sie nicht
# richtig entfernt, sondern stattdessen in eine ".fuse_hiddenXXXX"-Datei
# umbenennt. Deshalb wird auf den gemounteten Ordner (/backups) nur noch
# EINMALIG die fertige, bereits komplette Datei kopiert - dort wird nichts
# mehr geschrieben-und-wieder-gelöscht.
set -u

RETENTION_DAYS="${RETENTION_DAYS:-30}"
BACKUP_DIR="/backups"
TMP_DIR="/tmp/tresen-backup"
mkdir -p "${TMP_DIR}" "${BACKUP_DIR}"

# Je nach MariaDB-Image/Version heißt das Dump-Tool "mariadb-dump" (aktueller
# Name) oder "mysqldump" (älterer, nicht überall noch vorhandener
# Kompatibilitäts-Alias) - beim Start einmal ermitteln, welches vorhanden ist.
if command -v mariadb-dump >/dev/null 2>&1; then
  DUMP_CMD="mariadb-dump"
elif command -v mysqldump >/dev/null 2>&1; then
  DUMP_CMD="mysqldump"
else
  echo "FEHLER: Weder 'mariadb-dump' noch 'mysqldump' im Container gefunden - Backup nicht möglich." >&2
  exit 1
fi
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Verwende Dump-Kommando: ${DUMP_CMD}"

while true; do
  TIMESTAMP=$(date +%Y%m%d_%H%M%S)
  LOCAL_RAW="${TMP_DIR}/tresen_${TIMESTAMP}.sql"
  LOCAL_GZ="${LOCAL_RAW}.gz"
  LOCAL_ERR="${TMP_DIR}/last_error.log"
  FINAL_FILE="${BACKUP_DIR}/tresen_${TIMESTAMP}.sql.gz"

  # Bewusst NICHT direkt in gzip pipen: der Exit-Code einer Pipe ist in "sh"
  # der des LETZTEN Befehls (gzip), nicht vom Dump-Kommando - ein
  # fehlgeschlagener Dump würde dadurch unbemerkt als "erfolgreich"
  # durchgehen. Deshalb erst in eine lokale Datei schreiben und deren
  # Exit-Code selbst prüfen.
  if "${DUMP_CMD}" -h db -u root "${MYSQL_DATABASE}" > "${LOCAL_RAW}" 2>"${LOCAL_ERR}"; then
    gzip -f "${LOCAL_RAW}"
    if cp "${LOCAL_GZ}" "${FINAL_FILE}"; then
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup erfolgreich: ${FINAL_FILE} ($(du -h "${FINAL_FILE}" | cut -f1))"
    else
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] FEHLER: Dump erstellt, aber Kopieren nach ${BACKUP_DIR} fehlgeschlagen (Ordner vorhanden? Berechtigungen?)." >&2
    fi
    rm -f "${LOCAL_GZ}" "${LOCAL_ERR}"
  else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] FEHLER beim Dump:" >&2
    cat "${LOCAL_ERR}" >&2
    rm -f "${LOCAL_RAW}"
  fi

  # Backups älter als RETENTION_DAYS Tage löschen (nur fertige, alte Dateien -
  # deutlich unkritischer für das .fuse_hidden-Problem als frisch geschriebene).
  find "${BACKUP_DIR}" -name "tresen_*.sql.gz" -mtime "+${RETENTION_DAYS}" -delete 2>/dev/null

  sleep 86400
done
