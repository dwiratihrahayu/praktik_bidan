<?php
$current_page = basename($_SERVER['PHP_SELF']);

$is_master_active = in_array($current_page, ['pasien.php', 'petugas.php', 'bidan_terapis.php', 'pelayanan.php']);
$is_transaksi_active = in_array($current_page, ['pendaftaran.php', 'pemeriksaan.php']);
$is_laporan_active = in_array($current_page, [
    'laporan_pasien.php', 
    'laporan_petugas.php', 
    'laporan_bidan_terapis.php', 
    'laporan_pelayanan.php', 
    'laporan_pendaftaran.php', 
    'laporan_pemeriksaan.php'
]);

$logo_path = file_exists('assets/images/logo.png') ? 'assets/images/logo.png' : (file_exists('logo.png') ? 'logo.png' : '');
?>
<!-- Sidebar Navigation -->
<div class="sidebar">
    <div class="sidebar-brand">
        <?php if (!empty($logo_path)): ?>
            <img src="<?php echo $logo_path; ?>" alt="Logo PMB Siti Maryam">
        <?php else: ?>
            <div class="logo-fallback"><i class="fa-solid fa-user-nurse fa-2x"></i></div>
        <?php endif; ?>
        <h3>PMB SITI MARYAM</h3>
        <p>Administrator Bidan</p>
    </div>

    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-gauge menu-icon"></i> 
                <span>Dashboard</span>
            </a>
        </li>
        
        <!-- Menu Data Master -->
        <li class="has-dropdown <?php echo $is_master_active ? 'active' : ''; ?>">
            <a href="javascript:void(0)" onclick="toggleDropdown(this)">
                <span><i class="fa-solid fa-folder-open menu-icon"></i> Data Master</span>
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </a>
            <ul class="submenu">
                <li class="<?php echo ($current_page == 'pasien.php') ? 'active' : ''; ?>">
                    <a href="pasien.php"><i class="fa-solid fa-user-injured menu-icon"></i> Data Pasien</a>
                </li>
                <li class="<?php echo ($current_page == 'petugas.php') ? 'active' : ''; ?>">
                    <a href="petugas.php"><i class="fa-solid fa-user-gear menu-icon"></i> Data Petugas</a>
                </li>
                <li class="<?php echo ($current_page == 'bidan_terapis.php') ? 'active' : ''; ?>">
                    <a href="bidan_terapis.php"><i class="fa-solid fa-stethoscope menu-icon"></i> Data Bidan Terapis</a>
                </li>
                <li class="<?php echo ($current_page == 'pelayanan.php') ? 'active' : ''; ?>">
                    <a href="pelayanan.php"><i class="fa-solid fa-hand-holding-medical menu-icon"></i> Data Pelayanan</a>
                </li>
            </ul>
        </li>

        <!-- Menu Pelayanan & Transaksi -->
        <li class="has-dropdown <?php echo $is_transaksi_active ? 'active' : ''; ?>">
            <a href="javascript:void(0)" onclick="toggleDropdown(this)">
                <span><i class="fa-solid fa-briefcase-medical menu-icon"></i> Pelayanan</span>
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </a>
            <ul class="submenu">
                <li class="<?php echo ($current_page == 'pendaftaran.php') ? 'active' : ''; ?>">
                    <a href="pendaftaran.php"><i class="fa-solid fa-clipboard-user menu-icon"></i> Data Pendaftaran</a>
                </li>
                <li class="<?php echo ($current_page == 'pemeriksaan.php') ? 'active' : ''; ?>">
                    <a href="pemeriksaan.php"><i class="fa-solid fa-file-medical menu-icon"></i> Data Pemeriksaan</a>
                </li>
            </ul>
        </li>

        <!-- Menu Laporan -->
        <li class="has-dropdown <?php echo $is_laporan_active ? 'active' : ''; ?>">
            <a href="javascript:void(0)" onclick="toggleDropdown(this)">
                <span><i class="fa-solid fa-file-contract menu-icon"></i> Laporan</span>
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </a>
            <ul class="submenu">
                <li class="<?php echo ($current_page == 'laporan_pasien.php') ? 'active' : ''; ?>">
                    <a href="laporan_pasien.php?jenis=pasien"><i class="fa-solid fa-user-injured menu-icon"></i> Laporan Pasien</a>
                </li>
                <li class="<?php echo ($current_page == 'laporan_petugas.php') ? 'active' : ''; ?>">
                    <a href="laporan_petugas.php?jenis=petugas"><i class="fa-solid fa-user-gear menu-icon"></i> Laporan Petugas</a>
                </li>
                <li class="<?php echo ($current_page == 'laporan_bidan_terapis.php') ? 'active' : ''; ?>">
                    <a href="laporan_bidan_terapis.php?jenis=bidan"><i class="fa-solid fa-stethoscope menu-icon"></i> Laporan Bidan Terapis</a>
                </li>
                <li class="<?php echo ($current_page == 'laporan_pelayanan.php') ? 'active' : ''; ?>">
                    <a href="laporan_pelayanan.php?jenis=pelayanan"><i class="fa-solid fa-hand-holding-medical menu-icon"></i> Laporan Pelayanan</a>
                </li>
                <li class="<?php echo ($current_page == 'laporan_pendaftaran.php') ? 'active' : ''; ?>">
                    <a href="laporan_pendaftaran.php?jenis=pendaftaran"><i class="fa-solid fa-clipboard-user menu-icon"></i> Laporan Pendaftaran</a>
                </li>
                <li class="<?php echo ($current_page == 'laporan_pemeriksaan.php') ? 'active' : ''; ?>">
                    <a href="laporan_pemeriksaan.php?jenis=pemeriksaan"><i class="fa-solid fa-file-medical menu-icon"></i> Laporan Pemeriksaan</a>
                </li>
            </ul>
        </li>

        <li>
            <a href="javascript:void(0)" onclick="openLogoutModal()">
                <i class="fa-solid fa-right-from-bracket menu-icon"></i> 
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
