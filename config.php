<?php
// Die Werte kommen aus den Umgebungsvariablen, die docker-compose.yml aus
// der .env-Datei an diesen Container weiterreicht (siehe "environment:"
// beim web-Service). Die Zugangsdaten stehen damit nur an einer Stelle: .env
return [
    'host'     => getenv('DB_HOST') ?: 'db',
    'dbname'   => getenv('DB_NAME') ?: 'tresen',
    'user'     => getenv('DB_USER') ?: 'tresen',
    'password' => getenv('DB_PASSWORD') ?: '',
];
