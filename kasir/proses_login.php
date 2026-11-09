<?php
/**
 * File: kasir/proses_login.php
 * Fungsi: Proses login untuk kasir
 */
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input kosong
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email dan password wajib diisi!";
    header("Location: login.php");
    exit;
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid!";
    header("Location: login.php");
    exit;
}

try {
    // Cari user dengan email dan role kasir
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'kasir'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Jika user tidak ditemukan
    if (!$user) {
        $_SESSION['error'] = "Email atau password salah!";
        header("Location: login.php");
        exit;
    }
    
    // Verifikasi password
    $passwordValid = false;
    
    // Cek dengan password_verify (jika password di-hash)
    if (password_verify($password, $user['password'])) {
        $passwordValid = true;
    }
    // Cek password plain text (jika belum di-hash)
    elseif ($password === $user['password']) {
        $passwordValid = true;
    }
    
    if (!$passwordValid) {
        $_SESSION['error'] = "Email atau password salah!";
        header("Location: login.php");
        exit;
    }
    
    // Set session kasir
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = 'kasir';
    $_SESSION['login_time'] = time();
    
    // Redirect ke dashboard kasir
    header("Location: dashboard.php");
    exit;
    
} catch (PDOException $e) {
    error_log("Kasir login error: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem. Silakan coba lagi.";
    header("Location: login.php");
    exit;
}
?>