<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

// Proteksi halaman login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$pesan_error = "";

// --- 1. OTOMATIS BUAT TABEL JIKA BELUM ADA ---
$create_table = "CREATE TABLE IF NOT EXISTS `pelayanan` (
  `id_pelayanan` VARCHAR(10) NOT NULL,
  `jenis_pelayanan` ENUM('Pelayanan Bayi','Pelayanan Wanita','Terapi Patologi') NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_pelayanan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

@mysqli_query($koneksi, "ALTER TABLE `pelayanan` MODIFY COLUMN `jenis_pelayanan` ENUM('Pelayanan Bayi','Pelayanan Wanita','Terapi Patologi') NOT NULL;");

// --- 2. PROSES SIMPAN / EDIT DATA PELAYANAN ---
if (isset($_POST['simpan_pelayanan'])) {
    $id_pelayanan    = mysqli_real_escape_string($koneksi, $_POST['id_pelayanan']);
    $jenis_pelayanan = mysqli_real_escape_string($koneksi, $_POST['jenis_pelayanan']);
    $keterangan      = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $cek_id = mysqli_query($koneksi, "SELECT * FROM pelayanan WHERE id_pelayanan = '$id_pelayanan'");
    
    if (mysqli_num_rows($cek_id) == 0) {
        $query = "INSERT INTO pelayanan (id_pelayanan, jenis_pelayanan, keterangan) 
                  VALUES ('$id_pelayanan', '$jenis_pelayanan', '$keterangan')";
    } else {
        $query = "UPDATE pelayanan SET 
                    jenis_pelayanan='$jenis_pelayanan', 
                    keterangan='$keterangan' 
                  WHERE id_pelayanan='$id_pelayanan'";
    }

    if (mysqli_query($koneksi, $query)) {
        header("Location: pelayanan.php?status=sukses");
        exit;
    } else {
        $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}

// --- 3. PROSES HAPUS DATA PELAYANAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM pelayanan WHERE id_pelayanan='$id_hapus'")) {
        header("Location: pelayanan.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- Data edit jika ada param ?edit=... ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $r = mysqli_query($koneksi, "SELECT * FROM pelayanan WHERE id_pelayanan='$edit_id'");
    if ($r) $edit_data = mysqli_fetch_assoc($r);
}

// --- 4. PENCARIAN & QUERY DATA ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE jenis_pelayanan LIKE '%$keyword%' OR id_pelayanan LIKE '%$keyword%' OR keterangan LIKE '%$keyword%'" : "";

$query_pelayanan = "SELECT * FROM pelayanan $where ORDER BY id_pelayanan ASC";
$result_pelayanan = mysqli_query($koneksi, $query_pelayanan);

$page_title   = "Data Pelayanan - PMB Siti Maryam";
$header_title = "Data Master / Data Pelayanan";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <!-- Notifikasi Error / Sukses -->
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pelayanan berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pelayanan berhasil dihapus!</div>
        <?php endif; ?>

        <!-- ========== FORM INPUT LANGSUNG DI HALAMAN ========== -->
        <div class="form-card">
            <div class="form-card-header">
                <h5><i class="fa-solid fa-hand-holding-medical"></i> <?php echo $edit_data ? 'Edit Data Pelayanan' : 'Input Data Pelayanan'; ?></h5>
            </div>
            <form action="pelayanan.php" method="POST">
                <div class="form-inline-row">
                    <label>ID Pelayanan :</label>
                    <input type="text" name="id_pelayanan" required maxlength="10" 
                        value="<?php echo htmlspecialchars($edit_data['id_pelayanan'] ?? ''); ?>"
                        <?php echo $edit_data ? 'readonly style="background:#f8fafc; color:#64748b;"' : ''; ?>>
                </div>
                <div class="form-inline-row">
                    <label>Jenis Pelayanan :</label>
                    <select name="jenis_pelayanan" required>
                        <option value="Pelayanan Bayi" <?php echo (($edit_data['jenis_pelayanan'] ?? 'Pelayanan Bayi') == 'Pelayanan Bayi') ? 'selected' : ''; ?>>Pelayanan Bayi</option>
                        <option value="Pelayanan Wanita" <?php echo (($edit_data['jenis_pelayanan'] ?? '') == 'Pelayanan Wanita') ? 'selected' : ''; ?>>Pelayanan Wanita</option>
                        <option value="Terapi Patologi" <?php echo (($edit_data['jenis_pelayanan'] ?? '') == 'Terapi Patologi') ? 'selected' : ''; ?>>Terapi Patologi</option>
                    </select>
                </div>
                <div class="form-inline-row">
                    <label>Keterangan :</label>
                    <textarea name="keterangan" rows="2" placeholder="Rincian pelayanan..."><?php echo htmlspecialchars($edit_data['keterangan'] ?? ''); ?></textarea>
                </div>

                <div class="form-action-row">
                    <button type="submit" name="simpan_pelayanan" class="btn-form-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    <a href="pelayanan.php" class="btn-form-batal"><i class="fa-solid fa-xmark"></i> Batal</a>
                    <a href="pelayanan.php" class="btn-form-selesai"><i class="fa-solid fa-check-double"></i> Selesai</a>
                </div>
            </form>
        </div>

        <!-- ========== PENCARIAN & TABEL ========== -->
        <div class="table-card">
            <div class="table-header">
                <form action="pelayanan.php" method="GET" class="search-box">
                    <label style="font-weight:600; color:var(--text-main); white-space:nowrap;">Cari Nama ... :</label>
                    <input type="text" name="cari" placeholder="Cari ID / Jenis Pelayanan..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
                </form>
            </div>
            <div class="table-action-row">
                <a href="pelayanan.php" class="btn-tambah"><i class="fa-solid fa-plus"></i> TAMBAH</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Pelayanan</th>
                            <th>Jenis Pelayanan</th>
                            <th>Keterangan / Layanan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_pelayanan && mysqli_num_rows($result_pelayanan) > 0) {
                            while ($row = mysqli_fetch_assoc($result_pelayanan)) {
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['id_pelayanan']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['jenis_pelayanan']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?></td>
                            <td>
                                <a href="pelayanan.php?edit=<?php echo urlencode($row['id_pelayanan']); ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="pelayanan.php?hapus=<?php echo $row['id_pelayanan']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data pelayanan ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Data pelayanan belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>