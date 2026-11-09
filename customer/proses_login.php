<?php
/**
 * SPRINT 3 - Authentication Integration
 * File: admin/proses_login.php
 * Fungsi: Proses login untuk admin
 */
require '../config/koneksi.php';

if (session_status() !== PHP_SESSION_NONE) {
    session_destroy();
}
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email dan password wajib diisi!";
    header("Location: login.php");
    exit;
}

try {
    // HANYA role 'admin' yang bisa login di sini
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "Email atau password salah!";
        header("Location: login.php");
        exit;
    }
    
    $passwordValid = false;
    if (password_verify($password, $user['password'])) {
        $passwordValid = true;
    } elseif ($password === $user['password']) {
        $passwordValid = true;
    }
    
    if (!$passwordValid) {
        $_SESSION['error'] = "Email atau password salah!";
        header("Location: login.php");
        exit;
    }
    
    // SET SESSION KHUSUS ADMIN
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = 'admin';  // ← Role dipaksa 'admin'
    $_SESSION['login_time'] = time();
    
    header("Location: dashboard.php");
    exit;
    
} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem.";
    header("Location: login.php");
    exit;
}
?>