<?php
/**
 * Root Entry Point - PMB Siti Maryam
 * Otomatis mengarahkan user sesuai status login & role hak akses
 */
session_start();

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Pasien') {
        header("Location: dashboard_pasien.php");
    } else {
        header("Location: dashboard.php");
    }
} else {
    header("Location: login.php");
}
exit;
?>
