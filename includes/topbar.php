<?php
$user_display_name = htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$user_role_name    = htmlspecialchars($_SESSION['role'] ?? 'Petugas', ENT_QUOTES, 'UTF-8');
$user_initial      = strtoupper(substr($user_display_name, 0, 1));
$header_display_title = $header_title ?? $page_title ?? 'Dashboard';
?>
<!-- Top Navigation Bar -->
<div class="top-header">
    <div class="header-title-container">
        <button class="btn-toggle-sidebar" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h2><?php echo htmlspecialchars($header_display_title, ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>

    <div class="user-profile">
        <div class="user-avatar">
            <?php echo $user_initial; ?>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo $user_display_name; ?></span>
            <span class="user-role"><?php echo $user_role_name; ?></span>
        </div>
    </div>
</div>
