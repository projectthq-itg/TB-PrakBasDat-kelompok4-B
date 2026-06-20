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
$statusFilter = $_GET['status'] ?? 'semua';
$search = $_GET['search'] ?? '';

// Ambil data janji
$janjiList = getJanjiByPerawat($perawat_id, $statusFilter, $search);
$notifUnread = countNotifikasiPerawatUnread($perawat_id);

// Fungsi getPerawatByUserId
function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getJanjiByPerawat($perawat_id, $status = 'semua', $search = '') {
    global $pdo;
    $sql = "
        SELECT j.*, h.nama_hewan, h.jenis_hewan, h.umur, h.ras,
               l.nama_layanan, l.harga,
               p.nama_lengkap as nama_pelanggan, p.no_telepon
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        WHERE j.perawat_id = ?
    ";
    
    $params = [$perawat_id];
    
    if ($status !== 'semua') {
        $sql .= " AND j.status = ?";
        $params[] = $status;
    }
    
    if (!empty($search)) {
        $sql .= " AND (h.nama_hewan LIKE ? OR p.nama_lengkap LIKE ? OR l.nama_layanan LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY j.tanggal DESC, j.waktu DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    <title>Janji Temu - Perawat</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
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

        .search-box {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .search-box input {
            padding: 8px 16px;
            border: 1.5px solid var(--sage-light);
            border-radius: 20px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: var(--cream);
            color: var(--text);
            min-width: 200px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--gold);
        }

        .search-box button {
            padding: 8px 20px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all .3s var(--ease);
        }

        .search-box button:hover {
            background: var(--gold-dark);
        }

        .table-wrapper {
            overflow-x: auto;
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
            white-space: nowrap;
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

        .status-pending {
            background: #FFF6E5;
            color: #8A6D1F;
        }

        .status-confirmed {
            background: #E5F0FF;
            color: #1A5A8A;
        }

        .status-selesai {
            background: #E5F5E5;
            color: #1A6A3A;
        }

        .status-batal {
            background: #FDEAEA;
            color: #B23B3B;
        }

        .btn-action {
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all .3s var(--ease);
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-detail {
            background: var(--cream);
            color: var(--ink);
        }

        .btn-detail:hover {
            background: var(--gold-soft);
        }

        .btn-confirm {
            background: #E5F0FF;
            color: #1A5A8A;
        }

        .btn-confirm:hover {
            background: #B8D4F0;
        }

        .btn-selesai {
            background: #E5F5E5;
            color: #1A6A3A;
        }

        .btn-selesai:hover {
            background: #B8D9B8;
        }

        .btn-batal {
            background: #FDEAEA;
            color: #B23B3B;
        }

        .btn-batal:hover {
            background: #F5C6CB;
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

        .action-group {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box {
                margin-left: 0;
            }
            .search-box input {
                min-width: auto;
                flex: 1;
            }
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
            .action-group {
                flex-direction: column;
                gap: 4px;
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
                <a href="janji_temu.php" class="active"><span class="nav-icon">📅</span><span>Janji Temu</span></a>
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
                    <h2>📅 Janji Temu</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div class="filter-bar">
                <a href="janji_temu.php?status=semua" class="filter-btn <?php echo $statusFilter === 'semua' ? 'active' : ''; ?>">Semua</a>
                <a href="janji_temu.php?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">⏳ Menunggu</a>
                <a href="janji_temu.php?status=confirmed" class="filter-btn <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">✅ Dikonfirmasi</a>
                <a href="janji_temu.php?status=selesai" class="filter-btn <?php echo $statusFilter === 'selesai' ? 'active' : ''; ?>">✔️ Selesai</a>
                <a href="janji_temu.php?status=batal" class="filter-btn <?php echo $statusFilter === 'batal' ? 'active' : ''; ?>">❌ Dibatalkan</a>
                
                <form class="search-box" method="GET">
                    <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                    <input type="text" name="search" placeholder="🔍 Cari hewan/pelanggan..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Cari</button>
                </form>
            </div>

            <?php if (empty($janjiList)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h3>Tidak Ada Janji Temu</h3>
                    <p>Belum ada janji temu yang terdaftar.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="janji-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pelanggan</th>
                                <th>Hewan</th>
                                <th>Layanan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($janjiList as $janji): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($janji['nama_pelanggan'] ?? 'Unknown'); ?></strong>
                                        <br>
                                        <small style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($janji['no_telepon'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($janji['nama_hewan'] ?? 'Hewan'); ?></strong>
                                        <br>
                                        <small style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($janji['jenis_hewan'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($janji['nama_layanan'] ?? 'Layanan'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($janji['tanggal'])); ?></td>
                                    <td><?php echo substr($janji['waktu'], 0, 5); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $janji['status']; ?>">
                                            <?php 
                                                $statusMap = [
                                                    'pending' => 'Menunggu',
                                                    'confirmed' => 'Dikonfirmasi',
                                                    'selesai' => 'Selesai',
                                                    'batal' => 'Dibatalkan'
                                                ];
                                                echo $statusMap[$janji['status']] ?? ucfirst($janji['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="janji_detail.php?id=<?php echo $janji['id']; ?>" class="btn-action btn-detail">📋 Detail</a>
                                            
                                            <?php if ($janji['status'] === 'pending'): ?>
                                                <a href="janji_update_status.php?id=<?php echo $janji['id']; ?>&status=confirmed" class="btn-action btn-confirm" onclick="return confirm('Konfirmasi janji ini?')">✅ Konfirmasi</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($janji['status'] === 'confirmed'): ?>
                                                <a href="janji_update_status.php?id=<?php echo $janji['id']; ?>&status=selesai" class="btn-action btn-selesai" onclick="return confirm('Tandai janji ini selesai?')">✔️ Selesai</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($janji['status'] === 'pending' || $janji['status'] === 'confirmed'): ?>
                                                <a href="janji_update_status.php?id=<?php echo $janji['id']; ?>&status=batal" class="btn-action btn-batal" onclick="return confirm('Batalkan janji ini?')">❌ Batal</a>
                                            <?php endif; ?>
                                        </div>
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