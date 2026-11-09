<?php
/**
 * File: includes/auth_check.php
 * Fungsi: Proteksi halaman berdasarkan role
 */

// Inisialisasi session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login dan punya role yang sesuai
 * @param string $requiredRole - 'admin', 'kasir', atau 'pelanggan'
 */
function cek_login($requiredRole) {
    
    // ============================================
    // 1. CEK: Apakah user sudah login?
    // ============================================
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        // Belum login → arahkan ke login sesuai role yang diminta
        $pesan = urlencode("Silakan login terlebih dahulu.");
        
        if ($requiredRole === 'admin') {
            header("Location: ../admin/login.php?error=" . $pesan);
        } elseif ($requiredRole === 'kasir') {
            header("Location: ../kasir/login.php?error=" . $pesan);
        } else {
            header("Location: ../customer/login.php?error=" . $pesan);
        }
        exit;
    }
    
    // ============================================
    // 2. CEK: Apakah role user sesuai?
    // ============================================
    if ($_SESSION['role'] !== $requiredRole) {
        // User sudah login TAPI role tidak cocok
        // → ARAHKAN KE DASHBOARD SESUAI ROLE USER (BUKAN KE LOGIN!)
        
        $pesan = urlencode("⛔ AKSES DITOLAK! Anda tidak memiliki izin untuk mengakses halaman ini.");
        
        // Redirect ke dashboard sesuai role user yang sebenarnya
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard.php?error=" . $pesan);
        } elseif ($_SESSION['role'] === 'kasir') {
            header("Location: ../kasir/dashboard.php?error=" . $pesan);
        } else {
            // Customer → arahkan ke home customer
            header("Location: ../customer/home.php?error=" . $pesan);
        }
        exit;
    }
    
    // Jika semua lolos, user boleh akses halaman
    return true;
}

/**
 * Ambil data user yang sedang login
 */
function walini_get_user() {
    return [
        'id_user' => $_SESSION['id_user'] ?? null,
        'nama'    => $_SESSION['nama'] ?? 'User',
        'email'   => $_SESSION['email'] ?? '',
        'role'    => $_SESSION['role'] ?? 'pelanggan'
    ];
}
?>