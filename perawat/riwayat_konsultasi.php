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

// Ambil semua konsultasi selesai
$konsultasiList = getRiwayatKonsultasi($perawat_id);

function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getRiwayatKonsultasi($perawat_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT j.*, 
               h.nama_hewan, h.jenis_hewan, h.ras,
               l.nama_layanan,
               p.nama_lengkap as nama_pelanggan
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        WHERE j.perawat_id = ? AND j.status = 'selesai'
        ORDER BY j.tanggal DESC, j.waktu DESC
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
    <title>Riwayat Konsultasi - Perawat</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .table-wrapper {
            overflow-x: auto;
        }

        .konsultasi-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--paper);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .konsultasi-table th {
            background: var(--cream);
            padding: 14px 18px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            border-bottom: 2px solid rgba(22,36,27,.06);
            white-space: nowrap;
        }

        .konsultasi-table td {
            padding: 14px 18px;
            font-size: 14px;
            border-bottom: 1px solid rgba(22,36,27,.04);
            vertical-align: middle;
        }

        .konsultasi-table tr:hover td {
            background: rgba(247,243,234,.3);
        }

        .konsultasi-table .status {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            background: #E5F5E5;
            color: #1A6A3A;
        }

        .btn-detail {
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all .3s var(--ease);
            display: inline-block;
            background: var(--cream);
            color: var(--ink);
        }

        .btn-detail:hover {
            background: var(--gold-soft);
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

        @media (max-width: 768px) {
            .konsultasi-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .konsultasi-table th,
            .konsultasi-table td {
                padding: 10px 14px;
                font-size: 13px;
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
                <a href="hewan_perawat.php"><span class="nav-icon">🐾</span><span>Hewan Pasien</span></a>
                <a href="riwayat_konsultasi.php" class="active"><span class="nav-icon">📋</span><span>Riwayat Konsultasi</span></a>
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
                    <h2>📋 Riwayat Konsultasi</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div style="margin-bottom:20px;color:var(--text-muted);font-size:14px;">
                Total konsultasi selesai: <strong><?php echo count($konsultasiList); ?></strong>
            </div>

            <?php if (empty($konsultasiList)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>Belum Ada Riwayat Konsultasi</h3>
                    <p>Belum ada konsultasi yang selesai.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="konsultasi-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Hewan</th>
                                <th>Layanan</th>
                                <th>Keluhan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($konsultasiList as $konsultasi): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($konsultasi['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($konsultasi['nama_pelanggan']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($konsultasi['nama_hewan']); ?></strong>
                                        <br>
                                        <small style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($konsultasi['jenis_hewan'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($konsultasi['nama_layanan']); ?></td>
                                    <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo htmlspecialchars(substr($konsultasi['keluhan'] ?? '', 0, 50)); ?>
                                        <?php echo strlen($konsultasi['keluhan'] ?? '') > 50 ? '...' : ''; ?>
                                    </td>
                                    <td>
                                        <span class="status">✔️ Selesai</span>
                                    </td>
                                    <td>
                                        <a href="janji_detail.php?id=<?php echo $konsultasi['id']; ?>" class="btn-detail">📋 Detail</a>
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