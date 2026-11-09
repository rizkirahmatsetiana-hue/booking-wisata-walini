<?php
require '../config/koneksi.php';

// Pastikan session dimulai untuk menyimpan pesan error/sukses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. AMBIL & BERSIHKAN INPUT
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. VALIDASI BACKEND (Tugas 7: Wajib ada!)
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = "Nama lengkap wajib diisi.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter.";
    }

    // Jika ada error, simpan ke session dan redirect kembali
    if (!empty($errors)) {
        $_SESSION['error'] = implode(" ", $errors);
        header("Location: ../customer/registrasi.php");
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // 3. CEK EMAIL SUDAH TERDAFTAR (Prepared Statement)
        $cek = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $cek->execute([$email]);
        
        if ($cek->fetch()) {
            $_SESSION['error'] = "Email sudah terdaftar! Silakan gunakan email lain atau login.";
            header("Location: ../customer/registrasi.php");
            exit;
        }

        // 4. HASH PASSWORD (Tugas 7: Password Hashing)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 5. INSERT KE TABEL USERS (Prepared Statement)
        $stmtUser = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'pelanggan')");
        $stmtUser->execute([$nama, $email, $hashed_password]);
        $id_user = $pdo->lastInsertId();

        // 6. INSERT KE TABEL PELANGGAN (Prepared Statement)
        $stmtPelanggan = $pdo->prepare("INSERT INTO pelanggan (id_user, nama, email, no_hp, alamat) VALUES (?, ?, ?, ?, ?)");
        $stmtPelanggan->execute([$id_user, $nama, $email, $no_hp, $alamat]);

        // 7. COMMIT TRANSAKSI (Simpan permanen)
        $pdo->commit();
        
        // 8. REDIRECT DENGAN PESAN SUKSES
        $_SESSION['success'] = "Registrasi berhasil! Silakan login dengan akun Anda.";
        header("Location: ../customer/login.php");
        exit;

    } catch (PDOException $e) {
        // 9. ROLLBACK & ERROR HANDLING AMAN (Tugas 7: Tidak bocor info)
        $pdo->rollBack();
        
        // Simpan detail error di log server (tidak terlihat user)
        error_log("Registrasi Error: " . $e->getMessage()); 
        
        $_SESSION['error'] = "Terjadi kesalahan sistem saat registrasi. Silakan coba lagi.";
        header("Location: ../customer/registrasi.php");
        exit;
    }
} else {
    // Jika bukan metode POST, tendang ke halaman registrasi
    header("Location: ../customer/registrasi.php");
    exit;
}
?>