<?php
/**
 * File: api/proses_booking.php
 * Fungsi: Proses booking tiket customer
 */
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: ../customer/login.php");
    exit;
}

// 2. CEK ROLE
if ($_SESSION['role'] !== 'pelanggan') {
    echo "<div style='padding: 40px; text-align: center; font-family: Arial;'>";
    echo "<h2 style='color: red;'>Akses Ditolak!</h2>";
    echo "<p>Hanya pelanggan yang dapat melakukan booking.</p>";
    echo "<a href='../customer/login.php'>Login</a>";
    echo "</div>";
    exit;
}

// 3. CEK METHOD POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../customer/destinasi.php");
    exit;
}

// 4. AMBIL DATA DARI FORM
$id_tiket = (int)($_POST['id_tiket'] ?? 0);
$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$jumlah = (int)($_POST['jumlah'] ?? 1);
$id_destinasi = (int)($_POST['id_destinasi'] ?? 0);

// 5. VALIDASI INPUT
if ($id_tiket <= 0) {
    echo "<h2>Error: Tiket tidak valid!</h2>";
    echo "<a href='../customer/destinasi.php'>Kembali</a>";
    exit;
}

if ($jumlah <= 0) {
    echo "<h2>Error: Jumlah tiket harus lebih dari 0!</h2>";
    echo "<a href='../customer/booking.php?id_destinasi=$id_destinasi'>Kembali</a>";
    exit;
}

// Validasi tanggal tidak di masa lalu
if (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
    echo "<h2>Error: Tanggal booking tidak boleh di masa lalu!</h2>";
    echo "<a href='../customer/booking.php?id_destinasi=$id_destinasi'>Kembali</a>";
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 6. AMBIL DATA PELANGGAN
    $stmt = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
    $pelanggan = $stmt->fetch();
    
    if (!$pelanggan) {
        throw new Exception("Data pelanggan tidak ditemukan. Silakan lengkapi profil terlebih dahulu.");
    }
    
    // 7. AMBIL DATA TIKET (DENGAN LOCK untuk cegah race condition)
    $stmt = $pdo->prepare("SELECT id_tiket, harga, stok FROM tiket WHERE id_tiket = ? FOR UPDATE");
    $stmt->execute([$id_tiket]);
    $tiket = $stmt->fetch();
    
    if (!$tiket) {
        throw new Exception("Tiket tidak ditemukan!");
    }
    
    // 8. VALIDASI STOK - PENTING!
    if ($tiket['stok'] <= 0) {
        throw new Exception("Maaf, tiket sudah habis! Silakan pilih destinasi lain.");
    }
    
    if ($tiket['stok'] < $jumlah) {
        throw new Exception("Stok tidak mencukupi! Tersedia: {$tiket['stok']} tiket, Anda meminta: $jumlah tiket.");
    }
    
    // 9. HITUNG TOTAL
    $harga_satuan = (float)$tiket['harga'];
    $total_harga = $harga_satuan * $jumlah;
    
    // 10. GENERATE KODE BOOKING
    $kode_booking = 'WS' . date('ymd') . rand(1000, 9999);
    
    // 11. INSERT BOOKING
    $stmt = $pdo->prepare("
        INSERT INTO booking (id_pelanggan, id_user, kode_booking, tanggal_booking, total_harga, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([$pelanggan['id_pelanggan'], $_SESSION['id_user'], $kode_booking, $tanggal, $total_harga]);
    $id_booking = $pdo->lastInsertId();
    
    // 12. INSERT DETAIL BOOKING
    $stmt = $pdo->prepare("
        INSERT INTO detail_booking (id_booking, id_tiket, jumlah, harga_satuan, subtotal) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$id_booking, $id_tiket, $jumlah, $harga_satuan, $total_harga]);
    
    // 13. UPDATE STOK (DIHAPUS: Stok hanya akan dikurangi saat Kasir/Admin mengonfirmasi pembayaran)
    // $stmt = $pdo->prepare("UPDATE tiket SET stok = stok - ? WHERE id_tiket = ?");
    // $stmt->execute([$jumlah, $id_tiket]);
    
    $pdo->commit();
    
    // 14. REDIRECT KE PEMBAYARAN
    header("Location: ../customer/pembayaran.php?id_booking=$id_booking&total=$total_harga");
    exit;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div style='padding: 40px; text-align: center; font-family: Arial; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; max-width: 600px; margin: 50px auto;'>";
    echo "<h2 style='color: #721c24;'>️ Gagal Booking</h2>";
    echo "<p><strong>" . $e->getMessage() . "</strong></p>";
    echo "<br><a href='../customer/destinasi.php' style='padding: 10px 20px; background: #198754; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Kembali ke Destinasi</a>";
    echo "</div>";
    exit;
}
?>