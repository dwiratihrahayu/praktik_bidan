<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

// Proteksi halaman login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$pesan_error = "";
$pesan_sukses = "";

// --- 1. OTOMATIS BUAT TABEL JIKA BELUM ADA ---
$create_table = "CREATE TABLE IF NOT EXISTS `bidan_terapis` (
  `id_bidan_terapis` VARCHAR(3) NOT NULL,
  `nama_bidan_terapis` VARCHAR(35) NOT NULL,
  `no_hp` VARCHAR(15) NOT NULL,
  `role` ENUM('Bidan','Terapis') NOT NULL,
  `spesialis` ENUM('-','Terapi Anak','Terapi Dewasa') NOT NULL,
  PRIMARY KEY (`id_bidan_terapis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

@mysqli_query($koneksi, "ALTER TABLE `bidan_terapis` MODIFY COLUMN `spesialis` ENUM('-','Terapi Anak','Terapi Dewasa') NOT NULL;");

// --- 2. PROSES SIMPAN / EDIT BIDAN TERAPIS ---
if (isset($_POST['simpan_bidan_terapis'])) {
    $id_bidan_terapis   = mysqli_real_escape_string($koneksi, $_POST['id_bidan_terapis']);
    $nama_bidan_terapis = mysqli_real_escape_string($koneksi, $_POST['nama_bidan_terapis']);
    $no_hp              = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $role               = mysqli_real_escape_string($koneksi, $_POST['role']);
    $spesialis          = mysqli_real_escape_string($koneksi, $_POST['spesialis']);

    $cek_id = mysqli_query($koneksi, "SELECT * FROM bidan_terapis WHERE id_bidan_terapis = '$id_bidan_terapis'");
    
    if (mysqli_num_rows($cek_id) == 0) {
        $query = "INSERT INTO bidan_terapis (id_bidan_terapis, nama_bidan_terapis, no_hp, role, spesialis) 
                  VALUES ('$id_bidan_terapis', '$nama_bidan_terapis', '$no_hp', '$role', '$spesialis')";
    } else {
        $query = "UPDATE bidan_terapis SET 
                    nama_bidan_terapis='$nama_bidan_terapis', 
                    no_hp='$no_hp', 
                    role='$role', 
                    spesialis='$spesialis' 
                  WHERE id_bidan_terapis='$id_bidan_terapis'";
    }

    if (mysqli_query($koneksi, $query)) {
        header("Location: bidan_terapis.php?status=sukses");
        exit;
    } else {
        $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}

// --- 3. PROSES HAPUS BIDAN TERAPIS ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM bidan_terapis WHERE id_bidan_terapis='$id_hapus'")) {
        header("Location: bidan_terapis.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- 4. PENCARIAN & QUERY DATA ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE nama_bidan_terapis LIKE '%$keyword%' OR id_bidan_terapis LIKE '%$keyword%' OR spesialis LIKE '%$keyword%'" : "";

$query_bidan = "SELECT * FROM bidan_terapis $where ORDER BY id_bidan_terapis DESC";
$result_bidan = mysqli_query($koneksi, $query_bidan);

$page_title   = "Data Bidan Terapis - PMB Siti Maryam";
$header_title = "Data Master / Data Bidan Terapis";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-body">
        <!-- Notifikasi Error / Sukses -->
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data bidan / terapis berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data bidan / terapis berhasil dihapus!</div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <button class="btn-add" onclick="openFormModal()">
                    <i class="fa-solid fa-plus"></i> Tambah Bidan / Terapis
                </button>

                <form action="bidan_terapis.php" method="GET" class="search-box">
                    <input type="text" name="cari" placeholder="Cari ID / Nama / Spesialis..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>No. HP / WA</th>
                            <th>Role / Jabatan</th>
                            <th>Spesialis</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_bidan && mysqli_num_rows($result_bidan) > 0) {
                            while ($row = mysqli_fetch_assoc($result_bidan)) {
                                $role_badge = ($row['role'] == 'Bidan') ? 'badge-info' : 'badge-warning';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['id_bidan_terapis']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_bidan_terapis']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                            <td><span class="badge <?php echo $role_badge; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['spesialis']); ?></td>
                            <td>
                                <button class="btn-action btn-edit" onclick='openFormModal(<?php echo json_encode($row); ?>)' title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="bidan_terapis.php?hapus=<?php echo $row['id_bidan_terapis']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">Data bidan terapis belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Tambah/Edit Bidan Terapis -->
<div class="modal-overlay" id="bidanModal">
    <div class="modal-box">
        <h3 id="modalTitle">Tambah Bidan / Terapis</h3>
        <form action="bidan_terapis.php" method="POST">
            
            <div class="form-group">
                <label>ID Bidan Terapis (3 Karakter)</label>
                <input type="text" name="id_bidan_terapis" id="id_bidan_terapis" required maxlength="3" placeholder="Contoh: B01 / T01">
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_bidan_terapis" id="nama_bidan_terapis" required placeholder="Nama lengkap & gelar">
            </div>

            <div class="form-group">
                <label>No. HP / WhatsApp</label>
                <input type="text" name="no_hp" id="no_hp" required placeholder="08xxxxxxxxxx">
            </div>

            <div class="form-group">
                <label>Role / Profesi</label>
                <select name="role" id="role" required onchange="handleRoleChange()">
                    <option value="Bidan">Bidan</option>
                    <option value="Terapis">Terapis</option>
                </select>
            </div>

            <div class="form-group">
                <label>Spesialis</label>
                <select name="spesialis" id="spesialis" required>
                    <option value="-">- (Khusus Bidan)</option>
                    <option value="Terapi Anak">Terapi Anak</option>
                    <option value="Terapi Dewasa">Terapi Dewasa</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeFormModal()">Batal</button>
                <button type="submit" name="simpan_bidan_terapis" class="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function handleRoleChange() {
        const role = document.getElementById('role').value;
        const spesialisSelect = document.getElementById('spesialis');
        
        if (role === 'Bidan') {
            spesialisSelect.value = '-';
        }
    }

    function openFormModal(data = null) {
        const modal = document.getElementById('bidanModal');
        const inputId = document.getElementById('id_bidan_terapis');

        if (data) {
            document.getElementById('modalTitle').innerText = "Edit Data Bidan / Terapis";
            inputId.value = data.id_bidan_terapis;
            inputId.readOnly = true;
            inputId.style.background = "#f8fafc";
            
            document.getElementById('nama_bidan_terapis').value = data.nama_bidan_terapis;
            document.getElementById('no_hp').value = data.no_hp;
            document.getElementById('role').value = data.role;
            document.getElementById('spesialis').value = data.spesialis;
        } else {
            document.getElementById('modalTitle').innerText = "Tambah Bidan / Terapis Baru";
            inputId.value = '';
            inputId.readOnly = false;
            inputId.style.background = "#ffffff";
            
            document.getElementById('nama_bidan_terapis').value = '';
            document.getElementById('no_hp').value = '';
            document.getElementById('role').value = 'Bidan';
            document.getElementById('spesialis').value = '-';
        }
        modal.style.display = 'flex';
    }

    function closeFormModal() { 
        document.getElementById('bidanModal').style.display = 'none'; 
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById('bidanModal')) closeFormModal();
    }
</script>

<?php include 'includes/footer.php'; ?>