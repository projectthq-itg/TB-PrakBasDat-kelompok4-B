<?php
require_once '../config.php';
requireLogin();

if (!isPerawat() && !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$perawat = getPerawatByUserId($user_id);

if (!$perawat) {
    header('Location: ../login.php');
    exit;
}

$perawat_id = $perawat['id'];
$message = '';
$error = '';

// Handle update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $keahlian = trim($_POST['keahlian'] ?? '');
    $pengalaman = intval($_POST['pengalaman'] ?? 0);
    $email = trim($_POST['email'] ?? '');

    if (empty($nama_lengkap)) {
        $error = 'Nama lengkap harus diisi!';
    } else {
        try {
            // Update users
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user_id]);

            // Update perawat
            $stmt = $pdo->prepare("UPDATE perawat SET nama_lengkap = ?, no_telepon = ?, keahlian = ?, pengalaman = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $no_telepon, $keahlian, $pengalaman, $perawat_id]);

            $message = 'Profil berhasil diperbarui!';
            $perawat = getPerawatByUserId($user_id);
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
            $filename = 'perawat_' . $perawat_id . '_' . time() . '.' . $ext;
            // Path untuk menyimpan file (relatif dari root)
            $upload_path = '../uploads/perawat/';
            $full_path = $upload_path . $filename;

            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                // Simpan path dengan format ../uploads/perawat/filename
                $db_path = '../uploads/perawat/' . $filename;
                $stmt = $pdo->prepare("UPDATE perawat SET foto_profile = ? WHERE id = ?");
                $stmt->execute([$db_path, $perawat_id]);
                $perawat = getPerawatByUserId($user_id);
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

$notifUnread = countNotifikasiPerawatUnread($perawat_id);

// Fungsi getPerawatByUserId
function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function countNotifikasiPerawatUnread($perawat_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifikasi_perawat WHERE perawat_id = ? AND dibaca = 0");
    $stmt->execute([$perawat_id]);
    return $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Perawat - Klinik Hewan</title>
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

        .profile-role .badge-role {
            display: inline-block;
            background: var(--gold);
            color: var(--ink);
            padding: 2px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 12px;
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
                <span>Klinik Hewan</span>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <img src="<?php echo $perawat['foto_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=100&background=C8A664&color=fff'; ?>" alt="Foto">
                </div>
                <div class="sidebar-user-info">
                    <h4><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></h4>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> • Perawat</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                <a href="profil.php" class="active"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="janji_temu.php"><span class="nav-icon">📅</span><span>Janji Temu</span></a>
                <a href="hewan_perawat.php"><span class="nav-icon">🐾</span><span>Hewan Pasien</span></a>
                <a href="riwayat_konsultasi.php"><span class="nav-icon">📋</span><span>Riwayat Konsultasi</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="laporan.php"><span class="nav-icon">📄</span><span>Laporan</span></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>👤 Profil Saya</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
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
                        <img src="<?php echo $perawat['foto_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=200&background=C8A664&color=fff'; ?>" alt="Foto Profil">
                        <form method="POST" enctype="multipart/form-data" style="display:inline;">
                            <label class="upload-overlay" for="foto_profile">
                                📷 Upload Foto
                            </label>
                            <input type="file" id="foto_profile" name="foto_profile" accept="image/*" style="display:none;" onchange="this.form.submit()">
                        </form>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></div>
                    <div class="profile-role">
                        <span class="badge-role">Perawat</span>
                    </div>
                    <div style="margin-top:12px;font-size:13px;color:var(--text-muted);">
                        ⭐ <?php echo $perawat['pengalaman'] ?? 0; ?> tahun pengalaman
                    </div>
                </div>

                <div class="profile-form">
                    <form method="POST">
                        <input type="hidden" name="update_profil" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($perawat['nama_lengkap']); ?>" required>
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
                                <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($perawat['no_telepon'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Keahlian</label>
                                <input type="text" name="keahlian" value="<?php echo htmlspecialchars($perawat['keahlian'] ?? ''); ?>" placeholder="Contoh: Kardiologi Hewan">
                            </div>
                            <div class="form-group">
                                <label>Pengalaman (tahun)</label>
                                <input type="number" name="pengalaman" value="<?php echo $perawat['pengalaman'] ?? 0; ?>" min="0">
                            </div>
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