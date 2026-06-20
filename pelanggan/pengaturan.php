<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$notifUnread = countNotifikasiUnread($pelanggan_id);
$message = '';
$error = '';

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Semua field password harus diisi!';
    } elseif (strlen($new) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password'])) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$newHash, $user_id])) {
                $message = 'Password berhasil diperbarui!';
                addNotifikasi($pelanggan_id, 'Password Diperbarui', 'Password akun Anda telah berhasil diperbarui.', 'success');
            } else {
                $error = 'Gagal memperbarui password!';
            }
        } else {
            $error = 'Password saat ini salah!';
        }
    }
}

// Delete account
if (isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'] ?? '';
    if ($confirm_delete === 'YA HAPUS') {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Delete pelanggan (cascade will delete related data)
            $stmt = $pdo->prepare("DELETE FROM pelanggan WHERE id = ?");
            $stmt->execute([$pelanggan_id]);

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();

            session_destroy();
            header('Location: ../login.php?deleted=1');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal menghapus akun: ' . $e->getMessage();
        }
    } else {
        $error = 'Konfirmasi hapus akun tidak sesuai!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .settings-wrapper {
            display: grid;
            gap: 28px;
            max-width: 700px;
        }

        .settings-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 28px 32px;
            box-shadow: var(--shadow-sm);
        }

        .settings-card h3 {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .settings-card .desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--sage-light);
            border-radius: var(--radius-sm);
            font-size: 14px;
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

        .btn-submit {
            padding: 12px 28px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s var(--ease);
        }

        .btn-submit:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #E5F5E5;
            color: #1A6A3A;
            border: 1px solid #B8D9B8;
        }

        .alert-danger {
            background: #FDEAEA;
            color: #B23B3B;
            border: 1px solid #F5C6CB;
        }

        .delete-section {
            border-top: 1px solid rgba(22,36,27,.06);
            padding-top: 24px;
            margin-top: 8px;
        }

        .delete-section .warning {
            color: #e74c3c;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .settings-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="https://img.pikbest.com/png-images/20241028/cuty-cat-simple-logo-_11020834.png!sw800" alt="Logo" width="40">
                <span>Klinik Hewan</span>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <img src="<?php echo $pelanggan['foto_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($pelanggan['nama_lengkap']) . '&size=100&background=C8A664&color=fff'; ?>" alt="Foto">
                </div>
                <div class="sidebar-user-info">
                    <h4><?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?></h4>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                <a href="profil.php"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="hewan_saya.php"><span class="nav-icon">🐾</span><span>Hewan Saya</span></a>
                <a href="buat_janji.php"><span class="nav-icon">📅</span><span>Buat Janji</span></a>
                <a href="riwayat_janji.php"><span class="nav-icon">📋</span><span>Riwayat Janji</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="pengaturan.php" class="active"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>⚙️ Pengaturan</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div class="settings-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Change Password -->
                <div class="settings-card">
                    <h3>🔑 Ubah Password</h3>
                    <p class="desc">Ganti password akun Anda untuk keamanan yang lebih baik</p>
                    <form method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div class="form-group">
                            <label>Password Saat Ini</label>
                            <input type="password" name="current_password" placeholder="Masukkan password saat ini" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" placeholder="Ulangi password baru" required>
                        </div>
                        <button type="submit" class="btn-submit">💾 Update Password</button>
                    </form>
                </div>

                <!-- Delete Account -->
                <div class="settings-card">
                    <h3>🗑️ Hapus Akun</h3>
                    <p class="desc">Tindakan ini permanen dan tidak dapat dibatalkan. Semua data Anda akan dihapus.</p>
                    <div class="delete-section">
                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus akun ini? Semua data akan hilang secara permanen!')">
                            <input type="hidden" name="delete_account" value="1">
                            <div class="form-group">
                                <label>Ketik <strong class="warning">YA HAPUS</strong> untuk konfirmasi</label>
                                <input type="text" name="confirm_delete" placeholder="YA HAPUS" required>
                            </div>
                            <button type="submit" class="btn-submit btn-danger">🗑️ Hapus Akun</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('menuToggle');
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>