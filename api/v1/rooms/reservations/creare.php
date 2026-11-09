<?php
/**
 * S2-03: Membuat Reservasi/Booking Baru
 * Endpoint: POST /api/v1/reservations/create
 * Response: 201 Created
 * 
 * Body (POST):
 * - id_tiket (required, integer)
 * - tanggal (required, date, >= hari ini)
 * - jumlah (required, integer, min: 1)
 */
require __DIR__ . '/../../../config/koneksi.php';
require __DIR__ . '/../../response.php';

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed. Gunakan POST.', null, 405);
}

// Validasi login pelanggan
$session = requireLogin('pelanggan');

// Ambil data POST
$id_tiket = $_POST['id_tiket'] ?? null;
$tanggal = $_POST['tanggal'] ?? null;
$jumlah = (int)($_POST['jumlah'] ?? 0);

// Validasi input
$errors = [];
if (!$id_tiket) $errors['id_tiket'] = 'ID tiket wajib diisi';
if (!$tanggal) $errors['tanggal'] = 'Tanggal wajib diisi';
if ($jumlah < 1) $errors['jumlah'] = 'Jumlah minimal 1';

if (!empty($errors)) {
    errorResponse('Validasi gagal.', $errors, 422);
}

// Cek tanggal tidak boleh di masa lalu
if (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
    errorResponse('Tanggal booking tidak boleh di masa lalu.', ['tanggal' => 'Tanggal harus >= hari ini'], 422);
}

try {
    $pdo->beginTransaction();
    
    // 1. Ambil id_pelanggan dari session
    $stmt = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_user = ?");
    $stmt->execute([$session['id_user']]);
    $pelanggan = $stmt->fetch();
    
    if (!$pelanggan) {
        $pdo->rollBack();
        errorResponse('Data pelanggan tidak ditemukan. Silakan lengkapi profil terlebih dahulu.', null, 404);
    }
    
    // 2. Cek tiket ada
    $stmt = $pdo->prepare("SELECT * FROM tiket WHERE id_tiket = ?");
    $stmt->execute([$id_tiket]);
    $tiket = $stmt->fetch();
    
    if (!$tiket) {
        $pdo->rollBack();
        errorResponse('Tiket tidak ditemukan.', null, 404);
    }
    
    // 3. Cek stok tersedia
    if ($tiket['stok'] < $jumlah) {
        $pdo->rollBack();
        errorResponse("Stok tidak mencukupi. Tersedia: {$tiket['stok']}, diminta: {$jumlah}", ['jumlah' => 'Stok tidak cukup'], 422);
    }
    
    // 4. Generate kode booking unik
    $kode_booking = generateKodeBooking();
    
    // 5. Hitung total harga
    $total_harga = $tiket['harga'] * $jumlah;
    
    // 6. Insert ke tabel booking
    // SESUAIKAN dengan struktur tabel booking Anda
    $stmt = $pdo->prepare("
        INSERT INTO booking 
        (id_pelanggan, kode_booking, tanggal_booking, total_harga, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([
        $pelanggan['id_pelanggan'], 
        $kode_booking, 
        $tanggal, 
        $total_harga
    ]);
    $id_booking = $pdo->lastInsertId();
    
    // 7. Insert ke tabel detail_booking
    // SESUAIKAN dengan struktur tabel detail_booking Anda
    $stmt = $pdo->prepare("
        INSERT INTO detail_booking 
        (id_booking, id_tiket, jumlah, harga_satuan, subtotal) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $id_booking, 
        $id_tiket, 
        $jumlah, 
        $tiket['harga'], 
        $total_harga
    ]);
    
    $pdo->commit();
    
    successResponse('Booking berhasil dibuat. Silakan upload bukti pembayaran.', [
        'booking' => [
            'id_booking' => (int)$id_booking,
            'kode_booking' => $kode_booking,
            'tanggal_booking' => $tanggal,
            'total_harga' => (int)$total_harga,
            'status' => 'pending'
        ],
        'detail' => [
            'id_tiket' => (int)$id_tiket,
            'nama_tiket' => $tiket['nama_tiket'],
            'jumlah' => $jumlah,
            'harga_satuan' => (int)$tiket['harga']
        ],
        'next_step' => 'Upload bukti pembayaran ke endpoint: /api/v1/reservations/upload-receipt.php'
    ], 201);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    errorResponse('Terjadi kesalahan sistem saat menyimpan transaksi: ' . $e->getMessage(), null, 500);
}
?>