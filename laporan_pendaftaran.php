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

// Ambil parameter filter dari URL (default 'semua')
$filter = $_GET['filter'] ?? 'semua';
$nilai_filter = $_GET['nilai'] ?? '';

// Menggunakan kolom tanggal_daftar sesuai database Anda
$kolom_tanggal = "tanggal_daftar"; 

// Buat query dasar
$query_sql = "SELECT * FROM pendaftaran";
$info_filter = "Semua Data Pendaftaran";

// Logika Filter Berdasarkan Periode
if ($filter == 'harian' && !empty($nilai_filter)) {
    $query_sql .= " WHERE DATE($kolom_tanggal) = '$nilai_filter'";
    $info_filter = "Periode Harian: " . date('d-m-Y', strtotime($nilai_filter));
} elseif ($filter == 'mingguan' && !empty($nilai_filter)) {
    $tahun = substr($nilai_filter, 0, 4);
    $minggu = substr($nilai_filter, 6);
    $query_sql .= " WHERE YEAR($kolom_tanggal) = $tahun AND WEEK($kolom_tanggal, 1) = $minggu";
    $info_filter = "Periode Mingguan ke-$minggu Tahun $tahun";
} elseif ($filter == 'bulanan' && !empty($nilai_filter)) {
    $tahun = substr($nilai_filter, 0, 4);
    $bulan = substr($nilai_filter, 5, 2);
    $query_sql .= " WHERE YEAR($kolom_tanggal) = $tahun AND MONTH($kolom_tanggal) = $bulan";
    
    $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $info_filter = "Periode Bulanan: " . $nama_bulan[(int)$bulan] . " $tahun";
} elseif ($filter == 'tahunan' && !empty($nilai_filter)) {
    $query_sql .= " WHERE YEAR($kolom_tanggal) = '$nilai_filter'";
    $info_filter = "Periode Tahunan: Tahun $nilai_filter";
}

$result = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Pendaftaran - PMB Siti Maryam</title>
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

        /* Form Filter Styling */
        .filter-box { background: #f8fafc; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 180px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; }
        .filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #fff; color: #334155; }
        .btn-filter { background: #0f172a; color: #fff; padding: 8px 16px; height: 38px; border-radius: 6px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter:hover { background: #1e293b; }
        .btn-reset { background: #e2e8f0; color: #334155; padding: 8px 16px; height: 38px; border-radius: 6px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-reset:hover { background: #cbd5e1; }

        /* Info Filter Aktif */
        .filter-info { font-size: 13px; font-weight: 600; color: #0284c7; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; }

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
            .action-buttons, .filter-box { display: none; }
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
                <h2><i class="fa-solid fa-file-lines" style="color: #0284c7;"></i> Laporan Data Pendaftaran</h2>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-print"><i class="fa-solid fa-print"></i> Cetak Laporan</button>
                <a href="dashboard.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            </div>
        </div>

        <!-- Form Filter Periode -->
        <form method="GET" action="" class="filter-box">
            <div class="filter-group">
                <label for="filter">Filter Berdasarkan</label>
                <select name="filter" id="filter" onchange="ubahFormatInput(this.value)">
                    <option value="semua" <?php if($filter == 'semua') echo 'selected'; ?>>Semua Data</option>
                    <option value="harian" <?php if($filter == 'harian') echo 'selected'; ?>>Harian</option>
                    <option value="mingguan" <?php if($filter == 'mingguan') echo 'selected'; ?>>Mingguan</option>
                    <option value="bulanan" <?php if($filter == 'bulanan') echo 'selected'; ?>>Bulanan</option>
                    <option value="tahunan" <?php if($filter == 'tahunan') echo 'selected'; ?>>Tahunan</option>
                </select>
            </div>

            <div class="filter-group" id="input-nilai-container">
                <label for="nilai">Pilih Tanggal / Waktu</label>
                <?php if ($filter == 'mingguan'): ?>
                    <input type="week" name="nilai" id="nilai" value="<?php echo htmlspecialchars($nilai_filter); ?>" required>
                <?php elseif ($filter == 'bulanan'): ?>
                    <input type="month" name="nilai" id="nilai" value="<?php echo htmlspecialchars($nilai_filter); ?>" required>
                <?php elseif ($filter == 'tahunan'): ?>
                    <select name="nilai" id="nilai" required>
                        <option value="">Pilih Tahun</option>
                        <?php 
                        $thn_sekarang = date('Y');
                        for ($t = $thn_sekarang; $t >= $thn_sekarang - 5; $t--) {
                            $sel = ($nilai_filter == $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>$t</option>";
                        }
                        ?>
                    </select>
                <?php else: ?>
                    <input type="date" name="nilai" id="nilai" value="<?php echo htmlspecialchars($nilai_filter); ?>">
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Terapkan</button>
                <a href="laporan_pendaftaran.php" class="btn-reset">Reset</a>
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
                            <?php 
                            $fields = mysqli_fetch_fields($result);
                            foreach ($fields as $field) {
                                $nama_kolom = ucwords(str_replace('_', ' ', $field->name));
                                echo "<th>" . htmlspecialchars($nama_kolom) . "</th>";
                            }
                            ?>
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
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Tidak ada data pendaftaran yang ditemukan untuk filter periode tersebut.</span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function ubahFormatInput(jenis) {
            const container = document.getElementById('input-nilai-container');
            let htmlInput = '<label for="nilai">Pilih Waktu</label>';
            
            if (jenis === 'harian') {
                htmlInput += '<input type="date" name="nilai" id="nilai" required>';
            } else if (jenis === 'mingguan') {
                htmlInput += '<input type="week" name="nilai" id="nilai" required>';
            } else if (jenis === 'bulanan') {
                htmlInput += '<input type="month" name="nilai" id="nilai" required>';
            } else if (jenis === 'tahunan') {
                htmlInput += '<select name="nilai" id="nilai" required>';
                htmlInput += '<option value="">Pilih Tahun</option>';
                const tahunSekarang = new Date().getFullYear();
                for (let t = tahunSekarang; t >= tahunSekarang - 5; t--) {
                    htmlInput += `<option value="${t}">${t}</option>`;
                }
                htmlInput += '</select>';
            } else {
                htmlInput += '<input type="text" disabled placeholder="Semua data dipilih" style="background:#e2e8f0;">';
            }
            container.innerHTML = htmlInput;
        }
    </script>
</body>
</html>