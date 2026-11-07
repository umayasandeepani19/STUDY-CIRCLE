<?php
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:system-ui;max-width:640px;margin:60px auto;padding:0 20px">';
        echo '<h2 style="color:#0a5c40">Database connection failed</h2>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<ol><li>Start <b>Apache</b> and <b>MySQL</b> in XAMPP.</li>';
        echo '<li>Check the values in <code>config/config.php</code> (or <code>config/config.local.php</code> on live hosting).</li>';
        echo '<li>Open <a href="' . htmlspecialchars(BASE_URL) . '/setup.php">setup.php</a> once to create the database and tables.</li></ol></body>';
        exit;
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function rows(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function row(string $sql, array $params = []) { $r = q($sql, $params)->fetch(); return $r === false ? null : $r; }
function val(string $sql, array $params = []) { $v = q($sql, $params)->fetchColumn(); return $v === false ? null : $v; }
