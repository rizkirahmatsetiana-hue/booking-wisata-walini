<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

// Ambil ID destinasi dari URL
$id_destinasi = (int)($_GET['id'] ?? 0);

if ($id_destinasi <= 0) {
    header("Location: destinasi.php");
    exit;
}

try {
    // Ambil data destinasi
    $stmt = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
    $stmt->execute([$id_destinasi]);
    $destinasi = $stmt->fetch();
    
    if (!$destinasi) {
        header("Location: destinasi.php");
        exit;
    }
    
    // Ambil semua fasilitas destinasi ini
    $stmt = $pdo->prepare("SELECT * FROM fasilitas WHERE id_destinasi = ? ORDER BY id_fasilitas ASC");
    $stmt->execute([$id_destinasi]);
    $fasilitas_list = $stmt->fetchAll();
    
    $total_fasilitas = count($fasilitas_list);
    
} catch (PDOException $e) {
    error_log("Fasilitas Error: " . $e->getMessage());
    $fasilitas_list = [];
    $total_fasilitas = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fasilitas - <?= htmlspecialchars($destinasi['nama_destinasi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
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
        .back-btn { color: #333; text-decoration: none; font-size: 16px; display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .back-btn:hover { color: #198754; }
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
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .page-header h2 { color: #333; font-size: 24px; display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .page-header p { color: #666; font-size: 14px; line-height: 1.6; }
        .badge-count { background: #e7f3ff; color: #0d6efd; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 14px; display: inline-block; margin-top: 10px; }
        
        .fasilitas-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .fasilitas-card { 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .fasilitas-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .fasilitas-card img { 
            width: 100%; 
            height: 200px; 
            object-fit: cover;
            cursor: pointer;
        }
        .fasilitas-card .card-body { padding: 20px; }
        .fasilitas-card h3 { font-size: 18px; color: #333; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .fasilitas-card h3 i { color: #198754; }
        .fasilitas-card p { font-size: 14px; color: #666; line-height: 1.6; }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Modal untuk gambar besar */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal img { max-width: 90%; max-height: 90%; border-radius: 10px; }
        .modal-close {
            position: absolute;
            top: 20px; right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            .topbar { padding-left: 60px !important; }
            .fasilitas-grid { grid-template-columns: 1fr !important; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

<button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="topbar">
    <a href="destinasi.php" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali ke Destinasi</a>
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
            <h2><i class="bi bi-stars" style="color: #ffc107;"></i> Fasilitas: <?= htmlspecialchars($destinasi['nama_destinasi']) ?></h2>
            <p><?= htmlspecialchars($destinasi['deskripsi'] ?? '') ?></p>
            <span class="badge-count"><i class="bi bi-check-circle-fill"></i> <?= $total_fasilitas ?> Fasilitas Tersedia</span>
        </div>

        <div class="fasilitas-grid">
            <?php if (!empty($fasilitas_list)): ?>
                <?php foreach ($fasilitas_list as $f): ?>
                <div class="fasilitas-card">
                    <img 
                        src="../assets/img/<?= htmlspecialchars($f['gambar'] ?? 'default.jpg') ?>" 
                        alt="<?= htmlspecialchars($f['nama_fasilitas']) ?>"
                        onclick="openModal(this.src)"
                        onerror="this.src='https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=400&h=300&fit=crop'"
                    >
                    <div class="card-body">
                        <h3><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($f['nama_fasilitas']) ?></h3>
                        <p><?= htmlspecialchars($f['deskripsi'] ?? 'Tidak ada deskripsi') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-info-circle" style="font-size: 64px; color: #cbd5e1;"></i>
                    <h3 style="color: #475569; margin-top: 20px; font-size: 20px;">Belum Ada Fasilitas</h3>
                    <p style="color: #94a3b8; margin: 10px 0 25px 0; font-size: 15px;">
                        Informasi fasilitas untuk destinasi ini belum tersedia.
                    </p>
                    <a href="destinasi.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: #198754; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-arrow-left"></i> Kembali ke Destinasi
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal untuk gambar besar -->
<div class="modal" id="imageModal" onclick="closeModal()">
    <span class="modal-close">&times;</span>
    <img id="modalImg" src="" alt="Fasilitas">
</div>

<script>
function openModal(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('imageModal').classList.add('active');
}
function closeModal() {
    document.getElementById('imageModal').classList.remove('active');
}
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