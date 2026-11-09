<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

// Ambil data pelanggan
try {
    $stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
    $pelanggan = $stmt->fetch();
} catch (PDOException $e) {
    $pelanggan = null;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    
    if (empty($nama) || empty($email)) {
        $error = "Nama dan email wajib diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id_user = ?");
            $stmt->execute([$nama, $email, $_SESSION['id_user']]);
            
            if ($pelanggan) {
                $stmt = $pdo->prepare("UPDATE pelanggan SET nama = ?, email = ?, no_hp = ?, alamat = ? WHERE id_user = ?");
                $stmt->execute([$nama, $email, $no_hp, $alamat, $_SESSION['id_user']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pelanggan (id_user, nama, email, no_hp, alamat) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['id_user'], $nama, $email, $no_hp, $alamat]);
            }
            
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            $success = "Profil berhasil diperbarui!";
            
            $stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id_user = ?");
            $stmt->execute([$_SESSION['id_user']]);
            $pelanggan = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Gagal memperbarui profil.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Profil - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* === RESET === */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f0f2f5; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            overflow-x: hidden;
            color: #333;
        }
        
        /* === HAMBURGER === */
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
        }
        .overlay-menu.active { display: block; }
        
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
        .layout { display: flex; min-height: 100vh; margin-top: 56px; }
        
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
            z-index: 1000;
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
        .sidebar .logo p { color: #aaa; font-size: 11px; }
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
        
        /* === PAGE HEADER === */
        .page-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .page-header h2 { 
            color: #333; 
            font-size: 22px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        /* === PROFILE CARD === */
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            margin: 0 auto 25px;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }
        
        .profile-name {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-email {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
        }
        
        /* === FORM === */
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600; 
            color: #333; 
            margin-bottom: 8px; 
            font-size: 14px; 
        }
        .form-group label i { color: #198754; }
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus, 
        .form-group textarea:focus { 
            outline: none; 
            border-color: #198754; 
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        /* === BUTTON === */
        .btn-save {
            width: 100%;
            padding: 14px;
            background: #198754;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
            min-height: 48px;
        }
        .btn-save:hover { background: #146c43; }
        
        /* === ALERT === */
        .alert { 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* === STATS INFO === */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-item .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #198754;
        }
        .stat-item .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* ===================================
           TABLET (768px - 1023px)
           =================================== */
        @media (max-width: 1023px) {
            .profile-stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* ===================================
           MOBILE (< 768px)
           =================================== */
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
            }
            
            .topbar { padding-left: 60px !important; }
            .search-box { margin: 0 10px; max-width: none; }
            
            .page-header { padding: 15px; }
            .page-header h2 { font-size: 18px; }
            
            .profile-card { padding: 20px; }
            .profile-avatar { width: 80px; height: 80px; font-size: 32px; }
            .profile-name { font-size: 18px; }
            
            .profile-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .form-group input, 
            .form-group textarea {
                font-size: 16px; /* Mencegah zoom di iOS */
                padding: 14px;
            }
            
            .btn-save {
                font-size: 15px;
                min-height: 50px;
            }
        }
        
        /* ===================================
           HP KECIL (< 375px)
           =================================== */
        @media (max-width: 374px) {
            .main-content { padding: 10px !important; }
            .profile-card { padding: 15px; }
            .profile-avatar { width: 70px; height: 70px; font-size: 28px; }
        }
        
        /* ===================================
           DESKTOP BESAR (≥ 1400px)
           =================================== */
        @media (min-width: 1400px) {
            .main-content { padding: 30px 40px; }
            .profile-card { max-width: 700px; padding: 40px; }
        }
    </style>
</head>
<body>

<!-- HAMBURGER -->
<button class="hamburger-btn" onclick="toggleMenu()">☰</button>

<!-- OVERLAY -->
<div class="overlay-menu" onclick="closeMenu()"></div>

<!-- TOPBAR -->
<div class="topbar">
    <div class="user-top" style="margin-left: auto;">
        <i class="bi bi-person-circle"></i>
        <span><?= htmlspecialchars($user['nama'] ?? 'User') ?></span>
    </div>
</div>

<!-- LAYOUT -->
<div class="layout">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <h3>WALINI</h3>
            <p>Customer Panel</p>
        </div>
        <a href="home.php"><i class="bi bi-house-fill"></i> Beranda</a>
        <a href="destinasi.php"><i class="bi bi-geo-alt-fill"></i> Destinasi</a>
        <a href="riwayat.php"><i class="bi bi-receipt"></i> Pesanan</a>
        <a href="profile.php" class="active"><i class="bi bi-person-fill"></i> Profil</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-person-fill"></i> Profil Saya</h2>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> <?= $success ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <!-- Stats Info -->
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-value">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE id_user = ?");
                        $stmt->execute([$_SESSION['id_user']]);
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) { echo 0; }
                    ?>
                </div>
                <div class="stat-label">Total Booking</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE id_user = ? AND status = 'confirmed'");
                        $stmt->execute([$_SESSION['id_user']]);
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) { echo 0; }
                    ?>
                </div>
                <div class="stat-label">Booking Selesai</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga), 0) FROM booking WHERE id_user = ? AND status = 'confirmed'");
                        $stmt->execute([$_SESSION['id_user']]);
                        $total = $stmt->fetchColumn();
                        echo 'Rp ' . number_format($total / 1000, 0) . 'K';
                    } catch (Exception $e) { echo 'Rp 0'; }
                    ?>
                </div>
                <div class="stat-label">Total Pengeluaran</div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="profile-name"><?= htmlspecialchars($user['nama'] ?? 'User') ?></div>
            <div class="profile-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="bi bi-person"></i> Nama Lengkap</label>
                    <input type="text" name="nama" 
                           value="<?= htmlspecialchars($pelanggan['nama'] ?? $user['nama'] ?? '') ?>" 
                           required 
                           placeholder="Masukkan nama lengkap">
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-envelope"></i> Email</label>
                    <input type="email" name="email" 
                           value="<?= htmlspecialchars($pelanggan['email'] ?? $user['email'] ?? '') ?>" 
                           required 
                           placeholder="Masukkan email">
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-phone"></i> No. HP</label>
                    <input type="tel" name="no_hp" 
                           value="<?= htmlspecialchars($pelanggan['no_hp'] ?? '') ?>" 
                           placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-geo-alt"></i> Alamat</label>
                    <textarea name="alamat" 
                              rows="3" 
                              placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($pelanggan['alamat'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
            </form>
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