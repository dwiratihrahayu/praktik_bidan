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

// Cek apakah mode menampilkan detail cetak satuan per pasien
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';
$no_rm_satuan = isset($_GET['no_rm']) ? mysqli_real_escape_string($koneksi, $_GET['no_rm']) : '';

if ($aksi == 'detail' && !empty($no_rm_satuan)) {
    // --- MODE CETAK SATUAN PER PASIEN ---
    $query_satuan = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm = '$no_rm_satuan' LIMIT 1");
    $pasien = mysqli_fetch_assoc($query_satuan);

    if (!$pasien) {
        echo "<script>alert('Data pasien tidak ditemukan!'); window.location='laporan_pasien.php';</script>";
        exit;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Pasien - <?php echo htmlspecialchars($pasien['nama_pasien']); ?></title>
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
            <a href="laporan_pasien.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
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
            <h3>Detail Data Rekam Medis Pasien</h3>
        </div>

        <!-- Informasi Detail Pasien -->
        <div class="detail-grid">
            <?php foreach ($pasien as $kolom => $isi): ?>
                <div class="detail-item">
                    <span class="label"><?php echo ucwords(str_replace('_', ' ', $kolom)); ?></span>
                    <span class="separator">:</span>
                    <span class="value"><?php echo htmlspecialchars($isi !== null && $isi !== '' ? $isi : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tanda Tangan Footer Cetak -->
        <div style="float: right; margin-top: 40px; text-align: center; font-size: 13px;">
            <p>Petugas Administrasi,</p>
            <br><br><br>
            <p><strong>( ______________________ )</strong></p>
        </div>
    </div>

</body>
</html>
<?php 
    exit; // Berhenti agar kode laporan keseluruhan di bawah tidak ikut tereksekusi
}

// --- MODE UTAMA: LAPORAN KESELURUHAN DATA PASIEN ---
$query_sql = "SELECT * FROM pasien";
$result = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Pasien - PMB Siti Maryam</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; color: #334155; padding: 30px; }
        .container { max-width: 1100px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        .header-laporan { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        .header-title-area { display: flex; align-items: center; gap: 15px; }
        .header-logo { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: #ffffff; border: 1px solid #e2e8f0; padding: 2px; }
        .header-laporan h2 { color: #1e293b; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        
        .action-buttons { display: flex; gap: 10px; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .btn-back { background: #64748b; color: #fff; }
        .btn-back:hover { background: #475569; }
        .btn-print { background: #0284c7; color: #fff; }
        .btn-print:hover { background: #0284c7dd; }
        .btn-print-satuan { background: #10b981; color: #fff; padding: 5px 10px; font-size: 11px; }
        .btn-print-satuan:hover { background: #059669; }

        .table-responsive { overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        
        .alert { padding: 15px; background: #fef2f2; color: #dc2626; border-radius: 8px; margin-top: 15px; font-size: 14px; display: flex; align-items: center; gap: 10px; border: 1px solid #fecaca; }

        @media print {
            body { background-color: #ffffff; padding: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .action-buttons, .col-aksi { display: none !important; }
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
                <h2><i class="fa-solid fa-file-lines" style="color: #0284c7;"></i> Laporan Data Pasien</h2>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-print"><i class="fa-solid fa-print"></i> Cetak Semua</button>
                <a href="dashboard.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            </div>
        </div>

        <!-- Bagian Konten Tabel -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <?php 
                            $fields = mysqli_fetch_fields($result);
                            foreach ($fields as $field) {
                                $nama_kolom = ucwords(str_replace('_', ' ', $field->name));
                                echo "<th>" . htmlspecialchars($nama_kolom) . "</th>";
                            }
                            ?>
                            <th class="col-aksi">Aksi Cetak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value !== null && $value !== '' ? $value : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endforeach; ?>
                                <td class="col-aksi">
                                    <a href="laporan_pasien.php?aksi=detail&no_rm=<?php echo urlencode($row['no_rm']); ?>" class="btn btn-print-satuan">
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
                <span>Belum ada data pasien yang tersedia di database.</span>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>