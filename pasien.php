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

// --- 1. OTOMATIS BUAT TABEL PASIEN JIKA BELUM ADA ---
$create_table = "CREATE TABLE IF NOT EXISTS `pasien` (
  `no_rm` VARCHAR(20) NOT NULL,
  `nik` VARCHAR(20) NOT NULL,
  `nama_pasien` VARCHAR(100) NOT NULL,
  `tanggal_lahir` DATE NOT NULL,
  `nama_orang_tua` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki','Perempuan') NOT NULL DEFAULT 'Perempuan',
  `no_hp` VARCHAR(20) NOT NULL,
  `alamat` TEXT NULL,
  PRIMARY KEY (`no_rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

// --- GENERATE NO RM OTOMATIS ---
$q_rm = mysqli_query($koneksi, "SELECT no_rm FROM pasien");
$max_num = 0;
if ($q_rm) {
    while ($r_rm = mysqli_fetch_assoc($q_rm)) {
        $num = (int) preg_replace('/[^0-9]/', '', $r_rm['no_rm']);
        if ($num > $max_num) $max_num = $num;
    }
}
$auto_rm = "RM" . sprintf("%04d", $max_num + 1);

// --- Data edit jika ada param ?edit=... ---
$edit_data = null;
$existing_username = "";
if (isset($_GET['edit'])) {
    $edit_no_rm = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $r = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm='$edit_no_rm'");
    if ($r && mysqli_num_rows($r) > 0) {
        $edit_data = mysqli_fetch_assoc($r);
        $nama_p = mysqli_real_escape_string($koneksi, $edit_data['nama_pasien']);
        $ru = mysqli_query($koneksi, "SELECT username FROM users WHERE nama_lengkap='$nama_p' AND role='Pasien' LIMIT 1");
        if ($ru && mysqli_num_rows($ru) > 0) {
            $u_data = mysqli_fetch_assoc($ru);
            $existing_username = $u_data['username'];
        }
    }
}

// --- 2. HANDLE TAMBAH / EDIT PASIEN ---
if (isset($_POST['simpan_pasien'])) {
    $is_edit         = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
    $old_nama_pasien = isset($_POST['old_nama_pasien']) ? mysqli_real_escape_string($koneksi, trim($_POST['old_nama_pasien'])) : '';

    $no_rm           = mysqli_real_escape_string($koneksi, trim($_POST['no_rm']));
    $nik             = mysqli_real_escape_string($koneksi, trim($_POST['nik']));
    $nama_pasien     = mysqli_real_escape_string($koneksi, trim($_POST['nama_pasien']));
    $tanggal_lahir   = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $nama_orang_tua  = mysqli_real_escape_string($koneksi, trim($_POST['nama_orang_tua']));
    $jenis_kelamin   = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $no_hp           = mysqli_real_escape_string($koneksi, trim($_POST['no_hp']));
    $alamat          = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    
    $username_pasien = mysqli_real_escape_string($koneksi, trim($_POST['username_pasien'] ?? ''));
    $password_pasien = $_POST['password_pasien'] ?? '';

    if (!$is_edit && empty($no_rm)) {
        $no_rm = $auto_rm;
    }

    $username_valid = true;
    if (!empty($username_pasien)) {
        $cek_u = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username_pasien'");
        if ($cek_u && mysqli_num_rows($cek_u) > 0) {
            $u_row = mysqli_fetch_assoc($cek_u);
            if (!$is_edit || $u_row['nama_lengkap'] !== $old_nama_pasien || $u_row['role'] !== 'Pasien') {
                $username_valid = false;
                $pesan_error = "Gagal: Username '$username_pasien' sudah digunakan oleh akun lain!";
            }
        }
    }

    if ($username_valid) {
        if (!$is_edit) {
            $query = "INSERT INTO pasien (no_rm, nik, nama_pasien, tanggal_lahir, nama_orang_tua, jenis_kelamin, no_hp, alamat) 
                      VALUES ('$no_rm', '$nik', '$nama_pasien', '$tanggal_lahir', '$nama_orang_tua', '$jenis_kelamin', '$no_hp', '$alamat')";
            if (mysqli_query($koneksi, $query)) {
                if (!empty($username_pasien) && !empty($password_pasien)) {
                    $pass_hash = password_hash($password_pasien, PASSWORD_DEFAULT);
                    mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username_pasien', '$pass_hash', '$nama_pasien', 'Pasien')");
                }
                header("Location: pasien.php?status=sukses");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        } else {
            $query = "UPDATE pasien SET 
                        nik='$nik', 
                        nama_pasien='$nama_pasien', 
                        tanggal_lahir='$tanggal_lahir', 
                        nama_orang_tua='$nama_orang_tua',
                        jenis_kelamin='$jenis_kelamin', 
                        no_hp='$no_hp', 
                        alamat='$alamat' 
                      WHERE no_rm='$no_rm'";
            if (mysqli_query($koneksi, $query)) {
                if (!empty($username_pasien)) {
                    $search_name = !empty($old_nama_pasien) ? $old_nama_pasien : $nama_pasien;
                    $cek_ex = mysqli_query($koneksi, "SELECT * FROM users WHERE nama_lengkap='$search_name' AND role='Pasien'");
                    if ($cek_ex && mysqli_num_rows($cek_ex) > 0) {
                        if (!empty($password_pasien)) {
                            $pass_hash = password_hash($password_pasien, PASSWORD_DEFAULT);
                            mysqli_query($koneksi, "UPDATE users SET username='$username_pasien', password='$pass_hash', nama_lengkap='$nama_pasien' WHERE nama_lengkap='$search_name' AND role='Pasien'");
                        } else {
                            mysqli_query($koneksi, "UPDATE users SET username='$username_pasien', nama_lengkap='$nama_pasien' WHERE nama_lengkap='$search_name' AND role='Pasien'");
                        }
                    } else {
                        if (!empty($password_pasien)) {
                            $pass_hash = password_hash($password_pasien, PASSWORD_DEFAULT);
                            mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username_pasien', '$pass_hash', '$nama_pasien', 'Pasien')");
                        }
                    }
                }
                header("Location: pasien.php?status=sukses");
                exit;
            } else {
                $pesan_error = "Gagal memperbarui data: " . mysqli_error($koneksi);
            }
        }
    }
}

// --- 3. HANDLE HAPUS PASIEN ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM pasien WHERE no_rm='$id_hapus'")) {
        header("Location: pasien.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- 4. QUERY DATA & PENCARIAN ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE nama_pasien LIKE '%$keyword%' OR nik LIKE '%$keyword%' OR no_rm LIKE '%$keyword%' OR alamat LIKE '%$keyword%' OR nama_orang_tua LIKE '%$keyword%'" : "";

$result_pasien = mysqli_query($koneksi, "SELECT * FROM pasien $where ORDER BY no_rm DESC");

$page_title   = "Data Pasien - PMB Siti Maryam";
$header_title = "Data Master / Data Pasien";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <!-- Alert Notifikasi -->
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pasien berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pasien berhasil dihapus!</div>
        <?php endif; ?>

        <!-- ========== FORM INPUT LANGSUNG DI HALAMAN ========== -->
        <div class="form-card">
            <div class="form-card-header">
                <h5><i class="fa-solid fa-user-plus"></i> <?php echo $edit_data ? 'Edit Data Pasien' : 'Input Data Pasien'; ?></h5>
            </div>
            <form action="pasien.php" method="POST">
                <input type="hidden" name="is_edit" value="<?php echo $edit_data ? '1' : '0'; ?>">
                <input type="hidden" name="old_nama_pasien" value="<?php echo htmlspecialchars($edit_data['nama_pasien'] ?? ''); ?>">

                <div class="form-inline-row">
                    <label>No RM :</label>
                    <input type="text" name="no_rm" id="no_rm" readonly style="background:#f8fafc; color:#0284c7; font-weight:bold;"
                           value="<?php echo htmlspecialchars($edit_data ? $edit_data['no_rm'] : $auto_rm); ?>">
                </div>
                <div class="form-inline-row">
                    <label>NIK :</label>
                    <input type="text" name="nik" id="nik" required placeholder="Masukkan NIK 16 digit" value="<?php echo htmlspecialchars($edit_data['nik'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Nama Pasien :</label>
                    <input type="text" name="nama_pasien" id="nama_pasien" required placeholder="Nama lengkap pasien" value="<?php echo htmlspecialchars($edit_data['nama_pasien'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Tanggal Lahir :</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" max="<?php echo date('Y-m-d'); ?>" required value="<?php echo htmlspecialchars($edit_data['tanggal_lahir'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Nama Orang Tua :</label>
                    <input type="text" name="nama_orang_tua" id="nama_orang_tua" required placeholder="Nama ayah / ibu" value="<?php echo htmlspecialchars($edit_data['nama_orang_tua'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Jenis Kelamin :</label>
                    <select name="jenis_kelamin" id="jenis_kelamin">
                        <option value="Laki-laki" <?php echo (($edit_data['jenis_kelamin'] ?? '') == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo (($edit_data['jenis_kelamin'] ?? 'Perempuan') == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-inline-row">
                    <label>No. HP :</label>
                    <input type="text" name="no_hp" id="no_hp" required placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($edit_data['no_hp'] ?? ''); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Alamat :</label>
                    <textarea name="alamat" id="alamat" rows="2" placeholder="Alamat lengkap"><?php echo htmlspecialchars($edit_data['alamat'] ?? ''); ?></textarea>
                </div>
                <div class="form-inline-row">
                    <label>Username :</label>
                    <input type="text" name="username_pasien" id="username_pasien" placeholder="Username login pasien (opsional)" value="<?php echo htmlspecialchars($existing_username); ?>">
                </div>
                <div class="form-inline-row">
                    <label>Password :</label>
                    <input type="password" name="password_pasien" id="password_pasien" placeholder="Buat password">
                </div>

                <div class="form-action-row">
                    <button type="submit" name="simpan_pasien" class="btn-form-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    <a href="pasien.php" class="btn-form-batal"><i class="fa-solid fa-xmark"></i> Batal</a>
                    <a href="pasien.php" class="btn-form-selesai"><i class="fa-solid fa-check-double"></i> Selesai</a>
                </div>
            </form>
        </div>

        <!-- ========== PENCARIAN & TABEL ========== -->
        <div class="table-card">
            <div class="table-header">
                <form action="pasien.php" method="GET" class="search-box">
                    <label style="font-weight:600; color:var(--text-main); white-space:nowrap;">Cari Nama ... :</label>
                    <input type="text" name="cari" placeholder="Cari No. RM / Nama / NIK..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No. RM</th>
                            <th>NIK</th>
                            <th>Nama Pasien</th>
                            <th>Tanggal Lahir</th>
                            <th>Nama Orang Tua</th>
                            <th>L/P</th>
                            <th>No. HP</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_pasien && mysqli_num_rows($result_pasien) > 0) {
                            while ($row = mysqli_fetch_assoc($result_pasien)) { 
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['no_rm']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['nik']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_pasien']); ?></strong></td>
                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_orang_tua']); ?></td>
                            <td><?php echo htmlspecialchars($row['jenis_kelamin']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                            <td><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                            <td>
                                <a href="pasien.php?edit=<?php echo urlencode($row['no_rm']); ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="pasien.php?hapus=<?php echo $row['no_rm']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data pasien ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } else {
                            echo '<tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 20px;">Data pasien belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>