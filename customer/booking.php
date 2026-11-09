<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();
$id_destinasi = (int)($_GET['id_destinasi'] ?? 0);

if ($id_destinasi <= 0) {
    header("Location: destinasi.php");
    exit;
}

// Ambil data destinasi
$stmt = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
$stmt->execute([$id_destinasi]);
$destinasi = $stmt->fetch();

if (!$destinasi) {
    header("Location: destinasi.php");
    exit;
}

// Ambil data tiket & stok
$stmt = $pdo->prepare("SELECT id_tiket, harga, stok FROM tiket WHERE id_destinasi = ?");
$stmt->execute([$id_destinasi]);
$tiket = $stmt->fetch();

if (!$tiket) {
    $error_msg = "Tiket tidak tersedia untuk destinasi ini.";
    $stok_tersedia = 0;
} else {
    $stok_tersedia = (int)$tiket['stok'];
    $id_tiket = $tiket['id_tiket'];
    $harga_tiket = $tiket['harga'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - <?= htmlspecialchars($destinasi['nama_destinasi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
}

* {
    max-width: 100% !important;
}
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    html, body { width: 100%; overflow-x: hidden; }
    body { background: #f0f2f5; }
    
    .topbar { 
        background: white; 
        padding: 12px 20px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
        display: flex; 
        align-items: center; 
        gap: 15px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        height: 56px;
    }
    .back-btn { color: #333; text-decoration: none; font-size: 20px; }
    .back-btn:hover { color: #198754; }
    .topbar h1 { color: #333; font-size: 20px; }
    
    .layout { 
        display: flex; 
        min-height: 100vh;
        margin-top: 56px;
    }
    
    .sidebar { 
        width: 220px; 
        background: #1a1a2e; 
        color: white; 
        padding: 20px 0; 
        position: fixed; 
        top: 56px; 
        bottom: 0; 
        left: 0;
        z-index: 99;
    }
    .sidebar a { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        padding: 14px 25px; 
        color: #aaa; 
        text-decoration: none; 
        font-size: 14px; 
    }
    .sidebar a:hover { background: rgba(255,255,255,0.05); color: white; }
    .sidebar a.active { background: #198754; color: white; }
    .sidebar a i { font-size: 18px; width: 20px; text-align: center; }
    
    .main-content { 
        margin-left: 220px; 
        flex: 1; 
        padding: 30px;
        width: calc(100% - 220px);
        min-height: calc(100vh - 56px);
    }
    
    .booking-container { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 30px; 
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
    }
    
    .destinasi-info { 
        background: white; 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    }
    .destinasi-info img { 
        width: 100%; 
        height: 350px; 
        object-fit: cover; 
    }
    .destinasi-info .info-body { padding: 25px; }
    .destinasi-info h2 { color: #333; margin-bottom: 10px; font-size: 24px; }
    .destinasi-info .lokasi { color: #666; font-size: 14px; margin-bottom: 15px; }
    .destinasi-info .lokasi i { color: #198754; }
    .destinasi-info .harga { color: #198754; font-size: 22px; font-weight: bold; }
    .destinasi-info .harga small { font-size: 14px; color: #666; font-weight: normal; }
    
    .booking-form { 
        background: white; 
        border-radius: 12px; 
        padding: 30px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    }
    .form-group { margin-bottom: 25px; }
    .form-group label { 
        display: block; 
        font-weight: 600; 
        color: #333; 
        margin-bottom: 10px; 
        font-size: 15px; 
    }
    .form-group input[type="date"] { 
        width: 100%; 
        padding: 14px; 
        border: 2px solid #e0e0e0; 
        border-radius: 8px; 
        font-size: 15px; 
    }
    .form-group input[type="date"]:focus { outline: none; border-color: #198754; }
    
    .jumlah-control { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
    }
    .jumlah-control button { 
        width: 45px; 
        height: 45px; 
        border: 2px solid #e0e0e0; 
        background: white; 
        border-radius: 8px; 
        font-size: 20px; 
        cursor: pointer;
        transition: all 0.2s;
    }
    .jumlah-control button:hover { 
        background: #198754; 
        color: white; 
        border-color: #198754; 
    }
    .jumlah-control input { 
        width: 70px; 
        text-align: center; 
        padding: 12px; 
        border: 2px solid #e0e0e0; 
        border-radius: 8px; 
        font-size: 18px; 
        font-weight: bold; 
    }
    .jumlah-control input:focus { outline: none; border-color: #198754; }
    
    .stok-info { 
        font-size: 13px; 
        color: #666; 
        margin-top: 10px; 
    }
    .stok-info strong { color: #198754; }
    .stok-info.habis { color: #dc3545; font-weight: bold; }
    
    .total-box { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 20px 0; 
        border-top: 2px solid #f0f0f0; 
        margin-top: 25px; 
    }
    .total-label { font-size: 18px; font-weight: bold; color: #333; }
    .total-value { font-size: 24px; font-weight: bold; color: #198754; }
    
    .btn-bayar { 
        width: 100%; 
        padding: 18px; 
        background: #198754; 
        color: white; 
        border: none; 
        border-radius: 8px; 
        font-size: 17px; 
        font-weight: bold; 
        cursor: pointer; 
        margin-top: 20px;
        transition: background 0.3s;
    }
    .btn-bayar:hover { background: #146c43; }
    .btn-bayar:disabled { background: #dc3545; cursor: not-allowed; }
    
    .alert-habis { 
        background: #f8d7da; 
        color: #721c24; 
        padding: 20px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        border: 1px solid #f5c6cb; 
        text-align: center; 
        font-weight: bold; 
        font-size: 16px;
    }
    .alert-habis small { font-weight: normal; font-size: 13px; display: block; margin-top: 8px; }
    
    @media (max-width: 768px) {
        .sidebar { display: none; }
        .main-content { margin-left: 0; width: 100%; padding: 15px; }
        .booking-container { grid-template-columns: 1fr; }
        .destinasi-info img { height: 200px; }
    }
    /* === RESPONSIVE - TAMBAHAN SAJA, TIDAK MERUBAH YANG LAMA === */

/* Tombol Hamburger - HANYA muncul di mobile */
.hamburger-btn {
    display: none;
    position: fixed;
    top: 10px;
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
}

/* Overlay - HANYA muncul saat sidebar aktif di mobile */
.overlay-menu {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 998;
}

.overlay-menu.active {
    display: block;
}

/* === MOBILE (< 768px) === */
@media (max-width: 768px) {
    /* Tampilkan hamburger */
    .hamburger-btn {
        display: block;
    }
    
    /* Sidebar jadi tersembunyi di kiri */
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 999;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    /* Main content full width */
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px !important;
    }
    
    /* Grid destinasi jadi 1 kolom */
    .destinasi-grid, .dest-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Stats cards jadi 1 kolom */
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Booking form jadi 1 kolom */
    .booking-container {
        grid-template-columns: 1fr !important;
    }
    
    /* Tabel bisa scroll horizontal */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    /* Tombol full width */
    .btn, button {
        width: 100%;
        min-height: 44px;
    }
    
    /* Input full width */
    input, select, textarea {
        width: 100% !important;
        font-size: 16px;
    }
    
    /* Gambar responsive */
    img {
        max-width: 100%;
        height: auto;
    }
}

/* === TABLET (768px - 1023px) === */
@media (min-width: 769px) and (max-width: 1023px) {
    .destinasi-grid, .dest-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
</style>
</head>
<body>

<div class="topbar">
    <a href="destinasi.php" class="back-btn">←</a>
    <h1>Pembayaran</h1>
</div>

<div class="layout">
    <div class="sidebar">
        <a href="home.php"><i class="bi bi-house-fill"></i> Beranda</a>
        <a href="destinasi.php" class="active"><i class="bi bi-geo-alt-fill"></i> Destinasi</a>
        <a href="riwayat.php"><i class="bi bi-receipt"></i> Pesanan</a>
        <a href="profile.php"><i class="bi bi-person-fill"></i> Profil</a>
        <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <?php if ($stok_tersedia <= 0): ?>
        <!-- TAMPILKAN PESAN HABIS -->
        <div class="booking-container">
            <div class="destinasi-info">
                <img src="../assets/img/<?= htmlspecialchars($destinasi['gambar'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($destinasi['nama_destinasi']) ?>">
                <div class="info-body">
                    <h2><?= htmlspecialchars($destinasi['nama_destinasi']) ?></h2>
                    <p class="lokasi"><i class="bi bi-geo-alt"></i> Rancabali, Jawa Barat</p>
                    <div class="harga">Rp. <?= number_format($destinasi['harga_tiket'], 0, ',', '.') ?> <small style="font-size: 14px; color: #666;">/orang</small></div>
                </div>
            </div>
            <div class="booking-form">
                <div class="alert-habis">
                    ⚠️ MAAAF, TIKET HABIS!<br>
                    <small style="font-weight: normal;">Stok tiket untuk destinasi ini sudah habis. Silakan pilih destinasi lain.</small>
                </div>
                <a href="destinasi.php" style="display: block; text-align: center; padding: 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    ← Kembali ke Daftar Destinasi
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- TAMPILKAN FORM BOOKING NORMAL -->
        <div class="booking-container">
            <div class="destinasi-info">
                <img src="../assets/img/<?= htmlspecialchars($destinasi['gambar'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($destinasi['nama_destinasi']) ?>">
                <div class="info-body">
                    <h2><?= htmlspecialchars($destinasi['nama_destinasi']) ?></h2>
                    <p class="lokasi"><i class="bi bi-geo-alt"></i> Rancabali, Jawa Barat</p>
                    <div class="harga">Rp. <?= number_format($destinasi['harga_tiket'], 0, ',', '.') ?> <small style="font-size: 14px; color: #666;">/orang</small></div>
                </div>
            </div>
            
            <div class="booking-form">
                <form action="../api/proses_booking.php" method="POST" id="bookingForm">
                    <input type="hidden" name="id_tiket" value="<?= $id_tiket ?>">
                    <input type="hidden" name="id_destinasi" value="<?= $id_destinasi ?>">
                    
                    <div class="form-group">
                        <label>Pilih Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Tiket</label>
                        <div class="jumlah-control">
                            <button type="button" onclick="kurangiTiket()">−</button>
                            <input type="number" name="jumlah" id="jumlah" value="1" min="1" max="<?= $stok_tersedia ?>" required>
                            <button type="button" onclick="tambahTiket()">+</button>
                        </div>
                        <div class="stok-info">Stok tersedia: <strong id="stokDisplay"><?= $stok_tersedia ?></strong> tiket</div>
                    </div>
                    
                    <div class="total-box">
                        <span class="total-label">Total</span>
                        <span class="total-value" id="totalHarga">Rp. <?= number_format($harga_tiket, 0, ',', '.') ?></span>
                    </div>
                    
                    <button type="submit" class="btn-bayar" id="btnBayar">Bayar Sekarang</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const hargaTiket = <?= $harga_tiket ?? 0 ?>;
const stokMaks = <?= $stok_tersedia ?>;

function updateTotal() {
    const jumlah = parseInt(document.getElementById('jumlah').value) || 1;
    const total = jumlah * hargaTiket;
    document.getElementById('totalHarga').textContent = 'Rp. ' + total.toLocaleString('id-ID');
}

function tambahTiket() {
    const input = document.getElementById('jumlah');
    if (parseInt(input.value) < stokMaks) {
        input.value = parseInt(input.value) + 1;
        updateTotal();
    }
}

function kurangiTiket() {
    const input = document.getElementById('jumlah');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateTotal();
    }
}

// Validasi tanggal tidak boleh di masa lalu
document.getElementById('tanggal').addEventListener('change', function() {
    const today = new Date().toISOString().split('T')[0];
    if (this.value < today) {
        alert('Tanggal tidak boleh di masa lalu!');
        this.value = today;
    }
});
</script>
<script>
// Toggle sidebar di mobile
function toggleMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay-menu');
    if (sidebar) sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
}

// Tutup sidebar saat klik overlay
function closeMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay-menu');
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

// Tutup sidebar saat klik link di dalamnya
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.remove('active');
                const overlay = document.querySelector('.overlay-menu');
                if (overlay) overlay.classList.remove('active');
            });
        });
    }
    
    // Tutup sidebar saat resize ke desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});
</script>
</body>
</html>