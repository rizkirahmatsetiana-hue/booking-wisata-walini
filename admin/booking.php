<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('admin');

$success = '';
$error = '';

// Handle verifikasi/tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_booking = (int)$_POST['id_booking'];
    $action = $_POST['action'];
    $catatan = trim($_POST['catatan'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // Ambil data booking
        $stmt = $pdo->prepare("SELECT * FROM booking WHERE id_booking = ?");
        $stmt->execute([$id_booking]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception("Booking tidak ditemukan");
        }
        
        if ($action === 'confirm') {
            // ✅ PENTING: Cek agar stok tidak berkurang 2x jika diklik berulang
            if ($booking['status'] === 'confirmed') {
                throw new Exception("Booking ini sudah dikonfirmasi sebelumnya.");
            }

            // 1. Update status booking
            $pdo->prepare("UPDATE booking SET status = 'confirmed', catatan_admin = ?, updated_at = NOW() WHERE id_booking = ?")
                ->execute([$catatan, $id_booking]);
            
            // 2. Ambil detail untuk e-tiket dan pengurangan stok
            $stmt = $pdo->prepare("
                SELECT db.id_tiket, db.jumlah, db.subtotal, t.nama_tiket, d.nama_destinasi, b.tanggal_booking
                FROM detail_booking db
                JOIN tiket t ON db.id_tiket = t.id_tiket
                JOIN destinasi d ON t.id_destinasi = d.id_destinasi
                JOIN booking b ON db.id_booking = b.id_booking
                WHERE db.id_booking = ?
            ");
            $stmt->execute([$id_booking]);
            $details = $stmt->fetchAll();
            
            foreach ($details as $detail) {
                // 3. Buat E-Tiket jika belum ada
                $stmt_cek = $pdo->prepare("SELECT id_e_tiket FROM e_tiket WHERE id_booking = ? AND nama_destinasi = ?");
                $stmt_cek->execute([$id_booking, $detail['nama_destinasi']]);
                
                if (!$stmt_cek->fetch()) {
                    $pdo->prepare("INSERT INTO e_tiket (id_booking, kode_booking, nama_destinasi, tanggal, jumlah_tiket, total_harga, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 'valid', NOW())")
                        ->execute([$id_booking, $booking['kode_booking'], $detail['nama_destinasi'], $detail['tanggal_booking'], $detail['jumlah'], $detail['subtotal']]);
                }
                
                // 4. ✅ KURANGI STOK DI SINI (Hanya saat admin mengonfirmasi)
                $pdo->prepare("UPDATE tiket SET stok = stok - ? WHERE id_tiket = ?")
                    ->execute([$detail['jumlah'], $detail['id_tiket']]);
            }
            
            $success = "✅ Booking {$booking['kode_booking']} berhasil dikonfirmasi! Stok telah dikurangi.";
            
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE booking SET status = 'rejected', catatan_admin = ?, updated_at = NOW() WHERE id_booking = ?")
                ->execute([$catatan, $id_booking]);
            $success = "⚠️ Booking {$booking['kode_booking']} ditolak.";
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Filter status
$filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if ($filter !== 'all') {
    $where = "WHERE b.status = ?";
    $params[] = $filter;
}

// Ambil semua booking
$stmt = $pdo->prepare("
    SELECT 
        b.id_booking, b.kode_booking, b.tanggal_booking, b.total_harga, b.status, 
        b.bukti_pembayaran, b.catatan_admin, b.created_at,
        p.nama as nama_pelanggan, p.email, p.no_hp,
        d.nama_destinasi, t.nama_tiket, db.jumlah
    FROM booking b
    JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
    JOIN detail_booking db ON b.id_booking = db.id_booking
    JOIN tiket t ON db.id_tiket = t.id_tiket
    JOIN destinasi d ON t.id_destinasi = d.id_destinasi
    $where
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Statistik
$total = count($bookings);
$pending = count(array_filter($bookings, fn($b) => in_array($b['status'], ['pending', 'menunggu_verifikasi'])));
$confirmed = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
$rejected = count(array_filter($bookings, fn($b) => $b['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Admin Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
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
        .sidebar .logo { text-align: center; padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; }
        .sidebar .logo h3 { color: #198754; font-weight: bold; font-size: 22px; margin: 0; }
        .sidebar .logo p { color: #aaa; font-size: 11px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: #ccc; text-decoration: none; font-size: 14px; }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: white; padding-left: 25px; }
        .sidebar a.active { background: #198754; color: white; }
        .sidebar a i { font-size: 18px; width: 22px; text-align: center; }

        .main-content { margin-left: 240px; padding: 30px; }
        .page-header { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { color: #333; font-size: 24px; margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #ddd; }
        .stat-card.all { border-left-color: #0d6efd; }
        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.confirmed { border-left-color: #198754; }
        .stat-card.rejected { border-left-color: #dc3545; }
        .stat-card .stat-label { font-size: 13px; color: #666; }
        .stat-card .stat-value { font-size: 28px; font-weight: bold; color: #333; }
        
        .filter-bar { background: white; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border: 2px solid #e0e0e0; border-radius: 20px; background: white; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; color: #666; }
        .filter-btn:hover { border-color: #198754; color: #198754; }
        .filter-btn.active { background: #198754; color: white; border-color: #198754; }
        
        .booking-item { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; border-left: 4px solid #ddd; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .booking-item.pending { border-left-color: #ffc107; }
        .booking-item.menunggu_verifikasi { border-left-color: #0dcaf0; }
        .booking-item.confirmed { border-left-color: #198754; }
        .booking-item.rejected { border-left-color: #dc3545; }
        
        .booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .booking-code { font-size: 16px; font-weight: bold; color: #0d6efd; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-menunggu_verifikasi { background: #d1ecf1; color: #0c5460; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .booking-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .info-item label { font-size: 12px; color: #666; display: block; }
        .info-item span { font-size: 14px; font-weight: 600; color: #333; }
        
        .bukti-img { max-width: 200px; max-height: 150px; border-radius: 8px; border: 2px solid #eee; cursor: pointer; margin: 10px 0; }
        
        .booking-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .booking-actions input[type="text"], .booking-actions textarea { width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; box-sizing: border-box; margin-bottom: 8px; }
        .btn-confirm { background: #198754; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .empty-state { text-align: center; padding: 40px; color: #999; background: white; border-radius: 12px; }

        .hamburger-btn { display: none; position: fixed; top: 8px; left: 10px; z-index: 1001; background: #198754; color: white; border: none; width: 44px; height: 44px; border-radius: 8px; font-size: 22px; cursor: pointer; align-items: center; justify-content: center; }
        .overlay-menu { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 998; }
        .overlay-menu.active { display: block; }

        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; transition: transform 0.3s ease !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            .stats-grid { grid-template-columns: 1fr 1fr !important; }
            .booking-info { grid-template-columns: 1fr !important; }
            .booking-actions { flex-direction: column !important; }
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
    <a href="booking.php" class="active"><i class="bi bi-calendar-check"></i> Booking</a>
    <a href="pelanggan.php"><i class="bi bi-people-fill"></i> Pelanggan</a>
    <a href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
    <a href="pengaturan.php"><i class="bi bi-gear-fill"></i> Pengaturan</a>
    <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-calendar-check"></i> Kelola Booking</h2>
        <span style="color:#666;"><?= $total ?> Total Booking</span>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div><?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card all"><div class="stat-label">Semua Booking</div><div class="stat-value"><?= $total ?></div></div>
        <div class="stat-card pending"><div class="stat-label">Pending</div><div class="stat-value"><?= $pending ?></div></div>
        <div class="stat-card confirmed"><div class="stat-label">Confirmed</div><div class="stat-value"><?= $confirmed ?></div></div>
        <div class="stat-card rejected"><div class="stat-label">Rejected</div><div class="stat-value"><?= $rejected ?></div></div>
    </div>

    <div class="filter-bar">
        <a href="?status=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Semua</a>
        <a href="?status=pending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="?status=menunggu_verifikasi" class="filter-btn <?= $filter === 'menunggu_verifikasi' ? 'active' : '' ?>">Menunggu Verifikasi</a>
        <a href="?status=confirmed" class="filter-btn <?= $filter === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
        <a href="?status=rejected" class="filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
    </div>

    <?php if (count($bookings) > 0): ?>
        <?php foreach ($bookings as $b): ?>
        <div class="booking-item <?= $b['status'] ?>">
            <div class="booking-header">
                <div>
                    <span class="booking-code"><?= htmlspecialchars($b['kode_booking']) ?></span>
                    <span style="color:#666; font-size:13px; margin-left:10px;"><i class="bi bi-calendar"></i> <?= date('d M Y H:i', strtotime($b['created_at'])) ?></span>
                </div>
                <span class="status-badge status-<?= $b['status'] ?>"><?= strtoupper(str_replace('_', ' ', $b['status'])) ?></span>
            </div>

            <div class="booking-info">
                <div class="info-item"><label><i class="bi bi-person"></i> Pelanggan</label><span><?= htmlspecialchars($b['nama_pelanggan']) ?></span></div>
                <div class="info-item"><label><i class="bi bi-envelope"></i> Email</label><span><?= htmlspecialchars($b['email']) ?></span></div>
                <div class="info-item"><label><i class="bi bi-geo-alt"></i> Destinasi</label><span><?= htmlspecialchars($b['nama_destinasi']) ?></span></div>
                <div class="info-item"><label><i class="bi bi-ticket"></i> Tiket</label><span><?= htmlspecialchars($b['nama_tiket']) ?> (<?= $b['jumlah'] ?>x)</span></div>
                <div class="info-item"><label><i class="bi bi-calendar-check"></i> Tanggal</label><span><?= date('d M Y', strtotime($b['tanggal_booking'])) ?></span></div>
                <div class="info-item"><label><i class="bi bi-cash-stack"></i> Total</label><span style="color:#198754; font-size:16px;">Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></span></div>
                <?php if ($b['catatan_admin']): ?>
                <div class="info-item" style="grid-column: 1 / -1;"><label><i class="bi bi-chat-left-text"></i> Catatan Admin</label><span><?= htmlspecialchars($b['catatan_admin']) ?></span></div>
                <?php endif; ?>
            </div>

            <?php if ($b['bukti_pembayaran']): ?>
            <div>
                <label style="font-size:12px; color:#666;"><i class="bi bi-image"></i> Bukti Pembayaran:</label><br>
                <!-- Pastikan path folder sesuai dengan tempat upload Anda (uploads/bukti/ atau assets/img/bukti/) -->
                <img src="../assets/img/bukti/<?= htmlspecialchars($b['bukti_pembayaran']) ?>" class="bukti-img" onclick="window.open(this.src)" onerror="this.src='../uploads/bukti/<?= htmlspecialchars($b['bukti_pembayaran']) ?>'">
            </div>
            <?php else: ?>
            <div style="background:#fff3cd; padding:8px; border-radius:6px; font-size:13px; color:#856404;">
                <i class="bi bi-exclamation-triangle"></i> Belum ada bukti pembayaran
            </div>
            <?php endif; ?>

            <?php if (in_array($b['status'], ['pending', 'menunggu_verifikasi'])): ?>
            <div class="booking-actions">
                <form method="POST">
                    <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                    <input type="hidden" name="action" value="confirm">
                    <textarea name="catatan" placeholder="Catatan untuk pelanggan (opsional)" rows="2"></textarea>
                    <button type="submit" class="btn-confirm" onclick="return confirm('Yakin konfirmasi pembayaran ini? Stok tiket akan berkurang.')">
                        <i class="bi bi-check-circle"></i> Konfirmasi & Kurangi Stok
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <textarea name="catatan" placeholder="Alasan penolakan (opsional)" rows="2"></textarea>
                    <button type="submit" class="btn-reject" onclick="return confirm('Yakin tolak booking ini?')">
                        <i class="bi bi-x-circle"></i> Tolak
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox" style="font-size:48px;"></i>
            <h4 style="margin:15px 0;">Tidak Ada Booking</h4>
            <p>Tidak ada booking dengan filter ini.</p>
        </div>
    <?php endif; ?>
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