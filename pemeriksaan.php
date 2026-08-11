<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

$logo_path = file_exists('logo.png') ? 'logo.png' : '';
$pesan_error = "";

// --- 1. OTOMATIS BUAT TABEL JIKA BELUM ADA ---
$create_table = "CREATE TABLE IF NOT EXISTS `pemeriksaan` (
  `id_pemeriksaan` VARCHAR(20) NOT NULL,
  `tanggal_periksa` DATE NOT NULL,
  `id_pendaftaran` VARCHAR(20) NOT NULL,
  `diagnosa` TEXT NOT NULL,
  `tindakan` TEXT NOT NULL,
  `status_validasi` ENUM('Sudah','Belum') NOT NULL,
  PRIMARY KEY (`id_pemeriksaan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

// --- 2. PROSES SIMPAN / EDIT DATA PEMERIKSAAN ---
if (isset($_POST['simpan_pemeriksaan'])) {
    $id_pemeriksaan   = mysqli_real_escape_string($koneksi, $_POST['id_pemeriksaan']);
    $tanggal_periksa  = mysqli_real_escape_string($koneksi, $_POST['tanggal_periksa']);
    $id_pendaftaran   = mysqli_real_escape_string($koneksi, $_POST['id_pendaftaran']);
    $diagnosa         = mysqli_real_escape_string($koneksi, $_POST['diagnosa']);
    $tindakan         = mysqli_real_escape_string($koneksi, $_POST['tindakan']);
    $status_validasi  = mysqli_real_escape_string($koneksi, $_POST['status_validasi']);

    $cek = mysqli_query($koneksi, "SELECT * FROM pemeriksaan WHERE id_pemeriksaan='$id_pemeriksaan'");
    if (mysqli_num_rows($cek) == 0) {
        $query = "INSERT INTO pemeriksaan (id_pemeriksaan, tanggal_periksa, id_pendaftaran, diagnosa, tindakan, status_validasi) 
                  VALUES ('$id_pemeriksaan', '$tanggal_periksa', '$id_pendaftaran', '$diagnosa', '$tindakan', '$status_validasi')";
    } else {
        $query = "UPDATE pemeriksaan SET 
                    tanggal_periksa='$tanggal_periksa', 
                    diagnosa='$diagnosa', 
                    tindakan='$tindakan', 
                    status_validasi='$status_validasi' 
                  WHERE id_pemeriksaan='$id_pemeriksaan'";
    }

    if (mysqli_query($koneksi, $query)) {
        header("Location: pemeriksaan.php?status=sukses");
        exit;
    } else {
        $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}

// --- 3. PROSES HAPUS DATA PEMERIKSAAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM pemeriksaan WHERE id_pemeriksaan='$id_hapus'")) {
        header("Location: pemeriksaan.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- Data edit jika ada param ?edit=... ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $r = mysqli_query($koneksi, "SELECT pemeriksaan.*, pas.nama_pasien, pas.no_rm FROM pemeriksaan LEFT JOIN pendaftaran ON pemeriksaan.id_pendaftaran = pendaftaran.id_pendaftaran LEFT JOIN pasien pas ON pendaftaran.no_rm = pas.no_rm WHERE pemeriksaan.id_pemeriksaan='$edit_id'");
    if ($r) $edit_data = mysqli_fetch_assoc($r);
}

// --- 4. PENCARIAN & QUERY DATA PEMERIKSAAN ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE pemeriksaan.id_pemeriksaan LIKE '%$keyword%' OR pemeriksaan.id_pendaftaran LIKE '%$keyword%' OR pemeriksaan.diagnosa LIKE '%$keyword%' OR pemeriksaan.tindakan LIKE '%$keyword%' OR pas.nama_pasien LIKE '%$keyword%' OR pas.no_rm LIKE '%$keyword%'" : "";

$query_pemeriksaan = "SELECT pemeriksaan.*, pendaftaran.tanggal_daftar, pas.nama_pasien, pas.no_rm
                      FROM pemeriksaan 
                      LEFT JOIN pendaftaran ON pemeriksaan.id_pendaftaran = pendaftaran.id_pendaftaran
                      LEFT JOIN pasien pas ON pendaftaran.no_rm = pas.no_rm
                      $where 
                      ORDER BY pemeriksaan.tanggal_periksa DESC";
$result_pemeriksaan = mysqli_query($koneksi, $query_pemeriksaan);

$page_title   = "Data Pemeriksaan - PMB Siti Maryam";
$header_title = "Data Transaksi / Data Pemeriksaan";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pemeriksaan berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pemeriksaan berhasil dihapus!</div>
        <?php endif; ?>

        <!-- ========== FORM INPUT HANYA TAMPIL SAAT EDIT ========== -->
        <?php if ($edit_data): ?>
        <div class="form-card">
            <div class="form-card-header">
                <h5><i class="fa-solid fa-file-medical"></i> Edit Data Pemeriksaan</h5>
            </div>
            <form action="pemeriksaan.php" method="POST">
                <div class="form-inline-row">
                    <label>ID Pemeriksaan :</label>
                    <input type="text" name="id_pemeriksaan" required readonly style="background:#f8fafc; color:#64748b; font-weight:bold;"
                        value="<?php echo htmlspecialchars($edit_data['id_pemeriksaan']); ?>">
                </div>

                <div class="form-inline-row">
                    <label>Tanggal Periksa :</label>
                    <input type="date" name="tanggal_periksa" value="<?php echo htmlspecialchars($edit_data['tanggal_periksa']); ?>" required>
                </div>

                <div class="form-inline-row">
                    <label>ID Pendaftaran Pasien :</label>
                    <input type="hidden" name="id_pendaftaran" value="<?php echo htmlspecialchars($edit_data['id_pendaftaran']); ?>">
                    <input type="text" disabled value="[<?php echo htmlspecialchars($edit_data['id_pendaftaran']); ?>] - <?php echo htmlspecialchars($edit_data['nama_pasien'] ?? ''); ?>" style="background:#f8fafc; font-weight:bold;">
                </div>

                <div class="form-inline-row">
                    <label>Diagnosa :</label>
                    <input type="text" name="diagnosa" required placeholder="Masukkan diagnosa..." value="<?php echo htmlspecialchars($edit_data['diagnosa'] ?? ''); ?>">
                </div>

                <div class="form-inline-row">
                    <label>Tindakan :</label>
                    <input type="text" name="tindakan" required placeholder="Masukkan tindakan..." value="<?php echo htmlspecialchars($edit_data['tindakan'] ?? ''); ?>">
                </div>

                <div class="form-inline-row">
                    <label>Status Validasi :</label>
                    <select name="status_validasi" required>
                        <option value="Sudah" <?php echo (($edit_data['status_validasi'] ?? '') == 'Sudah') ? 'selected' : ''; ?>>Sudah</option>
                        <option value="Belum" <?php echo (($edit_data['status_validasi'] ?? 'Belum') == 'Belum') ? 'selected' : ''; ?>>Belum</option>
                    </select>
                </div>

                <div class="form-action-row">
                    <button type="submit" name="simpan_pemeriksaan" class="btn-form-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    <a href="pemeriksaan.php" class="btn-form-batal"><i class="fa-solid fa-xmark"></i> Batal</a>
                    <a href="pemeriksaan.php" class="btn-form-selesai"><i class="fa-solid fa-check-double"></i> Selesai</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ========== PENCARIAN & TABEL ========== -->
        <div class="table-card">
            <div class="table-header">
                <form action="pemeriksaan.php" method="GET" class="search-box">
                    <label style="font-weight:600; color:var(--text-main); white-space:nowrap;">Cari Nama ... :</label>
                    <input type="text" name="cari" placeholder="Cari Pemeriksaan / No RM..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Pemeriksaan</th>
                            <th>Tanggal Periksa</th>
                            <th>ID Pendaftaran</th>
                            <th>Nama Pasien</th>
                            <th>Diagnosa</th>
                            <th>Tindakan</th>
                            <th>Status Validasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($result_pemeriksaan && mysqli_num_rows($result_pemeriksaan) > 0) {
                            while ($row = mysqli_fetch_assoc($result_pemeriksaan)) {
                                $badge_class = ($row['status_validasi'] == 'Sudah') ? 'badge-sudah' : 'badge-belum';
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['id_pemeriksaan']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['tanggal_periksa']); ?></td>
                            <td>
                                <span style="color: #0284c7; font-weight: 600;"><i class="fa-solid fa-link"></i> <?php echo htmlspecialchars($row['id_pendaftaran']); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($row['nama_pasien'])): ?>
                                    <strong><?php echo htmlspecialchars($row['nama_pasien']); ?></strong>
                                    <br><small style="color:#94a3b8;"><?php echo htmlspecialchars($row['no_rm'] ?? ''); ?></small>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['diagnosa']); ?></td>
                            <td><?php echo htmlspecialchars($row['tindakan']); ?></td>
                            <td><span class="badge-status <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['status_validasi']); ?></span></td>
                            <td>
                                <a href="pemeriksaan.php?edit=<?php echo urlencode($row['id_pemeriksaan']); ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="pemeriksaan.php?hapus=<?php echo $row['id_pemeriksaan']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data pemeriksaan ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 20px;">Data pemeriksaan belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>