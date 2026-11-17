<?php
require '../config/koneksi.php';
require '../includes/auth_check.php';
cek_login('pelanggan');

// Ambil daftar destinasi untuk dropdown
$stmt = $pdo->query("SELECT id_destinasi, nama_destinasi FROM destinasi ORDER BY nama_destinasi");
$destinasi_list = $stmt->fetchAll();

// Ambil semua ulasan (terbaru di atas)
$stmt_ulasan = $pdo->query("
    SELECT u.*, d.nama_destinasi 
    FROM ulasan u
    JOIN destinasi d ON u.id_destinasi = d.id_destinasi
    ORDER BY u.tanggal_ulasan DESC
");
$ulasan_list = $stmt_ulasan->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan & Rating - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f0f2f5; 
            font-family: 'Segoe UI', sans-serif; 
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .page-header { 
            background: white; 
            padding: 20px 25px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }
        .page-header h2 { color: #198754; font-size: 24px; }
        .card { 
            background: white; 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }
        .card h3 { color: #333; margin-bottom: 20px; font-size: 18px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 8px; 
            color: #333; 
        }
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px; 
            font-size: 14px; 
            font-family: inherit; 
        }
        .form-group select:focus, 
        .form-group textarea:focus { 
            border-color: #198754; 
            outline: none; 
        }
        
        /* Rating Bintang */
        .rating-input { 
            display: flex; 
            gap: 8px; 
            font-size: 32px; 
            direction: rtl; 
        }
        .rating-input input[type="radio"] { display: none; }
        .rating-input label { 
            color: #ddd; 
            cursor: pointer; 
            transition: color 0.2s; 
        }
        .rating-input label:hover,
        .rating-input label:hover ~ label { 
            color: #ffc107; 
        }
        .rating-input input[type="radio"]:checked ~ label { 
            color: #ffc107; 
        }
        
        /* Character Counter */
        .char-counter { 
            font-size: 12px; 
            margin-top: 5px; 
        }
        .char-counter.warning { 
            color: #dc3545; 
            font-weight: bold; 
        }
        .char-counter.ok { 
            color: #198754; 
            font-weight: bold; 
        }
        
        /* Tombol Submit */
        .btn-submit { 
            background: #198754; 
            color: white; 
            border: none; 
            padding: 14px 30px; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            width: 100%; 
        }
        .btn-submit:hover { background: #146c43; }
        .btn-submit:disabled { 
            background: #ccc; 
            cursor: not-allowed; 
        }
        
        /* Tampilan Ulasan */
        .ulasan-item { 
            border-bottom: 1px solid #eee; 
            padding: 15px 0; 
        }
        .ulasan-item:last-child { border-bottom: none; }
        .ulasan-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 8px; 
        }
        .ulasan-nama { font-weight: bold; color: #333; }
        .ulasan-destinasi { 
            font-size: 12px; 
            color: #666; 
            background: #e8f5e9; 
            padding: 3px 10px; 
            border-radius: 12px; 
        }
        .ulasan-rating { 
            color: #ffc107; 
            font-size: 16px; 
            margin-bottom: 8px; 
        }
        .ulasan-komentar { 
            color: #555; 
            line-height: 1.6; 
        }
        .ulasan-tanggal { 
            font-size: 11px; 
            color: #999; 
            margin-top: 5px; 
        }
        
        /* Alert */
        .alert { 
            padding: 12px 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .alert-danger { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        
        .empty-state { 
            text-align: center; 
            padding: 40px; 
            color: #999; 
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h2><i class="bi bi-star-fill"></i> Ulasan & Rating</h2>
        <p style="color: #666; margin-top: 5px;">Beri penilaian untuk destinasi wisata Walini</p>
    </div>

    <!-- Pesan Error/Success -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <!-- FORM ULASAN -->
    <div class="card">
        <h3><i class="bi bi-pencil-square"></i> Tulis Ulasan Anda</h3>
        
        <form method="POST" action="../api/proses_ulasan.php" id="formUlasan">
            
            <!-- Pilih Destinasi -->
            <div class="form-group">
                <label><i class="bi bi-geo-alt"></i> Pilih Destinasi</label>
                <select name="id_destinasi" required>
                    <option value="">-- Pilih Destinasi --</option>
                    <?php foreach ($destinasi_list as $d): ?>
                        <option value="<?= $d['id_destinasi'] ?>">
                            <?= htmlspecialchars($d['nama_destinasi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Rating Bintang -->
            <div class="form-group">
                <label><i class="bi bi-star"></i> Rating (1-5)</label>
                <div class="rating-input">
                    <input type="radio" name="rating" id="star5" value="5" required>
                    <label for="star5" title="5 bintang">★</label>
                    
                    <input type="radio" name="rating" id="star4" value="4">
                    <label for="star4" title="4 bintang">★</label>
                    
                    <input type="radio" name="rating" id="star3" value="3">
                    <label for="star3" title="3 bintang">★</label>
                    
                    <input type="radio" name="rating" id="star2" value="2">
                    <label for="star2" title="2 bintang">★</label>
                    
                    <input type="radio" name="rating" id="star1" value="1">
                    <label for="star1" title="1 bintang">★</label>
                </div>
                <small style="color: #666;">Klik bintang untuk memberi rating (1 = Buruk, 5 = Sangat Baik)</small>
            </div>
            
            <!-- Komentar -->
            <div class="form-group">
                <label><i class="bi bi-chat-left-text"></i> Komentar (Minimal 10 Karakter)</label>
                <textarea 
                    name="komentar" 
                    id="komentar" 
                    rows="4" 
                    placeholder="Tulis pengalaman Anda di sini... (minimal 10 karakter)"
                    required 
                    oninput="cekKarakter()"
                ></textarea>
                <div class="char-counter" id="charCounter">0 / 10 karakter minimum</div>
            </div>
            
            <button type="submit" class="btn-submit" id="btnSubmit" disabled>
                <i class="bi bi-send"></i> Kirim Ulasan
            </button>
        </form>
    </div>

    <!-- DAFTAR ULASAN -->
    <div class="card">
        <h3><i class="bi bi-chat-square-quote"></i> Ulasan Terbaru</h3>
        
        <?php if (count($ulasan_list) > 0): ?>
            <?php foreach ($ulasan_list as $u): ?>
                <div class="ulasan-item">
                    <div class="ulasan-header">
                        <span class="ulasan-nama"><?= htmlspecialchars($u['nama_pengguna']) ?></span>
                        <span class="ulasan-destinasi"><?= htmlspecialchars($u['nama_destinasi']) ?></span>
                    </div>
                    <div class="ulasan-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= $u['rating'] ? '★' : '☆' ?>
                        <?php endfor; ?>
                        <span style="color: #666; font-size: 13px; margin-left: 8px;">
                            (<?= $u['rating'] ?>/5)
                        </span>
                    </div>
                    <div class="ulasan-komentar">
                        <?= nl2br(htmlspecialchars($u['komentar'])) ?>
                    </div>
                    <div class="ulasan-tanggal">
                        <i class="bi bi-clock"></i> 
                        <?= date('d M Y, H:i', strtotime($u['tanggal_ulasan'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-chat-square" style="font-size: 48px;"></i>
                <h4 style="margin: 15px 0;">Belum Ada Ulasan</h4>
                <p>Jadilah yang pertama memberi ulasan!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ============================================
// VALIDASI FRONTEND (Real-time)
// ============================================

function cekKarakter() {
    const komentar = document.getElementById('komentar').value;
    const counter = document.getElementById('charCounter');
    const btn = document.getElementById('btnSubmit');
    const panjang = komentar.length;
    
    // Update counter
    counter.textContent = panjang + ' / 10 karakter minimum';
    
    // Ubah warna dan status tombol
    if (panjang < 10) {
        counter.className = 'char-counter warning';
        btn.disabled = true;  // Tombol tidak bisa diklik
    } else {
        counter.className = 'char-counter ok';
        btn.disabled = false; // Tombol bisa diklik
    }
}

// Validasi saat form di-submit
document.getElementById('formUlasan').addEventListener('submit', function(e) {
    const rating = document.querySelector('input[name="rating"]:checked');
    const komentar = document.getElementById('komentar').value.trim();
    
    // Cek rating
    if (!rating) {
        e.preventDefault();
        alert('⚠️ Pilih rating (1-5 bintang)!');
        return false;
    }
    
    // Cek komentar
    if (komentar.length < 10) {
        e.preventDefault();
        alert('⚠️ Komentar minimal 10 karakter! (Saat ini: ' + komentar.length + ' karakter)');
        return false;
    }
});
</script>

</body>
</html>