<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

$id_destinasi = (int)($_GET['id'] ?? 0);

if ($id_destinasi <= 0) {
    header("Location: destinasi.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM destinasi WHERE id_destinasi = ?");
    $stmt->execute([$id_destinasi]);
    $destinasi = $stmt->fetch();
    
    if (!$destinasi) {
        header("Location: destinasi.php");
        exit;
    }
    
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
        
        .main-content { padding: 80px 25px 25px; max-width: 1200px; margin: 0 auto; }
        
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
            transition: transform 0.3s;
        }
        .fasilitas-card:hover { transform: translateY(-5px); }
        .fasilitas-card img { width: 100%; height: 200px; object-fit: cover; cursor: pointer; }
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
        }
        
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
        .modal-close { position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; cursor: pointer; }
        
        @media (max-width: 768px) {
            .fasilitas-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <a href="destinasi.php" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali ke Destinasi</a>
    <div class="user-top">
        <i class="bi bi-person-circle"></i>
        <span><?= htmlspecialchars($user['nama'] ?? 'User') ?></span>
    </div>
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
                    onerror="this.src='https://via.placeholder.com/400x300?text=Gambar+Fasilitas'"
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
                <h3 style="color: #475569; margin-top: 20px;">Belum Ada Fasilitas</h3>
                <p style="color: #94a3b8; margin: 10px 0 25px 0;">Informasi fasilitas belum tersedia.</p>
                <a href="destinasi.php" style="display: inline-block; padding: 10px 24px; background: #198754; color: white; text-decoration: none; border-radius: 8px;">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

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
</script>

</body>
</html>