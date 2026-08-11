<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$pesan_error = "";

// --- 1. OTOMATIS BUAT TABEL ---
$create_table = "CREATE TABLE IF NOT EXISTS `petugas` (
  `id_petugas` VARCHAR(3) NOT NULL,
  `nama_petugas` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id_petugas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

// --- 2. PROSES SIMPAN / EDIT ---
if (isset($_POST['simpan_petugas'])) {
    $id_petugas   = mysqli_real_escape_string($koneksi, $_POST['id_petugas']);
    $nama_petugas = mysqli_real_escape_string($koneksi, $_POST['nama_petugas']);
    $no_hp        = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $status_form  = $_POST['status_form'];

    if ($status_form == 'tambah') {
        $cek_id = mysqli_query($koneksi, "SELECT * FROM petugas WHERE id_petugas = '$id_petugas'");
        if (mysqli_num_rows($cek_id) > 0) {
            $pesan_error = "Gagal: ID Petugas '$id_petugas' sudah digunakan. Gunakan ID lain.";
        } else {
            $query = "INSERT INTO petugas (id_petugas, nama_petugas, no_hp) VALUES ('$id_petugas', '$nama_petugas', '$no_hp')";
            if (mysqli_query($koneksi, $query)) {
                header("Location: petugas.php?status=sukses");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    } else {
        $query = "UPDATE petugas SET nama_petugas='$nama_petugas', no_hp='$no_hp' WHERE id_petugas='$id_petugas'";
        if (mysqli_query($koneksi, $query)) {
            header("Location: petugas.php?status=sukses");
            exit;
        } else {
            $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

// --- 3. PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM petugas WHERE id_petugas='$id_hapus'")) {
        header("Location: petugas.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- Data edit jika ada param ?edit=... ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $r = mysqli_query($koneksi, "SELECT * FROM petugas WHERE id_petugas='$edit_id'");
    if ($r) $edit_data = mysqli_fetch_assoc($r);
}

// --- 4. PENCARIAN & QUERY DATA ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE id_petugas LIKE '%$keyword%' OR nama_petugas LIKE '%$keyword%' OR no_hp LIKE '%$keyword%'" : "";
$result_petugas = mysqli_query($koneksi, "SELECT * FROM petugas $where ORDER BY id_petugas ASC");

$page_title   = "Data Petugas - PMB Siti Maryam";
$header_title = "Data Master / Data Petugas";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data petugas berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data petugas berhasil dihapus!</div>
        <?php endif; ?>

        <!-- ========== FORM INPUT LANGSUNG DI HALAMAN ========== -->
        <div class="form-card">
            <div class="form-card-header">
                <h5><i class="fa-solid fa-user-gear"></i> <?php echo $edit_data ? 'Edit Data Petugas' : 'Input Data Petugas'; ?></h5>
            </div>
            <form action="petugas.php" method="POST">
                <input type="hidden" name="status_form" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">

                <div class="form-inline-row">
                    <label>ID Petugas :</label>
                    <input type="text" name="id_petugas" required maxlength="3" placeholder="Contoh: P01"
                        value="<?php echo htmlspecialchars($edit_data['id_petugas'] ?? ''); ?>"
                        <?php echo $edit_data ? 'readonly style="background:#f8fafc; color:#64748b;"' : ''; ?>>
                </div>
                <div class="form-inline-row">
                    <label>Nama Petugas :</label>
                    <input type="text" name="nama_petugas" required placeholder="Nama lengkap petugas" value="<?php echo htmlspecialchars($edit_data['nama_petugas'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>No. HP :</label>
                    <input type="text" name="no_hp" required placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($edit_data['no_hp'] ?? ''); ?>">
                </div>

                <div class="form-action-row">
                    <button type="submit" name="simpan_petugas" class="btn-form-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    <a href="petugas.php" class="btn-form-batal"><i class="fa-solid fa-xmark"></i> Batal</a>
                    <a href="petugas.php" class="btn-form-selesai"><i class="fa-solid fa-check-double"></i> Selesai</a>
                </div>
            </form>
        </div>

        <!-- ========== PENCARIAN & TABEL ========== -->
        <div class="table-card">
            <div class="table-header">
                <form action="petugas.php" method="GET" class="search-box">
                    <label style="font-weight:600; color:var(--text-main); white-space:nowrap;">Cari Nama ... :</label>
                    <input type="text" name="cari" placeholder="Cari Petugas..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
                </form>
            </div>
            <div class="table-action-row">
                <a href="petugas.php" class="btn-tambah"><i class="fa-solid fa-plus"></i> TAMBAH</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Petugas</th>
                            <th>Nama Petugas</th>
                            <th>No. HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_petugas && mysqli_num_rows($result_petugas) > 0) {
                            while ($row = mysqli_fetch_assoc($result_petugas)) {
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['id_petugas']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_petugas']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                            <td>
                                <a href="petugas.php?edit=<?php echo urlencode($row['id_petugas']); ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="petugas.php?hapus=<?php echo $row['id_petugas']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data petugas ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Data petugas belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>