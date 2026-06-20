<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$message = '';
$error = '';

// Handle update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if (empty($nama_lengkap)) {
        $error = 'Nama lengkap harus diisi!';
    } else {
        try {
            // Update users
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user_id]);

            // Update pelanggan
            $stmt = $pdo->prepare("UPDATE pelanggan SET nama_lengkap = ?, no_telepon = ?, alamat = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $no_telepon, $alamat, $tanggal_lahir, $jenis_kelamin, $pelanggan_id]);

            $message = 'Profil berhasil diperbarui!';
            $pelanggan = getPelangganByUserId($user_id);
            $_SESSION['username'] = $pelanggan['nama_lengkap'];
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui profil: ' . $e->getMessage();
        }
    }
}

// Handle update foto
// Bagian upload foto - perbaiki path penyimpanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_profile'])) {
    $file = $_FILES['foto_profile'];
    if ($file['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (in_array($file['type'], $allowed)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'pelanggan_' . $pelanggan_id . '_' . time() . '.' . $ext;
            // Path untuk menyimpan file (relatif dari root)
            $upload_path = '../uploads/pelanggan/';
            $full_path = $upload_path . $filename;

            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                // Simpan path dengan format ../uploads/pelanggan/filename
                $db_path = '../uploads/pelanggan/' . $filename;
                $stmt = $pdo->prepare("UPDATE pelanggan SET foto_profile = ? WHERE id = ?");
                $stmt->execute([$db_path, $pelanggan_id]);
                $pelanggan = getPelangganByUserId($user_id);
                $message = 'Foto profil berhasil diperbarui!';
            } else {
                $error = 'Gagal mengupload foto!';
            }
        } else {
            $error = 'Format file tidak didukung! (JPG, PNG, GIF)';
        }
    } elseif ($file['error'] !== 4) {
        $error = 'Terjadi kesalahan upload!';
    }
}

$notifUnread = countNotifikasiUnread($pelanggan_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-wrapper {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 32px;
        }

        .profile-sidebar {
            text-align: center;
        }

        .profile-avatar {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 16px;
            border: 4px solid var(--gold);
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar .upload-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,.5);
            color: #fff;
            padding: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: background .3s;
            opacity: 0;
        }

        .profile-avatar:hover .upload-overlay {
            opacity: 1;
        }

        .profile-name {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .profile-role {
            font-size: 14px;
            color: var(--text-muted);
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 14px;
            border: 1.5px solid var(--sage-light);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color .3s, box-shadow .3s;
            background: var(--cream);
            color: var(--text);
            width: 100%;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(200,166,100,.12);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            padding: 14px 32px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s var(--ease);
            align-self: flex-start;
        }

        .btn-submit:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
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

        @media (max-width: 768px) {
            .profile-wrapper {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .profile-avatar {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="https://img.pikbest.com/png-images/20241028/cuty-cat-simple-logo-_11020834.png!sw800" alt="Logo" width="40">
                <span>Polwan</span>
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
                <a href="profil.php" class="active"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="hewan_saya.php"><span class="nav-icon">🐾</span><span>Hewan Saya</span></a>
                <a href="buat_janji.php"><span class="nav-icon">📅</span><span>Buat Janji</span></a>
                <a href="riwayat_janji.php"><span class="nav-icon">📋</span><span>Riwayat Janji</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>Profil Saya</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-wrapper">
                <div class="profile-sidebar">
                    <div class="profile-avatar">
                        <img src="<?php echo $pelanggan['foto_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($pelanggan['nama_lengkap']) . '&size=200&background=C8A664&color=fff'; ?>" alt="Foto Profil">
                        <form method="POST" enctype="multipart/form-data" style="display:inline;">
                            <label class="upload-overlay" for="foto_profile">
                                📷 Upload Foto
                            </label>
                            <input type="file" id="foto_profile" name="foto_profile" accept="image/*" style="display:none;" onchange="this.form.submit()">
                        </form>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?></div>
                    <div class="profile-role">Pelanggan</div>
                </div>

                <div class="profile-form">
                    <form method="POST">
                        <input type="hidden" name="update_profil" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled style="opacity:.6;cursor:not-allowed;">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>No. Telepon</label>
                                <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($pelanggan['no_telepon'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($pelanggan['tanggal_lahir'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="jenis_kelamin">
                                    <option value="">Pilih...</option>
                                    <option value="L" <?php echo ($pelanggan['jenis_kelamin'] ?? '') === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="P" <?php echo ($pelanggan['jenis_kelamin'] ?? '') === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" rows="3"><?php echo htmlspecialchars($pelanggan['alamat'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
                    </form>
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