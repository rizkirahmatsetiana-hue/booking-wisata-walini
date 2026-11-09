<?php
require '../config/koneksi.php';

// Pastikan session sudah dimulai (jika belum ada di koneksi.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. AMBIL & BERSIHKAN INPUT
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_login = trim($_POST['role_login'] ?? 'pelanggan');

    // 2. VALIDASI INPUT (Tugas 7: Validasi Backend)
    if (empty($email) || empty($password)) {
        $error_msg = urlencode("Email dan password wajib diisi!");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = urlencode("Format email tidak valid!");
    } else {
        try {
            // 3. QUERY DENGAN PREPARED STATEMENT (Tugas 7: Aman dari SQL Injection)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
            $stmt->execute([$email, $role_login]);
            $user = $stmt->fetch();

            // 4. CEK APAKAH USER DITEMUKAN
            if (!$user) {
                $error_msg = urlencode("Email atau password salah, atau role tidak sesuai!");
            } 
            // 5. VERIFIKASI PASSWORD HASH (Tugas 7: Password Hashing)
            elseif (!password_verify($password, $user['password'])) {
                $error_msg = urlencode("Email atau password salah!");
            } 
            // 6. JIKA SEMUA VALID, BUAT SESSION
            else {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time(); // Untuk session timeout

                // Ambil id_pelanggan HANYA JIKA role-nya pelanggan (Perbaikan Bug Duplikat)
                if ($user['role'] === 'pelanggan') {
                    $stmt2 = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_user = ?");
                    $stmt2->execute([$user['id_user']]); // Lebih aman pakai id_user daripada email
                    $pelanggan = $stmt2->fetch();
                    $_SESSION['id_pelanggan'] = $pelanggan ? $pelanggan['id_pelanggan'] : null;
                } else {
                    $_SESSION['id_pelanggan'] = null;
                }

                // 7. REDIRECT SESUAI ROLE
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] === 'kasir') {
                    header("Location: ../kasir/dashboard.php");
                } else {
                    header("Location: ../customer/home.php");
                }
                exit;
            }
        } catch (PDOException $e) {
            // 8. ERROR HANDLING AMAN (Tugas 7: Tidak Bocor Info Sensitif)
            error_log("Login Error: " . $e->getMessage()); // Simpan di log server
            $error_msg = urlencode("Terjadi kesalahan sistem. Silakan coba lagi.");
        }
    }

    // 9. JIKA ADA ERROR, KEMBALI KE HALAMAN LOGIN
    if (isset($error_msg)) {
        $redirect = '../customer/login.php';
        if ($role_login === 'admin') $redirect = '../admin/login.php';
        if ($role_login === 'kasir') $redirect = '../kasir/login.php';
        
        header("Location: $redirect?error=" . $error_msg);
        exit;
    }
} else {
    // Jika bukan metode POST, tendang ke login
    header("Location: ../customer/login.php");
    exit;
}
?>