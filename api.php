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

$method = $_SERVER['REQUEST_METHOD'];

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
