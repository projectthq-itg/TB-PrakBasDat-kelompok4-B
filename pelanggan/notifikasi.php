<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$notifUnread = countNotifikasiUnread($pelanggan_id);

// Tandai semua notifikasi sudah dibaca
if (isset($_GET['read_all'])) {
    $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = 1 WHERE pelanggan_id = ?");
    $stmt->execute([$pelanggan_id]);
    header('Location: notifikasi.php');
    exit;
}

// Tandai satu notifikasi sudah dibaca
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = 1 WHERE id = ? AND pelanggan_id = ?");
    $stmt->execute([$_GET['read'], $pelanggan_id]);
    header('Location: notifikasi.php');
    exit;
}

// Hapus notifikasi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM notifikasi WHERE id = ? AND pelanggan_id = ?");
    $stmt->execute([$_GET['delete'], $pelanggan_id]);
    header('Location: notifikasi.php');
    exit;
}

$notifikasi = getNotifikasi($pelanggan_id, 100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notif-item {
            display: flex;
            gap: 16px;
            padding: 16px 20px;
            background: var(--paper);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--sage-light);
            transition: transform .3s var(--ease), box-shadow .3s var(--ease);
            align-items: flex-start;
        }

        .notif-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .notif-item.unread {
            border-left-color: var(--gold);
            background: rgba(247,243,234,.4);
        }

        .notif-item .notif-icon {
            font-size: 24px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notif-item .notif-content {
            flex: 1;
        }

        .notif-item .notif-content h4 {
            font-size: 15px;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .notif-item .notif-content p {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .notif-item .notif-content small {
            font-size: 12px;
            color: var(--text-muted);
            opacity: .6;
        }

        .notif-item .notif-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .notif-item .notif-actions a {
            font-size: 18px;
            text-decoration: none;
            opacity: .4;
            transition: opacity .3s;
            padding: 4px;
        }

        .notif-item .notif-actions a:hover {
            opacity: 1;
        }

        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .notif-header h3 {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            color: var(--ink);
        }

        .notif-header a {
            font-size: 13px;
            color: var(--gold-dark);
            text-decoration: none;
            font-weight: 500;
            transition: color .3s;
        }

        .notif-header a:hover {
            color: var(--gold);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        @media (max-width: 480px) {
            .notif-item {
                flex-direction: column;
                padding: 14px 16px;
            }
            .notif-item .notif-actions {
                align-self: flex-end;
            }
            .notif-header {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
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
                <a href="profil.php"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="hewan_saya.php"><span class="nav-icon">🐾</span><span>Hewan Saya</span></a>
                <a href="buat_janji.php"><span class="nav-icon">📅</span><span>Buat Janji</span></a>
                <a href="riwayat_janji.php"><span class="nav-icon">📋</span><span>Riwayat Janji</span></a>
                <a href="notifikasi.php" class="active"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>🔔 Notifikasi</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div class="notif-header">
                <h3>Semua Notifikasi</h3>
                <div style="display:flex;gap:12px;">
                    <?php if ($notifUnread > 0): ?>
                        <a href="notifikasi.php?read_all=1">✅ Tandai semua sudah dibaca</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($notifikasi)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔔</div>
                    <h3>Belum Ada Notifikasi</h3>
                    <p>Semua notifikasi akan muncul di sini</p>
                </div>
            <?php else: ?>
                <div class="notif-list">
                    <?php foreach ($notifikasi as $notif): ?>
                        <div class="notif-item <?php echo $notif['dibaca'] ? 'read' : 'unread'; ?>">
                            <div class="notif-icon">
                                <?php echo $notif['jenis'] === 'success' ? '✅' : ($notif['jenis'] === 'warning' ? '⚠️' : ($notif['jenis'] === 'danger' ? '❌' : 'ℹ️')); ?>
                            </div>
                            <div class="notif-content">
                                <h4><?php echo htmlspecialchars($notif['judul']); ?></h4>
                                <p><?php echo htmlspecialchars($notif['pesan']); ?></p>
                                <small><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                            </div>
                            <div class="notif-actions">
                                <?php if (!$notif['dibaca']): ?>
                                    <a href="notifikasi.php?read=<?php echo $notif['id']; ?>" title="Tandai sudah dibaca">✅</a>
                                <?php endif; ?>
                                <a href="notifikasi.php?delete=<?php echo $notif['id']; ?>" title="Hapus" onclick="return confirm('Yakin ingin menghapus notifikasi ini?')">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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