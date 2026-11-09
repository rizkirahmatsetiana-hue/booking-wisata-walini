<?php
/**
 * S2-03: Verifikasi Booking (Admin/Kasir)
 * Endpoint: PUT /api/v1/reservations/verify
 * Response: 200 OK
 * 
 * Body (POST dengan _method=PUT):
 * - kode_booking (required)
 * - status (required: confirmed/rejected)
 * - catatan (optional)
 */
require __DIR__ . '/../../../config/koneksi.php';
require __DIR__ . '/../../response.php';

// Terima PUT atau POST dengan _method=PUT
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $method = 'PUT';
}

if ($method !== 'PUT') {
    errorResponse('Method not allowed. Gunakan PUT.', null, 405);
}

// Validasi login admin/kasir
$session = requireLogin();

if (!in_array($session['role'], ['admin', 'kasir'])) {
    errorResponse('Hanya admin/kasir yang bisa memverifikasi booking.', null, 403);
}

// Ambil data (support POST dan PUT)
if ($method === 'PUT') {
    parse_str(file_get_contents('php://input'), $input);
} else {
    $input = $_POST;
}

$kode_booking = $input['kode_booking'] ?? null;
$status_baru = strtolower($input['status'] ?? '');
$catatan = $input['catatan'] ?? '';

// Validasi
if (!$kode_booking || !$status_baru) {
    errorResponse('Parameter kode_booking dan status wajib diisi.');
}

$status_valid = ['confirmed', 'rejected', 'cancelled'];
if (!in_array($status_baru, $status_valid)) {
    errorResponse('Status tidak valid. Gunakan: confirmed, rejected, atau cancelled.', ['status' => 'Invalid status'], 422);
}

try {
    $pdo->beginTransaction();
    
    // 1. Cek booking ada
    $stmt = $pdo->prepare("SELECT * FROM booking WHERE kode_booking = ?");
    $stmt->execute([$kode_booking]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $pdo->rollBack();
        errorResponse('Booking dengan kode tersebut tidak ditemukan.', null, 404);
    }
    
    if ($booking['status'] !== 'menunggu_verifikasi') {
        $pdo->rollBack();
        errorResponse('Booking tidak dalam status menunggu verifikasi. Status saat ini: ' . $booking['status'], null, 422);
    }
    
    // 2. Update status booking
    $stmt = $pdo->prepare("
        UPDATE booking 
        SET status = ?, catatan_admin = ?, updated_at = NOW()
        WHERE kode_booking = ?
    ");
    $stmt->execute([$status_baru, $catatan, $kode_booking]);
    
   // 3. Jika confirmed, generate e-tiket & kurangi stok
if ($status_baru === 'confirmed') {
    $stmt = $pdo->prepare("
        SELECT db.*, t.nama_tiket, d.nama_destinasi, b.tanggal_booking
        FROM detail_booking db
        JOIN tiket t ON db.id_tiket = t.id_tiket
        JOIN destinasi d ON t.id_destinasi = d.id_destinasi
        JOIN booking b ON db.id_booking = b.id_booking
        WHERE db.id_booking = ?
    ");
    $stmt->execute([$booking['id_booking']]);
    $details = $stmt->fetchAll();
    
    $e_tikets = [];
    foreach ($details as $detail) {
        // Insert e-tiket SESUAI STRUKTUR TABEL ANDA
        $stmt = $pdo->prepare("
            INSERT INTO e_tiket 
            (id_booking, kode_booking, nama_destinasi, tanggal, jumlah_tiket, total_harga, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'valid', NOW())
        ");
        $stmt->execute([
            $booking['id_booking'], 
            $booking['kode_booking'],  // Pakai kode_booking dari tabel booking
            $detail['nama_destinasi'],
            $detail['tanggal_booking'],
            $detail['jumlah'],
            $detail['subtotal']  // Pakai subtotal dari detail_booking
        ]);
        
        $e_tikets[] = [
            'id_e_tiket' => (int)$pdo->lastInsertId(),
            'kode_booking' => $booking['kode_booking'],
            'nama_destinasi' => $detail['nama_destinasi'],
            'tanggal' => $detail['tanggal_booking'],
            'jumlah_tiket' => (int)$detail['jumlah'],
            'total_harga' => (float)$detail['subtotal'],
            'status' => 'valid'
        ];
        
        // Kurangi stok tiket
        $stmt = $pdo->prepare("UPDATE tiket SET stok = stok - ? WHERE id_tiket = ?");
        $stmt->execute([$detail['jumlah'], $detail['id_tiket']]);
    }
}
    
    $pdo->commit();
    
    $message = $status_baru === 'confirmed' 
        ? 'Booking berhasil dikonfirmasi. E-tiket telah dibuat.' 
        : 'Booking telah ' . $status_baru . '.';
    
    $responseData = [
        'booking' => [
            'kode_booking' => $kode_booking,
            'status_lama' => $booking['status'],
            'status_baru' => $status_baru,
            'catatan' => $catatan
        ]
    ];
    
    if ($status_baru === 'confirmed' && isset($e_tikets)) {
        $responseData['e_tikets'] = $e_tikets;
    }
    
    successResponse($message, $responseData);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
?>