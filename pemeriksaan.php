<?php
// Tampilkan error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

$logo_path = file_exists('logo.png') ? 'logo.png' : '';
$pesan_error = "";
$pesan_sukses = "";

// --- 1. OTOMATIS BUAT TABEL JIKA BELUM ADA ---
$create_table = "CREATE TABLE IF NOT EXISTS `pemeriksaan` (
  `id_pemeriksaan` VARCHAR(20) NOT NULL,
  `tanggal_periksa` DATE NOT NULL,
  `id_pendaftaran` VARCHAR(20) NOT NULL,
  `diagnosa` TEXT NOT NULL,
  `tindakan` TEXT NOT NULL,
  `status_validasi` ENUM('Sudah','Belum') NOT NULL,
  PRIMARY KEY (`id_pemeriksaan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

// --- 2. PROSES EDIT DATA PEMERIKSAAN ---
if (isset($_POST['simpan_pemeriksaan'])) {
    $id_pemeriksaan   = mysqli_real_escape_string($koneksi, $_POST['id_pemeriksaan']);
    $tanggal_periksa  = mysqli_real_escape_string($koneksi, $_POST['tanggal_periksa']);
    $diagnosa         = mysqli_real_escape_string($koneksi, $_POST['diagnosa']);
    $tindakan         = mysqli_real_escape_string($koneksi, $_POST['tindakan']);
    $status_validasi  = mysqli_real_escape_string($koneksi, $_POST['status_validasi']);

    $query = "UPDATE pemeriksaan SET 
                tanggal_periksa='$tanggal_periksa', 
                diagnosa='$diagnosa', 
                tindakan='$tindakan', 
                status_validasi='$status_validasi' 
              WHERE id_pemeriksaan='$id_pemeriksaan'";

    if (mysqli_query($koneksi, $query)) {
        header("Location: pemeriksaan.php?status=sukses");
        exit;
    } else {
        die("<div style='background: #fee2e2; color: #991b1b; padding: 20px; margin: 20px; border-radius: 8px; font-family: sans-serif;'>
                <h3>Gagal Menyimpan ke Database!</h3>
                <p><strong>Pesan Error MySQL:</strong> " . mysqli_error($koneksi) . "</p>
                <p><strong>Query:</strong> $query</p>
                <a href='pemeriksaan.php' style='display:inline-block; margin-top:10px; padding:8px 16px; background:#0284c7; color:#fff; text-decoration:none; border-radius:4px;'>Kembali</a>
             </div>");
    }
}

// --- 3. PROSES HAPUS DATA PEMERIKSAAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM pemeriksaan WHERE id_pemeriksaan='$id_hapus'")) {
        header("Location: pemeriksaan.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- 4. PENCARIAN & QUERY DATA PEMERIKSAAN ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE pemeriksaan.id_pemeriksaan LIKE '%$keyword%' OR pemeriksaan.id_pendaftaran LIKE '%$keyword%' OR pemeriksaan.diagnosa LIKE '%$keyword%' OR pemeriksaan.tindakan LIKE '%$keyword%' OR pas.nama_pasien LIKE '%$keyword%' OR pas.no_rm LIKE '%$keyword%'" : "";

// JOIN ke pendaftaran dan pasien untuk tampilkan nama pasien dan no_rm
$query_pemeriksaan = "SELECT pemeriksaan.*, pendaftaran.tanggal_daftar, pas.nama_pasien, pas.no_rm
                      FROM pemeriksaan 
                      LEFT JOIN pendaftaran ON pemeriksaan.id_pendaftaran = pendaftaran.id_pendaftaran
                      LEFT JOIN pasien pas ON pendaftaran.no_rm = pas.no_rm
                      $where 
                      ORDER BY pemeriksaan.tanggal_periksa DESC";
$result_pemeriksaan = mysqli_query($koneksi, $query_pemeriksaan);
$page_title   = "Data Pemeriksaan - PMB Siti Maryam";
$header_title = "Data Transaksi / Data Pemeriksaan";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div>
        <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pemeriksaan berhasil diperbarui!</div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data pemeriksaan berhasil dihapus!</div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-header">
                    <form action="pemeriksaan.php" method="GET" class="search-box">
                        <input type="text" name="cari" placeholder="Cari Pemeriksaan / No RM..." value="<?php echo htmlspecialchars($keyword); ?>">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Pemeriksaan</th>
                                <th>Tanggal Periksa</th>
                                <th>ID Pendaftaran</th>
                                <th>Nama Pasien</th>
                                <th>Diagnosa</th>
                                <th>Tindakan</th>
                                <th>Status Validasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result_pemeriksaan && mysqli_num_rows($result_pemeriksaan) > 0) {
                                while ($row = mysqli_fetch_assoc($result_pemeriksaan)) {
                                    $badge_class = ($row['status_validasi'] == 'Sudah') ? 'badge-sudah' : 'badge-belum';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['id_pemeriksaan']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['tanggal_periksa']); ?></td>
                                <td>
                                    <span style="color: #0284c7; font-weight: 600;"><i class="fa-solid fa-link"></i> <?php echo htmlspecialchars($row['id_pendaftaran']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['nama_pasien'])): ?>
                                        <strong><?php echo htmlspecialchars($row['nama_pasien']); ?></strong>
                                        <br><small style="color:#94a3b8;"><?php echo htmlspecialchars($row['no_rm'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['diagnosa']); ?></td>
                                <td><?php echo htmlspecialchars($row['tindakan']); ?></td>
                                <td><span class="badge-status <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['status_validasi']); ?></span></td>
                                <td>
                                    <button class="btn-action btn-edit" onclick='openFormModal(<?php echo json_encode($row); ?>)' title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a href="pemeriksaan.php?hapus=<?php echo $row['id_pemeriksaan']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data pemeriksaan ini?')" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 20px;">Data pemeriksaan belum ada atau tidak ditemukan.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Edit Pemeriksaan -->
    <div class="modal-overlay" id="pemeriksaanModal">
        <div class="modal-box">
            <h3 id="modalTitle">Edit Data Pemeriksaan</h3>
            <form action="pemeriksaan.php" method="POST">
                
                <div class="form-group">
                    <label>ID Pemeriksaan</label>
                    <input type="text" name="id_pemeriksaan" id="id_pemeriksaan" readonly required>
                </div>

                <div class="form-group">
                    <label>Tanggal Periksa</label>
                    <input type="date" name="tanggal_periksa" id="tanggal_periksa" required>
                </div>

                <div class="form-group">
                    <label>ID Pendaftaran Pasien</label>
                    <input type="text" id="id_pendaftaran_tampil" readonly>
                </div>

                <div class="form-group">
                    <label>Diagnosa</label>
                    <input type="text" name="diagnosa" id="diagnosa" required placeholder="Masukkan diagnosa...">
                </div>

                <div class="form-group">
                    <label>Tindakan</label>
                    <input type="text" name="tindakan" id="tindakan" required placeholder="Masukkan tindakan...">
                </div>

                <div class="form-group">
                    <label>Status Validasi</label>
                    <select name="status_validasi" id="status_validasi" required>
                        <option value="Sudah">Sudah</option>
                        <option value="Belum">Belum</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeFormModal()">Batal</button>
                    <button type="submit" name="simpan_pemeriksaan" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDropdown(element) {
            element.parentElement.classList.toggle('active');
        }

        function openFormModal(data) {
            const modal = document.getElementById('pemeriksaanModal');
            
            if (data) {
                document.getElementById('id_pemeriksaan').value = data.id_pemeriksaan;
                document.getElementById('tanggal_periksa').value = data.tanggal_periksa;
                document.getElementById('id_pendaftaran_tampil').value = data.id_pendaftaran;
                document.getElementById('diagnosa').value = data.diagnosa;
                document.getElementById('tindakan').value = data.tindakan;
                document.getElementById('status_validasi').value = data.status_validasi;
                modal.style.display = 'flex';
            }
        }

        function closeFormModal() { 
            document.getElementById('pemeriksaanModal').style.display = 'none'; 
        }

        window.onclick = function(e) {
            if (e.target === document.getElementById('pemeriksaanModal')) closeFormModal();
        }
    </script>

<?php include 'includes/footer.php'; ?>