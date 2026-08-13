<?php
// Einfacher Key-Value-Speicher als Ersatz für window.storage,
// damit die Tresen-App auf eurem eigenen Webspace läuft.
// Läuft mit MySQL genauso wie mit MariaDB (PDO_MYSQL-Treiber ist zu beiden
// wire-kompatibel, daher bleibt das DSN-Präfix "mysql:").

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';

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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    if ($key === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing_key']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT v FROM kv_store WHERE k = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }
    echo json_encode(['key' => $key, 'value' => $row['v']]);
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
    $key = isset($input['key']) ? $input['key'] : '';
    if ($key === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing_key']);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM kv_store WHERE k = ?");
        $stmt->execute([$key]);
        echo json_encode(['key' => $key, 'deleted' => true]);
        exit;
    }

    $value = isset($input['value']) ? $input['value'] : '';
    $stmt = $pdo->prepare(
        "INSERT INTO kv_store (k, v) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE v = VALUES(v)"
    );
    $stmt->execute([$key, $value]);
    echo json_encode(['key' => $key, 'value' => $value]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);
