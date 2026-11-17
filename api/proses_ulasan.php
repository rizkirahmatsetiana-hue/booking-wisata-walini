<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data
    $id_destinasi = (int)($_POST['id_destinasi'] ?? 0);
    $rating = $_POST['rating'] ?? '';
    $komentar = trim($_POST['komentar'] ?? '');
    
    // ===== VALIDASI RATING =====
    if ($rating === '') {
        header("Location: ../customer/ulasan.php?error=Rating wajib diisi!");
        exit;
    }
    
    if (!is_numeric($rating) || floor($rating) != $rating) {
        header("Location: ../customer/ulasan.php?error=Rating harus angka bulat!");
        exit;
    }
    
    $rating = (int)$rating;
    
    if ($rating < 1 || $rating > 5) {
        header("Location: ../customer/ulasan.php?error=Rating harus antara 1 sampai 5!");
        exit;
    }
    
    // ===== VALIDASI KOMENTAR =====
    if (empty($komentar)) {
        header("Location: ../customer/ulasan.php?error=Komentar wajib diisi!");
        exit;
    }
    
    if (strlen($komentar) < 10) {
        header("Location: ../customer/ulasan.php?error=Komentar minimal 10 karakter! (Saat ini: " . strlen($komentar) . " karakter)");
        exit;
    }
    
    // ===== SIMPAN KE DATABASE =====
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ulasan (id_destinasi, id_user, nama_pengguna, rating, komentar) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id_destinasi, 
            $_SESSION['id_user'], 
            $_SESSION['nama'],
            $rating, 
            $komentar
        ]);
        
        header("Location: ../customer/ulasan.php?success=Ulasan berhasil disimpan!");
        exit;
    } catch (PDOException $e) {
        header("Location: ../customer/ulasan.php?error=Gagal menyimpan ulasan!");
        exit;
    }
} else {
    header("Location: ../customer/ulasan.php");
    exit;
}
?>