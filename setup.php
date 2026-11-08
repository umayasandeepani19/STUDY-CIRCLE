<?php
// One-time installer: creates the database, tables and starter data.
// Works on XAMPP/WAMP. On InfinityFree-type hosts the database already exists,
// so just import database/studycircle.sql with phpMyAdmin instead (or run this too).
require_once __DIR__ . '/config/config.php';

$log = []; $ok = false;
try {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $log[] = 'Connected to existing database "' . DB_NAME . '".';
    } catch (PDOException $e) {
        // database may not exist yet -> try to create it (works with XAMPP root user)
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '', DB_NAME) . '`');
        $log[] = 'Created database "' . DB_NAME . '".';
    }
    $sql = file_get_contents(__DIR__ . '/database/studycircle.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);               // strip comment lines
    $statements = preg_split('/;\s*[\r\n]+/', trim($sql) . "\n");
    $n = 0;
    foreach ($statements as $s) {
        $s = trim($s);
        if ($s === '') continue;
        $pdo->exec($s);
        $n++;
    }
    $log[] = "Ran $n SQL statements (tables + starter data).";
    $users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $log[] = "Users in database: $users.";
    if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0755, true); }
    $log[] = is_writable(UPLOAD_DIR) ? 'Uploads folder is writable.' : 'WARNING: uploads/ folder is not writable - uploads will fail until you fix permissions.';
    $ok = true;
} catch (Throwable $e) {
    $log[] = 'ERROR: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>StudyCircle setup</title>
<style>body{font-family:system-ui,sans-serif;max-width:620px;margin:50px auto;padding:0 20px;color:#12243a}
li{margin:6px 0}.ok{color:#0a6b48}.bad{color:#b42318}a.b{display:inline-block;background:#0f8a5f;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600}
code{background:#eef3f7;padding:2px 6px;border-radius:4px}</style></head><body>
<h1 class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'Setup complete' : 'Setup failed' ?></h1>
<ul><?php foreach ($log as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
<?php if ($ok): ?>
<p><b>Admin:</b> <code>admin@nsbm.ac.lk</code> / <code>Admin@123</code><br><b>Student:</b> <code>student@nsbm.ac.lk</code> / <code>Student@123</code></p>
<p><a class="b" href="index.php">Open StudyCircle</a></p>
<p class="bad"><b>Important:</b> delete <code>setup.php</code> from the server before you share the live link.</p>
<?php else: ?>
<p>Check that MySQL is running and that the values in <code>config/config.php</code> are correct, then refresh this page.</p>
<?php endif; ?>
</body></html>
