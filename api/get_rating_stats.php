<?php
require '../config/koneksi.php';

header('Content-Type: application/json');

try {
    // Query AVG rating dan COUNT ulasan per destinasi
    $stmt = $pdo->query("
        SELECT 
            d.id_destinasi,
            d.nama_destinasi,
            COALESCE(AVG(u.rating), 0) as avg_rating,
            COUNT(u.id_ulasan) as total_ulasan
        FROM destinasi d
        LEFT JOIN ulasan u ON d.id_destinasi = u.id_destinasi
        GROUP BY d.id_destinasi
        ORDER BY avg_rating DESC
    ");
    
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>