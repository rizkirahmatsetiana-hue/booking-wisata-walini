<?php
/**
 * S2-03: List Booking Pending (untuk Admin/Kasir)
 * Endpoint: GET /api/v1/reservations/pending
 * Response: 200 OK
 */
require __DIR__ . '/../../../config/koneksi.php';
require __DIR__ . '/../../response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed. Gunakan GET.', null, 405);
}

// Validasi login admin/kasir
$session = requireLogin();

if (!in_array($session['role'], ['admin', 'kasir'])) {
    errorResponse('Hanya admin/kasir yang bisa mengakses endpoint ini.', null, 403);
}

try {
    // Filter status
    $status = $_GET['status'] ?? 'menunggu_verifikasi';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Query booking dengan detail
    $stmt = $pdo->prepare("
        SELECT 
            b.id_booking,
            b.kode_booking,
            b.tanggal_booking,
            b.total_harga,
            b.status,
            b.bukti_pembayaran,
            b.created_at,
            p.nama as nama_pelanggan,
            p.email,
            p.no_hp,
            d.nama_destinasi,
            t.nama_tiket,
            db.jumlah
        FROM booking b
        JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
        JOIN detail_booking db ON b.id_booking = db.id_booking
        JOIN tiket t ON db.id_tiket = t.id_tiket
        JOIN destinasi d ON t.id_destinasi = d.id_destinasi
        WHERE b.status = ?
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$status, $limit, $offset]);
    $bookings = $stmt->fetchAll();
    
    // Hitung total
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking WHERE status = ?");
    $stmt->execute([$status]);
    $total = (int)$stmt->fetch()['total'];
    
    successResponse('Daftar booking berhasil diambil.', [
        'bookings' => $bookings,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (PDOException $e) {
    errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
?>