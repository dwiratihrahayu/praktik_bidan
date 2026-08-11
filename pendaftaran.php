<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$logo_path = file_exists('logo.png') ? 'logo.png' : '';

// --- 1. OTOMATIS BUAT/CEK STRUKTUR TABEL ---
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `pasien` (
  `id_pasien` INT(11) NOT NULL AUTO_INCREMENT,
  `no_rm` VARCHAR(20) NOT NULL UNIQUE,
  `nama_pasien` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `petugas` (
  `id_petugas` VARCHAR(20) NOT NULL,
  `nama_petugas` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_petugas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `pendaftaran` (
  `id_pendaftaran` VARCHAR(20) NOT NULL,
  `no_rm` VARCHAR(20) NOT NULL,
  `id_petugas` VARCHAR(20) NOT NULL,
  `id_bidan_terapis` VARCHAR(20) DEFAULT NULL,
  `tanggal_daftar` DATE NOT NULL,
  `tanggal_periksa` DATE NOT NULL,
  `id_pelayanan` ENUM(
      'PB1', 'PB2', 'PB3', 'PB4', 'PB5', 'PB6', 
      'PW1', 'PW2', 'PW3', 'PW4', 'PW5', 'PW6', 
      'TP1', 'TP2', 'TP3', 'TP4', 'TP5', 'TP6'
  ) NOT NULL,
  `keluhan` TEXT NOT NULL,
  `status_validasi` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_pendaftaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

@mysqli_query($koneksi, "ALTER TABLE `pendaftaran` MODIFY COLUMN `id_pelayanan` ENUM(
    'PB1', 'PB2', 'PB3', 'PB4', 'PB5', 'PB6', 
    'PW1', 'PW2', 'PW3', 'PW4', 'PW5', 'PW6', 
    'TP1', 'TP2', 'TP3', 'TP4', 'TP5', 'TP6'
) NOT NULL;");

// Data Dummy Petugas jika kosong
$cek_petugas = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM petugas");
if ($row_petugas = mysqli_fetch_assoc($cek_petugas)) {
    if ($row_petugas['jml'] == 0) {
        mysqli_query($koneksi, "INSERT INTO petugas (id_petugas, nama_petugas) VALUES ('P01', 'Admin Utama'), ('P02', 'Petugas Pendaftaran 1')");
    }
}

// --- 2. SINKRONISASI OTOMATIS DARI PENDAFTARAN ONLINE ---
$cek_tabel_online = mysqli_query($koneksi, "SHOW TABLES LIKE 'pasien_online'");
if ($cek_tabel_online && mysqli_num_rows($cek_tabel_online) > 0) {
    $q_online = mysqli_query($koneksi, "SELECT * FROM pasien_online");
    if ($q_online) {
        while ($ro = mysqli_fetch_assoc($q_online)) {
            $id_online    = $ro['id_pendaftaran'] ?? ('ON' . rand(100,999));
            $no_rm_online = $ro['no_rm'] ?? 'RM9999';
            $nama_online  = $ro['nama_pasien'] ?? 'Pasien Online';
            $tgl_daftar   = $ro['tanggal_daftar'] ?? date('Y-m-d');
            $tgl_periksa  = $ro['tanggal_periksa'] ?? date('Y-m-d');
            $keluhan_on   = $ro['keluhan'] ?? 'Pendaftaran Online';
            $pelayanan    = $ro['id_pelayanan'] ?? 'PB1'; 

            mysqli_query($koneksi, "INSERT IGNORE INTO pasien (no_rm, nama_pasien) VALUES ('$no_rm_online', '$nama_online')");

            $cek_dup = mysqli_query($koneksi, "SELECT id_pendaftaran FROM pendaftaran WHERE id_pendaftaran = '$id_online' OR no_rm = '$no_rm_online'");
            if (mysqli_num_rows($cek_dup) == 0) {
                mysqli_query($koneksi, "INSERT INTO pendaftaran (id_pendaftaran, no_rm, id_petugas, tanggal_daftar, tanggal_periksa, id_pelayanan, keluhan, status_validasi) 
                                        VALUES ('$id_online', '$no_rm_online', 'P01', '$tgl_daftar', '$tgl_periksa', '$pelayanan', '$keluhan_on', 'Datang')");
            }
        }
    }
}

// --- 3. PROSES SIMPAN / EDIT DATA PENDAFTARAN ---
$notif = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_data'])) {
    $id_pendaftaran   = mysqli_real_escape_string($koneksi, $_POST['id_pendaftaran']);
    $id_petugas       = mysqli_real_escape_string($koneksi, $_POST['id_petugas']);
    $id_bidan_terapis = mysqli_real_escape_string($koneksi, $_POST['id_bidan_terapis']);
    $tanggal_daftar   = mysqli_real_escape_string($koneksi, $_POST['tanggal_daftar']);
    $tanggal_periksa  = mysqli_real_escape_string($koneksi, $_POST['tanggal_periksa']);
    $id_pelayanan     = mysqli_real_escape_string($koneksi, $_POST['id_pelayanan']);
    $status_validasi  = mysqli_real_escape_string($koneksi, $_POST['status_validasi']);
    $keluhan          = mysqli_real_escape_string($koneksi, $_POST['keluhan']);
    $is_edit          = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';

    if (!$is_edit) {
        // Mode Tambah
        $no_rm = mysqli_real_escape_string($koneksi, $_POST['no_rm'] ?? '');

        if(empty($no_rm)) {
            $notif = "Nomor Rekam Medis (No. RM) tidak boleh kosong!";
        } else {
            $query = "INSERT INTO pendaftaran (id_pendaftaran, no_rm, id_petugas, id_bidan_terapis, tanggal_daftar, tanggal_periksa, id_pelayanan, keluhan, status_validasi) 
                      VALUES ('$id_pendaftaran', '$no_rm', '$id_petugas', NULLIF('$id_bidan_terapis', ''), '$tanggal_daftar', '$tanggal_periksa', '$id_pelayanan', '$keluhan', '$status_validasi')";

            if (mysqli_query($koneksi, $query)) {
                $q_pmr_max = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(id_pemeriksaan, 4) AS UNSIGNED)) as max_no FROM pemeriksaan WHERE id_pemeriksaan LIKE 'PMR%'");
                $r_pmr_max = mysqli_fetch_assoc($q_pmr_max);
                $no_pmr    = ($r_pmr_max && $r_pmr_max['max_no'] > 0) ? (int)$r_pmr_max['max_no'] + 1 : 1;
                $auto_pmr_id = "PMR" . sprintf("%03d", $no_pmr);

                $query_sync = "INSERT INTO pemeriksaan (id_pemeriksaan, tanggal_periksa, id_pendaftaran, diagnosa, tindakan, status_validasi)
                    VALUES ('$auto_pmr_id', '$tanggal_periksa', '$id_pendaftaran', '-', '-', 'Belum')";
                mysqli_query($koneksi, $query_sync); 

                header("Location: pendaftaran.php?status=sukses");
                exit;
            } else {
                $notif = "Gagal menyimpan pendaftaran: " . mysqli_error($koneksi);
            }
        }
    } else {
        // Mode Edit
        $query_update = "UPDATE pendaftaran SET 
                         id_petugas = '$id_petugas', 
                         id_bidan_terapis = NULLIF('$id_bidan_terapis', ''), 
                         tanggal_daftar = '$tanggal_daftar', 
                         tanggal_periksa = '$tanggal_periksa', 
                         id_pelayanan = '$id_pelayanan', 
                         keluhan = '$keluhan', 
                         status_validasi = '$status_validasi' 
                         WHERE id_pendaftaran = '$id_pendaftaran'";

        if (mysqli_query($koneksi, $query_update)) {
            header("Location: pendaftaran.php?status=sukses");
            exit;
        } else {
            $notif = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}

// --- 4. PROSES HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM pemeriksaan WHERE id_pendaftaran = '$id_hapus'");
    mysqli_query($koneksi, "DELETE FROM pendaftaran WHERE id_pendaftaran = '$id_hapus'");
    header("Location: pendaftaran.php?status=terhapus");
    exit;
}

// --- Data edit jika ada param ?edit=... ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $r = mysqli_query($koneksi, "SELECT p.*, pas.nama_pasien FROM pendaftaran p LEFT JOIN pasien pas ON p.no_rm = pas.no_rm WHERE p.id_pendaftaran='$edit_id'");
    if ($r) $edit_data = mysqli_fetch_assoc($r);
}

// --- FUNGSI MAPPER KODE LAYANAN KE NAMA LENGKAP ---
function getNamaPelayanan($kode) {
    $daftar_layanan = [
        'PB1' => 'Pelayanan Bayi - Pijat Bayi Sehat & Bayi Sakit',
        'PB2' => 'Pelayanan Bayi - Pijat Bayi Prematur',
        'PB3' => 'Pelayanan Bayi - Perawatan New Born',
        'PB4' => 'Pelayanan Bayi - Baby Spa',
        'PB5' => 'Pelayanan Bayi - Pijat Anak Sehat & Sakit',
        'PB6' => 'Pelayanan Bayi - Kids Spa',
        'PW1' => 'Pelayanan Wanita - Pijat Ibu Hamil',
        'PW2' => 'Pelayanan Wanita - Pijat Laktasi',
        'PW3' => 'Pelayanan Wanita - Perawatan Pra Wedding',
        'PW4' => 'Pelayanan Wanita - Yoga Hamil',
        'PW5' => 'Pelayanan Wanita - Pijat Ibu Nifas',
        'PW6' => 'Pelayanan Wanita - Konsultasi Asi & MPASI',
        'TP1' => 'Terapi Patologi - Pijat Batuk Pilek',
        'TP2' => 'Terapi Patologi - Terapi Sembelit',
        'TP3' => 'Terapi Patologi - Terapi Tuina',
        'TP4' => 'Terapi Patologi - Terapi Iritasi Telinga',
        'TP5' => 'Terapi Patologi - Terapi Kesehatan Mata',
        'TP6' => 'Terapi Patologi - Terapi Persiapan Bicara'
    ];
    return isset($daftar_layanan[$kode]) ? $daftar_layanan[$kode] : $kode;
}

// --- 5. AMBIL DATA UTAMA UNTUK DITAMPILKAN DI TABEL ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where_cari = $keyword ? "WHERE p.id_pendaftaran LIKE '%$keyword%' OR pas.nama_pasien LIKE '%$keyword%' OR p.no_rm LIKE '%$keyword%' OR p.status_validasi LIKE '%$keyword%'" : "";

$query_tampil = "SELECT p.*, pas.nama_pasien, pet.nama_petugas,
                        pmr.id_pemeriksaan, pmr.status_validasi AS status_periksa
                 FROM pendaftaran p 
                 INNER JOIN pasien pas ON p.no_rm = pas.no_rm 
                 LEFT JOIN petugas pet ON p.id_petugas = pet.id_petugas
                 LEFT JOIN pemeriksaan pmr ON p.id_pendaftaran = pmr.id_pendaftaran
                 $where_cari
                 ORDER BY p.id_pendaftaran DESC";
$result_tampil = mysqli_query($koneksi, $query_tampil);

$data_pasien = mysqli_query($koneksi, "SELECT * FROM pasien ORDER BY nama_pasien ASC");
$data_petugas = mysqli_query($koneksi, "SELECT * FROM petugas ORDER BY nama_petugas ASC");

// Auto-generate ID Pendaftaran Baru
$q_id = mysqli_query($koneksi, "SELECT MAX(id_pendaftaran) as max_id FROM pendaftaran");
$r_id = mysqli_fetch_assoc($q_id);
$auto_id = "P001";
if ($r_id && !empty($r_id['max_id'])) {
    $no_urut = (int) substr($r_id['max_id'], 1) + 1;
    $auto_id = "P" . sprintf("%03d", $no_urut);
}

$page_title   = "Data Pendaftaran - PMB Siti Maryam";
$header_title = "Data Transaksi / Data Pendaftaran";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <?php if (!empty($notif)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $notif; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pendaftaran berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pendaftaran berhasil dihapus!</div>
        <?php endif; ?>

        <!-- ========== FORM INPUT LANGSUNG DI HALAMAN ========== -->
        <div class="form-card">
            <div class="form-card-header">
                <h5><i class="fa-solid fa-clipboard-user"></i> <?php echo $edit_data ? 'Edit Pendaftaran Pasien' : 'Input Data Pendaftaran Pasien'; ?></h5>
            </div>
            <form action="pendaftaran.php" method="POST">
                <input type="hidden" name="simpan_data" value="1">
                <input type="hidden" name="is_edit" value="<?php echo $edit_data ? '1' : '0'; ?>">

                <div class="form-inline-row">
                    <label>ID Pendaftaran :</label>
                    <input type="text" name="id_pendaftaran" value="<?php echo $edit_data ? htmlspecialchars($edit_data['id_pendaftaran']) : $auto_id; ?>" readonly style="background:#f8fafc; font-weight:bold; color:#0284c7;">
                </div>

                <?php if (!$edit_data): ?>
                <div class="form-inline-row">
                    <label>Pilih Pasien :</label>
                    <select name="no_rm" id="no_rm" required>
                        <option value="">-- Pilih Pasien --</option>
                        <?php 
                        if ($data_pasien) {
                            mysqli_data_seek($data_pasien, 0);
                            while($p = mysqli_fetch_assoc($data_pasien)) {
                                echo '<option value="'.$p['no_rm'].'">[RM: '.$p['no_rm'].'] - '.$p['nama_pasien'].'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-inline-row">
                    <label>Pasien :</label>
                    <input type="text" disabled value="[RM: <?php echo htmlspecialchars($edit_data['no_rm']); ?>] - <?php echo htmlspecialchars($edit_data['nama_pasien'] ?? ''); ?>" style="background:#f8fafc; font-weight:bold;">
                </div>
                <?php endif; ?>

                <div class="form-inline-row">
                    <label>Keluhan :</label>
                    <textarea name="keluhan" rows="2" placeholder="Masukkan keluhan periksa..." required><?php echo htmlspecialchars($edit_data['keluhan'] ?? ''); ?></textarea>
                </div>

                <div class="form-inline-row">
                    <label>Petugas :</label>
                    <select name="id_petugas" required>
                        <option value="">-- Pilih Petugas --</option>
                        <?php 
                        if ($data_petugas) {
                            mysqli_data_seek($data_petugas, 0);
                            while($pet = mysqli_fetch_assoc($data_petugas)) {
                                $sel = (($edit_data['id_petugas'] ?? '') == $pet['id_petugas']) ? 'selected' : '';
                                echo '<option value="'.$pet['id_petugas'].'" '.$sel.'>'.$pet['id_petugas'].' - '.$pet['nama_petugas'].'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-inline-row">
                    <label>ID Bidan / Terapis :</label>
                    <input type="text" name="id_bidan_terapis" placeholder="ID Bidan/Terapis (opsional)" value="<?php echo htmlspecialchars($edit_data['id_bidan_terapis'] ?? ''); ?>">
                </div>

                <!-- TANGGAL DAFTAR HANYA BISA DIPIIH SESUAI HARI INI -->
                <div class="form-inline-row">
                    <label>Tanggal Daftar :</label>
                    <input type="date" name="tanggal_daftar" 
                           value="<?php echo htmlspecialchars($edit_data['tanggal_daftar'] ?? date('Y-m-d')); ?>" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d'); ?>" 
                           required>
                </div>

                <!-- TANGGAL PERIKSA HANYA BISA DIPIIH SESUAI HARI INI -->
                <div class="form-inline-row">
                    <label>Tanggal Periksa :</label>
                    <input type="date" name="tanggal_periksa" 
                           value="<?php echo htmlspecialchars($edit_data['tanggal_periksa'] ?? date('Y-m-d')); ?>" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d'); ?>" 
                           required>
                </div>

                <div class="form-inline-row">
                    <label>Jenis Pelayanan :</label>
                    <select name="id_pelayanan" required>
                        <option value="">-- Pilih Pelayanan --</option>
                        <?php
                        $cur_pel = $edit_data['id_pelayanan'] ?? '';
                        $groups = [
                            'Pelayanan Bayi' => ['PB1'=>'PB1 - Pijat Bayi Sehat & Bayi Sakit','PB2'=>'PB2 - Pijat Bayi Prematur','PB3'=>'PB3 - Perawatan New Born','PB4'=>'PB4 - Baby Spa','PB5'=>'PB5 - Pijat Anak Sehat & Sakit','PB6'=>'PB6 - Kids Spa'],
                            'Pelayanan Wanita' => ['PW1'=>'PW1 - Pijat Ibu Hamil','PW2'=>'PW2 - Pijat Laktasi','PW3'=>'PW3 - Perawatan Pra Wedding','PW4'=>'PW4 - Yoga Hamil','PW5'=>'PW5 - Pijat Ibu Nifas','PW6'=>'PW6 - Konsultasi Asi & MPASI'],
                            'Terapi Patologi' => ['TP1'=>'TP1 - Pijat Batuk Pilek','TP2'=>'TP2 - Terapi Sembelit','TP3'=>'TP3 - Terapi Tuina','TP4'=>'TP4 - Terapi Iritasi Telinga','TP5'=>'TP5 - Terapi Kesehatan Mata','TP6'=>'TP6 - Terapi Persiapan Bicara']
                        ];
                        foreach($groups as $gLabel => $gOptions) {
                            echo '<optgroup label="'.$gLabel.'">';
                            foreach($gOptions as $val => $text) {
                                $s = ($cur_pel == $val) ? 'selected' : '';
                                echo '<option value="'.$val.'" '.$s.'>'.$text.'</option>';
                            }
                            echo '</optgroup>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-inline-row">
                    <label>Status Validasi :</label>
                    <select name="status_validasi" required>
                        <option value="Datang" <?php echo (($edit_data['status_validasi'] ?? 'Datang') == 'Datang') ? 'selected' : ''; ?>>Datang</option>
                        <option value="Tidak Datang" <?php echo (($edit_data['status_validasi'] ?? '') == 'Tidak Datang') ? 'selected' : ''; ?>>Tidak Datang</option>
                    </select>
                </div>

                <div class="form-action-row">
                    <button type="submit" class="btn-form-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    <a href="pendaftaran.php" class="btn-form-batal"><i class="fa-solid fa-xmark"></i> Batal</a>
                    <a href="pendaftaran.php" class="btn-form-selesai"><i class="fa-solid fa-check-double"></i> Selesai</a>
                </div>
            </form>
        </div>

        <!-- ========== PENCARIAN & TABEL ========== -->
        <div class="table-card">
            <div class="table-header">
                <form action="pendaftaran.php" method="GET" class="search-box">
                    <label style="font-weight:600; color:var(--text-main); white-space:nowrap;">Cari Nama ... :</label>
                    <input type="text" name="cari" placeholder="Cari Pendaftaran..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Pendaftaran</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Tgl Daftar</th>
                            <th>Tgl Periksa</th>
                            <th>Petugas</th>
                            <th>Pelayanan</th>
                            <th>Keluhan</th>
                            <th>Status Daftar</th>
                            <th>Status Periksa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($result_tampil && mysqli_num_rows($result_tampil) > 0) {
                            while ($row = mysqli_fetch_assoc($result_tampil)) {
                                $badge_status = ($row['status_validasi'] == 'Datang') ? 'badge-datang' : 'badge-tidak-datang';
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['id_pendaftaran']); ?></strong></td>
                            <td><span class="badge-rm"><?php echo htmlspecialchars($row['no_rm']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_pasien']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['tanggal_daftar']); ?></td>
                            <td><?php echo htmlspecialchars($row['tanggal_periksa']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_petugas'] ?? $row['id_petugas']); ?></td>
                            <td>
                                <span style="color: #0284c7; font-weight: 600;"><?php echo htmlspecialchars(getNamaPelayanan($row['id_pelayanan'])); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['keluhan']); ?></td>
                            <td><span class="badge-status <?php echo $badge_status; ?>"><?php echo htmlspecialchars($row['status_validasi']); ?></span></td>
                            <td>
                                <?php if (!empty($row['id_pemeriksaan'])): ?>
                                    <a href="pemeriksaan.php" class="badge-status badge-sudah" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <?php echo htmlspecialchars($row['id_pemeriksaan']); ?>
                                    </a>
                                    <?php if ($row['status_periksa'] == 'Sudah'): ?>
                                        <span class="badge-status badge-sudah" style="margin-top:3px; display:block;">Sudah Diperiksa</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-belum" style="margin-top:3px; display:block;">Belum Diperiksa</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge-status" style="background:#f1f5f9; color:#94a3b8;"><i class="fa-solid fa-clock"></i> Belum Sync</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="pendaftaran.php?edit=<?php echo urlencode($row['id_pendaftaran']); ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="pendaftaran.php?hapus=<?php echo $row['id_pendaftaran']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data pendaftaran ini? Data pemeriksaan terkait juga akan dihapus!')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="12" style="text-align: center; color: #94a3b8; padding: 20px;">Data pendaftaran belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>