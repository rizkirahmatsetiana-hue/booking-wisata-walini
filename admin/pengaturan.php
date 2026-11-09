<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('admin');

$success = '';
$error = '';

// Handle update pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_wisata = trim($_POST['nama_wisata'] ?? 'Walini Wisata');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $rekening_bri = trim($_POST['rekening_bri'] ?? '');
    $rekening_mandiri = trim($_POST['rekening_mandiri'] ?? '');
    
    try {
        // Simpan ke file config atau database
        $config = [
            'nama_wisata' => $nama_wisata,
            'email' => $email,
            'telepon' => $telepon,
            'alamat' => $alamat,
            'rekening_bri' => $rekening_bri,
            'rekening_mandiri' => $rekening_mandiri
        ];
        file_put_contents('../config/pengaturan.json', json_encode($config, JSON_PRETTY_PRINT));
        $success = "Pengaturan berhasil disimpan!";
    } catch (Exception $e) {
        $error = "Gagal menyimpan: " . $e->getMessage();
    }
}

// Load pengaturan
$config_file = '../config/pengaturan.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [
    'nama_wisata' => 'Walini Wisata',
    'email' => 'info@walini.com',
    'telepon' => '081234567890',
    'alamat' => 'Rancabali, Jawa Barat',
    'rekening_bri' => '0987654321000',
    'rekening_mandiri' => '1300012345678'
];

// Info sistem
$php_version = phpversion();
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$db_size_stmt = $pdo->query("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = 'booking_wisata_walini'");
$db_size = $db_size_stmt->fetch()['size'] ?? 0;
$db_size_mb = round($db_size / 1024 / 1024, 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <title>Pengaturan Sistem - Admin Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
        
        .page-header { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-header h2 { color: #333; font-size: 24px; margin: 0; }
        
        .card-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-box h3 { font-size: 18px; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .card-box h3 i { color: #198754; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 14px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #198754; }
        
        .btn-primary { background: #198754; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-primary:hover { background: #146c43; }
        
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-item { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .info-item label { font-size: 12px; color: #666; display: block; margin-bottom: 5px; }
        .info-item span { font-size: 16px; font-weight: bold; color: #333; }
        
        .system-status { display: flex; align-items: center; gap: 10px; padding: 10px; background: #d4edda; border-radius: 8px; margin-bottom: 10px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #198754; }
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
   PENGATURAN - PAKSA 1 KOLOM DI MOBILE
   ============================================ */
@media (max-width: 768px) {
    
    /* PAKSA SEMUA LAYOUT JADI 1 KOLOM */
    .row,
    .col,
    .col-2,
    .col-md-6,
    .col-lg-6,
    .col-xl-6,
    .grid-2,
    .two-column,
    .settings-grid,
    .pengaturan-grid,
    [class*="col-"],
    [class*="grid-"],
    [style*="display: flex"],
    [style*="display:grid"] {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Card full width */
    .card-box,
    .card,
    .info-card,
    .form-card,
    .setting-card,
    .panel {
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 15px !important;
        padding: 15px !important;
        box-sizing: border-box !important;
    }
    
    /* Form group full width */
    .form-group {
        width: 100% !important;
        margin-bottom: 15px !important;
    }
    
    .form-group label {
        display: block !important;
        width: 100% !important;
        font-size: 13px !important;
        margin-bottom: 5px !important;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100% !important;
        max-width: 100% !important;
        padding: 12px !important;
        font-size: 16px !important;
        box-sizing: border-box !important;
    }
    
    /* Info sistem card */
    .info-sistem,
    .system-info,
    .keamanan-card,
    .database-card {
        width: 100% !important;
        margin-top: 15px !important;
    }
    
    /* Grid info sistem (PHP, Server, dll) */
    .info-grid,
    .system-grid,
    [class*="info-"] {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
    
    .info-grid div,
    .system-grid div {
        font-size: 12px !important;
    }
    
    /* Tombol full width */
    .btn,
    button[type="submit"],
    input[type="submit"] {
        width: 100% !important;
        padding: 14px !important;
        font-size: 16px !important;
        box-sizing: border-box !important;
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
    
    /* Main content */
    .main-content {
        padding: 15px !important;
        padding-bottom: 100px !important;
        overflow-x: hidden !important;
    }
    
    /* Status badge */
    .status-badge,
    .badge {
        font-size: 11px !important;
        padding: 5px 10px !important;
    }
    
    /* List keamanan */
    .keamanan-list,
    .security-list,
    ul {
        padding-left: 20px !important;
    }
    
    .keamanan-list li,
    .security-list li {
        font-size: 12px !important;
        margin-bottom: 8px !important;
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
    <a href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
    <a href="pengaturan.php" class="active"><i class="bi bi-gear-fill"></i> Pengaturan</a>
    <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<div class="main-content">

    <div class="page-header">
        <h2><i class="bi bi-gear-fill"></i> Pengaturan Sistem</h2>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
        
        <!-- FORM PENGATURAN -->
        <div>
            <form method="POST">
                <div class="card-box">
                    <h3><i class="bi bi-building"></i> Informasi Wisata</h3>
                    <div class="form-group">
                        <label>Nama Wisata</label>
                        <input type="text" name="nama_wisata" value="<?= htmlspecialchars($config['nama_wisata']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Kontak</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($config['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" name="telepon" value="<?= htmlspecialchars($config['telepon']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="2"><?= htmlspecialchars($config['alamat']) ?></textarea>
                    </div>
                </div>

                <div class="card-box">
                    <h3><i class="bi bi-bank"></i> Rekening Pembayaran</h3>
                    <div class="form-group">
                        <label>Rekening BRI</label>
                        <input type="text" name="rekening_bri" value="<?= htmlspecialchars($config['rekening_bri']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Rekening Mandiri</label>
                        <input type="text" name="rekening_mandiri" value="<?= htmlspecialchars($config['rekening_mandiri']) ?>">
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="bi bi-save"></i> Simpan Pengaturan
                </button>
            </form>
        </div>

        <!-- INFO SISTEM -->
        <div>
            <div class="card-box">
                <h3><i class="bi bi-info-circle"></i> Informasi Sistem</h3>
                
                <div class="system-status">
                    <div class="status-dot"></div>
                    <div>
                        <div style="font-weight:bold; color:#198754;">Sistem Berjalan Normal</div>
                        <div style="font-size:12px; color:#666;">Semua layanan aktif</div>
                    </div>
                </div>

                <div class="info-grid" style="margin-top:15px;">
                    <div class="info-item">
                        <label>PHP Version</label>
                        <span style="font-size:14px;"><?= $php_version ?></span>
                    </div>
                    <div class="info-item">
                        <label>Server</label>
                        <span style="font-size:14px;"><?= explode(' ', $server_software)[0] ?></span>
                    </div>
                    <div class="info-item">
                        <label>Database Size</label>
                        <span style="font-size:14px;"><?= $db_size_mb ?> MB</span>
                    </div>
                    <div class="info-item">
                        <label>Timezone</label>
                        <span style="font-size:14px;"><?= date_default_timezone_get() ?></span>
                    </div>
                </div>
            </div>

            <div class="card-box">
                <h3><i class="bi bi-shield-check"></i> Keamanan</h3>
                <div style="font-size:14px; color:#666; line-height:1.6;">
                    <div style="margin-bottom:10px;">✅ Password di-hash dengan bcrypt</div>
                    <div style="margin-bottom:10px;">✅ Prepared statement (anti SQL injection)</div>
                    <div style="margin-bottom:10px;">✅ Session management aktif</div>
                    <div style="margin-bottom:10px;">✅ File upload validation</div>
                </div>
            </div>

            <div class="card-box">
                <h3><i class="bi bi-database"></i> Database</h3>
                <div style="font-size:14px; color:#666;">
                    <div style="margin-bottom:8px;"> Tabel: <strong>8 tabel</strong></div>
                    <div style="margin-bottom:8px;">📁 Database: <strong>booking_wisata_walini</strong></div>
                    <div style="margin-bottom:8px;">🔗 Koneksi: <strong style="color:#198754;">Aktif</strong></div>
                </div>
            </div>
        </div>
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