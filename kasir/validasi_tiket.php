<?php
require '../config/koneksi.php';

require '../includes/auth_check.php';
cek_login('kasir');

$booking_data = null;
$status_tiket = '';
$pesan_status = '';
$warna_status = '';

// Handle Pencarian Kode Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_booking'])) {
    $kode_booking = trim($_POST['kode_booking'] ?? '');
    
    if (!empty($kode_booking)) {
        try {
            // Cari booking berdasarkan kode_booking
            $stmt = $pdo->prepare("
                SELECT b.*, p.nama as nama_pelanggan, p.email, p.no_hp,
                       d.nama_destinasi, t.nama_tiket, db.jumlah, db.harga_satuan
                FROM booking b
                JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
                JOIN detail_booking db ON b.id_booking = db.id_booking
                JOIN tiket t ON db.id_tiket = t.id_tiket
                JOIN destinasi d ON t.id_destinasi = d.id_destinasi
                WHERE b.kode_booking = ?
            ");
            $stmt->execute([$kode_booking]);
            $booking_data = $stmt->fetch();
            
            if (!$booking_data) {
                $status_tiket = 'tidak_ditemukan';
                $pesan_status = 'Kode booking tidak ditemukan dalam sistem!';
                $warna_status = '#dc3545';
            } else {
                // Cek status booking
                if ($booking_data['status'] !== 'confirmed') {
                    $status_tiket = 'belum_konfirmasi';
                    $pesan_status = 'Booking belum dikonfirmasi. Status: ' . ucfirst($booking_data['status']);
                    $warna_status = '#ffc107';
                } else {
                    // Cek apakah e_tiket ada
                    $stmt_etiket = $pdo->prepare("SELECT status FROM e_tiket WHERE id_booking = ?");
                    $stmt_etiket->execute([$booking_data['id_booking']]);
                    $e_tiket = $stmt_etiket->fetch();
                    
                    if (!$e_tiket) {
                        $status_tiket = 'belum_dibuat';
                        $pesan_status = 'E-Tiket belum dibuat untuk booking ini!';
                        $warna_status = '#ffc107';
                    } elseif ($e_tiket['status'] === 'terpakai') {
                        $status_tiket = 'terpakai';
                        $pesan_status = 'E-Tiket sudah pernah digunakan!';
                        $warna_status = '#dc3545';
                    } else {
                        // E-Tiket valid, cek apakah sudah kedaluwarsa
                        $tanggal_booking = strtotime($booking_data['tanggal_booking']);
                        $hari_ini = strtotime(date('Y-m-d'));
                        
                        if ($tanggal_booking < $hari_ini) {
                            $status_tiket = 'kedaluwarsa';
                            $pesan_status = 'Tiket sudah KEDALUWARSA! Melewati tanggal booking (' . date('d M Y', $tanggal_booking) . ')';
                            $warna_status = '#dc3545';
                        } elseif ($tanggal_booking === $hari_ini) {
                            $status_tiket = 'valid_hari_ini';
                            $pesan_status = 'Tiket VALID untuk hari ini!';
                            $warna_status = '#198754';
                        } else {
                            $status_tiket = 'valid';
                            $pesan_status = 'Tiket VALID! Berlaku sampai ' . date('d M Y', $tanggal_booking);
                            $warna_status = '#198754';
                        }
                    }
                }
            }
            
        } catch (PDOException $e) {
            error_log("Validasi Error: " . $e->getMessage());
            $status_tiket = 'error';
            $pesan_status = 'Terjadi kesalahan sistem!';
            $warna_status = '#dc3545';
        }
    }
}

// Statistik
try {
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $booking_hari_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM e_tiket WHERE status = 'valid'");
    $stmt->execute();
    $tiket_valid = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM e_tiket WHERE status = 'terpakai'");
    $stmt->execute();
    $tiket_terpakai = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $booking_hari_ini = 0;
    $tiket_valid = 0;
    $tiket_terpakai = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Tiket - Kasir Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f0f2f5; }
        
        .topbar {
            background: white; padding: 12px 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: flex-end; align-items: center;
            position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: 56px;
        }
        .user-info { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; }
        
        .layout { display: flex; min-height: 100vh; margin-top: 56px; }
        
        .sidebar {
            width: 240px; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white; padding: 20px 0; position: fixed; top: 56px; bottom: 0; left: 0;
            z-index: 1000; overflow-y: auto; transition: transform 0.3s ease;
        }
        .sidebar .logo { text-align: center; padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; }
        .sidebar .logo h3 { color: #198754; font-weight: bold; font-size: 22px; margin: 0; }
        .sidebar .logo p { color: #aaa; font-size: 11px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: #ccc; text-decoration: none; font-size: 14px; }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: white; padding-left: 25px; }
        .sidebar a.active { background: #198754; color: white; }
        .sidebar a i { font-size: 18px; width: 22px; text-align: center; }
        
        .main-content { margin-left: 240px; flex: 1; padding: 25px; width: calc(100% - 240px); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #0d6efd;
        }
        .stat-card .stat-label { font-size: 14px; color: #666; margin-bottom: 10px; }
        .stat-card .stat-value { font-size: 32px; font-weight: bold; color: #333; }
        
        .validation-box {
            background: white; padding: 30px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .validation-box h3 { color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .search-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-form input {
            flex: 1; padding: 14px; border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 16px; outline: none;
        }
        .search-form input:focus { border-color: #198754; }
        .search-form button {
            padding: 14px 30px; background: #198754; color: white; border: none;
            border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;
        }
        .search-form button:hover { background: #146c43; }
        
        /* STATUS BOX BESAR */
        .status-box {
            text-align: center;
            padding: 30px;
            border-radius: 12px;
            margin: 20px 0;
            border: 3px solid;
        }
        .status-box .status-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        .status-box .status-text {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status-box .status-desc {
            font-size: 16px;
            color: #666;
        }
        
        .booking-detail {
            background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;
        }
        .booking-detail h4 { color: #333; margin-bottom: 15px; }
        .detail-row {
            display: flex; justify-content: space-between; padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; font-size: 14px; }
        .detail-value { color: #333; font-weight: 600; font-size: 14px; }
        
        .hamburger-btn { display: none; position: fixed; top: 8px; left: 10px; z-index: 1001; background: #198754; color: white; border: none; width: 44px; height: 44px; border-radius: 8px; font-size: 22px; cursor: pointer; align-items: center; justify-content: center; }
        .overlay-menu { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 998; }
        .overlay-menu.active { display: block; }
        
        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            .topbar { padding-left: 60px !important; }
            .stats-grid { grid-template-columns: 1fr !important; }
            .search-form { flex-direction: column; }
        }
    </style>
</head>
<body>

<button class="hamburger-btn" onclick="toggleMenu()">☰</button>
<div class="overlay-menu" onclick="closeMenu()"></div>

<div class="topbar">
    <div class="user-info">
        <i class="bi bi-person-circle"></i>
        <?= htmlspecialchars($_SESSION['nama'] ?? 'Kasir') ?>
    </div>
</div>

<div class="layout">
    <div class="sidebar">
        <div class="logo">
            <h3>WALINI</h3>
            <p>Kasir Panel</p>
        </div>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="validasi_tiket.php" class="active"><i class="bi bi-ticket-perforated"></i> Validasi Tiket</a>
        <a href="cek_pembayaran.php"><i class="bi bi-credit-card"></i> Cek Pembayaran</a>
        <a href="transaksi.php"><i class="bi bi-receipt"></i> Transaksi</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <h2 style="color: #333; margin-bottom: 25px;"><i class="bi bi-ticket-perforated"></i> Cek Validitas Tiket</h2>

        <!-- STATISTIK -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Booking Hari Ini</div>
                <div class="stat-value"><?= $booking_hari_ini ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #198754;">
                <div class="stat-label">Tiket Valid</div>
                <div class="stat-value"><?= $tiket_valid ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #dc3545;">
                <div class="stat-label">Tiket Terpakai</div>
                <div class="stat-value"><?= $tiket_terpakai ?></div>
            </div>
        </div>

        <!-- FORM PENCARIAN -->
        <div class="validation-box">
            <h3><i class="bi bi-search"></i> Cari Kode Booking</h3>
            <p style="color: #666; margin-bottom: 20px;">Masukkan kode booking untuk mengecek apakah tiket masih valid atau sudah kedaluwarsa.</p>
            
            <form method="POST" class="search-form">
                <input type="text" name="kode_booking" placeholder="Masukkan kode booking (contoh: WS2611072429)" value="<?= htmlspecialchars($_POST['kode_booking'] ?? '') ?>" required>
                <button type="submit">
                    <i class="bi bi-search"></i> Cek Tiket
                </button>
            </form>

            <?php if ($status_tiket): ?>
            <!-- STATUS BOX -->
            <div class="status-box" style="border-color: <?= $warna_status ?>; background: <?= $warna_status ?>15;">
                <div class="status-icon">
                    <?php if ($status_tiket === 'valid' || $status_tiket === 'valid_hari_ini'): ?>
                        <i class="bi bi-check-circle-fill" style="color: #198754;"></i>
                    <?php elseif ($status_tiket === 'kedaluwarsa' || $status_tiket === 'terpakai' || $status_tiket === 'tidak_ditemukan'): ?>
                        <i class="bi bi-x-circle-fill" style="color: #dc3545;"></i>
                    <?php else: ?>
                        <i class="bi bi-exclamation-triangle-fill" style="color: #ffc107;"></i>
                    <?php endif; ?>
                </div>
                <div class="status-text" style="color: <?= $warna_status ?>;">
                    <?php
                    if ($status_tiket === 'valid') echo 'TIKET VALID';
                    elseif ($status_tiket === 'valid_hari_ini') echo 'TIKET VALID (HARI INI)';
                    elseif ($status_tiket === 'kedaluwarsa') echo 'TIKET KEDALUWARSA';
                    elseif ($status_tiket === 'terpakai') echo 'TIKET SUDAH TERPAKAI';
                    elseif ($status_tiket === 'belum_konfirmasi') echo 'BELUM DIKONFIRMASI';
                    elseif ($status_tiket === 'belum_dibuat') echo 'E-TIKET BELUM DIBUAT';
                    elseif ($status_tiket === 'tidak_ditemukan') echo 'KODE TIDAK DITEMUKAN';
                    else echo 'ERROR';
                    ?>
                </div>
                <div class="status-desc"><?= $pesan_status ?></div>
            </div>
            <?php endif; ?>

            <!-- DETAIL BOOKING -->
            <?php if ($booking_data): ?>
            <div class="booking-detail">
                <h4><i class="bi bi-receipt"></i> Detail Booking</h4>
                
                <div class="detail-row">
                    <span class="detail-label">Kode Booking</span>
                    <span class="detail-value"><?= htmlspecialchars($booking_data['kode_booking']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nama Pelanggan</span>
                    <span class="detail-value"><?= htmlspecialchars($booking_data['nama_pelanggan']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">No. HP</span>
                    <span class="detail-value"><?= htmlspecialchars($booking_data['no_hp'] ?? '-') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Destinasi</span>
                    <span class="detail-value"><?= htmlspecialchars($booking_data['nama_destinasi']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tiket</span>
                    <span class="detail-value"><?= htmlspecialchars($booking_data['nama_tiket']) ?> (<?= $booking_data['jumlah'] ?>x)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Booking</span>
                    <span class="detail-value"><?= date('d M Y', strtotime($booking_data['tanggal_booking'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Pembayaran</span>
                    <span class="detail-value" style="color: #198754; font-size: 16px;">Rp <?= number_format($booking_data['total_harga'], 0, ',', '.') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status Booking</span>
                    <span class="detail-value"><?= ucfirst($booking_data['status']) ?></span>
                </div>
            </div>
            <?php endif; ?>
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