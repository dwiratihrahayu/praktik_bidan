<?php
session_start();
include 'koneksi.php';

// Auto Setup Tabel & Akun Default
if (isset($koneksi) && $koneksi) {
    // 1. Cek & Buat Tabel Users
    $table_check = mysqli_query($koneksi, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($table_check) == 0) {
        $sql_create = "CREATE TABLE `users` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `nama_lengkap` VARCHAR(100) NOT NULL,
          `role` ENUM('Petugas', 'Bidan Terapis', 'Pasien') NOT NULL DEFAULT 'Pasien',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        mysqli_query($koneksi, $sql_create);
    }

    // 2. Akun Pasien Default (Username: pasien1 | Password: pasien123)
    $pasien_check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='pasien1'");
    if (mysqli_num_rows($pasien_check) == 0) {
        $pass_pasien = password_hash('pasien123', PASSWORD_DEFAULT);
        mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('pasien1', '$pass_pasien', 'Ny. Rahmawati', 'Pasien')");
    }

    // 3. Akun Admin Default (Username: admin | Password: admin123)
    $admin_check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='admin'");
    if (mysqli_num_rows($admin_check) == 0) {
        $pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
        mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', '$pass_admin', 'Administrator Bidan', 'Petugas')");
    }
}

// Redirect Otomatis Jika Sudah Login
if (isset($_SESSION['login'])) {
    if ($_SESSION['role'] === 'Pasien') {
        header("Location: dashboard_pasien.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = $_POST['password'];
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        // Query verifikasi username & role
        $query  = "SELECT * FROM users WHERE username = '$username' AND role = '$role'";
        $result = mysqli_query($koneksi, $query);

        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $row['password']) || $password === $row['password']) {
                // Set Data Session
                $_SESSION['login']        = true;
                $_SESSION['user_id']      = $row['id'];
                $_SESSION['username']     = $row['username'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                $_SESSION['role']         = $row['role'];

                // REDIRECT SESUAI HAK AKSES / ROLE
                if ($row['role'] === 'Pasien') {
                    header("Location: dashboard_pasien.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Username atau Hak Akses ($role) tidak cocok!";
        }
    } else {
        $error = "Silahkan lengkapi semua isian!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System - PMB Siti Maryam</title>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dedicated Login CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

    <div class="login-card">
        <!-- 1. TAMPILAN LOGO PMB -->
        <div class="login-header">
            <div class="logo-container">
                <?php 
                $logo_src = file_exists('assets/images/logo.png') ? 'assets/images/logo.png' : (file_exists('logo.png') ? 'logo.png' : '');
                if (!empty($logo_src)): 
                ?>
                    <img src="<?php echo $logo_src; ?>" alt="Logo PMB Siti Maryam">
                <?php else: ?>
                    <i class="fa-solid fa-user-nurse logo-fallback-icon"></i>
                <?php endif; ?>
            </div>
            <h2>PMB SITI MARYAM</h2>
            <p>Silahkan masuk untuk mengelola sistem</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- 2. PILIHAN ROLE -->
            <div class="form-group">
                <label for="role">Masuk Sebagai</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user-shield"></i>
                    <select id="role" name="role" class="form-control" required autofocus>
                        <option value="Pasien" selected>Pasien</option>
                        <option value="Bidan Terapis">Bidan Terapis</option>
                        <option value="Petugas">Petugas / Admin</option>
                    </select>
                </div>
            </div>

            <!-- 3. USERNAME -->
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                </div>
            </div>

            <!-- 4. PASSWORD -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk Sistem
            </button>
        </form>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Praktik Bidan Siti Maryam
        </div>
    </div>

</body>
</html>