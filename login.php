<?php
require_once 'config.php';

// Jika sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    if (isPelanggan()) {
        header('Location: pelanggan/dashboard.php');
        exit;
    } elseif (isPerawat()) {
        header('Location: perawat/dashboard.php');
        exit;
    } elseif (isAdmin()) {
        header('Location: admin/dashboard.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Cek status user
            if ($user['status'] === 'nonaktif') {
                $error = 'Akun Anda sedang dinonaktifkan. Hubungi admin!';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                // Redirect berdasarkan role
                if ($user['role'] === 'pelanggan') {
                    header('Location: pelanggan/dashboard.php');
                } elseif ($user['role'] === 'perawat') {
                    header('Location: perawat/dashboard.php');
                } elseif ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: login.php');
                }
                exit;
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Klinik Hewan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --ink: #16241B;
            --ink-soft: #233326;
            --sage: #6F8F76;
            --sage-light: #DCE7DD;
            --gold: #C8A664;
            --gold-dark: #A8855B;
            --gold-soft: #E9DBB8;
            --cream: #F7F3EA;
            --paper: #FFFFFF;
            --text: #38423A;
            --text-muted: #6E7669;
            --shadow-sm: 0 4px 14px rgba(22,36,27,0.08);
            --shadow-md: 0 14px 40px rgba(22,36,27,0.14);
            --shadow-lg: 0 30px 70px rgba(15,22,16,0.28);
            --radius-sm: 10px;
            --radius-md: 18px;
            --radius-lg: 28px;
            --ease: cubic-bezier(.16,.84,.44,1);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cream);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            animation: slideUp .6s var(--ease);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-card {
            background: var(--paper);
            border-radius: var(--radius-lg);
            padding: 48px 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(22,36,27,.06);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header .logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: 'Fraunces', serif;
            font-size: 24px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 16px;
        }

        .login-header .logo img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }

        .login-header h1 {
            font-family: 'Fraunces', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: 6px;
            letter-spacing: .02em;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--sage-light);
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: border-color .3s, box-shadow .3s;
            background: var(--cream);
            color: var(--text);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(200,166,100,.12);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
            opacity: .6;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            font-size: 14px;
        }

        .form-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .form-options input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--gold);
            cursor: pointer;
        }

        .form-options a {
            color: var(--gold-dark);
            text-decoration: none;
            font-weight: 500;
            transition: color .3s;
        }

        .form-options a:hover {
            color: var(--gold);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all .3s var(--ease);
            box-shadow: var(--shadow-sm);
        }

        .btn-login:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #FDEAEA;
            color: #B23B3B;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 20px;
            display: <?php echo $error ? 'block' : 'none'; ?>;
            border: 1px solid #F5C6CB;
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 28px;
            border-top: 1px solid rgba(22,36,27,.06);
            color: var(--text-muted);
            font-size: 14px;
        }

        .login-footer a {
            color: var(--gold-dark);
            text-decoration: none;
            font-weight: 600;
            transition: color .3s;
        }

        .login-footer a:hover {
            color: var(--gold);
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: color .3s;
        }

        .back-home:hover {
            color: var(--ink);
        }

        .demo-credentials {
            background: var(--cream);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-muted);
            border: 1px dashed var(--sage-light);
        }

        .demo-credentials strong {
            color: var(--ink);
        }

        .demo-credentials .cred-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
            .login-header h1 {
                font-size: 24px;
            }
            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            .demo-credentials .cred-row {
                flex-direction: column;
                gap: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <img src="https://img.pikbest.com/png-images/20241028/cuty-cat-simple-logo-_11020834.png!sw800" alt="Logo">
                    <span>Klinik Hewan</span>
                </div>
                <h1>Selamat Datang</h1>
                <p>Masuk ke akun Anda untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message" style="display:block;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username Anda" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                </div>

                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember"> Ingat saya
                    </label>
                    <a href="#">Lupa password?</a>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>

            <div class="demo-credentials">
                <div style="font-weight:600;margin-bottom:6px;color:var(--ink);">🔑 Demo Akun:</div>
                <div class="cred-row">
                    <span><strong>Admin:</strong> admin</span>
                    <span style="color:var(--text-muted);">password: 123456</span>
                </div>
                <div class="cred-row">
                    <span><strong>Perawat:</strong> perawat1</span>
                    <span style="color:var(--text-muted);">password: 123456</span>
                </div>
                <div class="cred-row">
                    <span><strong>Pelanggan:</strong> pelanggan1</span>
                    <span style="color:var(--text-muted);">password: 123456</span>
                </div>
            </div>

            <div class="login-footer">
                <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
                <a href="index.php" class="back-home">← Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</body>
</html>