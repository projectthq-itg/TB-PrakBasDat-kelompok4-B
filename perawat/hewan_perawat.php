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
$notifUnread = countNotifikasiPerawatUnread($perawat_id);

// Ambil semua hewan yang pernah ditangani perawat ini
$hewanList = getHewanByPerawat($perawat_id);

function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getHewanByPerawat($perawat_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT DISTINCT h.*, p.nama_lengkap as nama_pelanggan, 
               COUNT(j.id) as total_konsultasi,
               MAX(j.tanggal) as terakhir_konsultasi
        FROM hewan_peliharaan h
        JOIN janji_temu j ON h.id = j.hewan_id
        JOIN pelanggan p ON h.pelanggan_id = p.id
        WHERE j.perawat_id = ?
        GROUP BY h.id
        ORDER BY terakhir_konsultasi DESC
    ");
    $stmt->execute([$perawat_id]);
    return $stmt->fetchAll();
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
    <title>Hewan Pasien - Perawat</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .hewan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .hewan-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform .3s var(--ease), box-shadow .3s var(--ease);
            border: 1px solid rgba(22,36,27,.06);
        }

        .hewan-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }

        .hewan-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 12px;
            border: 3px solid var(--gold);
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }

        .hewan-card h3 {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            color: var(--ink);
            text-align: center;
            margin-bottom: 4px;
        }

        .hewan-card .hewan-detail {
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 12px;
        }

        .hewan-card .hewan-detail span {
            display: inline-block;
            background: var(--cream);
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 500;
        }

        .hewan-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(22,36,27,.06);
        }

        .hewan-stats .stat-item {
            text-align: center;
        }

        .hewan-stats .stat-item .number {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
        }

        .hewan-stats .stat-item .label {
            font-size: 11px;
            color: var(--text-muted);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-family: 'Fraunces', serif;
            font-size: 22px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--text-muted);
        }

        @media (max-width: 480px) {
            .hewan-grid {
                grid-template-columns: 1fr;
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
                <a href="profil.php"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="janji_temu.php"><span class="nav-icon">📅</span><span>Janji Temu</span></a>
                <a href="hewan_perawat.php" class="active"><span class="nav-icon">🐾</span><span>Hewan Pasien</span></a>
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
                    <h2>🐾 Hewan Pasien</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div style="margin-bottom:20px;color:var(--text-muted);font-size:14px;">
                Total hewan pasien: <strong><?php echo count($hewanList); ?></strong>
            </div>

            <div class="hewan-grid">
                <?php if (empty($hewanList)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🐕</div>
                        <h3>Belum Ada Hewan Pasien</h3>
                        <p>Belum ada hewan yang melakukan konsultasi dengan Anda.</p>
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
                            <div class="hewan-detail">
                                <?php echo htmlspecialchars($hewan['jenis_hewan']); ?> 
                                <?php echo !empty($hewan['ras']) ? '• ' . htmlspecialchars($hewan['ras']) : ''; ?>
                            </div>
                            <div class="hewan-detail">
                                <span>👤 <?php echo htmlspecialchars($hewan['nama_pelanggan']); ?></span>
                            </div>
                            <div class="hewan-stats">
                                <div class="stat-item">
                                    <div class="number"><?php echo $hewan['total_konsultasi']; ?></div>
                                    <div class="label">Total Konsultasi</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number" style="font-size:14px;">
                                        <?php echo $hewan['terakhir_konsultasi'] ? date('d/m/Y', strtotime($hewan['terakhir_konsultasi'])) : '-'; ?>
                                    </div>
                                    <div class="label">Terakhir</div>
                                </div>
                            </div>
                            <div style="text-align:center;margin-top:12px;">
                                <span style="font-size:12px;color:var(--text-muted);">
                                    <?php echo $hewan['umur'] ? $hewan['umur'] . ' tahun' : ''; ?>
                                    <?php echo $hewan['berat'] ? '• ' . $hewan['berat'] . ' kg' : ''; ?>
                                </span>
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