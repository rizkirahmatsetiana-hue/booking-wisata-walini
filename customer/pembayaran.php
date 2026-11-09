<?php 
require '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_booking = $_GET['id_booking'] ?? 0;
$total = $_GET['total'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM booking WHERE id_booking = ? AND id_user = ?");
$stmt->execute([$id_booking, $_SESSION['id_user']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .btn-walini {
            background: #198754;
            color: white;
            border-radius: 10px;
            border: none;
            padding: 12px;
            font-weight: bold;
        }
        .btn-walini:hover { background: #146c43; color: white; }
        
        .payment-detail {
            display: none;
            background: #04325f;
            border: 2px dashed #198754;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
        .payment-detail.active { display: block; }
        
        .bank-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #198754;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .bank-card .bank-name {
            font-weight: bold;
            color: #198754;
            font-size: 14px;
        }
        .bank-card .account-number {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #333;
        }
        .bank-card .account-name {
            font-size: 13px;
            color: #666;
        }
        .copy-btn {
            background: #198754;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
        }
        .copy-btn:hover { background: #146c43; }
        
        .qr-container { text-align: center; padding: 20px; }
        .qr-container img {
            border: 3px solid #198754;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
        
        .ewallet-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #198754;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .timer-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
        }
        .timer-box .timer {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
        
        .instruction-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
        .instruction-box h6 { color: #0c5460; font-weight: bold; }
        .instruction-box ol { margin-bottom: 0; color: #0c5460; }
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

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-credit-card"></i> Pembayaran
                    </h4>
                    
                    <form action="../api/proses_pembayaran.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_booking" value="<?= $id_booking ?>">
                        <input type="hidden" name="jumlah_bayar" value="<?= $total ?>">
                        
                        <!-- TIMER -->
                        <div class="timer-box">
                            <small class="text-muted">Selesaikan pembayaran dalam:</small>
                            <div class="timer" id="countdown">23:59:59</div>
                            <small class="text-danger">*Booking akan dibatalkan jika tidak dibayar</small>
                        </div>

                        <!-- METODE PEMBAYARAN -->
                        <label class="fw-bold mb-2">Metode Pembayaran</label>
                        <div class="mb-3">
                            <div class="form-check mb-2 p-3 border rounded">
                                <input class="form-check-input" type="radio" name="metode" value="transfer" id="transfer" checked onchange="showPaymentDetail('transfer')">
                                <label class="form-check-label w-100" for="transfer">
                                    <i class="bi bi-bank"></i> <strong>Transfer Bank</strong> (BCA / BRI / Mandiri)
                                </label>
                            </div>
                            <div class="form-check mb-2 p-3 border rounded">
                                <input class="form-check-input" type="radio" name="metode" value="e-wallet" id="ewallet" onchange="showPaymentDetail('ewallet')">
                                <label class="form-check-label w-100" for="ewallet">
                                    <i class="bi bi-phone"></i> <strong>E-Wallet</strong> (GoPay / OVO / Dana)
                                </label>
                            </div>
                            <div class="form-check mb-2 p-3 border rounded">
                                <input class="form-check-input" type="radio" name="metode" value="qris" id="qris" onchange="showPaymentDetail('qris')">
                                <label class="form-check-label w-100" for="qris">
                                    <i class="bi bi-qr-code"></i> <strong>QRIS</strong> (Scan QR Code)
                                </label>
                            </div>
                        </div>

                        <!-- DETAIL: TRANSFER BANK -->
                        <div id="detail-transfer" class="payment-detail active">
                            <h6 class="fw-bold text-success mb-3">
                                <i class="bi bi-bank"></i> Transfer ke Rekening Berikut:
                            </h6>
                            
                            <div class="bank-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="bank-name">🏦 BANK BCA</div>
                                        <div class="account-number">1234567890</div>
                                        <div class="account-name">a.n. Walini Wisata</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyRekening('1234567890')">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                            </div>

                            <div class="bank-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="bank-name"> BANK BRI</div>
                                        <div class="account-number">0987654321000</div>
                                        <div class="account-name">a.n. Walini Wisata</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyRekening('0987654321000')">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                            </div>

                            <div class="bank-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="bank-name"> BANK MANDIRI</div>
                                        <div class="account-number">1300012345678</div>
                                        <div class="account-name">a.n. Walini Wisata</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyRekening('1300012345678')">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                            </div>

                            
                        </div>

                        <!-- DETAIL: E-WALLET -->
                        <div id="detail-ewallet" class="payment-detail">
                            <h6 class="fw-bold text-success mb-3">
                                <i class="bi bi-phone"></i> Kirim ke E-Wallet Berikut:
                            </h6>
                            
                            <div class="ewallet-card">
                                <div class="d-flex align-items-center">
                                    <span style="font-size:24px; margin-right:10px;">💚</span>
                                    <div>
                                        <div class="bank-name">GoPay</div>
                                        <div class="account-number" style="font-size:16px;">0812-3456-7890</div>
                                        <div class="account-name">a.n. Walini Wisata</div>
                                    </div>
                                </div>
                                <button type="button" class="copy-btn" onclick="copyRekening('081234567890')">
                                    <i class="bi bi-clipboard"></i> Salin
                                </button>
                            </div>

                            <div class="ewallet-card">
                                <div class="d-flex align-items-center">
                                    <span style="font-size:24px; margin-right:10px;">💜</span>
                                    <div>
                                        <div class="bank-name">OVO</div>
                                        <div class="account-number" style="font-size:16px;">0812-3456-7890</div>
                                        <div class="account-name">a.n. Walini Wisata</div>
                                    </div>
                                </div>
                                <button type="button" class="copy-btn" onclick="copyRekening('081234567890')">
                                    <i class="bi bi-clipboard"></i> Salin
                                </button>
                            </div>

                            <div class="ewallet-card">
                                <div class="d-flex align-items-center">
                                    <span style="font-size:24px; margin-right:10px;">💙</span>
                                    <div>
                                        <div class="bank-name">DANA</div>
                                        <div class="account-number" style="font-size:16px;">0812-3456-7890</div>
                                        <div class="account-name">a.n. Walini Wisata</div>
                                    </div>
                                </div>
                                <button type="button" class="copy-btn" onclick="copyRekening('081234567890')">
                                    <i class="bi bi-clipboard"></i> Salin
                                </button>
                            </div>

                            <div class="instruction-box">
                                <h6><i class="bi bi-info-circle"></i> Cara Pembayaran:</h6>
                                <ol>
                                    <li>Buka aplikasi E-Wallet (GoPay/OVO/Dana)</li>
                                    <li>Pilih menu <strong>"Kirim"</strong> atau <strong>"Transfer"</strong></li>
                                    <li>Masukkan nomor di atas</li>
                                    <li>Transfer sesuai <strong>total pembayaran</strong></li>
                                    <li>Simpan bukti & upload di bawah</li>
                                </ol>
                            </div>
                        </div>

                        <!-- DETAIL: QRIS -->
                        <div id="detail-qris" class="payment-detail">
                            <h6 class="fw-bold text-success mb-3 text-center">
                                <i class="bi bi-qr-code"></i> Scan QR Code di Bawah:
                            </h6>
                            
                            <div class="qr-container">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=WALINI-WISATA-QRIS-PAYMENT" 
                                     alt="QR Code Pembayaran" 
                                     style="width: 250px; height: 250px;">
                                <p class="mt-2 mb-0"><strong>QRIS - Walini Wisata</strong></p>
                                <small class="text-muted">Scan dengan aplikasi pembayaran apapun</small>
                            </div>

                            <div class="instruction-box">
                                <h6><i class="bi bi-info-circle"></i> Cara Pembayaran:</h6>
                                <ol>
                                    <li>Buka aplikasi pembayaran (GoPay, OVO, Dana, Mobile Banking)</li>
                                    <li>Pilih menu <strong>"Scan QR"</strong> atau <strong>"QRIS"</strong></li>
                                    <li>Arahkan kamera ke QR Code di atas</li>
                                    <li>Konfirmasi pembayaran sesuai <strong>total</strong></li>
                                    <li>Simpan bukti & upload di bawah</li>
                                </ol>
                            </div>
                        </div>

                        <!-- UPLOAD BUKTI -->
                        <label class="fw-bold mt-4 mb-2">
                            <i class="bi bi-upload"></i> Upload Bukti Pembayaran
                        </label>
                        <input type="file" name="bukti" class="form-control mb-3" accept="image/*" required>
                        <small class="text-muted">Format: JPG, PNG, JPEG (Maks. 2MB)</small>

                        <!-- TOTAL -->
                        <div class="bg-light p-3 rounded mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="fs-5">Total Pembayaran</strong>
                                <strong class="fs-4 text-success">Rp. <?= number_format($total, 0, ',', '.') ?></strong>
                            </div>
                        </div>

                        <!-- TOMBOL -->
                        <button type="submit" class="btn btn-walini w-100 py-3 mt-3 fs-5">
                            <i class="bi bi-check-circle"></i> Lanjutkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showPaymentDetail(method) {
    document.querySelectorAll('.payment-detail').forEach(el => {
        el.classList.remove('active');
    });
    const detailId = 'detail-' + method;
    const detailEl = document.getElementById(detailId);
    if (detailEl) {
        detailEl.classList.add('active');
    }
}

function copyRekening(nomor) {
    navigator.clipboard.writeText(nomor).then(() => {
        alert('✅ Nomor berhasil disalin: ' + nomor);
    }).catch(err => {
        const textArea = document.createElement("textarea");
        textArea.value = nomor;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        alert('✅ Nomor berhasil disalin: ' + nomor);
    });
}

function startCountdown() {
    let hours = 23, minutes = 59, seconds = 59;
    const timer = setInterval(() => {
        seconds--;
        if (seconds < 0) { seconds = 59; minutes--; }
        if (minutes < 0) { minutes = 59; hours--; }
        if (hours < 0) {
            clearInterval(timer);
            alert('⏰ Waktu pembayaran habis!');
            window.location.href = 'home.php';
            return;
        }
        document.getElementById('countdown').textContent = 
            `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }, 1000);
}

startCountdown();
showPaymentDetail('transfer');
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