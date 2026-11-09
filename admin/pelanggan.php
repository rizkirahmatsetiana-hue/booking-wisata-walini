<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('admin');  // ← INI YANG BENAR!

$user = walini_get_user();

// HAPUS data pelanggan yang bukan role pelanggan (cleanup)
try {
    $pdo->prepare("
        DELETE p FROM pelanggan p
        JOIN users u ON p.id_user = u.id_user
        WHERE u.role != 'pelanggan'
    ")->execute();
} catch (PDOException $e) {
    // Abaikan error
}

// Ambil hanya pelanggan dengan role pelanggan
try {
    $stmt = $pdo->query("
        SELECT p.*, u.email as user_email, u.role,
               (SELECT COUNT(*) FROM booking b WHERE b.id_pelanggan = p.id_pelanggan) as total_booking,
               (SELECT COALESCE(SUM(total_harga), 0) FROM booking b WHERE b.id_pelanggan = p.id_pelanggan AND b.status = 'confirmed') as total_spending
        FROM pelanggan p
        JOIN users u ON p.id_user = u.id_user
        WHERE u.role = 'pelanggan'
        ORDER BY p.created_at DESC
    ");
    $pelanggan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $pelanggan_list = [];
}

$total_pelanggan = count($pelanggan_list);
$total_transaksi = array_sum(array_column($pelanggan_list, 'total_booking'));
$total_pendapatan = array_sum(array_column($pelanggan_list, 'total_spending'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <title>Data Pelanggan - Admin Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
   
   <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #f0f2f5; overflow-x: hidden; }
    
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
    .user-info { display: flex; align-items: center; gap: 10px; font-weight: bold; }
    
    .layout { 
        display: flex; 
        min-height: 100vh;
        margin-top: 56px;
    }
    
    .sidebar { 
        width: 250px; 
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); 
        color: white; 
        padding: 20px 0; 
        position: fixed; 
        top: 56px;
        bottom: 0; 
        left: 0;
        z-index: 99;
    }
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
    
    .main-content { 
        margin-left: 250px; 
        flex: 1; 
        padding: 30px;
        width: calc(100% - 250px);
        min-height: calc(100vh - 56px);
    }
    
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
    .page-header h2 { color: #333; font-size: 24px; margin: 0; }
    
    .stats-grid { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px; 
        margin-bottom: 25px; 
        width: 100%;
    }
    .stat-card { 
        background: white; 
        padding: 25px; 
        border-radius: 12px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        border-left: 4px solid #6f42c1; 
        width: 100%;
    }
    .stat-card .stat-label { font-size: 13px; color: #666; margin-bottom: 8px; }
    .stat-card .stat-value { font-size: 32px; font-weight: bold; color: #333; }
    
    .card-box { 
        background: white; 
        border-radius: 12px; 
        padding: 25px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        margin-bottom: 20px;
        width: 100%;
    }
    .card-box h3 { font-size: 18px; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .card-box h3 i { color: #198754; }
    
    .search-box { 
        padding: 10px 15px; 
        border: 2px solid #e0e0e0; 
        border-radius: 8px; 
        font-size: 14px; 
        width: 300px; 
    }
    .search-box:focus { outline: none; border-color: #198754; }
    
    .table-container {
        width: 100%;
        overflow-x: auto;
    }
    
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
    .table-modern tr:hover td { background: #f8f9fa; }
    
    .avatar { 
        width: 40px; 
        height: 40px; 
        border-radius: 50%; 
        background: linear-gradient(135deg, #198754, #146c43); 
        color: white; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: bold; 
        font-size: 16px; 
    }
    
    @media (max-width: 768px) {
        .sidebar { display: none; }
        .main-content { margin-left: 0; width: 100%; padding: 15px; }
        .stats-grid { grid-template-columns: 1fr; }
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
/* ============================================
   BOOKING PAGE - MOBILE OPTIMIZATION
   ============================================ */
@media (max-width: 768px) {
    
    /* Stats row jadi 1 kolom */
    .stats-row {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
    }
    
    .stats-row .stat-mini {
        width: 100% !important;
        padding: 15px !important;
    }
    
    /* Filter tabs wrap */
    .filter-tabs {
        flex-wrap: wrap !important;
        gap: 8px !important;
        padding: 10px !important;
    }
    
    .filter-tabs .filter-tab {
        flex: 1 1 auto !important;
        min-width: 70px !important;
        font-size: 11px !important;
        padding: 8px 12px !important;
        text-align: center !important;
    }
    
    /* Booking card padding */
    .booking-card {
        padding: 15px !important;
        margin-bottom: 15px !important;
    }
    
    /* Booking header stack */
    .booking-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
        padding-bottom: 10px !important;
        margin-bottom: 10px !important;
    }
    
    .booking-code {
        font-size: 14px !important;
    }
    
    .status-badge {
        font-size: 10px !important;
        padding: 4px 10px !important;
    }
    
    /* Booking body stack */
    .booking-body {
        display: flex !important;
        flex-direction: column !important;
        gap: 15px !important;
    }
    
    /* Booking info full width */
    .booking-info {
        width: 100% !important;
    }
    
    .booking-info p {
        font-size: 13px !important;
        margin-bottom: 8px !important;
    }
    
    .booking-info .label {
        min-width: 80px !important;
        font-size: 12px !important;
    }
    
    .booking-info .total {
        font-size: 14px !important;
    }
    
    /* Bukti pembayaran */
    .bukti-section {
        margin-top: 10px !important;
    }
    
    .bukti-img {
        max-width: 100% !important;
        max-height: 150px !important;
        width: 100% !important;
    }
    
    /* Booking actions full width */
    .booking-actions {
        flex-direction: column !important;
        gap: 10px !important;
        padding-top: 10px !important;
    }
    
    .booking-actions .btn,
    .booking-actions button,
    .booking-actions form {
        width: 100% !important;
    }
    
    .booking-actions .btn {
        padding: 12px !important;
        font-size: 14px !important;
        justify-content: center !important;
    }
    
    /* Page header */
    .page-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 15px !important;
    }
    
    .page-header h2 {
        font-size: 18px !important;
    }
    
    /* Main content padding */
    .main-content {
        padding: 15px !important;
        padding-bottom: 100px !important;
    }
    
    /* Card box */
    .card-box {
        padding: 15px !important;
        margin-bottom: 15px !important;
    }
}

</style>
</head>
<body>
    <button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="topbar">
    <div class="user-info">
        <i class="bi bi-person-circle"></i>
        <?= htmlspecialchars($user['nama']) ?>
    </div>
</div>

<div class="layout">
    <div class="sidebar">
        <div class="logo"><h3>WALINI</h3><p>Admin Panel</p></div>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="data_destinasi.php"><i class="bi bi-geo-alt-fill"></i> Data Destinasi</a>
        <a href="booking.php"><i class="bi bi-calendar-check"></i> Booking</a>
        <a href="pelanggan.php" class="active"><i class="bi bi-people-fill"></i> Pelanggan</a>
        <a href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
        <a href="pengaturan.php"><i class="bi bi-gear-fill"></i> Pengaturan</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="main-content">

        <div class="page-header">
            <h2><i class="bi bi-people-fill"></i> Data Pelanggan</h2>
            <input type="text" class="search-box" id="searchInput" placeholder="🔍 Cari pelanggan..." onkeyup="filterTable()">
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Pelanggan</div>
                <div class="stat-value"><?= $total_pelanggan ?></div>
            </div>
            <div class="stat-card" style="border-left-color:#198754;">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?= $total_transaksi ?></div>
            </div>
            <div class="stat-card" style="border-left-color:#fd7e14;">
                <div class="stat-label">Total Pendapatan</div>
                <div class="stat-value" style="font-size:20px;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="card-box">
            <h3><i class="bi bi-list-ul"></i> Daftar Pelanggan</h3>
            <table class="table-modern" id="pelangganTable">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th>Total Booking</th>
                        <th>Total Spending</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pelanggan_list)): ?>
                        <?php foreach ($pelanggan_list as $p): ?>
                        <tr>
                            <td><div class="avatar"><?= strtoupper(substr($p['nama'], 0, 1)) ?></div></td>
                            <td><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($p['email'] ?? $p['user_email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(substr($p['alamat'] ?? '-', 0, 30)) ?></td>
                            <td><span style="background:#e7f1ff; color:#0d6efd; padding:3px 10px; border-radius:12px; font-weight:bold;"><?= $p['total_booking'] ?></span></td>
                            <td style="color:#198754; font-weight:bold;">Rp <?= number_format($p['total_spending'], 0, ',', '.') ?></td>
                            <td style="font-size:12px; color:#666;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:#666;">
                                <i class="bi bi-inbox" style="font-size:40px;"></i>
                                <p>Belum ada data pelanggan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#pelangganTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}
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