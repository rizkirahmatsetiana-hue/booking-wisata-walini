<?php
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: ../customer/login.php");
    exit;
}

$id_booking = (int)($_POST['id_booking'] ?? 0);
$file = $_FILES['bukti'] ?? null;

if ($id_booking <= 0 || !$file) {
    echo "<h2>Error: Data tidak valid</h2>";
    echo "<a href='../customer/pembayaran.php?id_booking=$id_booking'>Kembali</a>";
    exit;
}

// Generate nama file
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$nama = 'bukti_' . $id_booking . '_' . time() . '.' . $extension;
$folder = __DIR__ . '/../uploads/bukti/';

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$targetPath = $folder . $nama;

try {
    // 1. Ambil data booking
    $stmt = $pdo->prepare("SELECT * FROM booking WHERE id_booking = ?");
    $stmt->execute([$id_booking]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception("Booking tidak ditemukan");
    }
    
    // 2. Upload file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Gagal upload file");
    }
    
    // 3. Update booking - status MENUNGGU VERIFIKASI (belum confirmed!)
    $stmt = $pdo->prepare("UPDATE booking SET bukti_pembayaran = ?, status = 'menunggu_verifikasi' WHERE id_booking = ?");
    $stmt->execute([$nama, $id_booking]);
    
    // 4. Redirect ke halaman status (menunggu verifikasi)
    header("Location: ../customer/status_pembayaran.php?id_booking=" . $id_booking);
    exit;
    
} catch (Exception $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
    echo "<a href='../customer/pembayaran.php?id_booking=$id_booking'>Kembali</a>";
    exit;
}
?>