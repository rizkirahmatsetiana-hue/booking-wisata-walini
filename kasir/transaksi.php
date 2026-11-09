<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('kasir');

// Filter tanggal
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Ambil transaksi
$stmt = $pdo->prepare("
    SELECT 
        b.id_booking, b.kode_booking, b.tanggal_booking, b.total_harga, b.status, b.created_at,
        p.nama as nama_pelanggan, p.email, p.no_hp,
        d.nama_destinasi, t.nama_tiket, db.jumlah, db.harga_satuan
    FROM booking b
    JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
    JOIN detail_booking db ON b.id_booking = db.id_booking
    JOIN tiket t ON db.id_tiket = t.id_tiket
    JOIN destinasi d ON t.id_destinasi = d.id_destinasi
    WHERE DATE(b.created_at) = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$tanggal]);
$transaksi = $stmt->fetchAll();

// Statistik
$total_transaksi = count($transaksi);
$total_pendapatan = array_sum(array_column($transaksi, 'total_harga'));
$total_tiket = array_sum(array_column($transaksi, 'jumlah'));
$transaksi_confirmed = count(array_filter($transaksi, fn($t) => $t['status'] === 'confirmed'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <title>Transaksi - Kasir Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
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
        .main-content { margin-left: 250px; padding: 30px; }
        
        .page-header { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { color: #333; font-size: 24px; margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #ddd; }
        .stat-card.transaksi { border-left-color: #0d6efd; }
        .stat-card.pendapatan { border-left-color: #198754; }
        .stat-card.tiket { border-left-color: #6f42c1; }
        .stat-card.confirmed { border-left-color: #fd7e14; }
        .stat-card .stat-label { font-size: 13px; color: #666; }
        .stat-card .stat-value { font-size: 24px; font-weight: bold; color: #333; }
        
        .filter-bar { background: white; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: center; }
        .filter-bar label { font-weight: 600; color: #333; }
        .filter-bar input[type="date"] { padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .filter-bar input[type="date"]:focus { outline: none; border-color: #198754; }
        .filter-bar button { padding: 8px 16px; background: #198754; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        
        .card-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-box h3 { font-size: 18px; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .card-box h3 i { color: #198754; }
        
        .table-modern { width: 100%; border-collapse: collapse; }
        .table-modern th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid #e0e0e0; }
        .table-modern td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .table-modern tr:hover td { background: #f8f9fa; }
        
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .btn-detail { padding: 5px 10px; background: #0d6efd; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; }
        
        .empty-state { text-align: center; padding: 40px; color: #999; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
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
    </style>
</head>
<body>
    <button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

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
        <h2><i class="bi bi-receipt"></i> Transaksi</h2>
        <span style="color:#666;"><?= date('d M Y', strtotime($tanggal)) ?></span>
    </div>

    <div class="stats-grid">
        <div class="stat-card transaksi">
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value"><?= $total_transaksi ?></div>
        </div>
        <div class="stat-card pendapatan">
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value" style="font-size:18px;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card tiket">
            <div class="stat-label">Total Tiket</div>
            <div class="stat-value"><?= $total_tiket ?></div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-label">Confirmed</div>
            <div class="stat-value"><?= $transaksi_confirmed ?></div>
        </div>
    </div>

    <div class="filter-bar">
        <label><i class="bi bi-calendar"></i> Filter Tanggal:</label>
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="date" name="tanggal" value="<?= $tanggal ?>" max="<?= date('Y-m-d') ?>">
            <button type="submit"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
    </div>

    <div class="card-box">
        <h3><i class="bi bi-list-ul"></i> Daftar Transaksi - <?= date('d M Y', strtotime($tanggal)) ?></h3>
        
        <?php if ($total_transaksi > 0): ?>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Kode Booking</th>
                    <th>Pelanggan</th>
                    <th>Destinasi</th>
                    <th>Tiket</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaksi as $t): ?>
                <tr>
                    <td>
                        <div style="font-weight:bold; color:#0d6efd;"><?= htmlspecialchars($t['kode_booking']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($t['nama_pelanggan']) ?></div>
                        <div style="font-size:11px; color:#666;"><?= htmlspecialchars($t['email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($t['nama_destinasi']) ?></td>
                    <td><?= htmlspecialchars($t['nama_tiket']) ?></td>
                    <td><span style="background:#e7f1ff; color:#0d6efd; padding:3px 8px; border-radius:10px; font-weight:bold;"><?= $t['jumlah'] ?></span></td>
                    <td style="color:#198754; font-weight:bold;">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                    <td>
                        <span class="status-badge status-<?= $t['status'] ?>">
                            <?= strtoupper($t['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:12px; color:#666;"><?= date('H:i', strtotime($t['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox" style="font-size:48px;"></i>
            <h4 style="margin:15px 0;">Tidak Ada Transaksi</h4>
            <p>Tidak ada transaksi pada tanggal <?= date('d M Y', strtotime($tanggal)) ?></p>
        </div>
        <?php endif; ?>
    </div>

</div>
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