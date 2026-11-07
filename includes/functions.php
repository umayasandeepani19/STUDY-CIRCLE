<?php
/* ---------- basic helpers ---------- */
function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path)
{
    header('Location: ' . url($path));
    exit;
}
function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}
function get_str(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}
function get_int(string $key): int { return isset($_GET[$key]) ? (int) $_GET[$key] : 0; }
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

/* ---------- authentication ---------- */
function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $u = row('SELECT id, full_name, email, student_id, faculty, bio, role, status, created_at FROM users WHERE id = ?', [$_SESSION['uid']]);
        if ($u && $u['status'] === 'active') {
            $user = $u;
        } else {
            unset($_SESSION['uid']);
        }
    }
    return $user;
}
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }
function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
}
function require_login(): void
{
    if (!current_user()) {
        flash('info', 'Please log in to continue.');
        redirect('login.php');
    }
}
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash('error', 'That page is for administrators only.');
        redirect('student/dashboard.php');
    }
}
function home_for(array $u): string { return $u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'; }

/* ---------- formatting ---------- */
function format_bytes(int $b): string
{
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024) return round($b / 1024) . ' KB';
    return $b . ' B';
}
function time_ago(string $ts): string
{
    $d = time() - strtotime($ts);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' h ago';
    if ($d < 2592000) return floor($d / 86400) . ' d ago';
    return date('j M Y', strtotime($ts));
}
function stars(?float $avg, int $count = 0): string
{
    if (!$count) return '<span class="muted small">No ratings yet</span>';
    $full = (int) round($avg);
    $s = '<span class="stars" aria-label="' . h(number_format($avg, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) $s .= '<span class="' . ($i <= $full ? 'on' : '') . '">&#9733;</span>';
    return $s . '</span> <span class="muted small">' . number_format($avg, 1) . ' (' . $count . ')</span>';
}
function badge(string $status): string
{
    $map = ['approved' => 'green', 'pending' => 'amber', 'rejected' => 'red', 'open' => 'blue', 'solved' => 'green',
            'closed' => 'grey', 'active' => 'green', 'blocked' => 'red', 'admin' => 'blue', 'student' => 'grey'];
    return '<span class="badge badge-' . ($map[$status] ?? 'grey') . '">' . h(ucfirst($status)) . '</span>';
}
function initials(string $name): string
{
    $p = preg_split('/\s+/', trim($name));
    $i = strtoupper(substr($p[0], 0, 1));
    if (count($p) > 1) $i .= strtoupper(substr(end($p), 0, 1));
    return $i;
}

/* ---------- file upload ---------- */
function handle_upload(array $file): array
{
    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'png', 'jpg', 'jpeg'];
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please choose a file to upload.');
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('That file is larger than the server allows. Maximum is ' . MAX_UPLOAD_MB . ' MB.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . '). Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_MB * 1048576) {
        throw new RuntimeException('File is too large. Maximum is ' . MAX_UPLOAD_MB . ' MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('File type .' . $ext . ' is not allowed. Use: ' . implode(', ', $allowed) . '.');
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('The uploads folder is missing and could not be created.');
    }
    $stored = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $stored)) {
        throw new RuntimeException('Could not save the file. Check that the uploads folder is writable.');
    }
    return ['path' => $stored, 'name' => mb_substr(basename($file['name']), 0, 200), 'size' => (int) $file['size']];
}
function delete_upload(string $stored): void
{
    $p = UPLOAD_DIR . basename($stored);
    if ($stored !== 'sample_notes.txt' && is_file($p)) {
        @unlink($p);
    }
}

/* ---------- navigation helper ---------- */
function nav_active(string ...$files): string
{
    return in_array(basename($_SERVER['SCRIPT_NAME']), $files, true) ? ' active' : '';
}

/* ---------- pagination (keeps the current filters in the links) ---------- */
function pager(int $total, int $perPage, int $page): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $out = '<nav class="btn-row" style="margin-top:20px;justify-content:center" aria-label="Pages">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs = http_build_query(array_merge($_GET, ['page' => $i]));
        $out .= '<a class="btn btn-sm ' . ($i === $page ? 'btn-primary' : 'btn-ghost') . '" href="?' . h($qs) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}
function page_num(): int { return max(1, get_int('page')); }

/* ---------- shared SQL fragments ---------- */
const RESOURCE_SELECT = "SELECT r.*, u.full_name, s.code AS subject_code, s.name AS subject_name, c.name AS category_name,
    (SELECT AVG(rating) FROM resource_ratings WHERE resource_id = r.id) AS avg_rating,
    (SELECT COUNT(*) FROM resource_ratings WHERE resource_id = r.id) AS rating_count
  FROM resources r
  JOIN users u ON u.id = r.user_id
  JOIN subjects s ON s.id = r.subject_id
  JOIN categories c ON c.id = r.category_id";

function resource_card(array $r): string
{
    ob_start(); ?>
    <article class="res">
      <div class="meta"><span class="tag green"><?= h($r['subject_code']) ?></span><span class="tag"><?= h($r['category_name']) ?></span></div>
      <h3><a href="<?= h(url('student/resource_view.php?id=' . $r['id'])) ?>"><?= h($r['title']) ?></a></h3>
      <p class="muted small" style="margin:0"><?= h(mb_strimwidth((string) $r['description'], 0, 110, '...')) ?></p>
      <div><?= stars($r['avg_rating'] !== null ? (float) $r['avg_rating'] : null, (int) $r['rating_count']) ?></div>
      <div class="foot"><span>by <?= h($r['full_name']) ?></span><span><?= (int) $r['downloads'] ?> downloads</span></div>
    </article>
    <?php return ob_get_clean();
}
