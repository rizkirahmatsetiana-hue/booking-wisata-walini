<?php
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['email'] ?? '');  // Tetap pakai name="email" dari form
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Username dan password wajib diisi!";
    header("Location: login.php");
    exit;
}

try {
    // Cari berdasarkan email ATAU nama (username)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR nama = ?) AND role = 'admin'");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "Username atau password salah!";
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
        $_SESSION['error'] = "Username atau password salah!";
        header("Location: login.php");
        exit;
    }
    
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = 'admin';
    
    header("Location: dashboard.php");
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan sistem.";
    header("Location: login.php");
    exit;
}
?>