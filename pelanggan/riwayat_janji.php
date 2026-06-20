<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$notifUnread = countNotifikasiUnread($pelanggan_id);

$statusFilter = $_GET['status'] ?? 'semua';
$janjiList = getJanjiByPelangganId($pelanggan_id);

// Filter by status
if ($statusFilter !== 'semua') {
    $janjiList = array_filter($janjiList, function($j) use ($statusFilter) {
        return $j['status'] === $statusFilter;
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Janji - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all .3s var(--ease);
            background: var(--cream);
            color: var(--text-muted);
            border: 1px solid transparent;
        }

        .filter-btn:hover {
            background: var(--gold-soft);
            color: var(--ink);
        }

        .filter-btn.active {
            background: var(--gold);
            color: var(--ink);
            border-color: var(--gold);
        }

        .janji-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--paper);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .janji-table th {
            background: var(--cream);
            padding: 14px 18px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            border-bottom: 2px solid rgba(22,36,27,.06);
        }

        .janji-table td {
            padding: 14px 18px;
            font-size: 14px;
            border-bottom: 1px solid rgba(22,36,27,.04);
            vertical-align: middle;
        }

        .janji-table tr:hover td {
            background: rgba(247,243,234,.3);
        }

        .janji-table .status {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
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

        .empty-state a {
            color: var(--gold-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .empty-state a:hover {
            color: var(--gold);
        }

        @media (max-width: 768px) {
            .janji-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .janji-table th,
            .janji-table td {
                padding: 10px 14px;
                font-size: 13px;
            }
            .filter-bar {
                gap: 4px;
            }
            .filter-btn {
                padding: 6px 14px;
                font-size: 12px;
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
                <a href="riwayat_janji.php" class="active"><span class="nav-icon">📋</span><span>Riwayat Janji</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>📋 Riwayat Janji Temu</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div class="filter-bar">
                <a href="riwayat_janji.php?status=semua" class="filter-btn <?php echo $statusFilter === 'semua' ? 'active' : ''; ?>">Semua</a>
                <a href="riwayat_janji.php?status=menunggu" class="filter-btn <?php echo $statusFilter === 'menunggu' ? 'active' : ''; ?>">Menunggu</a>
                <a href="riwayat_janji.php?status=dikonfirmasi" class="filter-btn <?php echo $statusFilter === 'dikonfirmasi' ? 'active' : ''; ?>">Dikonfirmasi</a>
                <a href="riwayat_janji.php?status=selesai" class="filter-btn <?php echo $statusFilter === 'selesai' ? 'active' : ''; ?>">Selesai</a>
                <a href="riwayat_janji.php?status=dibatalkan" class="filter-btn <?php echo $statusFilter === 'dibatalkan' ? 'active' : ''; ?>">Dibatalkan</a>
            </div>

            <?php if (empty($janjiList)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h3>Belum Ada Janji Temu</h3>
                    <p>Anda belum memiliki riwayat janji temu. <a href="buat_janji.php">Buat janji sekarang</a></p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="janji-table">
                        <thead>
                            <tr>
                                <th>Hewan</th>
                                <th>Layanan</th>
                                <th>Perawat</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($janjiList as $janji): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($janji['nama_hewan']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($janji['nama_layanan']); ?></td>
                                    <td><?php echo htmlspecialchars($janji['nama_perawat']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($janji['tanggal'])); ?></td>
                                    <td><?php echo substr($janji['waktu'], 0, 5); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $janji['status']; ?>">
                                            <?php echo ucfirst($janji['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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