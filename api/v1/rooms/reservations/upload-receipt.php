<?php
/**
 * S2-03: Upload Bukti Pembayaran
 * Endpoint: POST /api/v1/reservations/upload-receipt
 * Response: 200 OK
 * 
 * Body (POST):
 * - id_booking (required, integer)
 * - bukti_pembayaran (required, file: jpg/png/jpeg, max 2MB)
 */
require __DIR__ . '/../../../config/koneksi.php';
require __DIR__ . '/../../response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed. Gunakan POST.', null, 405);
}

// Validasi login pelanggan
$session = requireLogin('pelanggan');

// Ambil data
$id_booking = $_POST['id_booking'] ?? null;

if (!$id_booking) {
    errorResponse('Parameter id_booking wajib diisi.');
}

// Validasi file upload
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
    errorResponse('File bukti pembayaran wajib diupload.');
}

$file = $_FILES['bukti_pembayaran'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
$max_size = 2 * 1024 * 1024; // 2MB

if (!in_array($file['type'], $allowed_types)) {
    errorResponse('Format file tidak valid. Hanya JPG, JPEG, PNG yang diperbolehkan.', ['file' => 'Format tidak valid'], 422);
}

if ($file['size'] > $max_size) {
    errorResponse('Ukuran file terlalu besar. Maksimal 2MB.', ['file' => 'Ukuran melebihi 2MB'], 422);
}

try {
    $pdo->beginTransaction();
    
    // 1. Cek booking ada dan milik user ini
    $stmt = $pdo->prepare("
        SELECT b.* FROM booking b
        JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
        WHERE b.id_booking = ? AND p.id_user = ?
    ");
    $stmt->execute([$id_booking, $session['id_user']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $pdo->rollBack();
        errorResponse('Booking tidak ditemukan atau bukan milik Anda.', null, 404);
    }
    
    if ($booking['status'] !== 'pending') {
        $pdo->rollBack();
        errorResponse('Booking sudah diproses. Status saat ini: ' . $booking['status'], null, 422);
    }
    
    // 2. Upload file
    $upload_dir = __DIR__ . '/../../../uploads/bukti/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'bukti_' . $booking['kode_booking'] . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $pdo->rollBack();
        errorResponse('Gagal mengupload file.', null, 500);
    }
    
    // 3. Update booking dengan bukti pembayaran
    // SESUAIKAN dengan kolom yang ada di tabel booking Anda
    $stmt = $pdo->prepare("
        UPDATE booking 
        SET bukti_pembayaran = ?, status = 'menunggu_verifikasi', updated_at = NOW()
        WHERE id_booking = ?
    ");
    $stmt->execute([$new_filename, $id_booking]);
    
    $pdo->commit();
    
    successResponse('Bukti pembayaran berhasil diupload. Menunggu verifikasi dari admin/kasir.', [
        'booking' => [
            'id_booking' => (int)$id_booking,
            'kode_booking' => $booking['kode_booking'],
            'bukti_pembayaran' => $new_filename,
            'status' => 'menunggu_verifikasi'
        ]
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
?>