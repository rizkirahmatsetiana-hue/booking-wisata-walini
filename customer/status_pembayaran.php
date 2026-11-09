<?php
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_booking = (int)($_GET['id_booking'] ?? 0);

if ($id_booking <= 0) {
    header("Location: riwayat.php");
    exit;
}

// Ambil data booking
$stmt = $pdo->prepare("SELECT * FROM booking WHERE id_booking = ?");
$stmt->execute([$id_booking]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: riwayat.php");
    exit;
}

// Cek status booking
$status = $booking['status'];

// Jika sudah confirmed, redirect ke e-tiket
if ($status === 'confirmed') {
    header("Location: e_tiket.php?kode_booking=" . $booking['kode_booking']);
    exit;
}

// Jika ditolak, redirect ke pembayaran untuk upload ulang
if ($status === 'rejected') {
    $_SESSION['error'] = "Pembayaran ditolak: " . ($booking['catatan_admin'] ?? 'Silakan upload bukti yang benar.');
    header("Location: pembayaran.php?id_booking=" . $id_booking);
    exit;
}

// Jika masih menunggu_verifikasi, tampilkan halaman ini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menunggu Verifikasi - Walini</title>
    <meta http-equiv="refresh" content="10">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { max-width: 500px; width: 100%; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 15px; font-size: 24px; }
        p { color: #666; margin-bottom: 20px; line-height: 1.6; }
        .info-box { background: #e7f3ff; padding: 15px; border-radius: 10px; margin: 20px 0; text-align: left; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #d0e7ff; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #666; }
        .info-value { font-weight: bold; color: #333; }
        .status-badge { display: inline-block; padding: 8px 20px; background: #ffc107; color: #856404; border-radius: 20px; font-weight: bold; margin: 15px 0; }
        .loading { display: flex; justify-content: center; gap: 5px; margin: 20px 0; }
        .loading span { width: 10px; height: 10px; background: #198754; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; }
        .loading span:nth-child(1) { animation-delay: -0.32s; }
        .loading span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
        .btn-riwayat { display: inline-block; padding: 12px 30px; background: white; color: #198754; border: 2px solid #198754; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 15px; }
        .btn-riwayat:hover { background: #f8f9fa; }
        /* === RESPONSIVE - TAMBAHAN SAJA, TIDAK MERUBAH YANG LAMA === */

/* Tombol Hamburger - HANYA muncul di mobile */
.hamburger-btn {
    display: none;
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 1001;
    background: #198754;
    color: white;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 8px;
    font-size: 22px;
    cursor: pointer;
}

/* Overlay - HANYA muncul saat sidebar aktif di mobile */
.overlay-menu {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 998;
}

.overlay-menu.active {
    display: block;
}

/* === MOBILE (< 768px) === */
@media (max-width: 768px) {
    /* Tampilkan hamburger */
    .hamburger-btn {
        display: block;
    }
    
    /* Sidebar jadi tersembunyi di kiri */
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 999;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    /* Main content full width */
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px !important;
    }
    
    /* Grid destinasi jadi 1 kolom */
    .destinasi-grid, .dest-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Stats cards jadi 1 kolom */
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Booking form jadi 1 kolom */
    .booking-container {
        grid-template-columns: 1fr !important;
    }
    
    /* Tabel bisa scroll horizontal */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    /* Tombol full width */
    .btn, button {
        width: 100%;
        min-height: 44px;
    }
    
    /* Input full width */
    input, select, textarea {
        width: 100% !important;
        font-size: 16px;
    }
    
    /* Gambar responsive */
    img {
        max-width: 100%;
        height: auto;
    }
}

/* === TABLET (768px - 1023px) === */
@media (min-width: 769px) and (max-width: 1023px) {
    .destinasi-grid, .dest-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
    </style>
</head>
<body>            
    <div class="container">
        <div class="icon"></div>
        <h1>Menunggu Verifikasi</h1>
        <p>Bukti pembayaran Anda sedang diverifikasi oleh admin/kasir. Mohon tunggu beberapa saat.</p>
        
        <div class="status-badge"> Menunggu Verifikasi</div>
        
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Kode Booking</span>
                <span class="info-value"><?= $booking['kode_booking'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Pembayaran</span>
                <span class="info-value" style="color: #198754;">Rp. <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Booking</span>
                <span class="info-value"><?= date('d M Y', strtotime($booking['tanggal_booking'])) ?></span>
            </div>
        </div>
        
        <div class="loading">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <p style="font-size: 13px; color: #999;">Halaman akan otomatis refresh setiap 10 detik</p>
        
        <a href="riwayat.php" class="btn-riwayat">Lihat Riwayat Pesanan</a>
    </div>
    <script>
// Toggle sidebar di mobile
function toggleMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay-menu');
    if (sidebar) sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
}

// Tutup sidebar saat klik overlay
function closeMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay-menu');
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

// Tutup sidebar saat klik link di dalamnya
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.remove('active');
                const overlay = document.querySelector('.overlay-menu');
                if (overlay) overlay.classList.remove('active');
            });
        });
    }
    
    // Tutup sidebar saat resize ke desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});
</script>
</body>
</html>