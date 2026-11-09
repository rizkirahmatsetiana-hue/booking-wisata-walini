<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('kasir');

$user = walini_get_user();

try {
    // Booking hari ini
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE DATE(tanggal_booking) = CURDATE()");
    $booking_hari_ini = $stmt->fetchColumn();
    
    // Pendapatan hari ini
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM booking WHERE DATE(tanggal_booking) = CURDATE() AND status = 'confirmed'");
    $pendapatan_hari_ini = $stmt->fetchColumn();
    
    // Booking pending
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'menunggu_verifikasi'");
    $booking_pending = $stmt->fetchColumn();
    
    // Tiket terjual hari ini
    $stmt = $pdo->query("SELECT COALESCE(SUM(db.jumlah), 0) FROM detail_booking db JOIN booking b ON db.id_booking = b.id_booking WHERE DATE(b.tanggal_booking) = CURDATE() AND b.status = 'confirmed'");
    $tiket_terjual = $stmt->fetchColumn();
} catch (PDOException $e) {
    $booking_hari_ini = 0;
    $pendapatan_hari_ini = 0;
    $booking_pending = 0;
    $tiket_terjual = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Dashboard Kasir - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        .hamburger-btn {
            display: none;
            position: fixed;
            top: 8px;
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
            align-items: center;
            justify-content: center;
        }
        
        .overlay-menu {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
        .overlay-menu.active { display: block; }
        
        .topbar {
            background: white;
            padding: 12px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            height: 56px;
        }
        .user-top {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #198754;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .layout { display: flex; min-height: 100vh; margin-top: 56px; }
        
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .sidebar .logo { text-align: center; padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; }
        .sidebar .logo h3 { color: #198754; font-weight: bold; font-size: 22px; margin: 0; }
        .sidebar .logo p { color: #aaa; font-size: 11px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: #ccc; text-decoration: none; font-size: 14px; }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: white; padding-left: 25px; }
        .sidebar a.active { background: #198754; color: white; }
        .sidebar a i { font-size: 18px; width: 22px; text-align: center; }
        
        .main-content { margin-left: 240px; flex: 1; padding: 25px; width: calc(100% - 240px); }
        
        .page-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .page-header h2 { color: #333; font-size: 22px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #e7f3ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stat-card .stat-label { font-size: 13px; color: #666; margin-bottom: 8px; }
        .stat-card .stat-value { font-size: 28px; font-weight: bold; color: #333; }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .action-card {
            background: white;
            padding: 30px 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .action-card .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e7f3ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 15px;
        }
        .action-card h3 { font-size: 16px; margin-bottom: 8px; }
        .action-card p { font-size: 12px; color: #666; }
        
        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            
            .stats-grid { grid-template-columns: 1fr !important; gap: 12px; }
            .stat-card { padding: 20px; }
            .stat-card .stat-value { font-size: 24px; }
            
            .quick-actions { grid-template-columns: 1fr !important; gap: 12px; }
            .action-card { padding: 25px 15px; }
        }
        
        @media (max-width: 374px) {
            .main-content { padding: 10px !important; }
            .stat-card .stat-value { font-size: 20px; }
        }
    </style>
</head>
<body>

<button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="topbar">
    <div class="user-top">
        <div class="user-avatar"><?= strtoupper(substr($user['nama'] ?? 'K', 0, 1)) ?></div>
        <div>
            <div style="font-size: 13px;"><?= htmlspecialchars($user['nama'] ?? 'Kasir') ?></div>
            <div style="font-size: 11px; color: #666;">Kasir</div>
        </div>
    </div>
</div>

<div class="layout">
   <!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">
        <h3>WALINI</h3>
        <p>Kasir Panel</p>
    </div>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="validasi_tiket.php" class="<?= basename($_SERVER['PHP_SELF']) == 'validasi_tiket.php' ? 'active' : '' ?>">
        <i class="bi bi-ticket-perforated"></i> Validasi Tiket
    </a>
    <a href="cek_pembayaran.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cek_pembayaran.php' ? 'active' : '' ?>">
        <i class="bi bi-credit-card"></i> Cek Pembayaran
    </a>
    <a href="transaksi.php" class="<?= basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i> Transaksi
    </a>
    <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

    <div class="main-content">
        <div class="page-header">
            <h2>Dashboard Kasir</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-label">Booking Hari Ini</div>
                <div class="stat-value"><?= $booking_hari_ini ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #d4edda; color: #198754;"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-label">Pendapatan Hari Ini</div>
                <div class="stat-value">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fff3cd; color: #856404;"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-label">Menunggu Verifikasi</div>
                <div class="stat-value"><?= $booking_pending ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f8d7da; color: #dc3545;"><i class="bi bi-ticket"></i></div>
                <div class="stat-label">Tiket Terjual Hari Ini</div>
                <div class="stat-value"><?= $tiket_terjual ?></div>
            </div>
        </div>

        <h3 style="margin-bottom: 20px; color: #333;">Aksi Cepat</h3>
        <div class="quick-actions">
            <a href="validasi_tiket.php" class="action-card">
                <div class="action-icon"><i class="bi bi-ticket-perforated"></i></div>
                <h3>Validasi Tiket</h3>
                <p>Scan atau input kode booking untuk validasi tiket masuk pengunjung</p>
            </a>
            <a href="cek_pembayaran.php" class="action-card">
                <div class="action-icon" style="background: #d4edda; color: #198754;"><i class="bi bi-credit-card"></i></div>
                <h3>Cek Pembayaran</h3>
                <p>Verifikasi bukti pembayaran dari pelanggan yang sudah melakukan booking</p>
            </a>
        </div>
    </div>
</div>

<script>
function toggleMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay-menu');
    if (sidebar) sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
}
function closeMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay-menu');
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => link.addEventListener('click', closeMenu));
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) closeMenu();
    });
});
</script>

</body>
</html>