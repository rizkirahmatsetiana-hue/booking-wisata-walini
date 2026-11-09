<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

try {
    // PERBAIKAN 1: JOIN ke tabel pelanggan untuk memastikan data hanya milik user yang login
    $stmt = $pdo->prepare("
        SELECT b.*, d.nama_destinasi, d.gambar 
        FROM booking b 
        JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
        JOIN detail_booking db ON b.id_booking = db.id_booking 
        JOIN tiket t ON db.id_tiket = t.id_tiket 
        JOIN destinasi d ON t.id_destinasi = d.id_destinasi 
        WHERE p.id_user = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['id_user']]);
    $riwayat = $stmt->fetchAll();
} catch (PDOException $e) {
    // PERBAIKAN 2: Catat error di log server (Tugas 7: Error Handling Aman)
    error_log("Riwayat Pesanan Error: " . $e->getMessage());
    $riwayat = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Pesanan - Walini</title>
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
        .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 14px; margin-left: 8px; }
        .user-top { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; }
        
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
        .page-header h2 { color: #333; font-size: 22px; display: flex; align-items: center; gap: 10px; }
        
        .riwayat-list { display: flex; flex-direction: column; gap: 15px; }
        .riwayat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .riwayat-card img { width: 100px; height: 80px; object-fit: cover; border-radius: 8px; }
        .riwayat-info { flex: 1; }
        .riwayat-info h3 { font-size: 16px; color: #333; margin-bottom: 5px; }
        .riwayat-info p { font-size: 13px; color: #666; margin-bottom: 3px; }
        .riwayat-info .kode { font-weight: bold; color: #0d6efd; }
        .riwayat-info .total { font-weight: bold; color: #198754; font-size: 16px; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-menunggu { background: #cff4fc; color: #055160; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .btn-e-tiket {
            padding: 8px 15px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-e-tiket:hover { background: #146c43; }
        
        .btn-bayar {
            padding: 8px 15px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-bayar:hover { background: #0a58ca; }

        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            .topbar { padding-left: 60px !important; }
            .riwayat-card { flex-direction: column; align-items: flex-start; }
            .riwayat-card img { width: 100%; height: 150px; }
            .riwayat-info { width: 100%; margin-bottom: 10px; }
            .status-badge, .btn-e-tiket, .btn-bayar { width: 100%; text-align: center; justify-content: center; margin-top: 5px; }
        }
    </style>
</head>
<body>

<button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="topbar">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" placeholder="Cari pesanan..." onkeyup="filterPesanan()">
    </div>
    <div class="user-top">
        <i class="bi bi-person-circle"></i>
        <span><?= htmlspecialchars($user['nama'] ?? 'User') ?></span>
    </div>
</div>

<div class="layout">
    <div class="sidebar">
        <div class="logo">
            <h3>WALINI</h3>
            <p>Customer Panel</p>
        </div>
        <a href="home.php"><i class="bi bi-house-fill"></i> Beranda</a>
        <a href="destinasi.php"><i class="bi bi-geo-alt-fill"></i> Destinasi</a>
        <a href="riwayat.php" class="active"><i class="bi bi-receipt"></i> Pesanan</a>
        <a href="profile.php"><i class="bi bi-person-fill"></i> Profil</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-receipt"></i> Riwayat Pesanan</h2>
        </div>

        <div class="riwayat-list" id="riwayatList">
            <?php if (!empty($riwayat)): ?>
                <?php foreach ($riwayat as $r): 
                    $status = strtolower($r['status']);
                    $status_class = 'status-' . str_replace('_', '-', $status);
                    $status_text = ucfirst(str_replace('_', ' ', $status));
                ?>
                <div class="riwayat-card" data-search="<?= strtolower($r['nama_destinasi'] . ' ' . $r['kode_booking']) ?>">
                    <img src="../assets/img/<?= htmlspecialchars($r['gambar'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($r['nama_destinasi']) ?>">
                    <div class="riwayat-info">
                        <h3><?= htmlspecialchars($r['nama_destinasi']) ?></h3>
                        <p class="kode">Kode: <?= htmlspecialchars($r['kode_booking']) ?></p>
                        <p>Tanggal: <?= date('d M Y', strtotime($r['tanggal_booking'])) ?></p>
                        <p class="total">Total: Rp. <?= number_format($r['total_harga'], 0, ',', '.') ?></p>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                        <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                        
                        <?php if ($status === 'confirmed'): ?>
                            <!-- PERBAIKAN 3: Gunakan kode_booking dan urlencode untuk keamanan URL -->
                            <a href="e_tiket.php?kode_booking=<?= urlencode($r['kode_booking']) ?>" class="btn-e-tiket">
                                <i class="bi bi-ticket"></i> E-Tiket
                            </a>
                        <?php elseif ($status === 'pending' || $status === 'menunggu_verifikasi' || $status === 'rejected'): ?>
                            <a href="pembayaran.php?id_booking=<?= $r['id_booking'] ?>" class="btn-bayar">
                                <i class="bi bi-credit-card"></i> Bayar / Upload
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; color: #666;">
                    <i class="bi bi-inbox" style="font-size: 60px;"></i>
                    <p style="margin-top: 15px;">Belum ada pesanan</p>
                    <a href="destinasi.php" style="color: #198754; text-decoration: none; font-weight: bold;">Mulai Booking →</a>
                </div>
            <?php endif; ?>
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

// Fitur Pencarian Pesanan (Bonus Usability - Tugas 8)
function filterPesanan() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.riwayat-card');
    
    cards.forEach(card => {
        const searchData = card.getAttribute('data-search');
        if (searchData.includes(input)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

</body>
</html>