<?php
$area      = $area ?? 'public';           // public | student | admin
$pageTitle = $pageTitle ?? APP_NAME;
$me        = current_user();
$flashes   = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> | <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(url('assets/css/style.css')) ?>">
</head>
<body class="area-<?= h($area) ?>">
<a class="skip" href="#main">Skip to content</a>

<?php if ($area === 'admin'): ?>
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= h(url('admin/dashboard.php')) ?>"><img class="brand-logo"
src="<?= h(url('assets/img/logo.png')) ?>"
alt="StudyCircle Logo"><span>StudyCircle <small>Admin</small></span></a>
    <nav>
      <a class="<?= nav_active('dashboard.php') ?>" href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
      <a class="<?= nav_active('users.php', 'user_form.php') ?>" href="<?= h(url('admin/users.php')) ?>">Users</a>
      <a class="<?= nav_active('resources.php') ?>" href="<?= h(url('admin/resources.php')) ?>">Notes &amp; materials</a>
      <a class="<?= nav_active('subjects.php') ?>" href="<?= h(url('admin/subjects.php')) ?>">Subjects</a>
      <a class="<?= nav_active('categories.php') ?>" href="<?= h(url('admin/categories.php')) ?>">Categories</a>
      <a class="<?= nav_active('forum.php') ?>" href="<?= h(url('admin/forum.php')) ?>">Forum moderation</a>
      <a class="<?= nav_active('comments.php') ?>" href="<?= h(url('admin/comments.php')) ?>">Comments</a>
      <a class="<?= nav_active('reports.php') ?>" href="<?= h(url('admin/reports.php')) ?>">Reports</a>
    </nav>
    <div class="side-foot">
      <a href="<?= h(url('student/dashboard.php')) ?>">View student site</a>
      <a href="<?= h(url('logout.php')) ?>">Log out</a>
    </div>
  </aside>
  <div class="admin-body">
    <header class="admin-top">
      <button class="icon-btn" id="menuBtn" aria-label="Menu" type="button">&#9776;</button>
      <h1><?= h($pageTitle) ?></h1>
      <span class="who"><span class="avatar"><?= h(initials($me['full_name'])) ?></span><?= h($me['full_name']) ?></span>
    </header>
    <main id="main" class="admin-main">
<?php else: ?>
<header class="topbar">
  <div class="wrap bar">
    <a class="brand" href="<?= h(url($me ? home_for($me) : 'index.php')) ?>"><img class="brand-logo" src="<?= h(url('assets/img/logo.png')) ?>" alt="NSBM logo"><span>NSBM StudyCircle</span></a>
    <button class="icon-btn menu-toggle" id="menuBtn" aria-label="Menu" type="button">&#9776;</button>
    <nav class="mainnav" id="mainnav">
      <?php if ($me): ?>
        <a class="<?= nav_active('dashboard.php') ?>" href="<?= h(url('student/dashboard.php')) ?>">Dashboard</a>
        <a class="<?= nav_active('resources.php', 'resource_view.php') ?>" href="<?= h(url('student/resources.php')) ?>">Study materials</a>
        <a class="<?= nav_active('forum.php', 'question_new.php', 'question_view.php') ?>" href="<?= h(url('student/forum.php')) ?>">Q&amp;A forum</a>
        <a class="<?= nav_active('groups.php', 'group_view.php', 'group_form.php', 'session_form.php') ?>" href="<?= h(url('student/groups.php')) ?>">Study groups</a>
        <a class="<?= nav_active('upload.php', 'my_uploads.php', 'edit_resource.php') ?>" href="<?= h(url('student/my_uploads.php')) ?>">My uploads</a>
        <?php if (is_admin()): ?><a href="<?= h(url('admin/dashboard.php')) ?>">Admin panel</a><?php endif; ?>
        <span class="navsep"></span>
        <a class="userlink<?= nav_active('profile.php') ?>" href="<?= h(url('student/profile.php')) ?>"><span class="avatar"><?= h(initials($me['full_name'])) ?></span><?= h(explode(' ', $me['full_name'])[0]) ?></a>
        <a class="btn btn-ghost btn-sm" href="<?= h(url('logout.php')) ?>">Log out</a>
      <?php else: ?>
        <a href="<?= h(url('index.php#features')) ?>">Features</a>
        <a class="btn btn-ghost btn-sm" href="<?= h(url('login.php')) ?>">Log in</a>
        <a class="btn btn-primary btn-sm" href="<?= h(url('register.php')) ?>">Join StudyCircle</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main" class="<?= $area === 'public' ? '' : 'wrap page' ?>">
<?php endif; ?>

<?php if ($flashes): ?>
  <div class="<?= $area === 'public' ? 'wrap ' : '' ?>flashes">
  <?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= h($f['type']) ?>" role="status"><?= h($f['msg']) ?></div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
