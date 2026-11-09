<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id'];
$r = row('SELECT * FROM resources WHERE id = ?', [get_int('id')]);
if (!$r || ($r['status'] !== 'approved' && (int) $r['user_id'] !== $uid && !is_admin())) {
    flash('error', 'That file is not available.');
    redirect('student/resources.php');
}
$path = UPLOAD_DIR . basename($r['file_path']);
if (!is_file($path)) {
    flash('error', 'The file is missing on the server. Ask the uploader to upload it again.');
    redirect('student/resource_view.php?id=' . (int) $r['id']);
}
if ((int) $r['user_id'] !== $uid) {
    q('INSERT INTO downloads (resource_id, user_id) VALUES (?,?)', [$r['id'], $uid]);
    q('UPDATE resources SET downloads = downloads + 1 WHERE id = ?', [$r['id']]);
}
$name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $r['file_name']);
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
