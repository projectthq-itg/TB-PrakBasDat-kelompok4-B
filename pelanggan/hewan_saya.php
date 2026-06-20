<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$hewanList = getHewanByPelangganId($pelanggan_id);
$notifUnread = countNotifikasiUnread($pelanggan_id);

// Handle hapus hewan
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM hewan_peliharaan WHERE id = ? AND pelanggan_id = ?");
    if ($stmt->execute([$id, $pelanggan_id])) {
        addNotifikasi($pelanggan_id, 'Hewan Dihapus', "Hewan peliharaan telah dihapus dari daftar.", 'warning');
        header('Location: hewan_saya.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hewan Saya - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .hewan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }

        .hewan-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform .3s var(--ease), box-shadow .3s var(--ease);
            border: 1px solid rgba(22,36,27,.06);
        }

        .hewan-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }

        .hewan-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 12px;
            border: 3px solid var(--gold);
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        .hewan-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hewan-card h3 {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .hewan-card .hewan-detail {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .hewan-card .hewan-detail span {
            display: inline-block;
            background: var(--cream);
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 500;
        }

        .hewan-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 14px;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all .3s var(--ease);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: var(--cream);
            color: var(--ink);
        }

        .btn-edit:hover {
            background: var(--gold-soft);
        }

        .btn-delete {
            background: #FDEAEA;
            color: #B23B3B;
        }

        .btn-delete:hover {
            background: #F5C6CB;
        }

        .btn-add {
            padding: 12px 28px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .3s var(--ease);
        }

        .btn-add:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .empty-hewan {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }

        .empty-hewan .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-hewan h3 {
            font-family: 'Fraunces', serif;
            font-size: 22px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .empty-hewan p {
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        @media (max-width: 480px) {
            .hewan-grid {
                grid-template-columns: 1fr 1fr;
            }
            .hewan-card {
                padding: 14px;
            }
            .hewan-avatar {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }
            .header-actions {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            .btn-add {
                justify-content: center;
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
                <a href="hewan_saya.php" class="active"><span class="nav-icon">🐾</span><span>Hewan Saya</span></a>
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
                    <h2>🐾 Hewan Saya</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Hewan berhasil dihapus!</div>
            <?php endif; ?>

            <div class="header-actions">
                <p style="color:var(--text-muted);">Total: <?php echo count($hewanList); ?> hewan peliharaan</p>
                <a href="hewan_tambah.php" class="btn-add">➕ Tambah Hewan</a>
            </div>

            <div class="hewan-grid">
                <?php if (empty($hewanList)): ?>
                    <div class="empty-hewan">
                        <div class="empty-icon">🐕</div>
                        <h3>Belum Ada Hewan Peliharaan</h3>
                        <p>Tambahkan hewan peliharaan Anda untuk mulai membuat janji temu.</p>
                        <a href="hewan_tambah.php" class="btn-add">➕ Tambah Hewan</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($hewanList as $hewan): ?>
                        <div class="hewan-card">
                            <div class="hewan-avatar">
                                <?php if (!empty($hewan['foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($hewan['foto']); ?>" alt="<?php echo htmlspecialchars($hewan['nama_hewan']); ?>">
                                <?php else: ?>
                                    <?php echo $hewan['jenis_hewan'] === 'Kucing' ? '🐱' : ($hewan['jenis_hewan'] === 'Anjing' ? '🐶' : '🐾'); ?>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($hewan['nama_hewan']); ?></h3>
                            <div class="hewan-detail"><?php echo htmlspecialchars($hewan['jenis_hewan']); ?> <?php echo !empty($hewan['ras']) ? '• ' . htmlspecialchars($hewan['ras']) : ''; ?></div>
                            <div class="hewan-detail"><span><?php echo $hewan['umur'] ?? '?'; ?> tahun</span> • <span><?php echo $hewan['berat'] ?? '?'; ?> kg</span></div>
                            <div class="hewan-actions">
                                <a href="hewan_edit.php?id=<?php echo $hewan['id']; ?>" class="btn-action btn-edit">✏️ Edit</a>
                                <a href="hewan_saya.php?hapus=<?php echo $hewan['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus hewan ini?')">🗑️ Hapus</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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