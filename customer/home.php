<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

// Hanya ambil Tiket Masuk (id_destinasi = 2) untuk home page
try {
    $stmt = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
    $stmt->execute([2]);
    $destinasi = $stmt->fetchAll();
} catch (PDOException $e) {
    $destinasi = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Beranda - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* === BASE STYLES === */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        /* === HAMBURGER BUTTON === */
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
        
        /* === OVERLAY === */
        .overlay-menu {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            pointer-events: none;
        }
        .overlay-menu.active { display: block; pointer-events: auto; }
        
        /* === TOPBAR === */
        .topbar {
            background: white;
            padding: 12px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            height: 56px;
        }
        .search-box {
            display: flex;
            align-items: center;
            background: #f0f2f5;
            border-radius: 20px;
            padding: 8px 15px;
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
        }
        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 14px;
            margin-left: 8px;
        }
        .user-top {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* === LAYOUT === */
        .layout {
            display: flex;
            min-height: 100vh;
            margin-top: 56px;
        }
        
        /* === SIDEBAR === */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 999;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .sidebar .logo {
            text-align: center;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        .sidebar .logo h3 { color: #198754; font-weight: bold; font-size: 22px; margin: 0; }
        .sidebar .logo p { color: #aaa; font-size: 11px; margin: 3px 0 0 0; }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 20px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: white; padding-left: 25px; }
        .sidebar a.active { background: #198754; color: white; }
        .sidebar a i { font-size: 18px; width: 22px; text-align: center; }
        
        /* === MAIN CONTENT === */
        .main-content {
            margin-left: 240px;
            flex: 1;
            padding: 25px;
            width: calc(100% - 240px);
            min-height: calc(100vh - 56px);
        }
        
        /* === HERO === */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('../assets/img/home.jpg');
            background-size: cover;
            background-position: center;
            height: 250px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }
        .hero h1 { color: white; font-size: 48px; font-weight: bold; letter-spacing: 5px; }
        
        /* === SECTION HEADER === */
        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section-header a { 
            color: #198754; 
            text-decoration: none; 
            font-weight: bold;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .section-header a:hover { color: #0d6efd; transform: translateX(5px); }
        
        /* === DESTINASI HIGHLIGHT (CARD DI TENGAH) === */
        .destinasi-highlight {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 40px;
        }
        
        .destinasi-card-highlight {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            position: relative;
        }
        
        .destinasi-card-highlight:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(25, 135, 84, 0.25);
            border-color: #198754;
        }
        
        .badge-unggulan {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(255, 152, 0, 0.4);
            z-index: 10;
        }
        
        .destinasi-card-highlight img { 
            width: 100%; 
            height: 300px; 
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .destinasi-card-highlight:hover img {
            transform: scale(1.05);
        }
        
        .destinasi-card-highlight .card-body { 
            padding: 25px; 
        }
        
        .destinasi-card-highlight h3 { 
            font-size: 24px; 
            color: #1a1a2e; 
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .destinasi-card-highlight p { 
            font-size: 14px; 
            color: #666; 
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .destinasi-card-highlight .harga { 
            font-size: 20px; 
            color: #198754; 
            font-weight: bold;
            margin-bottom: 20px;
            padding: 12px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 10px;
            text-align: center;
        }
        
        .destinasi-card-highlight a,
        .destinasi-card-highlight button {
            padding: 14px !important;
            font-size: 16px !important;
            border-radius: 10px !important;
            margin-top: 10px;
            transition: all 0.3s ease;
            width: 100%;
            display: block;
            text-align: center;
        }
        
        .destinasi-card-highlight a:hover,
        .destinasi-card-highlight button:hover:not([disabled]) {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* === INFO BOX === */
        .info-box {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .info-item {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }
        
        .info-item i {
            font-size: 20px;
            color: #198754;
        }
        
        /* === MOBILE RESPONSIVE === */
        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            
            .sidebar {
                transform: translateX(-100%) !important;
                width: 260px !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            }
            .sidebar.active { transform: translateX(0) !important; }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px !important;
                padding-top: 20px !important;
            }
            
            .topbar { padding-left: 60px !important; }
            .search-box { margin: 0 10px; max-width: none; }
            
            .hero { height: 180px; }
            .hero h1 { font-size: 32px; letter-spacing: 3px; }
            
            .destinasi-card-highlight { max-width: 100% !important; }
            .destinasi-card-highlight img { height: 250px !important; }
            .destinasi-card-highlight h3 { font-size: 20px !important; }
            .info-box { flex-direction: column; }
            .info-item { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<!-- HAMBURGER BUTTON -->
<button class="hamburger-btn" onclick="toggleMenu()">☰</button>

<!-- OVERLAY -->
<div class="overlay-menu" onclick="closeMenu()"></div>

<!-- TOPBAR -->
<div class="topbar">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Cari destinasi...">
    </div>
    <div class="user-top">
        <i class="bi bi-person-circle"></i>
        <span><?= htmlspecialchars($user['nama'] ?? 'User') ?></span>
    </div>
</div>

<!-- LAYOUT -->
<div class="layout">
    <!-- SIDEBAR - WAJIB ADA! -->
    <div class="sidebar">
        <div class="logo">
            <h3>WALINI</h3>
            <p>Customer Panel</p>
        </div>
        <a href="home.php" class="active"><i class="bi bi-house-fill"></i> Beranda</a>
        <a href="destinasi.php"><i class="bi bi-geo-alt-fill"></i> Destinasi</a>
        <a href="riwayat.php"><i class="bi bi-receipt"></i> Pesanan</a>
        <a href="profile.php"><i class="bi bi-person-fill"></i> Profil</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="hero">
            <h1>WALINI</h1>
        </div>

        <div class="section-header">
            <a href="destinasi.php">
                <i class="bi bi-grid-3x3-gap"></i> Lihat Semua Destinasi
            </a>
        </div>

        <!-- CARD DI TENGAH DENGAN TAMPILAN MENARIK -->
        <div class="destinasi-highlight">
            <?php if (!empty($destinasi)): ?>
                <?php foreach ($destinasi as $d): 
                    $stmt_stok = $pdo->prepare("SELECT stok FROM tiket WHERE id_destinasi = ?");
                    $stmt_stok->execute([$d['id_destinasi']]);
                    $stok_tiket = $stmt_stok->fetchColumn();
                    $stok_tiket = $stok_tiket ? (int)$stok_tiket : 0;
                    
                    // Cek fasilitas
                    try {
                        $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM fasilitas WHERE id_destinasi = ?");
                        $stmt_cek->execute([$d['id_destinasi']]);
                        $ada_fasilitas = (int)$stmt_cek->fetchColumn();
                    } catch (PDOException $e) {
                        $ada_fasilitas = 0;
                    }
                ?>
                <div class="destinasi-card-highlight">
                    <!-- BADGE UNGGULAN -->
                    <span class="badge-unggulan">⭐ UNGGULAN</span>
                    
                    <img src="../assets/img/<?= htmlspecialchars($d['gambar'] ?? 'default.jpg') ?>" 
                         alt="<?= htmlspecialchars($d['nama_destinasi']) ?>">
                    
                    <div class="card-body">
                        <h3><?= htmlspecialchars($d['nama_destinasi']) ?></h3>
                        <p><?= htmlspecialchars($d['deskripsi'] ?? '') ?></p>
                        
                        <div class="harga">
                            💰 Mulai Rp. <?= number_format($d['harga_tiket'], 0, ',', '.') ?>
                        </div>
                        
                        <?php if ($stok_tiket > 0): ?>
                            <a href="booking.php?id_destinasi=<?= $d['id_destinasi'] ?>" 
                               style="background: linear-gradient(135deg, #198754, #146c43); color: white; text-decoration: none; border: none; font-weight: bold;">
                                ✓ Tersedia (<?= $stok_tiket ?> Tiket)
                            </a>
                            
                            <?php if ($ada_fasilitas > 0): ?>
                            <a href="fasilitas.php?id=<?= $d['id_destinasi'] ?>" 
                               style="background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; text-decoration: none; font-weight: bold;">
                                ⭐ Lihat Fasilitas (<?= $ada_fasilitas ?>)
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button disabled style="background: #dc3545; color: white; border: none; font-weight: bold; cursor: not-allowed;">
                                ✗ Tiket Habis
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666; text-align: center;">Belum ada destinasi tersedia.</p>
            <?php endif; ?>
        </div>
        
        <!-- INFO BOX DI BAWAH CARD -->
        <div class="info-box">
            <div class="info-item">
                <i class="bi bi-clock"></i>
                <span>Buka pukul 09.00 sampai pukul 17.00</span>
            </div>
            <div class="info-item">
                <i class="bi bi-geo-alt"></i>
                <span>Ciwidey Rancabali Walini </span>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT - WAJIB ADA! -->
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

// Tutup sidebar saat klik link
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', closeMenu);
        });
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});
</script>

</body>
</html>