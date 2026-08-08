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

// Query khusus untuk mengambil data bidan terapis
$query_sql = "SELECT * FROM bidan_terapis";
$result = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Bidan Terapis - PMB Siti Maryam</title>
    <!-- FontAwesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; color: #334155; padding: 30px; }
        .container { max-width: 1100px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        /* Header Laporan */
        .header-laporan { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        
        .header-title-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Styling Logo di Header Laporan */
        .header-logo {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 2px;
        }

        .header-laporan h2 { color: #1e293b; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* Tombol Aksi */
        .action-buttons { display: flex; gap: 10px; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .btn-back { background: #64748b; color: #fff; }
        .btn-back:hover { background: #475569; }
        .btn-print { background: #0284c7; color: #fff; }
        .btn-print:hover { background: #0284c7dd; }

        /* Tabel Data */
        .table-responsive { overflow-x: auto; margin-top: 15px; }
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
            .action-buttons { display: none; }
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
                <h2><i class="fa-solid fa-file-lines" style="color: #0284c7;"></i> Laporan Data Bidan Terapis</h2>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-print"><i class="fa-solid fa-print"></i> Cetak Laporan</button>
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
                            // Mengambil nama kolom database bidan terapis secara otomatis
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
                <span>Belum ada data bidan terapis yang tersedia di database.</span>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>