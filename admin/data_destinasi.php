<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('admin');

$success = '';
$error = '';
$edit_id = null;
$edit_data = null;

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT gambar FROM destinasi WHERE id_destinasi = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        if ($old && !empty($old['gambar']) && file_exists('../assets/img/' . $old['gambar'])) {
            unlink('../assets/img/' . $old['gambar']);
        }
        
        $pdo->prepare("DELETE FROM tiket WHERE id_destinasi = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM destinasi WHERE id_destinasi = ?")->execute([$id]);
        
        $success = "Destinasi dan tiket terkait berhasil dihapus!";
    } catch (PDOException $e) {
        $error = "Gagal menghapus: " . $e->getMessage();
    }
}

// Handle EDIT (load data)
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

// Handle ADD/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_destinasi'] ?? null;
    $nama = trim($_POST['nama_destinasi'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga_input = $_POST['harga_tiket'] ?? '';
    $stok_input = (int)($_POST['stok'] ?? 100);
    $gambar_lama = $_POST['gambar_lama'] ?? '';
    
    // Parse harga - HANYA jika ada inputnya
    $harga = null;
    if ($harga_input !== '' && $harga_input !== null) {
        $harga_input = str_replace('.', '', $harga_input);
        $harga_input = str_replace(',', '.', $harga_input);
        $harga = (float)$harga_input;
    }
    
    // Validasi
    $errors = [];
    if (empty($nama)) $errors[] = "Nama destinasi wajib diisi";
    if ($stok_input < 0) $errors[] = "Stok tidak boleh negatif";
    
    // Handle upload gambar
    $gambar = $gambar_lama;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['gambar'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Format gambar harus JPG/PNG";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Ukuran gambar maksimal 5MB";
        } else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file['name']);
            $upload_dir = '../assets/img/';
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                if (!empty($gambar_lama) && file_exists($upload_dir . $gambar_lama)) {
                    unlink($upload_dir . $gambar_lama);
                }
                $gambar = $filename;
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            if ($id) {
                // ========== UPDATE ==========
                
                // 1. Ambil data lama dulu
                $stmt_old = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
                $stmt_old->execute([$id]);
                $data_lama = $stmt_old->fetch();
                
                if (!$data_lama) {
                    throw new Exception("Destinasi tidak ditemukan!");
                }
                
                // 2. Tentukan harga: gunakan input baru JIKA ADA, jika tidak pakai harga lama
                $harga_final = ($harga !== null && $harga > 0) ? $harga : (float)$data_lama['harga_tiket'];
                
                // 3. Update destinasi
                $stmt = $pdo->prepare("
                    UPDATE destinasi 
                    SET nama_destinasi = ?, deskripsi = ?, harga_tiket = ?, gambar = ?, updated_at = NOW() 
                    WHERE id_destinasi = ?
                ");
                $stmt->execute([$nama, $deskripsi, $harga_final, $gambar, $id]);
                
                // 4. Update tiket - HANYA stok yang diubah, harga TIDAK
                // Cek apakah tiket sudah ada
                $stmt_cek_tiket = $pdo->prepare("SELECT id_tiket FROM tiket WHERE id_destinasi = ?");
                $stmt_cek_tiket->execute([$id]);
                $tiket_ada = $stmt_cek_tiket->fetch();
                
                if ($tiket_ada) {
                    // Tiket sudah ada - UPDATE STOK SAJA, harga tetap
                    $stmt_tiket = $pdo->prepare("
                        UPDATE tiket 
                        SET stok = ?, updated_at = NOW()
                        WHERE id_destinasi = ?
                    ");
                    $stmt_tiket->execute([$stok_input, $id]);
                } else {
                    // Tiket belum ada - buat baru
                    $stmt_tiket = $pdo->prepare("
                        INSERT INTO tiket (id_destinasi, nama_tiket, harga, stok, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt_tiket->execute([
                        $id, 
                        'Tiket Masuk ' . $nama, 
                        $harga_final,
                        $stok_input
                    ]);
                }
                
                $pdo->commit();
                $success = "Destinasi berhasil diperbarui! (Stok: $stok_input, Harga tetap Rp " . number_format($harga_final, 0, ',', '.') . ")";
                
            } else {
                // ========== INSERT BARU ==========
                $harga_final = ($harga !== null && $harga > 0) ? $harga : 0;
                
                if ($harga_final <= 0) {
                    throw new Exception("Harga wajib diisi untuk destinasi baru!");
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO destinasi (nama_destinasi, deskripsi, harga_tiket, gambar, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$nama, $deskripsi, $harga_final, $gambar]);
                $id_destinasi_baru = $pdo->lastInsertId();
                
                // Otomatis buat tiket
                $stmt_tiket = $pdo->prepare("
                    INSERT INTO tiket (id_destinasi, nama_tiket, harga, stok, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt_tiket->execute([
                    $id_destinasi_baru, 
                    'Tiket Masuk ' . $nama, 
                    $harga_final,
                    $stok_input
                ]);
                
                $pdo->commit();
                $success = "Destinasi baru berhasil ditambahkan beserta tiketnya!";
            }
            
            header("Location: data_destinasi.php");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error database: " . $e->getMessage();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = implode(", ", $errors);
    }
}

// Ambil semua destinasi
$stmt = $pdo->query("SELECT * FROM destinasi ORDER BY id_destinasi ASC");
$destinasi_list = $stmt->fetchAll();
$total_destinasi = count($destinasi_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <title>Data Destinasi - Admin Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f0f2f5; }
        
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
        
        .page-header { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { color: #333; font-size: 24px; margin: 0; }
        .badge-count { background: #e7f3ff; color: #0d6efd; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 14px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .card-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-box h3 { font-size: 18px; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .card-box h3 i { color: #198754; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 14px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #198754; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group small { color: #666; font-size: 12px; display: block; margin-top: 5px; }
        
        .form-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; }
        
        .gambar-preview { max-width: 150px; max-height: 100px; border-radius: 8px; margin-top: 10px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: #198754; color: white; }
        .btn-primary:hover { background: #146c43; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; color: white; }
        .btn-edit { background: #0d6efd; color: white; padding: 5px 12px; font-size: 12px; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 12px; font-size: 12px; }
        
        .table-modern { width: 100%; border-collapse: collapse; }
        .table-modern th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid #e0e0e0; }
        .table-modern td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .table-modern tr:hover td { background: #f8f9fa; }
        .table-modern img { width: 60px; height: 40px; object-fit: cover; border-radius: 5px; }
        
        .action-btns { display: flex; gap: 5px; }
        
        .info-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        .harga-info { background: #fff3cd; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ffc107; font-size: 13px; }
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
   TABEL JADI CARD DI MOBILE
   ============================================ */
@media (max-width: 768px) {
    
    /* Sembunyikan header tabel */
    .table-modern thead {
        display: none !important;
    }
    
    /* Tabel jadi block */
    .table-modern, 
    .table-modern tbody, 
    .table-modern tr, 
    .table-modern td {
        display: block !important;
        width: 100% !important;
    }
    
    /* Setiap baris jadi card */
    .table-modern tr {
        background: white !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 12px !important;
        margin-bottom: 15px !important;
        padding: 15px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    }
    
    /* Setiap cell jadi baris */
    .table-modern td {
        padding: 8px 5px !important;
        border: none !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f0f0f0 !important;
    }
    
    .table-modern td:last-child {
        border-bottom: none !important;
    }
    
    /* Tambah label sebelum setiap data */
    .table-modern td::before {
        content: attr(data-label);
        font-weight: bold !important;
        color: #666 !important;
        font-size: 12px !important;
        min-width: 100px;
    }
    
    /* Gambar kecil */
    .table-modern td img {
        width: 50px !important;
        height: 40px !important;
        border-radius: 6px !important;
    }
    
    /* Tombol Edit/Hapus full width */
    .table-modern td:last-child {
        flex-direction: row !important;
        gap: 8px !important;
        justify-content: flex-end !important;
    }
    
    .table-modern td:last-child .btn {
        flex: 1 !important;
        font-size: 12px !important;
        padding: 8px !important;
    }
    
    /* Form row jadi 1 kolom */
    .form-row {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
    }
    
    .form-row .form-group {
        width: 100% !important;
    }
    
    /* Input full width */
    input, select, textarea {
        width: 100% !important;
        font-size: 16px !important;
    }
    
    /* Tombol Simpan full width */
    .btn-primary, button[type="submit"] {
        width: 100% !important;
        padding: 14px !important;
        font-size: 16px !important;
    }
    
    /* Card box padding */
    .card-box {
        padding: 15px !important;
        margin-bottom: 15px !important;
    }
    
    /* Page header */
    .page-header {
        flex-direction: column !important;
        align-items: flex-start !important;
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
    <a href="data_destinasi.php" class="active"><i class="bi bi-geo-alt-fill"></i> Data Destinasi</a>
    <a href="booking.php"><i class="bi bi-calendar-check"></i> Booking</a>
    <a href="pelanggan.php"><i class="bi bi-people-fill"></i> Pelanggan</a>
    <a href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
    <a href="pengaturan.php"><i class="bi bi-gear-fill"></i> Pengaturan</a>
    <a href="../api/proses_logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<div class="main-content">

    <div class="page-header">
        <h2><i class="bi bi-geo-alt-fill"></i> Data Destinasi</h2>
        <span class="badge-count"><?= $total_destinasi ?> Destinasi</span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- FORM TAMBAH/EDIT -->
    <div class="card-box">
        <h3><i class="bi bi-pencil-square"></i> <?= $edit_data ? 'Edit' : 'Tambah' ?> Destinasi</h3>
        
        <?php if ($edit_data): ?>
        <div class="harga-info">
            <strong>💡 Info:</strong> Harga saat ini: <strong>Rp <?= number_format($edit_data['harga_tiket'], 0, ',', '.') ?></strong>. 
            Kosongkan field harga jika tidak ingin mengubahnya. Stok bisa diubah tanpa mempengaruhi harga.
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_destinasi" value="<?= $edit_data['id_destinasi'] ?? '' ?>">
            <input type="hidden" name="gambar_lama" value="<?= $edit_data['gambar'] ?? '' ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Destinasi</label>
                    <input type="text" name="nama_destinasi" value="<?= htmlspecialchars($edit_data['nama_destinasi'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Harga Tiket (Rp) <?= $edit_data ? '(Kosongkan jika tidak diubah)' : '' ?></label>
                    <input type="number" name="harga_tiket" value="<?= $edit_data['harga_tiket'] ?? '' ?>" min="0" step="1000" <?= !$edit_data ? 'required' : '' ?>>
                    <small>Contoh: 350000</small>
                </div>
                <div class="form-group">
                    <label>Stok Tiket</label>
                    <input type="number" name="stok" value="<?= $edit_data['stok'] ?? 100 ?>" min="0" required>
                    <small>Jumlah tiket tersedia</small>
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi"><?= htmlspecialchars($edit_data['deskripsi'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Gambar</label>
                <?php if ($edit_data && !empty($edit_data['gambar'])): ?>
                    <img src="../assets/img/<?= htmlspecialchars($edit_data['gambar']) ?>" class="gambar-preview" alt="Gambar saat ini">
                    <p style="color:#666; font-size:12px; margin-top:5px;">Gambar saat ini. Upload baru untuk mengganti.</p>
                <?php endif; ?>
                <input type="file" name="gambar" accept="image/jpeg,image/jpg,image/png">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> <?= $edit_data ? 'Update' : 'Simpan' ?>
            </button>
            <?php if ($edit_data): ?>
            <a href="data_destinasi.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- DAFTAR DESTINASI -->
    <div class="card-box">
        <h3><i class="bi bi-list-ul"></i> Daftar Destinasi</h3>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>Nama Destinasi</th>
                    <th>Deskripsi</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status Tiket</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
    <?php 
    $no = 1;
    foreach ($destinasi_list as $d): 
        // Cek apakah destinasi punya tiket
        $stmt_cek = $pdo->prepare("SELECT id_tiket, stok FROM tiket WHERE id_destinasi = ?");
        $stmt_cek->execute([$d['id_destinasi']]);
        $tiket_cek = $stmt_cek->fetch();
        $punya_tiket = $tiket_cek ? true : false;
    ?>
    <tr>
        <td><strong><?= $no++ ?></strong></td>
        <td><img src="../assets/img/<?= htmlspecialchars($d['gambar'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($d['nama_destinasi']) ?>"></td>
        <td><strong><?= htmlspecialchars($d['nama_destinasi']) ?></strong></td>
        <td><?= htmlspecialchars(substr($d['deskripsi'] ?? '', 0, 40)) ?>...</td>
        <td style="color:#198754; font-weight:bold;">Rp <?= number_format($d['harga_tiket'], 0, ',', '.') ?></td>
        <td>
            <?php 
            // Tampilkan stok dengan warna berbeda jika 0
            if ($tiket_cek && $tiket_cek['stok'] == 0) {
                echo '<span style="color: #dc3545; font-weight: bold;">' . $tiket_cek['stok'] . '</span>';
            } else {
                echo $tiket_cek ? $tiket_cek['stok'] : '-';
            }
            ?>
        </td>
        <td>
            <?php 
            if ($punya_tiket) {
                if ($tiket_cek['stok'] > 0) {
                    echo '<span class="info-badge badge-success">✓ Ada Tiket (' . $tiket_cek['stok'] . ')</span>';
                } else {
                    echo '<span class="info-badge badge-danger">✗ Habis</span>';
                }
            } else {
                echo '<span class="info-badge badge-danger"> Tidak Ada Tiket</span>';
            }
            ?>
        </td>
        <td>
            <div class="action-btns">
                <a href="?edit=<?= $d['id_destinasi'] ?>" class="btn btn-edit"><i class="bi bi-pencil"></i> Edit</a>
                <a href="?delete=<?= $d['id_destinasi'] ?>" class="btn btn-delete" onclick="return confirm('Yakin hapus destinasi ini?')"><i class="bi bi-trash"></i> Hapus</a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
        </table>
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