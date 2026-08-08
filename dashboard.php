<?php
session_start();
include 'koneksi.php';

// Proteksi halaman: Jika belum login, tendang balik ke login.php
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// --- QUERY PERHITUNGAN STATISTIK RINGKASAN ---
function getTotalCount($koneksi, $table) {
    $allowed_tables = ['pasien', 'petugas', 'bidan_terapis', 'pelayanan'];
    if (!in_array($table, $allowed_tables)) return 0;
    
    $query = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM `$table`");
    if ($query && $row = mysqli_fetch_assoc($query)) {
        return (int) $row['total'];
    }
    return 0;
}

$total_pasien    = getTotalCount($koneksi, 'pasien');
$total_petugas   = getTotalCount($koneksi, 'petugas');
$total_bidan     = getTotalCount($koneksi, 'bidan_terapis');
$total_pelayanan = getTotalCount($koneksi, 'pelayanan');

$page_title   = "Dashboard - PMB Siti Maryam";
$header_title = "Dashboard Utama";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <!-- Body Content -->
    <div class="content-body">
        <!-- Summary Cards -->
        <div class="card-grid">
            <a href="pasien.php" class="card">
                <div class="card-info">
                    <h4>Total Pasien</h4>
                    <h2><?php echo $total_pasien; ?></h2>
                </div>
                <div class="card-icon blue">
                    <i class="fa-solid fa-user-injured"></i>
                </div>
            </a>

            <a href="petugas.php" class="card">
                <div class="card-info">
                    <h4>Petugas</h4>
                    <h2><?php echo $total_petugas; ?></h2>
                </div>
                <div class="card-icon green">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
            </a>

            <a href="bidan_terapis.php" class="card">
                <div class="card-info">
                    <h4>Bidan Terapis</h4>
                    <h2><?php echo $total_bidan; ?></h2>
                </div>
                <div class="card-icon purple">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
            </a>

            <a href="pelayanan.php" class="card">
                <div class="card-info">
                    <h4>Pelayanan</h4>
                    <h2><?php echo $total_pelayanan; ?></h2>
                </div>
                <div class="card-icon orange">
                    <i class="fa-solid fa-hand-holding-medical"></i>
                </div>
            </a>
        </div>

        <!-- Welcome Message Card -->
        <div class="table-card">
            <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">Selamat Datang di Sistem Informasi PMB Siti Maryam</h3>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">
                Gunakan menu di sebelah kiri untuk mengelola data pasien, petugas, bidan terapis, pelayanan, transaksi pendaftaran & pemeriksaan, hingga laporan lengkap kesehatan.
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>