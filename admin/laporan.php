<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('admin');

// Filter periode
$periode = $_GET['periode'] ?? 'bulan_ini';
$startDate = '';
$endDate = '';

switch ($periode) {
    case 'hari_ini':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'minggu_ini':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d');
        break;
    case 'bulan_ini':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'tahun_ini':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
}

// Statistik periode
$stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total_harga), 0) as pendapatan FROM booking WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'confirmed'");
$stmt->execute([$startDate, $endDate]);
$stats = $stmt->fetch();

// Booking per destinasi
$stmt = $pdo->prepare("
    SELECT d.nama_destinasi, COUNT(b.id_booking) as total, COALESCE(SUM(b.total_harga), 0) as pendapatan
    FROM destinasi d
    LEFT JOIN tiket t ON d.id_destinasi = t.id_destinasi
    LEFT JOIN detail_booking db ON t.id_tiket = db.id_tiket
    LEFT JOIN booking b ON db.id_booking = b.id_booking AND b.status = 'confirmed' AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY d.id_destinasi
    ORDER BY total DESC
");
$stmt->execute([$startDate, $endDate]);
$per_destinasi = $stmt->fetchAll();

// Booking per status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM booking WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$startDate, $endDate]);
$per_status = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <title>Laporan - Admin Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar - Desktop (default) */
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
}

/* Mobile - Sidebar tersembunyi */
@media (max-width: 768px) {
    .sidebar {
        position: fixed !important;
        top: 56px !important;
        left: 0 !important;
        bottom: 0 !important;
        width: 260px !important;
        z-index: 1000 !important;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%) !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Sidebar muncul saat class active */
    .sidebar.active {
        transform: translateX(0) !important;
    }
}
        
        .main-content { margin-left: 250px; padding: 30px; }
        
        .page-header { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { color: #333; font-size: 24px; margin: 0; }
        
        .filter-bar { background: white; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; gap: 10px; }
        .filter-btn { padding: 8px 16px; border: 2px solid #e0e0e0; border-radius: 20px; background: white; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; color: #666; }
        .filter-btn:hover { border-color: #198754; color: #198754; }
        .filter-btn.active { background: #198754; color: white; border-color: #198754; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #198754; }
        .stat-card.pendapatan { border-left-color: #fd7e14; }
        .stat-card .stat-label { font-size: 13px; color: #666; margin-bottom: 8px; }
        .stat-card .stat-value { font-size: 28px; font-weight: bold; color: #333; }
        
        .card-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-box h3 { font-size: 18px; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .card-box h3 i { color: #198754; }
        
        .table-modern { width: 100%; border-collapse: collapse; }
        .table-modern th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid #e0e0e0; }
        .table-modern td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        
        .chart-container { position: relative; height: 300px; }
        /* ============================================
   SIDEBAR STYLING - MOBILE
   ============================================ */
@media (max-width: 768px) {
    .sidebar {
        position: fixed !important;
        top: 56px !important;
        left: 0 !important;
        bottom: 0 !important;
        width: 260px !important;
        height: calc(100vh - 56px) !important;
        z-index: 1000 !important;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%) !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        overflow-y: auto !important;
        padding: 20px 0 !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.3) !important;
    }
    
    .sidebar.active {
        transform: translateX(0) !important;
    }
    
    /* Logo di sidebar */
    .sidebar .logo {
        text-align: center !important;
        padding: 15px 20px !important;
        border-bottom: 1px solid rgba(255,255,255,0.1) !important;
        margin-bottom: 15px !important;
    }
    
    .sidebar .logo h3 {
        color: #198754 !important;
        font-weight: bold !important;
        font-size: 22px !important;
        margin: 0 !important;
    }
    
    .sidebar .logo p {
        color: #aaa !important;
        font-size: 11px !important;
        margin: 3px 0 0 0 !important;
    }
    
    /* Link di sidebar */
    .sidebar a {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding: 13px 20px !important;
        color: #ccc !important;
        text-decoration: none !important;
        font-size: 14px !important;
        transition: all 0.2s !important;
        border-left: 3px solid transparent !important;
    }
    
    .sidebar a:hover {
        background: rgba(255,255,255,0.08) !important;
        color: white !important;
        padding-left: 25px !important;
    }
    
    .sidebar a.active {
        background: #198754 !important;
        color: white !important;
        border-left: 3px solid #146c43 !important;
    }
    
    .sidebar a i {
        font-size: 18px !important;
        width: 22px !important;
        text-align: center !important;
    }
    
    /* Overlay gelap */
    .overlay-menu {
        display: none !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: rgba(0,0,0,0.5) !important;
        z-index: 999 !important;
    }
    
    .overlay-menu.active {
        display: block !important;
    }
    
    /* Main content full width */
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px !important;
    }
    
    /* Topbar geser untuk hamburger */
    .topbar {
        padding-left: 60px !important;
    }
    
    /* Stats grid jadi 1 kolom */
    .stats-grid, .stats-row {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    /* Card padding lebih kecil */
    .card-box, .stat-card {
        padding: 15px !important;
    }
}
/* ============================================
   PAKSA SCROLL VERTIKAL - HAPUS HORIZONTAL
   ============================================ */
@media (max-width: 768px) {
    
    /* HAPUS SEMUA HORIZONTAL SCROLL */
    html, body {
        overflow-x: hidden !important;
        max-width: 100vw !important;
        position: relative !important;
    }
    
    /* Main content scroll VERTIKAL saja */
    .main-content {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        max-width: 100% !important;
        width: 100% !important;
        height: auto !important;
        min-height: calc(100vh - 56px) !important;
        padding: 15px !important;
        padding-bottom: 100px !important;
    }
    
    /* Paksa semua elemen tidak overflow */
    * {
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    
    /* Card/box tidak boleh keluar layar */
    .card-box, .card, .box, .panel {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }
    
    /* Chart harus responsive */
    .chart-container, canvas, #chartDiv, .chart {
        max-width: 100% !important;
        width: 100% !important;
        height: auto !important;
        min-height: 200px !important;
    }
    
    /* Tabel scroll horizontal TAPI container saja */
    .table-container {
        overflow-x: auto !important;
        overflow-y: hidden !important;
        max-width: 100% !important;
    }
    
    /* Filter buttons wrap ke bawah */
    .filter-buttons, .filter-tabs, .btn-group {
        flex-wrap: wrap !important;
        width: 100% !important;
    }
    
    .filter-buttons button, .filter-tabs button, .btn-group button {
        flex: 1 1 45% !important;
        min-width: 0 !important;
        margin: 3px !important;
    }
    
    /* Stats grid jadi 1 kolom */
    .stats-grid, .stats-row, .stat-row {
        grid-template-columns: 1fr !important;
        flex-direction: column !important;
        display: flex !important;
        gap: 10px !important;
    }
    
    /* Page header stack */
    .page-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        width: 100% !important;
    }
    
    /* Print/export buttons stack */
    .print-buttons, .action-buttons, .export-buttons {
        flex-direction: column !important;
        width: 100% !important;
    }
    
    .print-buttons button, .action-buttons button, .export-buttons button {
        width: 100% !important;
        margin: 5px 0 !important;
    }
}
/* ============================================
   SIDEBAR - DESKTOP (Default, layar > 768px)
   ============================================ */
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
}

.sidebar .logo {
    text-align: center;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 15px;
}

.sidebar .logo h3 {
    color: #198754;
    font-weight: bold;
    font-size: 22px;
    margin: 0;
}

.sidebar .logo p {
    color: #aaa;
    font-size: 11px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 20px;
    color: #ccc;
    text-decoration: none;
    font-size: 14px;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.08);
    color: white;
    padding-left: 25px;
}

.sidebar a.active {
    background: #198754;
    color: white;
}

.sidebar a i {
    font-size: 18px;
    width: 22px;
    text-align: center;
}

/* ============================================
   TOMBOL HAMBURGER - HANYA MOBILE
   ============================================ */
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

/* ============================================
   OVERLAY - HANYA MOBILE
   ============================================ */
.overlay-menu {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 998;
}

/* ============================================
   MOBILE - LAYAR < 768px
   ============================================ */
@media (max-width: 768px) {
    /* Tampilkan tombol hamburger */
    .hamburger-btn {
        display: flex;
    }
    
    /* Sidebar jadi hidden di mobile */
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    /* Sidebar muncul saat class active */
    .sidebar.active {
        transform: translateX(0);
    }
    
    /* Overlay muncul saat active */
    .overlay-menu.active {
        display: block;
    }
    
    /* Main content full width */
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
    
    /* Topbar geser untuk hamburger */
    .topbar {
        padding-left: 60px;
    }
    
    /* Stats grid jadi 1 kolom */
    .stats-grid, .stats-row {
        grid-template-columns: 1fr;
    }
    
    /* Form row jadi 1 kolom */
    .form-row {
        grid-template-columns: 1fr;
    }
    
    /* Booking body stack */
    .booking-body {
        grid-template-columns: 1fr;
    }
    
    /* Action buttons full width */
    .booking-actions {
        flex-direction: column;
    }
    
    .booking-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    /* Font input lebih besar */
    input, select, textarea {
        font-size: 16px;
    }
}
    </style>
</head>
<body>
    <button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="sidebar">
    <div class="logo"><h3>WALINI</h3><p>Admin Panel</p></div>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="data_destinasi.php"><i class="bi bi-geo-alt-fill"></i> Data Destinasi</a>
    <a href="booking.php"><i class="bi bi-calendar-check"></i> Booking</a>
    <a href="pelanggan.php"><i class="bi bi-people-fill"></i> Pelanggan</a>
    <a href="laporan.php" class="active"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
    <a href="pengaturan.php"><i class="bi bi-gear-fill"></i> Pengaturan</a>
    <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<div class="main-content">

    <div class="page-header">
        <h2><i class="bi bi-file-earmark-bar-graph"></i> Laporan</h2>
        <span style="color:#666;"><?= date('d M Y') ?></span>
    </div>

    <div class="filter-bar">
        <a href="?periode=hari_ini" class="filter-btn <?= $periode === 'hari_ini' ? 'active' : '' ?>">Hari Ini</a>
        <a href="?periode=minggu_ini" class="filter-btn <?= $periode === 'minggu_ini' ? 'active' : '' ?>">Minggu Ini</a>
        <a href="?periode=bulan_ini" class="filter-btn <?= $periode === 'bulan_ini' ? 'active' : '' ?>">Bulan Ini</a>
        <a href="?periode=tahun_ini" class="filter-btn <?= $periode === 'tahun_ini' ? 'active' : '' ?>">Tahun Ini</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Booking (Confirmed)</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card pendapatan">
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value" style="font-size:24px;">Rp <?= number_format($stats['pendapatan'], 0, ',', '.') ?></div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        
        <div class="card-box">
            <h3><i class="bi bi-pie-chart"></i> Pendapatan per Destinasi</h3>
            <table class="table-modern">
                <thead>
                    <tr><th>Destinasi</th><th>Booking</th><th>Pendapatan</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($per_destinasi as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['nama_destinasi']) ?></strong></td>
                        <td><?= $d['total'] ?></td>
                        <td style="color:#198754; font-weight:bold;">Rp <?= number_format($d['pendapatan'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-box">
            <h3><i class="bi bi-bar-chart"></i> Status Booking</h3>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
const ctx = document.getElementById('statusChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($per_status, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($per_status, 'total')) ?>,
            backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545', '#6c757d']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
<script>
function toggleMenu() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.overlay-menu').classList.toggle('active');
}
function closeMenu() {
    document.querySelector('.sidebar').classList.remove('active');
    document.querySelector('.overlay-menu').classList.remove('active');
}
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', closeMenu);
});
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) closeMenu();
});
</script>

</body>
</html>