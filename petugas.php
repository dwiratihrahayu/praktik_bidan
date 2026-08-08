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
$create_table = "CREATE TABLE IF NOT EXISTS `petugas` (
  `id_petugas` VARCHAR(3) NOT NULL,
  `nama_petugas` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id_petugas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table);

// --- 2. PROSES SIMPAN / EDIT PETUGAS ---
if (isset($_POST['simpan_petugas'])) {
    $id_petugas   = mysqli_real_escape_string($koneksi, $_POST['id_petugas']);
    $nama_petugas = mysqli_real_escape_string($koneksi, $_POST['nama_petugas']);
    $no_hp        = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $status_form  = $_POST['status_form'];

    if ($status_form == 'tambah') {
        $cek_id = mysqli_query($koneksi, "SELECT * FROM petugas WHERE id_petugas = '$id_petugas'");
        if (mysqli_num_rows($cek_id) > 0) {
            $pesan_error = "Gagal: ID Petugas '$id_petugas' sudah digunakan. Gunakan ID lain.";
        } else {
            $query = "INSERT INTO petugas (id_petugas, nama_petugas, no_hp) 
                      VALUES ('$id_petugas', '$nama_petugas', '$no_hp')";
            if (mysqli_query($koneksi, $query)) {
                header("Location: petugas.php?status=sukses");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    } else {
        $query = "UPDATE petugas SET 
                    nama_petugas='$nama_petugas', 
                    no_hp='$no_hp' 
                  WHERE id_petugas='$id_petugas'";
        if (mysqli_query($koneksi, $query)) {
            header("Location: petugas.php?status=sukses");
            exit;
        } else {
            $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

// --- 3. PROSES HAPUS PETUGAS ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM petugas WHERE id_petugas='$id_hapus'")) {
        header("Location: petugas.php?status=terhapus");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}

// --- 4. PENCARIAN & QUERY DATA ---
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$where = $keyword ? "WHERE id_petugas LIKE '%$keyword%' OR nama_petugas LIKE '%$keyword%' OR no_hp LIKE '%$keyword%'" : "";

$query_petugas = "SELECT * FROM petugas $where ORDER BY id_petugas ASC";
$result_petugas = mysqli_query($koneksi, $query_petugas);

$page_title   = "Data Petugas - PMB Siti Maryam";
$header_title = "Data Master / Data Petugas";

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
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data petugas berhasil disimpan!</div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'terhapus'): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data petugas berhasil dihapus!</div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <button class="btn-add" onclick="openFormModal()">
                    <i class="fa-solid fa-plus"></i> Tambah Petugas
                </button>

                <form action="petugas.php" method="GET" class="search-box">
                    <input type="text" name="cari" placeholder="Cari Petugas..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Petugas</th>
                            <th>Nama Petugas</th>
                            <th>No. HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_petugas && mysqli_num_rows($result_petugas) > 0) {
                            while ($row = mysqli_fetch_assoc($result_petugas)) {
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['id_petugas']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_petugas']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                            <td>
                                <button class="btn-action btn-edit" onclick='openFormModal(<?php echo json_encode($row); ?>)' title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="petugas.php?hapus=<?php echo $row['id_petugas']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data petugas ini?')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Data petugas belum ada atau tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Tambah/Edit Petugas -->
<div class="modal-overlay" id="petugasModal">
    <div class="modal-box">
        <h3 id="modalTitle">Tambah Petugas Baru</h3>
        <form action="petugas.php" method="POST">
            <input type="hidden" name="status_form" id="status_form" value="tambah">
            
            <div class="form-group">
                <label>ID Petugas (Maksimal 3 Karakter)</label>
                <input type="text" name="id_petugas" id="id_petugas_input" required maxlength="3" placeholder="Contoh: P01">
            </div>

            <div class="form-group">
                <label>Nama Petugas</label>
                <input type="text" name="nama_petugas" id="nama_petugas_input" required placeholder="Nama lengkap petugas">
            </div>

            <div class="form-group">
                <label>No. HP / WhatsApp</label>
                <input type="text" name="no_hp" id="no_hp_input" required placeholder="08xxxxxxxxxx">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeFormModal()">Batal</button>
                <button type="submit" name="simpan_petugas" class="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openFormModal(data = null) {
        const modal = document.getElementById('petugasModal');
        const inputId = document.getElementById('id_petugas_input');
        const statusForm = document.getElementById('status_form');

        if (data) {
            document.getElementById('modalTitle').innerText = "Edit Data Petugas";
            statusForm.value = "edit";
            
            inputId.value = data.id_petugas;
            inputId.readOnly = true;
            inputId.style.background = "#f8fafc";
            inputId.style.color = "#64748b";

            document.getElementById('nama_petugas_input').value = data.nama_petugas;
            document.getElementById('no_hp_input').value = data.no_hp;
        } else {
            document.getElementById('modalTitle').innerText = "Tambah Petugas Baru";
            statusForm.value = "tambah";

            inputId.value = '';
            inputId.readOnly = false;
            inputId.style.background = "#ffffff";
            inputId.style.color = "#334155";

            document.getElementById('nama_petugas_input').value = '';
            document.getElementById('no_hp_input').value = '';
        }
        modal.style.display = 'flex';
    }

    function closeFormModal() { 
        document.getElementById('petugasModal').style.display = 'none'; 
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById('petugasModal')) closeFormModal();
    }
</script>

<?php include 'includes/footer.php'; ?>