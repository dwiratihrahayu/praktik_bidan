<?php
// Tampilkan error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

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

// Kolom id_pelayanan diubah menjadi ENUM sesuai daftar ID baru (PB1 - PB6, PW1 - PW6, TP1 - TP6)
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

// ALTER TABLE jika tabel sudah ada sebelumnya agar kolom ENUM ter-update otomatis
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

// --- 3. PROSES SIMPAN / TAMBAH DATA PADA FORM ONSITE ---
$notif = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_data'])) {
    $id_pendaftaran   = mysqli_real_escape_string($koneksi, $_POST['id_pendaftaran']);
    $id_petugas       = mysqli_real_escape_string($koneksi, $_POST['id_petugas']);
    $id_bidan_terapis = mysqli_real_escape_string($koneksi, $_POST['id_bidan_terapis']);
    $tanggal_daftar   = mysqli_real_escape_string($koneksi, $_POST['tanggal_daftar']);
    $tanggal_periksa  = mysqli_real_escape_string($koneksi, $_POST['tanggal_periksa']);
    $id_pelayanan     = mysqli_real_escape_string($koneksi, $_POST['id_pelayanan']);
    $status_validasi  = mysqli_real_escape_string($koneksi, $_POST['status_validasi']);

    $tipe_pasien = mysqli_real_escape_string($koneksi, $_POST['tipe_pasien']);
    if ($tipe_pasien == 'lama') {
        $no_rm = mysqli_real_escape_string($koneksi, $_POST['no_rm_lama']);
    } else {
        $no_rm = mysqli_real_escape_string($koneksi, $_POST['no_rm_baru']);
        $nama_pasien_baru = mysqli_real_escape_string($koneksi, $_POST['nama_pasien_baru']);
        $nik_baru = mysqli_real_escape_string($koneksi, $_POST['nik_baru'] ?? '');
        $tanggal_lahir_baru = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir_baru'] ?? '');
        $jenis_kelamin_baru = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin_baru'] ?? '');
        $nama_orang_tua_baru = mysqli_real_escape_string($koneksi, $_POST['nama_orang_tua_baru'] ?? '');
        $no_hp_baru = mysqli_real_escape_string($koneksi, $_POST['no_hp_baru'] ?? '');
        $alamat_baru = mysqli_real_escape_string($koneksi, $_POST['alamat_baru'] ?? '');
        $username_pasien = mysqli_real_escape_string($koneksi, $_POST['username_pasien'] ?? '');
        $password_pasien = mysqli_real_escape_string($koneksi, $_POST['password_pasien'] ?? '');
        
        if(!empty($nama_pasien_baru)) {
            mysqli_query($koneksi, "INSERT INTO pasien (no_rm, nik, nama_pasien, tanggal_lahir, nama_orang_tua, jenis_kelamin, no_hp, alamat) VALUES ('$no_rm', '$nik_baru', '$nama_pasien_baru', '$tanggal_lahir_baru', '$nama_orang_tua_baru', '$jenis_kelamin_baru', '$no_hp_baru', '$alamat_baru') ON DUPLICATE KEY UPDATE nama_pasien='$nama_pasien_baru', nik='$nik_baru', tanggal_lahir='$tanggal_lahir_baru', nama_orang_tua='$nama_orang_tua_baru', jenis_kelamin='$jenis_kelamin_baru', no_hp='$no_hp_baru', alamat='$alamat_baru'");
            
            if(!empty($username_pasien) && !empty($password_pasien)) {
                $hashed_password = password_hash($password_pasien, PASSWORD_DEFAULT);
                $cek_username = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username_pasien'");
                if(mysqli_num_rows($cek_username) == 0) {
                    mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username_pasien', '$hashed_password', '$nama_pasien_baru', 'Pasien')");
                }
            }
        }
    }
    $keluhan = mysqli_real_escape_string($koneksi, $_POST['keluhan']);

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

            echo "<script>alert('Pendaftaran berhasil diproses! Data pemeriksaan otomatis dibuat (ID: $auto_pmr_id).'); window.location='pendaftaran.php';</script>";
            exit;
        } else {
            $notif = "Gagal menyimpan pendaftaran: " . mysqli_error($koneksi);
        }
    }
}

// --- 4. PROSES EDIT DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_data'])) {
    $id_pendaftaran   = mysqli_real_escape_string($koneksi, $_POST['id_pendaftaran']);
    $id_petugas       = mysqli_real_escape_string($koneksi, $_POST['id_petugas']);
    $id_bidan_terapis = mysqli_real_escape_string($koneksi, $_POST['id_bidan_terapis']);
    $tanggal_daftar   = mysqli_real_escape_string($koneksi, $_POST['tanggal_daftar']);
    $tanggal_periksa  = mysqli_real_escape_string($koneksi, $_POST['tanggal_periksa']);
    $id_pelayanan     = mysqli_real_escape_string($koneksi, $_POST['id_pelayanan']);
    $keluhan          = mysqli_real_escape_string($koneksi, $_POST['keluhan']);
    $status_validasi  = mysqli_real_escape_string($koneksi, $_POST['status_validasi']);

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
        echo "<script>alert('Data berhasil diperbarui!'); window.location='pendaftaran.php';</script>";
        exit;
    } else {
        $notif = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
}

// --- 5. PROSES HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM pemeriksaan WHERE id_pendaftaran = '$id_hapus'");
    mysqli_query($koneksi, "DELETE FROM pendaftaran WHERE id_pendaftaran = '$id_hapus'");
    echo "<script>alert('Data berhasil dihapus beserta data pemeriksaan terkait!'); window.location='pendaftaran.php';</script>";
    exit;
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

// --- 6. AMBIL DATA UTAMA UNTUK DITAMPILKAN DI TABEL ---
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

// Auto-generate No. RM Pasien Baru
$q_rm = mysqli_query($koneksi, "SELECT MAX(no_rm) as max_rm FROM pasien");
$r_rm = mysqli_fetch_assoc($q_rm);
$auto_rm = "RM0001";
if ($r_rm && !empty($r_rm['max_rm'])) {
    $clean_rm = preg_replace('/[^0-9]/', '', $r_rm['max_rm']);
    if($clean_rm !== '') {
        $no_rm_urut = (int)$clean_rm + 1;
        $auto_rm = "RM" . sprintf("%04d", $no_rm_urut);
    }
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

            <div class="table-card">
                <div class="table-header">
                    <button class="btn-add" onclick="bukaModalTambah()">
                        <i class="fa-solid fa-plus"></i> Tambah Pendaftaran
                    </button>

                    <form action="pendaftaran.php" method="GET" class="search-box">
                        <input type="text" name="cari" placeholder="Cari Pendaftaran..." value="<?php echo htmlspecialchars($keyword); ?>">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
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
                                    <button class="btn-action btn-edit" onclick="bukaModalEdit('<?php echo $row['id_pendaftaran']; ?>', '<?php echo $row['no_rm']; ?>', '<?php echo addslashes($row['nama_pasien']); ?>', '<?php echo $row['id_petugas']; ?>', '<?php echo $row['id_bidan_terapis']; ?>', '<?php echo $row['tanggal_daftar']; ?>', '<?php echo $row['tanggal_periksa']; ?>', '<?php echo $row['id_pelayanan']; ?>', '<?php echo addslashes($row['keluhan']); ?>', '<?php echo $row['status_validasi']; ?>')" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
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

    <!-- MODAL FORM TAMBAH PENDAFTARAN -->
    <div class="modal-overlay" id="modalFormPendaftaran">
        <div class="modal-box">
            <h3>Tambah Pendaftaran Pasien</h3>
            <form action="pendaftaran.php" method="POST">
                <input type="hidden" name="simpan_data" value="1">

                <div class="form-group">
                    <label>ID Pendaftaran (Otomatis)</label>
                    <input type="text" name="id_pendaftaran" value="<?php echo $auto_id; ?>" readonly style="background: #f8fafc; font-weight: bold; color: #0284c7;">
                </div>

                <div class="form-group">
                    <label>Kategori Pasien</label>
                    <div class="toggle-container">
                        <label><input type="radio" name="tipe_pasien" value="lama" checked onclick="gantiTipePasien('lama')"> Pasien Lama</label>
                        <label><input type="radio" name="tipe_pasien" value="baru" onclick="gantiTipePasien('baru')"> Pasien Baru</label>
                    </div>
                </div>

                <div id="groupPasienLama" class="form-group">
                    <label>Pilih Pasien Lama</label>
                    <select name="no_rm_lama" id="no_rm_lama">
                        <option value="">-- Pilih Pasien Lama --</option>
                        <?php 
                        mysqli_data_seek($data_pasien, 0);
                        while($p = mysqli_fetch_assoc($data_pasien)) {
                            echo '<option value="'.$p['no_rm'].'">[RM: '.$p['no_rm'].'] - '.$p['nama_pasien'].'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div id="groupPasienBaru" style="display: none;">
                    <div class="form-group">
                        <label>No. Rekam Medis Baru (Otomatis)</label>
                        <input type="text" name="no_rm_baru" id="no_rm_baru" value="<?php echo $auto_rm; ?>" readonly style="background: #f8fafc; font-weight: bold; color: #0284c7;">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap Pasien Baru</label>
                        <input type="text" name="nama_pasien_baru" id="nama_pasien_baru" placeholder="Masukkan nama lengkap pasien...">
                    </div>
                    <div class="form-group">
                        <label>NIK</label>
                        <input type="text" name="nik_baru" id="nik_baru" placeholder="Masukkan NIK 16 digit">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir_baru" id="tanggal_lahir_baru">
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin_baru" id="jenis_kelamin_baru">
                            <option value="Laki-laki">Laki-laki (L)</option>
                            <option value="Perempuan">Perempuan (P)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Orang Tua</label>
                        <input type="text" name="nama_orang_tua_baru" id="nama_orang_tua_baru" placeholder="Nama ayah / ibu">
                    </div>
                    <div class="form-group">
                        <label>No. Handphone</label>
                        <input type="text" name="no_hp_baru" id="no_hp_baru" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat_baru" id="alamat_baru" rows="2" placeholder="Alamat lengkap pasien..."></textarea>
                    </div>
                    <hr style="margin: 15px 0; border: 0; border-top: 1px dashed #cbd5e1;">
                    <div class="form-group">
                        <label>Username (Untuk Login Pasien)</label>
                        <input type="text" name="username_pasien" id="username_pasien" placeholder="Buat username untuk pasien login">
                    </div>
                    <div class="form-group">
                        <label>Password (Untuk Login Pasien)</label>
                        <input type="password" name="password_pasien" id="password_pasien" placeholder="Buat password">
                    </div>
                </div>

                <div class="form-group">
                    <label>Keluhan Pasien</label>
                    <textarea name="keluhan" rows="3" placeholder="Masukkan keluhan periksa..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Petugas yang Melayani</label>
                    <select name="id_petugas" required>
                        <option value="">-- Pilih Petugas --</option>
                        <?php 
                        mysqli_data_seek($data_petugas, 0);
                        while($pet = mysqli_fetch_assoc($data_petugas)) {
                            echo '<option value="'.$pet['id_petugas'].'">'.$pet['id_petugas'].' - '.$pet['nama_petugas'].'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ID Bidan / Terapis (Opsional)</label>
                    <input type="text" name="id_bidan_terapis" placeholder="Masukkan ID Bidan/Terapis jika ada...">
                </div>

                <div class="form-group">
                    <label>Tanggal Daftar</label>
                    <input type="date" name="tanggal_daftar" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Tanggal Periksa</label>
                    <input type="date" name="tanggal_periksa" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <!-- Input Pilihan Pelayanan Berdasarkan ID Baru (PB1, PB2, dst) -->
                <div class="form-group">
                    <label>Jenis Pelayanan</label>
                    <select name="id_pelayanan" required>
                        <option value="">-- Pilih Pelayanan --</option>
                        <optgroup label="Pelayanan Bayi">
                            <option value="PB1">PB1 - Pijat Bayi Sehat & Bayi Sakit</option>
                            <option value="PB2">PB2 - Pijat Bayi Prematur</option>
                            <option value="PB3">PB3 - Perawatan New Born</option>
                            <option value="PB4">PB4 - Baby Spa</option>
                            <option value="PB5">PB5 - Pijat Anak Sehat & Sakit</option>
                            <option value="PB6">PB6 - Kids Spa</option>
                        </optgroup>
                        <optgroup label="Pelayanan Wanita">
                            <option value="PW1">PW1 - Pijat Ibu Hamil</option>
                            <option value="PW2">PW2 - Pijat Laktasi</option>
                            <option value="PW3">PW3 - Perawatan Pra Wedding</option>
                            <option value="PW4">PW4 - Yoga Hamil</option>
                            <option value="PW5">PW5 - Pijat Ibu Nifas</option>
                            <option value="PW6">PW6 - Konsultasi Asi & MPASI</option>
                        </optgroup>
                        <optgroup label="Terapi Patologi">
                            <option value="TP1">TP1 - Pijat Batuk Pilek</option>
                            <option value="TP2">TP2 - Terapi Sembelit</option>
                            <option value="TP3">TP3 - Terapi Tuina</option>
                            <option value="TP4">TP4 - Terapi Iritasi Telinga</option>
                            <option value="TP5">TP5 - Terapi Kesehatan Mata</option>
                            <option value="TP6">TP6 - Terapi Persiapan Bicara</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status Validasi</label>
                    <select name="status_validasi" required>
                        <option value="Datang">Datang</option>
                        <option value="Tidak Datang">Tidak Datang</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="tutupModalTambah()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan Pendaftaran</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PENDAFTARAN -->
    <div class="modal-overlay" id="modalFormEdit">
        <div class="modal-box">
            <h3>Edit Pendaftaran Pasien</h3>
            <form action="pendaftaran.php" method="POST">
                <input type="hidden" name="edit_data" value="1">

                <div class="form-group">
                    <label>ID Pendaftaran</label>
                    <input type="text" name="id_pendaftaran" id="edit_id_pendaftaran" readonly style="background: #f8fafc; font-weight: bold; color: #0284c7;">
                </div>

                <div class="form-group">
                    <label>No. RM &amp; Nama Pasien</label>
                    <input type="text" id="edit_info_pasien" readonly style="background: #f8fafc; font-weight: bold;">
                </div>

                <div class="form-group">
                    <label>Petugas</label>
                    <select name="id_petugas" id="edit_id_petugas" required>
                        <?php 
                        mysqli_data_seek($data_petugas, 0);
                        while($pet = mysqli_fetch_assoc($data_petugas)) {
                            echo '<option value="'.$pet['id_petugas'].'">'.$pet['id_petugas'].' - '.$pet['nama_petugas'].'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ID Bidan / Terapis</label>
                    <input type="text" name="id_bidan_terapis" id="edit_id_bidan_terapis">
                </div>

                <div class="form-group">
                    <label>Tanggal Daftar</label>
                    <input type="date" name="tanggal_daftar" id="edit_tanggal_daftar" required>
                </div>

                <div class="form-group">
                    <label>Tanggal Periksa</label>
                    <input type="date" name="tanggal_periksa" id="edit_tanggal_periksa" required>
                </div>

                <!-- Input Pilihan Pelayanan Edit Berdasarkan ID Baru (PB1, PB2, dst) -->
                <div class="form-group">
                    <label>Jenis Pelayanan</label>
                    <select name="id_pelayanan" id="edit_id_pelayanan" required>
                        <option value="">-- Pilih Pelayanan --</option>
                        <optgroup label="Pelayanan Bayi">
                            <option value="PB1">PB1 - Pijat Bayi Sehat & Bayi Sakit</option>
                            <option value="PB2">PB2 - Pijat Bayi Prematur</option>
                            <option value="PB3">PB3 - Perawatan New Born</option>
                            <option value="PB4">PB4 - Baby Spa</option>
                            <option value="PB5">PB5 - Pijat Anak Sehat & Sakit</option>
                            <option value="PB6">PB6 - Kids Spa</option>
                        </optgroup>
                        <optgroup label="Pelayanan Wanita">
                            <option value="PW1">PW1 - Pijat Ibu Hamil</option>
                            <option value="PW2">PW2 - Pijat Laktasi</option>
                            <option value="PW3">PW3 - Perawatan Pra Wedding</option>
                            <option value="PW4">PW4 - Yoga Hamil</option>
                            <option value="PW5">PW5 - Pijat Ibu Nifas</option>
                            <option value="PW6">PW6 - Konsultasi Asi & MPASI</option>
                        </optgroup>
                        <optgroup label="Terapi Patologi">
                            <option value="TP1">TP1 - Pijat Batuk Pilek</option>
                            <option value="TP2">TP2 - Terapi Sembelit</option>
                            <option value="TP3">TP3 - Terapi Tuina</option>
                            <option value="TP4">TP4 - Terapi Iritasi Telinga</option>
                            <option value="TP5">TP5 - Terapi Kesehatan Mata</option>
                            <option value="TP6">TP6 - Terapi Persiapan Bicara</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label>Keluhan</label>
                    <textarea name="keluhan" id="edit_keluhan" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label>Status Validasi</label>
                    <select name="status_validasi" id="edit_status_validasi" required>
                        <option value="Datang">Datang</option>
                        <option value="Tidak Datang">Tidak Datang</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="tutupModalEdit()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalTambah = document.getElementById('modalFormPendaftaran');
        const modalEdit = document.getElementById('modalFormEdit');

        function toggleDropdown(element) {
            element.parentElement.classList.toggle('active');
        }

        function bukaModalTambah() { modalTambah.style.display = 'flex'; }
        function tutupModalTambah() { modalTambah.style.display = 'none'; }
        function tutupModalEdit() { modalEdit.style.display = 'none'; }

        function gantiTipePasien(tipe) {
            const groupLama = document.getElementById('groupPasienLama');
            const groupBaru = document.getElementById('groupPasienBaru');
            if(tipe === 'lama') {
                groupLama.style.display = 'block';
                groupBaru.style.display = 'none';
                document.getElementById('no_rm_lama').required = true;
                document.getElementById('nama_pasien_baru').required = false;
                document.getElementById('nik_baru').required = false;
                document.getElementById('tanggal_lahir_baru').required = false;
                document.getElementById('jenis_kelamin_baru').required = false;
                document.getElementById('nama_orang_tua_baru').required = false;
                document.getElementById('no_hp_baru').required = false;
                document.getElementById('username_pasien').required = false;
                document.getElementById('password_pasien').required = false;
            } else {
                groupLama.style.display = 'none';
                groupBaru.style.display = 'block';
                document.getElementById('no_rm_lama').required = false;
                document.getElementById('nama_pasien_baru').required = true;
                document.getElementById('nik_baru').required = true;
                document.getElementById('tanggal_lahir_baru').required = true;
                document.getElementById('jenis_kelamin_baru').required = true;
                document.getElementById('nama_orang_tua_baru').required = true;
                document.getElementById('no_hp_baru').required = true;
                document.getElementById('username_pasien').required = true;
                document.getElementById('password_pasien').required = true;
            }
        }

        function bukaModalEdit(id_pendaftaran, no_rm, nama_pasien, id_petugas, id_bidan_terapis, tanggal_daftar, tanggal_periksa, id_pelayanan, keluhan, status_validasi) {
            document.getElementById('edit_id_pendaftaran').value = id_pendaftaran;
            document.getElementById('edit_info_pasien').value = "[" + no_rm + "] " + nama_pasien;
            document.getElementById('edit_id_petugas').value = id_petugas;
            document.getElementById('edit_id_bidan_terapis').value = id_bidan_terapis;
            document.getElementById('edit_tanggal_daftar').value = tanggal_daftar;
            document.getElementById('edit_tanggal_periksa').value = tanggal_periksa;
            document.getElementById('edit_id_pelayanan').value = id_pelayanan;
            document.getElementById('edit_keluhan').value = keluhan;
            document.getElementById('edit_status_validasi').value = status_validasi;
            modalEdit.style.display = 'flex';
        }

        window.onclick = function(e) {
            if (e.target === modalTambah) tutupModalTambah();
            if (e.target === modalEdit) tutupModalEdit();
        }
    </script>

<?php include 'includes/footer.php'; ?>