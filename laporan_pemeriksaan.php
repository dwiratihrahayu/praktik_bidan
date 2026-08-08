<?php
session_start();
include 'koneksi.php';

// Proteksi halaman: Jika belum login, tendang balik ke login.php
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Cek file logo
$logo_path = file_exists('assets/images/logo.png') ? 'assets/images/logo.png' : (file_exists('logo.png') ? 'logo.png' : '');

// Ambil parameter aksi cetak satuan dan parameter pencarian
$aksi          = $_GET['aksi'] ?? '';
$id_pemeriksa  = $_GET['id_pemeriksaan'] ?? '';
$cari_diagnosa = $_GET['diagnosa'] ?? '';

// --- JIKA MODE CETAK SATUAN PER PEMERIKSAAN / PASIEN ---
if ($aksi === 'cetak_satuan' && !empty($id_pemeriksa)) {
    $q_satuan = mysqli_query($koneksi, "SELECT pemeriksaan.*, pendaftaran.tanggal_daftar, pendaftaran.no_rm, pasien.nama_pasien, pasien.nik, pasien.alamat, pasien.no_hp 
                                        FROM pemeriksaan 
                                        LEFT JOIN pendaftaran ON pemeriksaan.id_pendaftaran = pendaftaran.id_pendaftaran 
                                        LEFT JOIN pasien ON pendaftaran.no_rm = pasien.no_rm 
                                        WHERE pemeriksaan.id_pemeriksaan = '$id_pemeriksa' LIMIT 1");
    $data_satuan = mysqli_fetch_assoc($q_satuan);

    if (!$data_satuan) {
        echo "<script>alert('Data pemeriksaan tidak ditemukan!'); window.location='laporan_pemeriksaan.php';</script>";
        exit;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Pemeriksaan - <?php echo htmlspecialchars($data_satuan['id_pemeriksaan']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; color: #334155; padding: 30px; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #cbd5e1; padding-bottom: 20px; margin-bottom: 25px; gap: 20px; }
        .kop-logo { width: 70px; height: 70px; object-fit: contain; }
        .kop-text { text-align: center; flex: 1; }
        .kop-text h1 { font-size: 18px; color: #0f172a; text-transform: uppercase; font-weight: 700; }
        .kop-text h2 { font-size: 14px; color: #0284c7; margin-top: 3px; }
        .kop-text p { font-size: 11px; color: #64748b; margin-top: 2px; }

        .title-laporan { text-align: center; margin-bottom: 25px; }
        .title-laporan h3 { font-size: 16px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0f172a; display: inline-block; padding-bottom: 4px; }

        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px; margin-bottom: 30px; }
        .detail-item { display: flex; font-size: 13px; line-height: 1.6; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px; }
        .detail-item .label { width: 140px; font-weight: 600; color: #475569; flex-shrink: 0; }
        .detail-item .separator { margin-right: 10px; color: #94a3b8; }
        .detail-item .value { color: #1e293b; font-weight: 500; }

        .action-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .btn-back { background: #64748b; color: #fff; }
        .btn-print { background: #0284c7; color: #fff; }

        @media print {
            body { background-color: #ffffff; padding: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .action-buttons { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        <!-- Tombol Aksi -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-print"><i class="fa-solid fa-print"></i> Cetak Halaman</button>
            <a href="laporan_pemeriksaan.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Kop Surat -->
        <div class="kop-surat">
            <?php if (!empty($logo_path)): ?>
                <img src="<?php echo $logo_path; ?>" alt="Logo" class="kop-logo">
            <?php endif; ?>
            <div class="kop-text">
                <h1>PRAKTIK MANDIRI BIDAN (PMB) SITI MARYAM</h1>
                <h2>Layanan Kesehatan Ibu dan Anak & Spa</h2>
                <p>Jl. Kesehatan No. 1 - Telp. 081234567890</p>
            </div>
        </div>

        <div class="title-laporan">
            <h3>Surat / Laporan Hasil Pemeriksaan Pasien</h3>
        </div>

        <!-- Informasi Detail -->
        <div class="detail-grid">
            <div class="detail-item"><span class="label">ID Pemeriksaan</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['id_pemeriksaan']); ?></span></div>
            <div class="detail-item"><span class="label">Tanggal Periksa</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['tanggal_periksa']); ?></span></div>
            <div class="detail-item"><span class="label">ID Pendaftaran</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['id_pendaftaran']); ?></span></div>
            <div class="detail-item"><span class="label">Tanggal Daftar</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['tanggal_daftar'] ?? '-'); ?></span></div>
            <div class="detail-item"><span class="label">No. RM</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['no_rm'] ?? '-'); ?></span></div>
            <div class="detail-item"><span class="label">Nama Pasien</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['nama_pasien'] ?? '-'); ?></span></div>
            <div class="detail-item"><span class="label">NIK</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['nik'] ?? '-'); ?></span></div>
            <div class="detail-item"><span class="label">No. HP</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['no_hp'] ?? '-'); ?></span></div>
            <div class="detail-item"><span class="label">Diagnosa</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['diagnosa']); ?></span></div>
            <div class="detail-item"><span class="label">Tindakan</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['tindakan']); ?></span></div>
            <div class="detail-item"><span class="label">Status Validasi</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['status_validasi']); ?></span></div>
            <div class="detail-item"><span class="label">Alamat</span><span class="separator">:</span><span class="value"><?php echo htmlspecialchars($data_satuan['alamat'] ?? '-'); ?></span></div>
        </div>

        <!-- Tanda Tangan Footer Cetak -->
        <div style="float: right; margin-top: 40px; text-align: center; font-size: 13px;">
            <p>Bidan Pemeriksa,</p>
            <br><br><br>
            <p><strong>( Bidan Siti Maryam, S.Tr.Keb )</strong></p>
        </div>
    </div>

</body>
</html>
<?php 
    exit; 
}

// --- JIKA MODE UTAMA: LAPORAN KESELURUHAN ---
$query_sql = "SELECT pemeriksaan.*, pendaftaran.tanggal_daftar 
              FROM pemeriksaan 
              LEFT JOIN pendaftaran ON pemeriksaan.id_pendaftaran = pendaftaran.id_pendaftaran";
$info_filter = "Semua Data Pemeriksaan";

// Logika Filter Berdasarkan Diagnosa
if (!empty($cari_diagnosa)) {
    $query_sql .= " WHERE pemeriksaan.diagnosa LIKE '%$cari_diagnosa%'";
    $info_filter = "Hasil Pencarian Diagnosa: \"" . htmlspecialchars($cari_diagnosa) . "\"";
}

$query_sql .= " ORDER BY pemeriksaan.tanggal_periksa DESC";
$result = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Pemeriksaan - PMB Siti Maryam</title>
    <!-- FontAwesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; color: #334155; padding: 30px; }
        .container { max-width: 1100px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        /* Header Laporan */
        .header-laporan { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        .header-title-area { display: flex; align-items: center; gap: 15px; }
        .header-logo { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: #ffffff; border: 1px solid #e2e8f0; padding: 2px; }
        .header-laporan h2 { color: #1e293b; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* Tombol Aksi */
        .action-buttons { display: flex; gap: 10px; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .btn-back { background: #64748b; color: #fff; }
        .btn-back:hover { background: #475569; }
        .btn-print { background: #0284c7; color: #fff; }
        .btn-print:hover { background: #0284c7dd; }
        .btn-print-satuan { background: #10b981; color: #fff; padding: 5px 10px; font-size: 11px; }
        .btn-print-satuan:hover { background: #059669; }

        /* Form Filter Styling */
        .filter-box { background: #f8fafc; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 250px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; }
        .filter-group input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #fff; color: #334155; outline: none; }
        .btn-filter { background: #0f172a; color: #fff; padding: 8px 16px; height: 38px; border-radius: 6px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter:hover { background: #1e293b; }
        .btn-reset { background: #e2e8f0; color: #334155; padding: 8px 16px; height: 38px; border-radius: 6px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-reset:hover { background: #cbd5e1; }

        /* Info Filter Aktif */
        .filter-info { font-size: 13px; font-weight: 600; color: #0284c7; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; }

        /* Badge Status */
        .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-sudah { background-color: #dcfce7; color: #166534; }
        .badge-belum { background-color: #fee2e2; color: #991b1b; }

        /* Tabel Data */
        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        
        /* Alert Kosong */
        .alert { padding: 15px; background: #fef2f2; color: #dc2626; border-radius: 8px; margin-top: 15px; font-size: 14px; display: flex; align-items: center; gap: 10px; border: 1px solid #fecaca; }

        /* Pengaturan Khusus Saat Dicetak (Print) */
        @media print {
            body { background-color: #ffffff; padding: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .action-buttons, .filter-box, .col-aksi { display: none !important; }
            table { font-size: 11px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Bagian Header & Tombol Navigasi -->
        <div class="header-laporan">
            <div class="header-title-area">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?php echo $logo_path; ?>" alt="Logo" class="header-logo">
                <?php endif; ?>
                <h2><i class="fa-solid fa-file-lines" style="color: #0284c7;"></i> Laporan Data Pemeriksaan</h2>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-print"><i class="fa-solid fa-print"></i> Cetak Laporan</button>
                <a href="dashboard.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            </div>
        </div>

        <!-- Form Filter Berdasarkan Diagnosa -->
        <form method="GET" action="" class="filter-box">
            <div class="filter-group">
                <label for="diagnosa">Cari Berdasarkan Diagnosa</label>
                <input type="text" name="diagnosa" id="diagnosa" placeholder="Contoh: Demam..." value="<?php echo htmlspecialchars($cari_diagnosa); ?>">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-filter"><i class="fa-solid fa-search"></i> Cari</button>
                <a href="laporan_pemeriksaan.php" class="btn-reset">Reset</a>
            </div>
        </form>

        <div class="filter-info">
            <i class="fa-solid fa-circle-info"></i> <?php echo $info_filter; ?>
        </div>

        <!-- Bagian Konten Tabel -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Pemeriksaan</th>
                            <th>Tanggal Periksa</th>
                            <th>ID Pendaftaran</th>
                            <th>Tanggal Pendaftaran</th>
                            <th>Diagnosa</th>
                            <th>Tindakan</th>
                            <th>Status Validasi</th>
                            <th class="col-aksi">Aksi Cetak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                            $badge_class = ($row['status_validasi'] == 'Sudah') ? 'badge-sudah' : 'badge-belum';
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['id_pemeriksaan']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['tanggal_periksa']); ?></td>
                                <td><?php echo htmlspecialchars($row['id_pendaftaran']); ?></td>
                                <td><?php echo htmlspecialchars($row['tanggal_daftar'] ?? '-'); ?></td>
                                <td><span style="color: #0284c7; font-weight: 600;"><?php echo htmlspecialchars($row['diagnosa']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['tindakan']); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($row['status_validasi']); ?>
                                    </span>
                                </td>
                                <td class="col-aksi">
                                    <a href="laporan_pemeriksaan.php?aksi=cetak_satuan&id_pemeriksaan=<?php echo urlencode($row['id_pemeriksaan']); ?>" class="btn btn-print-satuan" target="_blank">
                                        <i class="fa-solid fa-print"></i> Cetak Pasien
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Tidak ada data pemeriksaan yang ditemukan dengan diagnosa tersebut.</span>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>