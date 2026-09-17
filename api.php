<?php
// Einfacher Key-Value-Speicher als Ersatz für window.storage,
// damit die Tresen-App auf eurem eigenen Webspace läuft.
// Läuft mit MySQL genauso wie mit MariaDB (PDO_MYSQL-Treiber ist zu beiden
// wire-kompatibel, daher bleibt das DSN-Präfix "mysql:").
//
// WICHTIG (Sicherheit): Der Eintrag "auth" (enthält Theke-/Admin-/Ersteller-PIN)
// ist NIEMALS über den normalen Key-Value-Zugriff lesbar oder direkt
// beschreibbar - weder per GET noch per generischem POST. PIN-Prüfung und
// -Änderung laufen ausschließlich über die drei dedizierten Aktionen weiter
// unten (auth_status, verify_pin, set_auth), die die PIN-Werte selbst nie an
// den Client zurückgeben.

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';
const AUTH_KEY = 'auth';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_connection_failed']);
    exit;
}

// Tabelle wird beim ersten Aufruf automatisch angelegt.
$pdo->exec("CREATE TABLE IF NOT EXISTS kv_store (
    k VARCHAR(190) PRIMARY KEY,
    v LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Buchungen liegen NICHT im Key-Value-Speicher, sondern in einer eigenen
// Tabelle mit einer Zeile pro Buchung. Das hält jede einzelne neue Buchung
// schnell, auch wenn die Historie über Jahre auf mehrere tausend Einträge
// anwächst - anders als ein einzelner, bei jeder Änderung komplett neu
// geschriebener JSON-Textblock.
$pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
    id VARCHAR(40) PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    member_id VARCHAR(40) NOT NULL,
    ts BIGINT NOT NULL,
    total DECIMAL(10,2) NULL,
    amount DECIMAL(10,2) NULL,
    method VARCHAR(30) NULL,
    note TEXT NULL,
    items LONGTEXT NULL,
    INDEX idx_member (member_id),
    INDEX idx_ts (ts),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function kv_get($pdo, $key) {
    $stmt = $pdo->prepare("SELECT v FROM kv_store WHERE k = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['v'] : null;
}
function kv_set($pdo, $key, $value) {
    $stmt = $pdo->prepare(
        "INSERT INTO kv_store (k, v) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE v = VALUES(v)"
    );
    $stmt->execute([$key, $value]);
}
function get_auth($pdo) {
    $raw = kv_get($pdo, AUTH_KEY);
    return $raw ? json_decode($raw, true) : null;
}
function pin_matches($stored, $given) {
    return !empty($stored) && $given !== '' && hash_equals((string)$stored, (string)$given);
}
function tx_row_to_array($row) {
    return [
        'id' => $row['id'],
        'type' => $row['type'],
        'memberId' => $row['member_id'],
        'ts' => (int)$row['ts'],
        'total' => $row['total'] !== null ? (float)$row['total'] : null,
        'amount' => $row['amount'] !== null ? (float)$row['amount'] : null,
        'method' => $row['method'],
        'note' => $row['note'],
        'items' => $row['items'] ? json_decode($row['items'], true) : null,
    ];
}
// Einmalige, sich selbst erledigende Migration: ältere Installationen hatten
// die komplette Buchungshistorie als ein JSON-Array unter dem kv_store-
// Schlüssel "transactions" gespeichert. Falls das noch vorhanden ist und die
// neue Tabelle noch leer ist, einmalig übernehmen und den alten Eintrag danach
// umbenennen (nicht löschen, für den Fall der Fälle).
function migrate_legacy_transactions_if_needed($pdo) {
    $legacyRaw = kv_get($pdo, 'transactions');
    if ($legacyRaw === null) return;
    $countStmt = $pdo->query("SELECT COUNT(*) AS c FROM transactions");
    $count = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['c'];
    if ($count > 0) return; // schon migriert oder bereits Daten vorhanden
    $legacy = json_decode($legacyRaw, true);
    if (!is_array($legacy)) return;
    $insert = $pdo->prepare(
        "INSERT IGNORE INTO transactions (id, type, member_id, ts, total, amount, method, note, items)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($legacy as $t) {
        $insert->execute([
            $t['id'] ?? uniqid('legacy_', true),
            $t['type'] ?? 'order',
            $t['memberId'] ?? '',
            $t['ts'] ?? 0,
            isset($t['total']) ? $t['total'] : null,
            isset($t['amount']) ? $t['amount'] : null,
            $t['method'] ?? null,
            $t['note'] ?? null,
            isset($t['items']) ? json_encode($t['items']) : null,
        ]);
    }
    // Alten Blob umbenennen statt löschen - dient als Sicherheitsnetz.
    kv_set($pdo, 'transactions_migrated_backup', $legacyRaw);
    $del = $pdo->prepare("DELETE FROM kv_store WHERE k = 'transactions'");
    $del->execute();
}

$method = $_SERVER['REQUEST_METHOD'];

// Ab hier alles in try/catch: jeder unerwartete Datenbankfehler soll als
// saubere JSON-Antwort mit HTTP 500 zurückkommen (und im Server-Log
// nachvollziehbar sein), statt als rohe PHP-Fehlerseite oder - schlimmer -
// als scheinbar erfolgreiche Antwort ohne tatsächlich gespeicherte Daten.
try {

if ($method === 'GET') {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    if ($key === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing_key']);
        exit;
    }
    if ($key === AUTH_KEY) {
        // PINs dürfen nie direkt gelesen werden - nur über die Aktionen unten.
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
    $v = kv_get($pdo, $key);
    if ($v === null) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }
    echo json_encode(['key' => $key, 'value' => $v]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_body']);
        exit;
    }
    $action = isset($input['action']) ? $input['action'] : 'set';

    // ---- Ob überhaupt schon PINs eingerichtet sind / ob es einen Ersteller-PIN gibt.
    // Gibt bewusst nur Wahr/Falsch zurück, nie die PINs selbst.
    if ($action === 'auth_status') {
        $auth = get_auth($pdo);
        echo json_encode([
            'hasAuth' => $auth !== null,
            'hasOwnerPin' => $auth !== null && !empty($auth['ownerPin']),
        ]);
        exit;
    }

    // ---- Einen eingegebenen PIN serverseitig prüfen. Gibt nur die
    // Berechtigungsstufe zurück ('theke' | 'admin' | 'owner' | null), nie
    // einen der gespeicherten PIN-Werte.
    if ($action === 'verify_pin') {
        $pin = isset($input['pin']) ? (string)$input['pin'] : '';
        $auth = get_auth($pdo);
        $level = null;
        if ($auth) {
            if (pin_matches($auth['ownerPin'] ?? null, $pin)) {
                $level = 'owner';
            } elseif (pin_matches($auth['adminPin'] ?? null, $pin)) {
                $level = 'admin';
            } elseif (pin_matches($auth['thekePin'] ?? null, $pin)) {
                $level = 'theke';
            }
        }
        echo json_encode(['level' => $level]);
        exit;
    }

    // ---- PINs anlegen (Ersteinrichtung) oder ändern.
    if ($action === 'set_auth') {
        $existing = get_auth($pdo);
        $isFirstSetup = $existing === null;

        $wantsOwnerOnly = isset($input['ownerPin']) && $input['ownerPin'] !== ''
            && empty($input['thekePin']) && empty($input['adminPin']);
        $isOwnerBootstrap = !$isFirstSetup && $wantsOwnerOnly && empty($existing['ownerPin']);

        if (!$isFirstSetup && !$isOwnerBootstrap) {
            // Bestehende Theke-/Admin-PINs (oder einen bereits gesetzten
            // Ersteller-PIN) ändern verlangt eine gültige Bestätigung mit dem
            // aktuellen Admin- oder Ersteller-PIN - serverseitig geprüft.
            $confirmPin = isset($input['confirmPin']) ? (string)$input['confirmPin'] : '';
            $authorized = pin_matches($existing['ownerPin'] ?? null, $confirmPin)
                || pin_matches($existing['adminPin'] ?? null, $confirmPin);
            if (!$authorized) {
                http_response_code(403);
                echo json_encode(['error' => 'unauthorized']);
                exit;
            }
        }

        $next = $existing ?: ['thekePin' => '', 'adminPin' => '', 'ownerPin' => ''];

        foreach (['thekePin', 'adminPin', 'ownerPin'] as $field) {
            if (!empty($input[$field])) {
                $candidate = (string)$input[$field];
                // Ersteller-PIN muss sich von Theke-/Admin-PIN unterscheiden.
                if ($field === 'ownerPin') {
                    $theke = $input['thekePin'] ?? ($next['thekePin'] ?? '');
                    $admin = $input['adminPin'] ?? ($next['adminPin'] ?? '');
                    if ($candidate === $theke || $candidate === $admin) {
                        http_response_code(400);
                        echo json_encode(['error' => 'owner_pin_must_differ']);
                        exit;
                    }
                }
                $next[$field] = $candidate;
            }
        }

        if (!empty($next['thekePin']) && !empty($next['adminPin']) && $next['thekePin'] === $next['adminPin']) {
            http_response_code(400);
            echo json_encode(['error' => 'theke_admin_must_differ']);
            exit;
        }

        if ($isFirstSetup && (empty($next['thekePin']) || empty($next['adminPin']))) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_setup']);
            exit;
        }

        kv_set($pdo, AUTH_KEY, json_encode($next));
        echo json_encode(['ok' => true]);
        exit;
    }

    // ---- Buchungen: eigene Tabelle statt JSON-Blob ----

    if ($action === 'migrate_transactions') {
        migrate_legacy_transactions_if_needed($pdo);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'add_transaction') {
        $t = isset($input['transaction']) ? $input['transaction'] : null;
        if (!is_array($t) || empty($t['id']) || empty($t['type']) || empty($t['memberId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_transaction']);
            exit;
        }
        $stmt = $pdo->prepare(
            "INSERT INTO transactions (id, type, member_id, ts, total, amount, method, note, items)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $t['id'],
            $t['type'],
            $t['memberId'],
            $t['ts'] ?? (int)(microtime(true) * 1000),
            $t['total'] ?? null,
            $t['amount'] ?? null,
            $t['method'] ?? null,
            $t['note'] ?? null,
            isset($t['items']) ? json_encode($t['items']) : null,
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Aktuellen Saldo je Mitglied direkt per SQL-Summe berechnen, statt die
    // komplette Historie an den Client zu übertragen und dort zu summieren.
    if ($action === 'get_balances') {
        $stmt = $pdo->query(
            "SELECT member_id,
                    SUM(CASE WHEN type = 'order' THEN total
                             WHEN type = 'payment' THEN -amount
                             ELSE 0 END) AS balance
             FROM transactions
             GROUP BY member_id"
        );
        $balances = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $balances[$row['member_id']] = (float)$row['balance'];
        }
        echo json_encode(['balances' => $balances]);
        exit;
    }

    // Gefilterte, mit Limit/Offset seitenweise abrufbare Buchungsliste für den
    // Verlauf - läuft komplett über die Datenbank (Index auf ts/member_id),
    // nicht mehr über einen im Browser gehaltenen Riesen-Array.
    if ($action === 'list_transactions') {
        $where = [];
        $params = [];
        if (!empty($input['memberId'])) {
            $where[] = "member_id = ?";
            $params[] = $input['memberId'];
        }
        if (!empty($input['type'])) {
            if ($input['type'] === 'order') {
                $where[] = "type = 'order'";
            } elseif ($input['type'] === 'payment') {
                $where[] = "type = 'payment' AND amount >= 0";
            } elseif ($input['type'] === 'korrektur') {
                $where[] = "type = 'payment' AND amount < 0";
            }
        }
        if (!empty($input['from'])) {
            $where[] = "ts >= ?";
            $params[] = (int)$input['from'];
        }
        if (!empty($input['to'])) {
            $where[] = "ts <= ?";
            $params[] = (int)$input['to'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM transactions $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['c'];

        // Summen passend zum aktuellen Filter (Mitglied/Art/Zeitraum) -
        // unabhängig von Pagination, damit "Weitere laden" die Summe nicht
        // verändert und sie auch bei nur teilweise geladener Liste stimmt.
        $sumStmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN type = 'order' THEN total ELSE 0 END) AS order_sum,
                SUM(CASE WHEN type = 'payment' AND amount >= 0 THEN amount ELSE 0 END) AS payment_sum,
                SUM(CASE WHEN type = 'payment' AND amount < 0 THEN -amount ELSE 0 END) AS correction_sum
             FROM transactions $whereSql"
        );
        $sumStmt->execute($params);
        $sumRow = $sumStmt->fetch(PDO::FETCH_ASSOC);
        $summary = [
            'orderSum' => (float)($sumRow['order_sum'] ?? 0),
            'paymentSum' => (float)($sumRow['payment_sum'] ?? 0),
            'correctionSum' => (float)($sumRow['correction_sum'] ?? 0),
        ];

        $limit = isset($input['limit']) ? max(1, min(500, (int)$input['limit'])) : 100;
        $offset = isset($input['offset']) ? max(0, (int)$input['offset']) : 0;

        $stmt = $pdo->prepare(
            "SELECT * FROM transactions $whereSql ORDER BY ts DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        $rows = array_map('tx_row_to_array', $stmt->fetchAll(PDO::FETCH_ASSOC));

        echo json_encode(['transactions' => $rows, 'total' => $total, 'summary' => $summary]);
        exit;
    }

    // Kompletten Verlauf leeren - verlangt wie beim PIN-Wechsel eine
    // serverseitig geprüfte Bestätigung mit dem Ersteller-PIN.
    if ($action === 'clear_transactions') {
        $confirmPin = isset($input['confirmPin']) ? (string)$input['confirmPin'] : '';
        $auth = get_auth($pdo);
        if (!$auth || !pin_matches($auth['ownerPin'] ?? null, $confirmPin)) {
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            exit;
        }
        $pdo->exec("DELETE FROM transactions");
        echo json_encode(['ok' => true]);
        exit;
    }

    // ---- Generischer Key-Value-Speicher für alles außer "auth" ----
    $key = isset($input['key']) ? $input['key'] : '';
    if ($key === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing_key']);
        exit;
    }
    if ($key === AUTH_KEY) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM kv_store WHERE k = ?");
        $stmt->execute([$key]);
        echo json_encode(['key' => $key, 'deleted' => true]);
        exit;
    }

    $value = isset($input['value']) ? $input['value'] : '';
    kv_set($pdo, $key, $value);
    echo json_encode(['key' => $key, 'value' => $value]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);

} catch (Throwable $e) {
    // Ins Server-Log schreiben (sichtbar z. B. über "docker logs tresen-web"),
    // aber nur eine generische Fehlermeldung an den Client zurückgeben -
    // keine internen Details/Stacktraces nach außen geben.
    error_log('api.php Fehler: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
