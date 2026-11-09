<?php
// Mulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simpan role terakhir sebelum logout (untuk redirect)
$lastRole = $_SESSION['role'] ?? 'pelanggan';

// Hapus semua session variables
$_SESSION = array();

// Hapus session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect ke halaman login yang sesuai dengan role terakhir
switch ($lastRole) {
    case 'admin':
        header("Location: ../admin/login.php");
        break;
    case 'kasir':
        header("Location: ../kasir/login.php");
        break;
    default:
        header("Location: ../customer/login.php");
        break;
}
exit;
?>