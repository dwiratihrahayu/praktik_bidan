<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

// Proteksi login
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

// --- FUNGSI GENERATE NO. RM OTOMATIS (Format: RM-YYMM001) ---
function generateNoRM($koneksi) {
    $tahunBulan = date('ym');
    $prefix = "RM-" . $tahunBulan;
    
    $query = "SELECT no_rm FROM pasien WHERE no_rm LIKE '$prefix%' ORDER BY no_rm DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $noUrut = (int) substr($row['no_rm'], -3);
        $noUrut++;
    } else {
        $noUrut = 1;
    }
    
    return $prefix . str_pad($noUrut, 3, '0', STR_PAD_LEFT);
}

// --- 2. HANDLE TAMBAH / EDIT PASIEN ---
if (isset($_POST['simpan_pasien'])) {
    $no_rm           = mysqli_real_escape_string($koneksi, $_POST['no_rm']);
    $nik             = mysqli_real_escape_string($koneksi, $_POST['nik']);
    $nama_pasien     = mysqli_real_escape_string($koneksi, $_POST['nama_pasien']);
    $tanggal_lahir   = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $nama_orang_tua  = mysqli_real_escape_string($koneksi, $_POST['nama_orang_tua']);
    $jenis_kelamin   = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $no_hp           = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat          = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    if (empty($no_rm)) {
        $no_rm = generateNoRM($koneksi);
        $query = "INSERT INTO pasien (no_rm, nik, nama_pasien, tanggal_lahir, nama_orang_tua, jenis_kelamin, no_hp, alamat) 
                  VALUES ('$no_rm', '$nik', '$nama_pasien', '$tanggal_lahir', '$nama_orang_tua', '$jenis_kelamin', '$no_hp', '$alamat')";
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
    }

    if (mysqli_query($koneksi, $query)) {
        header("Location: pasien.php?status=sukses");
        exit;
    } else {
        $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
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

        <div class="table-card">
            <div class="table-header">
                <button class="btn-add" onclick="openFormModal()"><i class="fa-solid fa-plus"></i> Tambah Pasien</button>
                <form action="pasien.php" method="GET" class="search-box">
                    <input type="text" name="cari" placeholder="Cari No. RM / Nama / NIK..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
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
                                <button class="btn-action btn-edit" onclick='openFormModal(<?php echo json_encode($row); ?>)' title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
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

<!-- Modal Form Pasien -->
<div class="modal-overlay" id="pasienModal">
    <div class="modal-box">
        <h3 id="modalTitle">Tambah Pasien</h3>
        <form action="pasien.php" method="POST">
            <input type="hidden" name="no_rm" id="no_rm">
            
            <div class="form-group">
                <label>Nomor Rekam Medis (No. RM)</label>
                <input type="text" id="tampilan_no_rm" disabled placeholder="Otomatis digenerate oleh sistem" style="background: #f8fafc; color: #64748b; font-weight: 600;">
            </div>
            <div class="form-group">
                <label>NIK Pasien</label>
                <input type="text" name="nik" id="nik" required placeholder="Masukkan NIK 16 digit">
            </div>
            <div class="form-group">
                <label>Nama Pasien</label>
                <input type="text" name="nama_pasien" id="nama_pasien" required placeholder="Nama lengkap pasien">
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" id="tanggal_lahir" required>
            </div>
            <div class="form-group">
                <label>Nama Orang Tua</label>
                <input type="text" name="nama_orang_tua" id="nama_orang_tua" required placeholder="Nama ayah / ibu">
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" id="jenis_kelamin">
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label>No. HP / WhatsApp</label>
                <input type="text" name="no_hp" id="no_hp" required placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" id="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeFormModal()">Batal</button>
                <button type="submit" name="simpan_pasien" class="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openFormModal(data = null) {
        const modal = document.getElementById('pasienModal');
        if (data) {
            document.getElementById('modalTitle').innerText = "Edit Data Pasien";
            document.getElementById('no_rm').value = data.no_rm;
            document.getElementById('tampilan_no_rm').value = data.no_rm;
            document.getElementById('nik').value = data.nik;
            document.getElementById('nama_pasien').value = data.nama_pasien;
            document.getElementById('tanggal_lahir').value = data.tanggal_lahir;
            document.getElementById('nama_orang_tua').value = data.nama_orang_tua;
            document.getElementById('jenis_kelamin').value = data.jenis_kelamin;
            document.getElementById('no_hp').value = data.no_hp;
            document.getElementById('alamat').value = data.alamat;
        } else {
            document.getElementById('modalTitle').innerText = "Tambah Pasien Baru";
            document.getElementById('no_rm').value = '';
            document.getElementById('tampilan_no_rm').value = 'Otomatis dibuat sistem';
            document.getElementById('nik').value = '';
            document.getElementById('nama_pasien').value = '';
            document.getElementById('tanggal_lahir').value = '';
            document.getElementById('nama_orang_tua').value = '';
            document.getElementById('jenis_kelamin').value = 'Perempuan';
            document.getElementById('no_hp').value = '';
            document.getElementById('alamat').value = '';
        }
        modal.style.display = 'flex';
    }

    function closeFormModal() { 
        document.getElementById('pasienModal').style.display = 'none'; 
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById('pasienModal')) closeFormModal();
    }
</script>

<?php include 'includes/footer.php'; ?>