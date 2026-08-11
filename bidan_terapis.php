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
$create_table = "CREATE TABLE IF NOT EXISTS `bidan_terapis` (
  `id_bidan_terapis` VARCHAR(3) NOT NULL,
  `nama_bidan_terapis` VARCHAR(35) NOT NULL,
  `no_hp` VARCHAR(15) NOT NULL,
  `role` ENUM('Bidan','Terapis') NOT NULL,
  `spesialis` ENUM('-','Terapi Anak','Terapi Dewasa') NOT NULL,
  PRIMARY KEY (`id_bidan_terapis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);
@mysqli_query($koneksi, "ALTER TABLE `bidan_terapis` MODIFY COLUMN `spesialis` ENUM('-','Terapi Anak','Terapi Dewasa') NOT NULL;");

// --- 2. PROSES SIMPAN / EDIT ---
if (isset($_POST['simpan_bidan_terapis'])) {
    $id_bidan_terapis   = mysqli_real_escape_string($koneksi, $_POST['id_bidan_terapis']);
    $nama_bidan_terapis = mysqli_real_escape_string($koneksi, $_POST['nama_bidan_terapis']);
    $no_hp              = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $role               = mysqli_real_escape_string($koneksi, $_POST['role']);
    $spesialis          = mysqli_real_escape_string($koneksi, $_POST['spesialis']);

    $cek_id = mysqli_query($koneksi, "SELECT * FROM bidan_terapis WHERE id_bidan_terapis = '$id_bidan_terapis'");
    if (mysqli_num_rows($cek_id) == 0) {
        $query = "INSERT INTO bidan_terapis (id_bidan_terapis, nama_bidan_terapis, no_hp, role, spesialis) VALUES ('$id_bidan_terapis', '$nama_bidan_terapis', '$no_hp', '$role', '$spesialis')";
    } else {
        $query = "UPDATE bidan_terapis SET nama_bidan_terapis='$nama_bidan_terapis', no_hp='$no_hp', role='$role', spesialis='$spesialis' WHERE id_bidan_terapis='$id_bidan_terapis'";
    }

    if (mysqli_query($koneksi, $query)) {
        header("Location: bidan_terapis.php?status=sukses");
        exit;
    } else {
        $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}

// --- 3. PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM bidan_terapis WHERE id_bidan_terapis='$id_hapus'")) {
        header("Location: bidan_terapis.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- Data edit jika ada param ?edit=... ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $r = mysqli_query($koneksi, "SELECT * FROM bidan_terapis WHERE id_bidan_terapis='$edit_id'");
    if ($r) $edit_data = mysqli_fetch_assoc($r);
}

// --- 4. PENCARIAN & QUERY DATA ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE nama_bidan_terapis LIKE '%$keyword%' OR id_bidan_terapis LIKE '%$keyword%' OR spesialis LIKE '%$keyword%'" : "";
$result_bidan = mysqli_query($koneksi, "SELECT * FROM bidan_terapis $where ORDER BY id_bidan_terapis DESC");

$page_title   = "Data Bidan Terapis - PMB Siti Maryam";
$header_title = "Data Master / Data Bidan Terapis";

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
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data bidan / terapis berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data bidan / terapis berhasil dihapus!</div>
        <?php endif; ?>

        <!-- ========== FORM INPUT LANGSUNG DI HALAMAN ========== -->
        <div class="form-card">
            <div class="form-card-header">
                <h5><i class="fa-solid fa-stethoscope"></i> <?php echo $edit_data ? 'Edit Data Bidan / Terapis' : 'Input Data Bidan Terapis'; ?></h5>
            </div>
            <form action="bidan_terapis.php" method="POST">
                <div class="form-inline-row">
                    <label>ID Bidan Terapis :</label>
                    <input type="text" name="id_bidan_terapis" required maxlength="3" placeholder="Contoh: B01 / T01"
                        value="<?php echo htmlspecialchars($edit_data['id_bidan_terapis'] ?? ''); ?>"
                        <?php echo $edit_data ? 'readonly style="background:#f8fafc; color:#64748b;"' : ''; ?>>
                </div>
                <div class="form-inline-row">
                    <label>Nama Lengkap :</label>
                    <input type="text" name="nama_bidan_terapis" required placeholder="Nama lengkap & gelar" value="<?php echo htmlspecialchars($edit_data['nama_bidan_terapis'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>No. HP :</label>
                    <input type="text" name="no_hp" required placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($edit_data['no_hp'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Role / Profesi :</label>
                    <select name="role" id="role_select" required onchange="handleRoleChange()">
                        <option value="Bidan" <?php echo (($edit_data['role'] ?? 'Bidan') == 'Bidan') ? 'selected' : ''; ?>>Bidan</option>
                        <option value="Terapis" <?php echo (($edit_data['role'] ?? '') == 'Terapis') ? 'selected' : ''; ?>>Terapis</option>
                    </select>
                </div>
                <div class="form-inline-row">
                    <label>Spesialis :</label>
                    <select name="spesialis" id="spesialis_select" required>
                        <option value="-" <?php echo (($edit_data['spesialis'] ?? '-') == '-') ? 'selected' : ''; ?>>- (Khusus Bidan)</option>
                        <option value="Terapi Anak" <?php echo (($edit_data['spesialis'] ?? '') == 'Terapi Anak') ? 'selected' : ''; ?>>Terapi Anak</option>
                        <option value="Terapi Dewasa" <?php echo (($edit_data['spesialis'] ?? '') == 'Terapi Dewasa') ? 'selected' : ''; ?>>Terapi Dewasa</option>
                    </select>
                </div>

                <div class="form-action-row">
                    <button type="submit" name="simpan_bidan_terapis" class="btn-form-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    <a href="bidan_terapis.php" class="btn-form-batal"><i class="fa-solid fa-xmark"></i> Batal</a>
                    <a href="bidan_terapis.php" class="btn-form-selesai"><i class="fa-solid fa-check-double"></i> Selesai</a>
                </div>
            </form>
        </div>

        <!-- ========== PENCARIAN & TABEL ========== -->
        <div class="table-card">
            <div class="table-header">
                <form action="bidan_terapis.php" method="GET" class="search-box">
                    <label style="font-weight:600; color:var(--text-main); white-space:nowrap;">Cari Nama ... :</label>
                    <input type="text" name="cari" placeholder="Cari ID / Nama / Spesialis..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
                </form>
            </div>
            <div class="table-action-row">
                <a href="bidan_terapis.php" class="btn-tambah"><i class="fa-solid fa-plus"></i> TAMBAH</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>No. HP / WA</th>
                            <th>Role / Jabatan</th>
                            <th>Spesialis</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_bidan && mysqli_num_rows($result_bidan) > 0) {
                            while ($row = mysqli_fetch_assoc($result_bidan)) {
                                $role_badge = ($row['role'] == 'Bidan') ? 'badge-info' : 'badge-warning';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['id_bidan_terapis']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_bidan_terapis']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                            <td><span class="badge <?php echo $role_badge; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['spesialis']); ?></td>
                            <td>
                                <a href="bidan_terapis.php?edit=<?php echo urlencode($row['id_bidan_terapis']); ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="bidan_terapis.php?hapus=<?php echo $row['id_bidan_terapis']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">Data bidan terapis belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function handleRoleChange() {
        const role = document.getElementById('role_select').value;
        const spesialis = document.getElementById('spesialis_select');
        if (role === 'Bidan') {
            spesialis.value = '-';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>