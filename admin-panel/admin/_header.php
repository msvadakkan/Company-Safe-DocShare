<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> – SafeDocShare</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">&#128196;</div>
            <div>
                <div class="brand-name">SafeDocShare</div>
                <div class="brand-sub">Admin Panel</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">&#9783;</span> Dashboard
            </a>
            <a href="users.php" class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
                <span class="nav-icon">&#128101;</span> Users
            </a>
            <a href="pdfs.php" class="nav-item <?= ($activePage ?? '') === 'pdfs' ? 'active' : '' ?>">
                <span class="nav-icon">&#128196;</span> PDF Files
            </a>
        </nav>
        <div class="sidebar-footer">
            <span class="admin-name">&#128100; <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </aside>
    <div class="main-wrapper">
        <header class="topbar">
            <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">&#9776;</button>
            <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
        </header>
        <main class="content">
