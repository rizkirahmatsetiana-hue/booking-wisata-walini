<?php
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect
if (isset($_SESSION['id_user']) && $_SESSION['role'] === 'pelanggan') {
    header("Location: home.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Nama, email, dan password wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        try {
            // Cek email sudah ada
            $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email sudah terdaftar!";
            } else {
                $pdo->beginTransaction();
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert ke users
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?, ?, ?, 'pelanggan', NOW())");
                $stmt->execute([$nama, $email, $hashed_password]);
                $id_user = $pdo->lastInsertId();
                
                // Insert ke pelanggan
                $stmt = $pdo->prepare("INSERT INTO pelanggan (id_user, nama, email, no_hp, alamat, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$id_user, $nama, $email, $no_hp, $alamat]);
                
                $pdo->commit();
                
                $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Terjadi kesalahan sistem.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Daftar Akun - Walini</title>
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
        
        .register-container {
            background: white;
            padding: 35px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .register-header h1 {
            color: #198754;
            font-size: 26px;
            margin-bottom: 5px;
        }
        
        .register-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 18px;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #198754;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-register {
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
            margin-top: 10px;
        }
        
        .btn-register:hover {
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .login-link a {
            color: #198754;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 25px 20px;
            }
            
            .register-header h1 {
                font-size: 22px;
            }
            
            .form-group input,
            .form-group textarea {
                font-size: 16px; /* Mencegah zoom di iOS */
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>WALINI</h1>
            <p>Daftar Akun Baru</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="bi bi-person"></i> Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-envelope"></i> Email</label>
                <input type="email" name="email" placeholder="Masukkan email aktif" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-phone"></i> No. HP</label>
                <input type="tel" name="no_hp" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-geo-alt"></i> Alamat</label>
                <textarea name="alamat" placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Buat password (min 6 karakter)" required>
                <div class="password-hint">
                    <i class="bi bi-info-circle"></i> Password minimal 6 karakter
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-lock-fill"></i> Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" placeholder="Ulangi password" required>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus"></i> Daftar Sekarang
            </button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>