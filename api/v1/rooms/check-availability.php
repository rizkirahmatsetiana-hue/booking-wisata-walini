<?php
/**
 * S2-03: Cek Ketersediaan Tiket
 * Endpoint: GET /api/v1/rooms/check-availability
 * Response: 200 OK
 */
require __DIR__ . '/../../../config/koneksi.php';
require __DIR__ . '/../../response.php';

// Hanya terima GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed. Gunakan GET.', null, 405);
}

// Ambil parameter
$id_destinasi = $_GET['id_destinasi'] ?? null;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$jumlah = (int)($_GET['jumlah'] ?? 1);

// Validasi
if (!$id_destinasi) {
    errorResponse('Parameter id_destinasi wajib diisi.');
}

if (!strtotime($tanggal)) {
    errorResponse('Format tanggal tidak valid. Gunakan YYYY-MM-DD.');
}

if ($jumlah < 1) {
    errorResponse('Jumlah tiket minimal 1.');
}

try {
    // 1. Cek destinasi
    $stmt = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
    $stmt->execute([$id_destinasi]);
    $destinasi = $stmt->fetch();
    
    if (!$destinasi) {
        errorResponse('Destinasi tidak ditemukan.', null, 404);
    }
    
    // 2. Ambil tiket untuk destinasi ini (yang paling murah)
    $stmt = $pdo->prepare("SELECT * FROM tiket WHERE id_destinasi = ? ORDER BY harga ASC LIMIT 1");
    $stmt->execute([$id_destinasi]);
    $tiket = $stmt->fetch();
    
    if (!$tiket) {
        errorResponse('Tiket tidak tersedia untuk destinasi ini.', null, 404);
    }
    
    // 3. Cek stok
    $tersedia = $tiket['stok'] >= $jumlah;
    $total_harga = $tiket['harga'] * $jumlah;
    
    successResponse('Ketersediaan tiket berhasil dicek.', [
        'destinasi' => [
            'id_destinasi' => (int)$destinasi['id_destinasi'],
            'nama_destinasi' => $destinasi['nama_destinasi'],
            'deskripsi' => $destinasi['deskripsi'],
            'gambar' => $destinasi['gambar']
        ],
        'tiket' => [
            'id_tiket' => (int)$tiket['id_tiket'],
            'nama_tiket' => $tiket['nama_tiket'],
            'harga' => (int)$tiket['harga'],
            'stok' => (int)$tiket['stok'],
            'tersedia' => $tersedia
        ],
        'booking_request' => [
            'tanggal' => $tanggal,
            'jumlah' => $jumlah,
            'total_harga' => (int)$total_harga
        ]
    ]);
    
} catch (PDOException $e) {
    errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
?>