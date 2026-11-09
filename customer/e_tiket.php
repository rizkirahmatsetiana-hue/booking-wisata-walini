<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

$user = walini_get_user();

// Ambil kode booking dari URL
$kode_booking = trim($_GET['kode_booking'] ?? '');

if (empty($kode_booking)) {
    header("Location: riwayat.php");
    exit;
}

try {
    // Ambil data booking lengkap
    $stmt = $pdo->prepare("
        SELECT b.*, p.nama as nama_pelanggan, p.email, p.no_hp,
               d.nama_destinasi, t.nama_tiket, db.jumlah, db.harga_satuan, db.subtotal
        FROM booking b
        JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
        JOIN detail_booking db ON b.id_booking = db.id_booking
        JOIN tiket t ON db.id_tiket = t.id_tiket
        JOIN destinasi d ON t.id_destinasi = d.id_destinasi
        WHERE b.kode_booking = ? AND p.id_user = ?
    ");
    $stmt->execute([$kode_booking, $_SESSION['id_user']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header("Location: riwayat.php");
        exit;
    }
    
    // Ambil data e_tiket
    $stmt = $pdo->prepare("SELECT * FROM e_tiket WHERE id_booking = ?");
    $stmt->execute([$booking['id_booking']]);
    $e_tiket = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("E-Tiket Error: " . $e->getMessage());
    header("Location: riwayat.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tiket - <?= htmlspecialchars($booking['kode_booking']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Library html2canvas untuk convert HTML ke gambar -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f0f2f5; 
            font-family: 'Segoe UI', sans-serif; 
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #333;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .back-btn:hover { color: #198754; }
        
        /* AREA E-TIKET YANG AKAN DIJADIKAN GAMBAR */
        #etiket-area {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .etiket-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px dashed #e0e0e0;
            margin-bottom: 20px;
        }
        .etiket-header h2 {
            color: #198754;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .etiket-header p {
            color: #666;
            font-size: 14px;
        }
        
        .etiket-body {
            padding: 20px 0;
        }
        
        .etiket-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .etiket-row:last-child { border-bottom: none; }
        .etiket-label { color: #666; font-size: 14px; }
        .etiket-value { 
            color: #333; 
            font-weight: 600; 
            font-size: 14px; 
            text-align: right;
        }
        
        .etiket-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px dashed #e0e0e0;
            margin-top: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #d4edda;
            color: #155724;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .barcode {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 3px;
            font-weight: bold;
            color: #333;
        }
        
        /* TOMBOL DOWNLOAD */
        .btn-download {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        .btn-download:hover {
            background: linear-gradient(135deg, #146c43, #0d5a35);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        .btn-download:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-back {
            display: block;
            width: 100%;
            padding: 14px;
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .btn-back:hover {
            background: #f8f9fa;
            border-color: #198754;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 10px;
            color: #198754;
            font-weight: 600;
        }
        .loading.active { display: block; }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            #etiket-area { padding: 20px; }
            .etiket-header h2 { font-size: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="riwayat.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
    </a>
    
    <!-- AREA E-TIKET (ini yang akan di-capture jadi gambar) -->
    <div id="etiket-area">
        <div class="etiket-header">
            <h2><i class="bi bi-ticket-perforated"></i> E-Tiket Walini</h2>
            <p>Booking Wisata Walini - Rancabali</p>
        </div>
        
        <div class="etiket-body">
            <div class="etiket-row">
                <span class="etiket-label">Kode Booking</span>
                <span class="etiket-value" style="color: #0d6efd; font-size: 16px;"><?= htmlspecialchars($booking['kode_booking']) ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Nama Pemesan</span>
                <span class="etiket-value"><?= htmlspecialchars($booking['nama_pelanggan']) ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Email</span>
                <span class="etiket-value"><?= htmlspecialchars($booking['email']) ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">No. HP</span>
                <span class="etiket-value"><?= htmlspecialchars($booking['no_hp'] ?? '-') ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Destinasi</span>
                <span class="etiket-value"><?= htmlspecialchars($booking['nama_destinasi']) ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Tiket</span>
                <span class="etiket-value"><?= htmlspecialchars($booking['nama_tiket']) ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Jumlah Tiket</span>
                <span class="etiket-value"><?= $booking['jumlah'] ?> Tiket</span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Tanggal Kunjungan</span>
                <span class="etiket-value"><?= date('d M Y', strtotime($booking['tanggal_booking'])) ?></span>
            </div>
            <div class="etiket-row">
                <span class="etiket-label">Total Pembayaran</span>
                <span class="etiket-value" style="color: #198754; font-size: 16px;">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
            </div>
        </div>
        
        <div class="etiket-footer">
            <div class="status-badge">
                <i class="bi bi-check-circle-fill"></i> VALID & CONFIRMED
            </div>
            <div class="barcode">
                <?= htmlspecialchars($booking['kode_booking']) ?>
            </div>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                Tunjukkan E-Tiket ini saat masuk ke lokasi
            </p>
        </div>
    </div>
    
    <!-- TOMBOL DOWNLOAD -->
    <button id="btnDownload" class="btn-download" onclick="downloadEticket()">
        <i class="bi bi-download"></i> Download E-Tiket (PNG)
    </button>
    
    <div id="loading" class="loading">
        <i class="bi bi-hourglass-split"></i> Memproses download...
    </div>
    
    <a href="riwayat.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
    </a>
</div>

<script>
function downloadEticket() {
    const btn = document.getElementById('btnDownload');
    const loading = document.getElementById('loading');
    const etiketArea = document.getElementById('etiket-area');
    
    // Disable tombol & tampilkan loading
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
    loading.classList.add('active');
    
    // Tunggu sebentar agar UI update
    setTimeout(() => {
        // Convert HTML ke canvas menggunakan html2canvas
        html2canvas(etiketArea, {
            scale: 2, // Resolusi 2x agar gambar tajam
            backgroundColor: '#ffffff',
            useCORS: true, // Izinkan cross-origin images
            logging: false
        }).then(canvas => {
            // Convert canvas ke data URL (PNG)
            const imageData = canvas.toDataURL('image/png');
            
            // Buat link download
            const link = document.createElement('a');
            link.download = 'E-Tiket-<?= htmlspecialchars($booking['kode_booking']) ?>.png';
            link.href = imageData;
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Kembalikan tombol ke kondisi awal
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-download"></i> Download E-Tiket (PNG)';
            loading.classList.remove('active');
            
        }).catch(error => {
            console.error('Error:', error);
            alert('Gagal mendownload E-Tiket. Silakan coba lagi.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-download"></i> Download E-Tiket (PNG)';
            loading.classList.remove('active');
        });
    }, 100);
}
</script>

</body>
</html>