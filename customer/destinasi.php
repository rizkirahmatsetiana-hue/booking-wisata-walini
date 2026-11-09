<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

// ==========================================
// LOGIKA PENCARIAN DESTINASI
// ==========================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if (!empty($search)) {
        // Jika ada kata kunci, cari berdasarkan nama atau deskripsi
        $stmt = $pdo->prepare("SELECT * FROM destinasi WHERE nama_destinasi LIKE ? OR deskripsi LIKE ? ORDER BY id_destinasi ASC");
        $keyword = '%' . $search . '%';
        $stmt->execute([$keyword, $keyword]);
    } else {
        // Jika tidak ada kata kunci, tampilkan semua
        $stmt = $pdo->query("SELECT * FROM destinasi ORDER BY id_destinasi ASC");
    }
    $destinasi_list = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Destinasi Search Error: " . $e->getMessage());
    $destinasi_list = [];
}
$total_destinasi = count($destinasi_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Destinasi - Walini</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h2 { color: #333; font-size: 22px; display: flex; align-items: center; gap: 10px; }
        .badge-count { background: #e7f3ff; color: #0d6efd; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 14px; }
        
        .destinasi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .destinasi-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .destinasi-card:hover { transform: translateY(-3px); }
        .destinasi-card img { width: 100%; height: 200px; object-fit: cover; }
        .destinasi-card .card-body { padding: 15px; }
        .destinasi-card h3 { font-size: 16px; color: #333; margin-bottom: 8px; }
        .destinasi-card p { font-size: 12px; color: #666; margin-bottom: 10px; }
        .destinasi-card .harga { color: #198754; font-weight: bold; font-size: 14px; }
        
        /* Tampilan Khusus Saat Tidak Ditemukan */
        .empty-state {
            grid-column: 1 / -1; /* Memaksa memenuhi seluruh lebar grid */
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            .topbar { padding-left: 60px !important; }
            .destinasi-grid { grid-template-columns: 1fr !important; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

<button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="topbar">
    <form method="GET" action="destinasi.php" class="search-box" style="text-decoration: none;">
        <i class="bi bi-search"></i>
        <input type="text" name="search" id="searchInput" placeholder="Cari destinasi..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        <?php if (!empty($search)): ?>
            <a href="destinasi.php" style="text-decoration: none; color: #dc3545; margin-left: 8px; font-weight: bold; font-size: 18px;" title="Hapus pencarian">✕</a>
        <?php endif; ?>
    </form>
    
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
        <a href="destinasi.php" class="active"><i class="bi bi-geo-alt-fill"></i> Destinasi</a>
        <a href="riwayat.php"><i class="bi bi-receipt"></i> Pesanan</a>
        <a href="profile.php"><i class="bi bi-person-fill"></i> Profil</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-geo-alt-fill"></i> Semua Destinasi</h2>
            <span class="badge-count"><?= $total_destinasi ?> Destinasi Tersedia</span>
        </div>

        <div class="destinasi-grid">
            <?php if (!empty($destinasi_list)): ?>
                <?php foreach ($destinasi_list as $d): 
                    $stmt_stok = $pdo->prepare("SELECT stok FROM tiket WHERE id_destinasi = ?");
                    $stmt_stok->execute([$d['id_destinasi']]);
                    $stok_tiket = $stmt_stok->fetchColumn();
                    $stok_tiket = $stok_tiket ? (int)$stok_tiket : 0;
                ?>
                <div class="destinasi-card">
                    <img src="../assets/img/<?= htmlspecialchars($d['gambar'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($d['nama_destinasi']) ?>">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($d['nama_destinasi']) ?></h3>
                        <p><?= htmlspecialchars(substr($d['deskripsi'] ?? '', 0, 50)) ?>...</p>
                        <div class="harga">Mulai Rp. <?= number_format($d['harga_tiket'], 0, ',', '.') ?></div>
                        <?php if ($stok_tiket > 0): ?>
                            <a href="booking.php?id_destinasi=<?= $d['id_destinasi'] ?>" style="margin-top: 10px; display: block; text-align: center; text-decoration: none; padding: 10px; background: #198754; color: white; border-radius: 8px; font-weight: bold;">
                                ✓ Tersedia (<?= $stok_tiket ?>)
                            </a>
                        <?php else: ?>
                            <button disabled style="margin-top: 10px; width: 100%; padding: 10px; background: #dc3545; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: not-allowed;">✗ Tiket Habis</button>
                        <?php endif; ?>
                      <?php 
// Cek apakah destinasi ini punya fasilitas
try {
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM fasilitas WHERE id_destinasi = ?");
    $stmt_cek->execute([$d['id_destinasi']]);
    $ada_fasilitas = (int)$stmt_cek->fetchColumn();
} catch (PDOException $e) {
    $ada_fasilitas = 0;
}

// Tombol hanya muncul jika ada fasilitas
if ($ada_fasilitas > 0): 
?>
<a href="fasilitas.php?id=<?= $d['id_destinasi'] ?>" style="margin-top: 8px; display: block; text-align: center; text-decoration: none; padding: 8px; background: #0d6efd; color: white; border-radius: 8px; font-weight: bold; font-size: 13px;">
    <i class="bi bi-stars"></i> Lihat Fasilitas
</a>
<?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
            <?php else: ?>
                <!-- TAMPILAN KHUSUS SAAT DESTINASI TIDAK DITEMUKAN -->
                <div class="empty-state">
                    <i class="bi bi-emoji-frown" style="font-size: 64px; color: #cbd5e1;"></i>
                    <h3 style="color: #475569; margin-top: 20px; font-size: 20px;">Destinasi Tidak Ditemukan</h3>
                    <p style="color: #94a3b8; margin: 10px 0 25px 0; font-size: 15px;">
                        Maaf, tidak ada destinasi yang cocok dengan kata kunci "<strong><?= htmlspecialchars($search) ?></strong>".
                    </p>
                    <a href="destinasi.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: #198754; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-arrow-left"></i> Kembali ke Semua Destinasi
                    </a>
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

// Fitur Tambahan: Jika user menghapus teks di search box, otomatis reload ke semua destinasi
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        if (this.value.trim() === '' && window.location.href.includes('search=')) {
            window.location.href = 'destinasi.php';
        }
    });
}
</script>

</body>
</html>