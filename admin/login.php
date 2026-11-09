<?php
require '../config/koneksi.php';
?>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/responsive.css">
    <meta charset="UTF-8">
    <title>Login Admin - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('../assets/img/login.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }
        .card-login {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            position: relative;
            z-index: 1;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .logo h1 { color: #198754; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .logo p { text-align: center; color: #666; margin-bottom: 25px; }
        .btn-masuk {
            background: #198754;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            width: 100%;
        }
        .btn-masuk:hover { background: #146c43; color: white; }
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

<div class="card-login">
    <div class="logo">
        <h1>WALINI</h1>
        <p>Admin Panel</p>
    </div>
    
    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <form action="../api/proses_login.php" method="POST">
        <!-- PENTING: Hidden input untuk role -->
        <input type="hidden" name="role_login" value="admin">
        
        <div class="mb-3">
            <label class="form-label fw-bold">Username</label>
            <input type="text" name="email" class="form-control" placeholder="Masukkan username" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn btn-masuk">Masuk</button>
    </form>
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