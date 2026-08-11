<?php
session_start();
include 'koneksi.php';

// Proteksi Keamanan: Hanya Pasien yang Boleh Mengakses
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'Pasien') {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil data detail pasien dari database tabel pasien berdasarkan nama lengkap akun
$nama_lengkap_session = $_SESSION['nama_lengkap'];
$query_pasien = mysqli_query($koneksi, "SELECT * FROM pasien WHERE nama_pasien = '$nama_lengkap_session' LIMIT 1");
$data_pasien_master = mysqli_fetch_assoc($query_pasien);

if ($data_pasien_master) {
    $nama_pasien   = $data_pasien_master['nama_pasien'];
    $no_rm         = $data_pasien_master['no_rm'];
    $no_hp         = $data_pasien_master['no_hp'] ?? '-';
    $nik           = $data_pasien_master['nik'] ?? '-';
    $tgl_lahir     = (!empty($data_pasien_master['tanggal_lahir']) && $data_pasien_master['tanggal_lahir'] != '0000-00-00') ? date('d-m-Y', strtotime($data_pasien_master['tanggal_lahir'])) : '-';
    $alamat        = $data_pasien_master['alamat'] ?? '-';
    $jenis_kelamin = $data_pasien_master['jenis_kelamin'] ?? '-';
    $nama_ortu     = $data_pasien_master['nama_orang_tua'] ?? '-';
} else {
    $nama_pasien   = $nama_lengkap_session ?? $username;
    $no_rm         = 'RM' . str_pad($user_id, 3, '0', STR_PAD_LEFT); 
    $no_hp         = '-';
    $nik           = '-';
    $tgl_lahir     = '-';
    $alamat        = '-';
    $jenis_kelamin = '-';
    $nama_ortu     = '-';
}

// --- PROSES SIMPAN PENDAFTARAN ONLINE KHUSUS PASIEN LAMA ---
$notif_sukses = "";
$notif_error  = "";
$aktifkan_tab = "dashboard"; 

// Pastikan tabel petugas ada dan memiliki minimal 1 petugas default
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `petugas` (
  `id_petugas` VARCHAR(20) NOT NULL,
  `nama_petugas` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_petugas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$cek_petugas = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM petugas");
if ($cek_petugas && $row_petugas = mysqli_fetch_assoc($cek_petugas)) {
    if ($row_petugas['jml'] == 0) {
        mysqli_query($koneksi, "INSERT INTO petugas (id_petugas, nama_petugas) VALUES ('P01', 'Admin Utama'), ('P02', 'Petugas Pendaftaran 1')");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_pendaftaran_online'])) {
    $id_pendaftaran   = mysqli_real_escape_string($koneksi, $_POST['id_pendaftaran']);
    $tanggal_periksa  = mysqli_real_escape_string($koneksi, $_POST['tanggal_periksa']);
    $id_pelayanan     = mysqli_real_escape_string($koneksi, $_POST['id_pelayanan']);
    $keluhan          = mysqli_real_escape_string($koneksi, $_POST['keluhan']);
    $tanggal_daftar   = date('Y-m-d');
    $status_validasi  = 'Datang';
    
    $q_pet = mysqli_query($koneksi, "SELECT id_petugas FROM petugas ORDER BY id_petugas ASC LIMIT 1");
    $id_petugas = 'P01';
    if ($q_pet && mysqli_num_rows($q_pet) > 0) {
        $r_pet = mysqli_fetch_assoc($q_pet);
        $id_petugas = $r_pet['id_petugas'];
    }
    
    mysqli_query($koneksi, "INSERT IGNORE INTO pasien (no_rm, nama_pasien) VALUES ('$no_rm', '$nama_pasien')");

    $query_insert = "INSERT INTO pendaftaran (id_pendaftaran, no_rm, id_petugas, id_bidan_terapis, tanggal_daftar, tanggal_periksa, id_pelayanan, keluhan, status_validasi) 
                     VALUES ('$id_pendaftaran', '$no_rm', '$id_petugas', NULL, '$tanggal_daftar', '$tanggal_periksa', '$id_pelayanan', '$keluhan', '$status_validasi')";

    if (mysqli_query($koneksi, $query_insert)) {
        // --- SINKRONISASI OTOMATIS: Buat record pemeriksaan awal agar terhubung ---
        $q_pmr_max = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(id_pemeriksaan, 4) AS UNSIGNED)) as max_no FROM pemeriksaan WHERE id_pemeriksaan LIKE 'PMR%'");
        $r_pmr_max = mysqli_fetch_assoc($q_pmr_max);
        $no_pmr    = ($r_pmr_max && $r_pmr_max['max_no'] > 0) ? (int)$r_pmr_max['max_no'] + 1 : 1;
        $auto_pmr_id = "PMR" . sprintf("%03d", $no_pmr);

        mysqli_query($koneksi, "INSERT INTO pemeriksaan (id_pemeriksaan, tanggal_periksa, id_pendaftaran, diagnosa, tindakan, status_validasi)
                               VALUES ('$auto_pmr_id', '$tanggal_periksa', '$id_pendaftaran', '-', '-', 'Belum')");

        $notif_sukses = "Pendaftaran online berhasil dikirim dan tersimpan ke riwayat Anda!";
        $aktifkan_tab = "riwayat"; 
    } else {
        $notif_error = "Gagal memproses pendaftaran: " . mysqli_error($koneksi);
        $aktifkan_tab = "daftar"; 
    }
}

// Auto-generate ID Pendaftaran berikutnya
$q_id = mysqli_query($koneksi, "SELECT MAX(id_pendaftaran) as max_id FROM pendaftaran");
$r_id = mysqli_fetch_assoc($q_id);
$auto_id = "P001";
if ($r_id && !empty($r_id['max_id'])) {
    $clean_id = preg_replace('/[^0-9]/', '', $r_id['max_id']);
    if($clean_id !== '') {
        $no_urut = (int)$clean_id + 1;
        $auto_id = "P" . sprintf("%03d", $no_urut);
    }
}

// FUNGSI MAPPER KODE LAYANAN KE NAMA LENGKAP
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

// --- AMBIL RIWAYAT KUNJUNGAN + DIAGNOSA & TINDAKAN DARI TABEL PEMERIKSAAN ---
$query_kunjungan = mysqli_query($koneksi, "
    SELECT p.*, pmr.diagnosa, pmr.tindakan 
    FROM pendaftaran p 
    LEFT JOIN pemeriksaan pmr ON p.id_pendaftaran = pmr.id_pendaftaran 
    WHERE p.no_rm = '$no_rm' 
    ORDER BY p.tanggal_daftar DESC, p.id_pendaftaran DESC
");
$total_kunjungan = mysqli_num_rows($query_kunjungan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pasien - PMB Siti Maryam</title>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Style CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .stat-card { background: #ffffff; padding: 20px 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 5px solid #0284c7; }
        .stat-card.green { border-left-color: #10b981; }
        .stat-card .title { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 22px; font-weight: 700; color: #0f172a; margin-top: 8px; }
        .section-card { background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; border: 1px solid var(--border-color); }
        .section-header { padding: 18px 25px; border-bottom: 1px solid #f1f5f9; font-size: 15px; font-weight: 700; color: #1e293b; background: #f8fafc; }
        .section-body { padding: 25px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 40px; }
        .info-item { display: flex; font-size: 14px; line-height: 1.6; }
        .info-item .label { width: 140px; font-weight: 700; color: #0f172a; flex-shrink: 0; }
        .info-item .separator { margin-right: 10px; font-weight: 600; color: #64748b; }
        .info-item .value { color: #334155; }
        .welcome-card { background: #ffffff; padding: 20px 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color); }
        .welcome-card h2 { font-size: 20px; color: #0f172a; font-weight: 700; }
        .welcome-card p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

    <div class="sidebar" style="background-color: #0f172a;">
        <div class="sidebar-brand">
            <div class="sidebar-logo" style="width: 65px; height: 65px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; overflow: hidden;">
                <?php 
                $logo_pasien_src = file_exists('assets/images/logo.png') ? 'assets/images/logo.png' : (file_exists('logo.png') ? 'logo.png' : '');
                if (!empty($logo_pasien_src)): 
                ?>
                    <img src="<?php echo $logo_pasien_src; ?>" alt="Logo PMB" style="width: 100%; height: 100%; object-fit: contain;">
                <?php else: ?>
                    <i class="fa-solid fa-user-nurse" style="font-size: 28px; color: #0284c7;"></i>
                <?php endif; ?>
            </div>
            <h3>PORTAL PASIEN</h3>
            <p>PMB Siti Maryam</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" id="menu-dashboard" onclick="switchTab('dashboard', this)"><i class="fa-solid fa-gauge"></i> Dashboard Utama</a></li>
            <li><a href="#" id="menu-daftar" onclick="switchTab('daftar', this)"><i class="fa-solid fa-calendar-plus"></i> Daftar Online (Pasien Lama)</a></li>
            <li><a href="#" id="menu-riwayat" onclick="switchTab('riwayat', this)"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Kunjungan</a></li>
            <li><a href="logout.php" class="logout" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="welcome-card">
            <div>
                <h2>Selamat Datang, <?php echo htmlspecialchars($nama_pasien); ?>! 👋</h2>
                <p>Sistem Informasi Pelayanan Kesehatan Ibu dan Anak</p>
            </div>
            <div>
                <span class="badge-rm"><i class="fa-solid fa-id-card"></i> RM: <?php echo htmlspecialchars($no_rm); ?></span>
            </div>
        </div>

        <!-- TAB 1: DASHBOARD UTAMA -->
        <div id="tab-dashboard" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="title">TOTAL KUNJUNGAN BEROBAT</div>
                    <div class="value"><?php echo $total_kunjungan; ?> Kunjungan</div>
                </div>
                <div class="stat-card green">
                    <div class="title">NO. HANDPHONE PASIEN</div>
                    <div class="value"><?php echo htmlspecialchars($no_hp); ?></div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">Informasi Data Diri Pasien</div>
                <div class="section-body">
                    <div class="info-grid">
                        <div>
                            <div class="info-item"><span class="label">NIK</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($nik); ?></span></div>
                            <div class="info-item" style="margin-top: 12px;"><span class="label">Tanggal Lahir</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($tgl_lahir); ?></span></div>
                            <div class="info-item" style="margin-top: 12px;"><span class="label">Alamat</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($alamat); ?></span></div>
                        </div>
                        <div>
                            <div class="info-item"><span class="label">Jenis Kelamin</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($jenis_kelamin); ?></span></div>
                            <div class="info-item" style="margin-top: 12px;"><span class="label">Nama Orang Tua</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($nama_ortu); ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: DAFTAR ONLINE -->
        <div id="tab-daftar" class="tab-content">
            <div class="section-card">
                <div class="section-header">Form Pendaftaran Online - Pasien Lama</div>
                <div class="section-body">
                    <?php if (!empty($notif_sukses)): ?><div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $notif_sukses; ?></div><?php endif; ?>
                    <?php if (!empty($notif_error)): ?><div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $notif_error; ?></div><?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="simpan_pendaftaran_online" value="1">
                        <div class="form-group">
                            <label>ID Pendaftaran (Otomatis)</label>
                            <input type="text" name="id_pendaftaran" value="<?php echo $auto_id; ?>" readonly style="background: #f8fafc; font-weight: bold; color: #0284c7;">
                        </div>
                        <div class="form-group">
                            <label>Nomor Rekam Medis (No. RM)</label>
                            <input type="text" value="<?php echo htmlspecialchars($no_rm); ?>" readonly style="background: #f8fafc; font-weight: bold;">
                        </div>
                        <div class="form-group">
                            <label>Nama Pasien</label>
                            <input type="text" value="<?php echo htmlspecialchars($nama_pasien); ?>" readonly style="background: #f8fafc; font-weight: bold;">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Rencana Periksa</label>
                            <input type="date" name="tanggal_periksa" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Jenis Pelayanan</label>
                            <select name="id_pelayanan" required>
                                <option value="" disabled selected>-- Pilih Jenis Pelayanan --</option>
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
                            <label>Keluhan / Catatan Pemeriksaan</label>
                            <textarea name="keluhan" rows="4" placeholder="Tuliskan keluhan atau tujuan pemeriksaan Anda di sini..." required></textarea>
                        </div>
                        <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Kirim Pendaftaran Online</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB 3: RIWAYAT KUNJUNGAN -->
        <div id="tab-riwayat" class="tab-content">
            <div class="section-card">
                <div class="section-header">Riwayat Kunjungan / Berobat Anda (No. RM: <?php echo htmlspecialchars($no_rm); ?>)</div>
                <div class="section-body">
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Pendaftaran</th>
                                    <th>Tgl Daftar</th>
                                    <th>Tgl Periksa</th>
                                    <th>Jenis Pelayanan</th>
                                    <th>Keluhan</th>
                                    <th>Diagnosa</th>
                                    <th>Tindakan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($total_kunjungan > 0) {
                                    mysqli_data_seek($query_kunjungan, 0);
                                    while ($row = mysqli_fetch_assoc($query_kunjungan)) {
                                        $diagnosa = (!empty($row['diagnosa']) && $row['diagnosa'] !== '-') ? $row['diagnosa'] : '-';
                                        $tindakan = (!empty($row['tindakan']) && $row['tindakan'] !== '-') ? $row['tindakan'] : '-';
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['id_pendaftaran']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['tanggal_daftar']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tanggal_periksa']); ?></td>
                                    <td><span style="color: #0284c7; font-weight: 600;"><?php echo htmlspecialchars(getNamaPelayanan($row['id_pelayanan'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['keluhan']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($diagnosa); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($tindakan); ?></strong></td>
                                    <td><span style="color: #0369a1; font-weight: 600;"><?php echo htmlspecialchars($row['status_validasi']); ?></span></td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="9" class="empty-state" style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada riwayat kunjungan berobat yang tercatat untuk pasien ini.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabId, element) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            const menus = document.querySelectorAll('.sidebar-menu li a');
            menus.forEach(menu => {
                if(!menu.classList.contains('logout')) {
                    menu.classList.remove('active');
                }
            });
            document.getElementById('tab-' + tabId).classList.add('active');
            if(element) {
                element.classList.add('active');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            let initialTab = "<?php echo $aktifkan_tab; ?>";
            let targetMenu = document.getElementById('menu-' + initialTab);
            if(targetMenu) {
                switchTab(initialTab, targetMenu);
            } else {
                switchTab('dashboard', document.getElementById('menu-dashboard'));
            }
        });
    </script>
</body>
</html>