<?php
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect ke home
if (isset($_SESSION['id_user']) && $_SESSION['role'] === 'pelanggan') {
    header("Location: home.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Login - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../assets/img/login.jpg');
            background-size: cover;
            background-position: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #198754;
            font-size: 28px;
            margin-bottom: 5px;
            letter-spacing: 2px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group label i {
            color: #198754;
            width: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #198754;
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: #0d6efd;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
            color: #0a58ca;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #198754;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-login:hover {
            background: #146c43;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #999;
            font-size: 13px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .register-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: #198754;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        /* Modal Lupa Password */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-content h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .modal-content p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .modal-content .form-group {
            margin-bottom: 15px;
        }
        
        .btn-submit-reset {
            width: 100%;
            padding: 12px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        .btn-submit-reset:hover {
            background: #0a58ca;
        }
        
        .btn-cancel {
            width: 100%;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .modal-alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .modal-alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .modal-alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .form-group input {
                font-size: 16px; /* Mencegah zoom di iOS */
            }
            
            .modal-content {
                padding: 25px 20px;
            }
        }
        /* ============================================
   LOGIN PAGE - MOBILE OPTIMIZATION
   ============================================ */
@media (max-width: 768px) {
    
    /* Body padding lebih kecil */
    body {
        padding: 10px !important;
    }
    
    /* Card login lebih compact */
    .login-container {
        padding: 25px 20px !important;
        max-width: 100% !important;
        width: 100% !important;
        border-radius: 12px !important;
        margin: 0 !important;
    }
    
    /* Header lebih kecil */
    .login-header {
        margin-bottom: 20px !important;
    }
    
    .login-header h1 {
        font-size: 24px !important;
        margin-bottom: 5px !important;
    }
    
    .login-header p {
        font-size: 12px !important;
    }
    
    /* Form group lebih rapat */
    .form-group {
        margin-bottom: 15px !important;
    }
    
    .form-group label {
        font-size: 13px !important;
        margin-bottom: 6px !important;
    }
    
    .form-group input {
        padding: 10px 12px !important;
        font-size: 14px !important;
        border-radius: 6px !important;
    }
    
    /* Tombol masuk lebih kecil */
    .btn-login {
        padding: 12px !important;
        font-size: 15px !important;
        border-radius: 6px !important;
        min-height: 44px !important;
    }
    
    /* Error message lebih kecil */
    .error-message {
        padding: 10px !important;
        font-size: 12px !important;
        margin-bottom: 15px !important;
    }
}

/* HP KECIL (< 375px) */
@media (max-width: 374px) {
    .login-container {
        padding: 20px 15px !important;
    }
    
    .login-header h1 {
        font-size: 22px !important;
    }
    
    .form-group input {
        padding: 10px !important;
        font-size: 14px !important;
    }
}
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>WALINI</h1>
        <p>Booking Tiket Wisata</p>
    </div>
    
    <?php if ($error): ?>
    <div class="error-message">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="success-message">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <form action="../api/proses_login.php" method="POST">
        <div class="form-group">
            <label><i class="bi bi-envelope"></i> Email</label>
            <input type="email" name="email" placeholder="Masukkan email Anda" required>
        </div>
        
        <div class="form-group">
            <label><i class="bi bi-lock"></i> Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>
        </div>
        
        <div class="forgot-password">
            <a href="#" onclick="openForgotModal(); return false;">Lupa password?</a>
        </div>
        
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Masuk
        </button>
    </form>
    
    <div class="divider"><span>atau</span></div>
    
    <div class="register-link">
        Belum punya akun? <a href="registrasi.php">Daftar di sini</a>
    </div>
</div>

<!-- MODAL LUPA PASSWORD -->
<div class="modal-overlay" id="forgotModal">
    <div class="modal-content">
        <h3><i class="bi bi-key"></i> Lupa Password</h3>
        <p>Masukkan email Anda. Kami akan mengirimkan link reset password.</p>
        
        <div id="modalAlert"></div>
        
        <form id="forgotForm" onsubmit="handleForgotPassword(event)">
            <div class="form-group">
                <label><i class="bi bi-envelope"></i> Email</label>
                <input type="email" id="forgotEmail" placeholder="Masukkan email terdaftar" required>
            </div>
            
            <button type="submit" class="btn-submit-reset">
                <i class="bi bi-send"></i> Kirim Link Reset
            </button>
            <button type="button" class="btn-cancel" onclick="closeForgotModal()">
                Batal
            </button>
        </form>
    </div>
</div>

<script>
function openForgotModal() {
    document.getElementById('forgotModal').classList.add('active');
    document.getElementById('modalAlert').innerHTML = '';
    document.getElementById('forgotEmail').value = '';
}

function closeForgotModal() {
    document.getElementById('forgotModal').classList.remove('active');
}

function handleForgotPassword(e) {
    e.preventDefault();
    
    const email = document.getElementById('forgotEmail').value.trim();
    const alertDiv = document.getElementById('modalAlert');
    
    if (!email) {
        alertDiv.innerHTML = '<div class="modal-alert error"><i class="bi bi-exclamation-triangle"></i> Email wajib diisi!</div>';
        return;
    }
    
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alertDiv.innerHTML = '<div class="modal-alert error"><i class="bi bi-exclamation-triangle"></i> Format email tidak valid!</div>';
        return;
    }
    
    // Kirim request ke server
    const formData = new FormData();
    formData.append('email', email);
    
    fetch('../api/proses_forgot_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertDiv.innerHTML = '<div class="modal-alert success"><i class="bi bi-check-circle"></i> ' + data.message + '</div>';
            setTimeout(() => closeForgotModal(), 3000);
        } else {
            alertDiv.innerHTML = '<div class="modal-alert error"><i class="bi bi-exclamation-triangle"></i> ' + data.message + '</div>';
        }
    })
    .catch(error => {
        alertDiv.innerHTML = '<div class="modal-alert error"><i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan. Coba lagi.</div>';
    });
}

// Tutup modal saat klik di luar
document.getElementById('forgotModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeForgotModal();
    }
});
</script>

</body>
</html>