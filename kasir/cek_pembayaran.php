<?php
require '../config/koneksi.php';

// ✅ GANTI DENGAN INI
require '../includes/auth_check.php';
cek_login('kasir');

$success = '';
$error = '';

// Handle Konfirmasi/Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_booking = (int)($_POST['id_booking'] ?? 0);
    $action = $_POST['action'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    if ($id_booking > 0 && in_array($action, ['confirm', 'reject'])) {
        try {
            $pdo->beginTransaction();
            
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
                $stmt = $pdo->prepare("UPDATE booking SET status = 'confirmed', catatan_admin = ?, updated_at = NOW() WHERE id_booking = ?");
                $stmt->execute([$catatan, $id_booking]);
                
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
                    
                    // 4. ✅ KURANGI STOK DI SINI (Hanya saat kasir mengonfirmasi)
                    $pdo->prepare("UPDATE tiket SET stok = stok - ? WHERE id_tiket = ?")
                        ->execute([$detail['jumlah'], $detail['id_tiket']]);
                }
                
                $success = "✅ Booking {$booking['kode_booking']} berhasil dikonfirmasi! Stok telah dikurangi.";
                
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE booking SET status = 'rejected', catatan_admin = ?, updated_at = NOW() WHERE id_booking = ?");
                $stmt->execute([$catatan, $id_booking]);
                $success = "⚠️ Booking {$booking['kode_booking']} ditolak.";
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Ambil semua booking dengan status menunggu_verifikasi
$stmt = $pdo->query("
    SELECT b.*, p.nama as nama_pelanggan, p.email, p.no_hp,
           db.jumlah, t.nama_tiket, d.nama_destinasi
    FROM booking b
    JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
    JOIN detail_booking db ON b.id_booking = db.id_booking
    JOIN tiket t ON db.id_tiket = t.id_tiket
    JOIN destinasi d ON t.id_destinasi = d.id_destinasi
    WHERE b.status = 'menunggu_verifikasi'
    ORDER BY b.created_at DESC
");
$bookings = $stmt->fetchAll();

$total_pending = count($bookings);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Pembayaran - Kasir Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        
        .page-header { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { color: #333; font-size: 24px; margin: 0; }
        .badge-pending { background: #ffc107; color: #856404; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 14px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .booking-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #ffc107; }
        .booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .booking-code { font-size: 20px; font-weight: bold; color: #0d6efd; }
        .booking-date { color: #666; font-size: 14px; margin-left: 10px; }
        .status-badge { background: #fff3cd; color: #856404; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 13px; }
        
        .booking-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-item label { font-size: 12px; color: #666; display: block; margin-bottom: 5px; }
        .info-item span { font-size: 15px; font-weight: 600; color: #333; }
        
        .bukti-section { text-align: center; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .bukti-section h4 { color: #333; margin-bottom: 15px; font-size: 16px; }
        .bukti-img { max-width: 400px; max-height: 300px; border-radius: 10px; border: 2px solid #e0e0e0; cursor: pointer; transition: transform 0.3s; }
        .bukti-img:hover { transform: scale(1.05); }
        .no-bukti { color: #dc3545; font-style: italic; }
        
        .action-form { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .action-form input[type="text"], .action-form textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        .action-form input[type="text"]:focus, .action-form textarea:focus { outline: none; border-color: #198754; }
        
        .btn-action { padding: 14px; border: none; border-radius: 8px; font-weight: bold; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; color: white; width: 100%; min-height: 48px; }
        .btn-confirm { background: #198754; }
        .btn-confirm:hover { background: #146c43; }
        .btn-reject { background: #dc3545; }
        .btn-reject:hover { background: #a71d2a; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal img { max-width: 90%; max-height: 90%; border-radius: 10px; }
        .modal-close { position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; cursor: pointer; }
        
        .hamburger-btn { display: none; position: fixed; top: 8px; left: 10px; z-index: 1001; background: #198754; color: white; border: none; width: 44px; height: 44px; border-radius: 8px; font-size: 22px; cursor: pointer; align-items: center; justify-content: center; }
        .overlay-menu { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 998; }
        .overlay-menu.active { display: block; }

        @media (max-width: 768px) {
            .hamburger-btn { display: flex !important; }
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.active { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
            .topbar { padding-left: 60px !important; }
            .booking-info { grid-template-columns: 1fr !important; }
            .action-form { flex-direction: column !important; }
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
                <h2><i class="bi bi-credit-card"></i> Cek Pembayaran</h2>
                <span class="badge-pending"><?= $total_pending ?> Menunggu Verifikasi</span>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
            <?php endif; ?>

            <?php if ($total_pending > 0): ?>
                <?php foreach ($bookings as $b): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <div>
                            <span class="booking-code"><?= htmlspecialchars($b['kode_booking']) ?></span>
                            <span class="booking-date"><i class="bi bi-calendar"></i> <?= date('d M Y H:i', strtotime($b['created_at'])) ?></span>
                        </div>
                        <span class="status-badge">⏳ MENUNGGU VERIFIKASI</span>
                    </div>

                    <div class="booking-info">
                        <div class="info-item">
                            <label><i class="bi bi-person"></i> Nama Pelanggan</label>
                            <span><?= htmlspecialchars($b['nama_pelanggan']) ?></span>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-envelope"></i> Email</label>
                            <span><?= htmlspecialchars($b['email']) ?></span>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-telephone"></i> No. HP</label>
                            <span><?= htmlspecialchars($b['no_hp'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-cash-stack"></i> Total Pembayaran</label>
                            <span style="color: #198754; font-size: 18px;">Rp. <?= number_format($b['total_harga'], 0, ',', '.') ?></span>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-geo-alt"></i> Destinasi</label>
                            <span><?= htmlspecialchars($b['nama_destinasi']) ?></span>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-ticket"></i> Tiket</label>
                            <span><?= htmlspecialchars($b['nama_tiket']) ?> (<?= $b['jumlah'] ?>x)</span>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-calendar-check"></i> Tanggal Booking</label>
                            <span><?= date('d M Y', strtotime($b['tanggal_booking'])) ?></span>
                        </div>
                    </div>

                    <div class="bukti-section">
                        <h4><i class="bi bi-image"></i> Bukti Pembayaran (klik untuk memperbesar):</h4>
                        <?php if (!empty($b['bukti_pembayaran'])): ?>
                            <img src="../assets/img/bukti/<?= htmlspecialchars($b['bukti_pembayaran']) ?>" 
                                 class="bukti-img" 
                                 alt="Bukti Pembayaran"
                                 onclick="openModal(this.src)"
                                 onerror="this.src='../uploads/bukti/<?= htmlspecialchars($b['bukti_pembayaran']) ?>'">
                        <?php else: ?>
                            <p class="no-bukti"><i class="bi bi-exclamation-circle"></i> Belum ada bukti pembayaran</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="action-form">
                        <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                        <textarea name="catatan" placeholder="Catatan untuk pelanggan (opsional)" rows="2"></textarea>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="action" value="confirm" class="btn-action btn-confirm" onclick="return confirm('Yakin konfirmasi booking ini? Stok tiket akan berkurang.')">
                                <i class="bi bi-check-circle"></i> Konfirmasi
                            </button>
                            <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Yakin tolak booking ini?')">
                                <i class="bi bi-x-circle"></i> Tolak
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox" style="font-size: 64px; color: #ddd;"></i>
                    <h3 style="color: #666; margin: 15px 0;">Tidak Ada Pembayaran Menunggu Verifikasi</h3>
                    <p style="color: #999;">Semua pembayaran sudah diproses.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal" id="imageModal" onclick="closeModal()">
        <span class="modal-close">&times;</span>
        <img id="modalImg" src="" alt="Bukti Pembayaran">
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