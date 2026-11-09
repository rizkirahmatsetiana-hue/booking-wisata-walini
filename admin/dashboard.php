<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('admin');

// ============================================
// STATISTIK DARI DATABASE (Data Dinamis)
// ============================================

// Total Destinasi
$stmt = $pdo->query("SELECT COUNT(*) as total FROM destinasi");
$total_destinasi = $stmt->fetch()['total'];

// Total Booking
$stmt = $pdo->query("SELECT COUNT(*) as total FROM booking");
$total_booking = $stmt->fetch()['total'];

// Total Pelanggan
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pelanggan");
$total_pelanggan = $stmt->fetch()['total'];

// Total Pendapatan (booking confirmed)
$stmt = $pdo->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM booking WHERE status = 'confirmed'");
$total_pendapatan = $stmt->fetch()['total'];

// Booking Pending
$stmt = $pdo->query("SELECT COUNT(*) as total FROM booking WHERE status IN ('pending', 'menunggu_verifikasi')");
$booking_pending = $stmt->fetch()['total'];

// Booking Confirmed
$stmt = $pdo->query("SELECT COUNT(*) as total FROM booking WHERE status = 'confirmed'");
$booking_confirmed = $stmt->fetch()['total'];

// Booking Hari Ini
$stmt = $pdo->query("SELECT COUNT(*) as total FROM booking WHERE DATE(created_at) = CURDATE()");
$booking_hari_ini = $stmt->fetch()['total'];

// Pendapatan Hari Ini
$stmt = $pdo->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM booking WHERE DATE(created_at) = CURDATE() AND status = 'confirmed'");
$pendapatan_hari_ini = $stmt->fetch()['total'];

// ============================================
// DATA GRAFIK BOOKING (7 Hari Terakhir)
// ============================================
$grafik_labels = [];
$grafik_data = [];
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $tanggal_label = date('d M', strtotime("-$i days"));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking WHERE DATE(created_at) = ?");
    $stmt->execute([$tanggal]);
    $total = $stmt->fetch()['total'];
    
    $grafik_labels[] = $tanggal_label;
    $grafik_data[] = (int)$total;
}

// ============================================
// BOOKING TERBARU (5 Terakhir)
// ============================================
$stmt = $pdo->query("
    SELECT 
        b.id_booking, b.kode_booking, b.tanggal_booking, b.total_harga, b.status, b.created_at,
        p.nama as nama_pelanggan, p.email,
        d.nama_destinasi, t.nama_tiket, db.jumlah
    FROM booking b
    JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
    JOIN detail_booking db ON b.id_booking = db.id_booking
    JOIN tiket t ON db.id_tiket = t.id_tiket
    JOIN destinasi d ON t.id_destinasi = d.id_destinasi
    ORDER BY b.created_at DESC
    LIMIT 5
");
$booking_terbaru = $stmt->fetchAll();

// ============================================
// DESTINASI TERPOPULER (Berdasarkan Jumlah Booking)
// ============================================
$stmt = $pdo->query("
    SELECT 
        d.nama_destinasi, d.gambar,
        COUNT(b.id_booking) as total_booking,
        COALESCE(SUM(b.total_harga), 0) as total_pendapatan
    FROM destinasi d
    LEFT JOIN tiket t ON d.id_destinasi = t.id_destinasi
    LEFT JOIN detail_booking db ON t.id_tiket = db.id_tiket
    LEFT JOIN booking b ON db.id_booking = b.id_booking AND b.status = 'confirmed'
    GROUP BY d.id_destinasi
    ORDER BY total_booking DESC
    LIMIT 3
");
$destinasi_populer = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f0f2f5; 
            font-family: 'Segoe UI', sans-serif; 
        }
        
        /* ============================================
   TOPBAR
   ============================================ */
.topbar {
    background: white;
    padding: 12px 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: flex;
    justify-content: flex-end;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
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

/* ============================================
   LAYOUT
   ============================================ */
.layout {
    display: flex;
    min-height: 100vh;
    margin-top: 56px;
}

/* ============================================
   SIDEBAR - DESKTOP
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
   MAIN CONTENT - DESKTOP
   ============================================ */
.main-content {
    margin-left: 240px;
    flex: 1;
    padding: 25px;
    width: calc(100% - 240px);
    min-height: calc(100vh - 56px);
}

/* ============================================
   HAMBURGER & OVERLAY - HIDDEN DI DESKTOP
   ============================================ */
.hamburger-btn {
    display: none;
}

.overlay-menu {
    display: none;
}

/* ============================================
   MOBILE - LAYAR < 768px
   ============================================ */
@media (max-width: 768px) {
    .hamburger-btn {
        display: flex !important;
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
    
    .overlay-menu.active {
        display: block !important;
    }
    
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        width: 260px;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px;
    }
    
    .topbar {
        padding-left: 60px;
    }
    
    .stats-grid, .stats-row {
        grid-template-columns: 1fr !important;
    }
    
    .form-row {
        grid-template-columns: 1fr !important;
    }
    
    input, select, textarea {
        font-size: 16px;
    }
}
        
        /* MAIN CONTENT */
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        /* HEADER */
        .page-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h2 { 
            color: #333; 
            font-size: 24px; 
            margin: 0;
        }
        .page-header .date-info {
            color: #666;
            font-size: 14px;
        }
        
        /* STAT CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        .stat-card.destinasi::before { background: #0d6efd; }
        .stat-card.booking::before { background: #198754; }
        .stat-card.pelanggan::before { background: #6f42c1; }
        .stat-card.pendapatan::before { background: #fd7e14; }
        .stat-card.pending::before { background: #ffc107; }
        .stat-card.confirmed::before { background: #20c997; }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stat-card.destinasi .stat-icon { background: #e7f1ff; color: #0d6efd; }
        .stat-card.booking .stat-icon { background: #d1e7dd; color: #198754; }
        .stat-card.pelanggan .stat-icon { background: #e2d9f3; color: #6f42c1; }
        .stat-card.pendapatan .stat-icon { background: #ffe5d0; color: #fd7e14; }
        .stat-card.pending .stat-icon { background: #fff3cd; color: #856404; }
        .stat-card.confirmed .stat-icon { background: #d1f2eb; color: #20c997; }
        
        .stat-card .stat-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .stat-card .stat-sub {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        /* CHART & TABLE CONTAINER */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .card-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-box h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-box h3 i { color: #198754; }
        
        /* CHART */
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* TABLE */
        .table-modern {
            width: 100%;
            border-collapse: collapse;
        }
        .table-modern th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 2px solid #e0e0e0;
        }
        .table-modern td {
            padding: 12px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-modern tr:hover td {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-menunggu_verifikasi { background: #d1ecf1; color: #0c5460; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        /* DESTINASI POPULER */
        .dest-populer-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .dest-populer-item:last-child { border-bottom: none; }
        .dest-populer-item img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
        }
        .dest-populer-item .dest-info { flex: 1; }
        .dest-populer-item .dest-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .dest-populer-item .dest-stats {
            font-size: 12px;
            color: #666;
        }
        .dest-populer-item .dest-booking {
            text-align: right;
        }
        .dest-populer-item .booking-count {
            font-size: 18px;
            font-weight: bold;
            color: #198754;
        }
        .dest-populer-item .booking-label {
            font-size: 11px;
            color: #999;
        }
        
        /* QUICK ACTIONS */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .action-btn {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .action-btn:hover {
            border-color: #198754;
            background: #f8fff9;
        }
        .action-btn i {
            font-size: 24px;
            color: #198754;
            display: block;
            margin-bottom: 8px;
        }
        .action-btn span {
            font-size: 13px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 15px; }
            .content-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
        /* ========================================
   MOBILE: Sidebar tersembunyi dulu
   ======================================== */
@media (max-width: 768px) {
    
    /* PAKSA sidebar tersembunyi di kiri layar */
    .sidebar {
        position: fixed !important;
        top: 56px !important;
        left: 0 !important;
        bottom: 0 !important;
        width: 260px !important;
        z-index: 1000 !important;
        transform: translateX(-100%) !important;  /* ← INI KUNCI! Sidebar tersembunyi */
        transition: transform 0.3s ease !important;
    }
    
    /* Saat class 'active' ditambahkan, sidebar muncul */
    .sidebar.active {
        transform: translateX(0) !important;  /* ← Sidebar muncul */
    }
    
    /* Main content full width (tidak ada margin kiri) */
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px !important;
    }
    
    /* Topbar geser kanan untuk hamburger */
    .topbar {
        padding-left: 60px !important;
    }
    
    /* Stats grid 1 kolom */
    .stats-grid, .stats-row {
        grid-template-columns: 1fr !important;
    }
    
    /* Booking body 1 kolom */
    .booking-body {
        grid-template-columns: 1fr !important;
    }
}

/* Tombol hamburger (hanya muncul di mobile) */
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

@media (max-width: 768px) {
    .hamburger-btn {
        display: flex !important;
    }
}

/* Overlay gelap */
.overlay-menu {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 998;
}
.overlay-menu.active {
    display: block !important;
}
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
    </style>
</head>
<body>
    
    <button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">
        <h3>WALINI</h3>
        <p>Admin Panel</p>
    </div>
    <a href="dashboard.php" class="active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="data_destinasi.php">
        <i class="bi bi-geo-alt-fill"></i> Data Destinasi
    </a>
    <a href="booking.php">
        <i class="bi bi-calendar-check"></i> Booking
    </a>
    <a href="pelanggan.php">
        <i class="bi bi-people-fill"></i> Pelanggan
    </a>
    <a href="laporan.php">
        <i class="bi bi-file-earmark-bar-graph"></i> Laporan
    </a>
    <a href="pengaturan.php">
        <i class="bi bi-gear-fill"></i> Pengaturan
    </a>
    <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h2>Dashboard Admin</h2>
            <div class="date-info">
                <i class="bi bi-calendar"></i> <?= date('l, d F Y') ?> | 
                <i class="bi bi-clock"></i> <?= date('H:i') ?> WIB
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: bold; color: #333;">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>
            </div>
            <div style="font-size: 12px; color: #666;">Administrator</div>
        </div>
    </div>

    <!-- STATISTICS CARDS -->
    <div class="stats-grid">
        <div class="stat-card destinasi">
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="stat-label">Total Destinasi</div>
            <div class="stat-value"><?= $total_destinasi ?></div>
            <div class="stat-sub">Destinasi aktif</div>
        </div>
        
        <div class="stat-card booking">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-label">Total Booking</div>
            <div class="stat-value"><?= $total_booking ?></div>
            <div class="stat-sub">Semua transaksi</div>
        </div>
        
        <div class="stat-card pelanggan">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-label">Total Pelanggan</div>
            <div class="stat-value"><?= $total_pelanggan ?></div>
            <div class="stat-sub">User terdaftar</div>
        </div>
        
        <div class="stat-card pendapatan">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
            <div class="stat-sub">Booking confirmed</div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-label">Booking Pending</div>
            <div class="stat-value"><?= $booking_pending ?></div>
            <div class="stat-sub">Menunggu verifikasi</div>
        </div>
        
        <div class="stat-card confirmed">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-label">Booking Confirmed</div>
            <div class="stat-value"><?= $booking_confirmed ?></div>
            <div class="stat-sub">Berhasil diverifikasi</div>
        </div>
    </div>

    <!-- CHART & BOOKING TERBARU -->
    <div class="content-grid">
        
        <!-- GRAFIK BOOKING -->
        <div class="card-box">
            <h3><i class="bi bi-graph-up"></i> Grafik Booking (7 Hari Terakhir)</h3>
            <div class="chart-container">
                <canvas id="bookingChart"></canvas>
            </div>
        </div>
        
        <!-- BOOKING TERBARU -->
        <div class="card-box">
            <h3><i class="bi bi-clock-history"></i> Booking Terbaru</h3>
            <?php if (count($booking_terbaru) > 0): ?>
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booking_terbaru as $b): ?>
                    <tr>
                        <td>
                            <div style="font-weight: bold; color: #0d6efd;"><?= htmlspecialchars($b['kode_booking']) ?></div>
                            <div style="font-size: 11px; color: #999;"><?= date('d M H:i', strtotime($b['created_at'])) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($b['nama_pelanggan']) ?></div>
                            <div style="font-size: 11px; color: #666;"><?= htmlspecialchars($b['nama_destinasi']) ?></div>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $b['status'] ?>">
                                <?= strtoupper(str_replace('_', ' ', $b['status'])) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #999;">
                <i class="bi bi-inbox" style="font-size: 48px;"></i>
                <p style="margin-top: 10px;">Belum ada booking</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DESTINASI POPULER & QUICK ACTIONS -->
    <div class="content-grid">
        
        <!-- DESTINASI POPULER -->
        <div class="card-box">
            <h3><i class="bi bi-star-fill"></i> Destinasi Terpopuler</h3>
            <?php if (count($destinasi_populer) > 0): ?>
                <?php foreach ($destinasi_populer as $d): ?>
                <div class="dest-populer-item">
                    <img src="../assets/img/<?= htmlspecialchars($d['gambar'] ?? 'default.jpg') ?>" 
                         alt="<?= htmlspecialchars($d['nama_destinasi']) ?>"
                         onerror="this.src='https://via.placeholder.com/60x60?text=IMG'">
                    <div class="dest-info">
                        <div class="dest-name"><?= htmlspecialchars($d['nama_destinasi']) ?></div>
                        <div class="dest-stats">
                            Pendapatan: Rp <?= number_format($d['total_pendapatan'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="dest-booking">
                        <div class="booking-count"><?= $d['total_booking'] ?></div>
                        <div class="booking-label">Booking</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #999;">
                <i class="bi bi-geo-alt" style="font-size: 48px;"></i>
                <p style="margin-top: 10px;">Belum ada data destinasi</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- QUICK ACTIONS -->
        <div class="card-box">
            <h3><i class="bi bi-lightning-fill"></i> Aksi Cepat</h3>
            <div class="quick-actions">
                <a href="data_destinasi.php" class="action-btn">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Destinasi</span>
                </a>
                <a href="booking.php" class="action-btn">
                    <i class="bi bi-list-check"></i>
                    <span>Kelola Booking</span>
                </a>
                <a href="pelanggan.php" class="action-btn">
                    <i class="bi bi-people"></i>
                    <span>Data Pelanggan</span>
                </a>
                <a href="laporan.php" class="action-btn">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Lihat Laporan</span>
                </a>
            </div>
        </div>
    </div>

</div>

<!-- CHART.JS SCRIPT -->
<script>
// S2-04: Frontend Integration - Chart Dinamis dari Database
const ctx = document.getElementById('bookingChart').getContext('2d');

const bookingChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($grafik_labels) ?>,
        datasets: [{
            label: 'Jumlah Booking',
            data: <?= json_encode($grafik_data) ?>,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#198754',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 12, family: 'Segoe UI' },
                    color: '#666'
                }
            },
            tooltip: {
                backgroundColor: '#1a1a2e',
                titleFont: { size: 13 },
                bodyFont: { size: 12 },
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' booking';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 5,
                    font: { size: 11 },
                    color: '#999'
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                ticks: {
                    font: { size: 11 },
                    color: '#999'
                },
                grid: {
                    display: false
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Auto refresh setiap 5 menit
setTimeout(() => {
    location.reload();
}, 300000);
</script>
<script>
// Tunggu DOM siap sepenuhnya
document.addEventListener('DOMContentLoaded', function() {
    
    function getSidebar() {
        return document.querySelector('.sidebar');
    }
    
    function getOverlay() {
        return document.querySelector('.overlay-menu');
    }
    
    window.toggleMenu = function() {
        var sidebar = getSidebar();
        var overlay = getOverlay();
        
        console.log('Sidebar:', sidebar);
        console.log('Overlay:', overlay);
        
        if (sidebar) {
            sidebar.classList.toggle('active');
            console.log('Sidebar class sekarang:', sidebar.className);
        } else {
            console.error('❌ Sidebar tidak ditemukan!');
        }
        
        if (overlay) {
            overlay.classList.toggle('active');
        }
    };
    
    window.closeMenu = function() {
        var sidebar = getSidebar();
        var overlay = getOverlay();
        if (sidebar) sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
    };
    
    // Tutup sidebar saat klik link
    var sidebar = getSidebar();
    if (sidebar) {
        var links = sidebar.querySelectorAll('a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', window.closeMenu);
        }
    }
    
    // Tutup sidebar saat resize ke desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            window.closeMenu();
        }
    });
    
    console.log('✅ Script sidebar siap!');
});
</script>

</body>
</html>