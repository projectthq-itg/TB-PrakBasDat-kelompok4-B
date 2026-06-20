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

// Filter tahun
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('m');

// Data statistik
$totalJanji = countJanjiByPerawat($perawat_id, $tahun, $bulan);
$totalJanjiBulan = countJanjiByPerawatBulan($perawat_id, $tahun, $bulan);
$layananTerpopuler = getLayananTerpopuler($perawat_id, $tahun);
$statistikBulanan = getStatistikBulanan($perawat_id, $tahun);

function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function countJanjiByPerawat($perawat_id, $tahun, $bulan) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM janji_temu WHERE perawat_id = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?");
    $stmt->execute([$perawat_id, $tahun, $bulan]);
    return $stmt->fetch()['total'];
}

function countJanjiByPerawatBulan($perawat_id, $tahun, $bulan) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as total 
        FROM janji_temu 
        WHERE perawat_id = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        GROUP BY status
    ");
    $stmt->execute([$perawat_id, $tahun, $bulan]);
    $results = $stmt->fetchAll();
    $data = ['pending' => 0, 'confirmed' => 0, 'selesai' => 0, 'batal' => 0];
    foreach ($results as $row) {
        $data[$row['status']] = $row['total'];
    }
    return $data;
}

function getLayananTerpopuler($perawat_id, $tahun) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.nama_layanan, COUNT(j.id) as total
        FROM janji_temu j
        JOIN layanan l ON j.layanan_id = l.id
        WHERE j.perawat_id = ? AND YEAR(j.tanggal) = ?
        GROUP BY j.layanan_id
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute([$perawat_id, $tahun]);
    return $stmt->fetchAll();
}

function getStatistikBulanan($perawat_id, $tahun) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(tanggal) as bulan,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
        FROM janji_temu
        WHERE perawat_id = ? AND YEAR(tanggal) = ?
        GROUP BY MONTH(tanggal)
        ORDER BY bulan ASC
    ");
    $stmt->execute([$perawat_id, $tahun]);
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
    <title>Laporan - Perawat</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .report-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .report-card h3 {
            font-family: 'Fraunces', serif;
            font-size: 16px;
            color: var(--ink);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(22,36,27,.06);
        }

        .report-card .number {
            font-family: 'Fraunces', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--gold-dark);
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-bar select {
            padding: 10px 16px;
            border: 1.5px solid var(--sage-light);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: var(--cream);
            color: var(--text);
        }

        .filter-bar select:focus {
            outline: none;
            border-color: var(--gold);
        }

        .filter-bar button {
            padding: 10px 24px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all .3s var(--ease);
        }

        .filter-bar button:hover {
            background: var(--gold-dark);
        }

        .stat-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .stat-row .label {
            color: var(--text-muted);
            font-size: 14px;
        }

        .stat-row .value {
            font-weight: 600;
            color: var(--ink);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--cream);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-bar .fill {
            height: 100%;
            background: var(--gold);
            border-radius: 4px;
            transition: width .6s var(--ease);
        }

        .layanan-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .layanan-item .name {
            color: var(--text);
            font-size: 14px;
        }

        .layanan-item .count {
            font-weight: 600;
            color: var(--ink);
            background: var(--cream);
            padding: 0 12px;
            border-radius: 12px;
        }

        @media (max-width: 480px) {
            .report-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar select,
            .filter-bar button {
                width: 100%;
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
                <a href="riwayat_konsultasi.php"><span class="nav-icon">📋</span><span>Riwayat Konsultasi</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="laporan.php" class="active"><span class="nav-icon">📄</span><span>Laporan</span></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>📄 Laporan</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <form class="filter-bar" method="GET">
                <select name="tahun">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $tahun ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <select name="bulan">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $bulan ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0,0,0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit">Tampilkan</button>
            </form>

            <div class="report-grid">
                <!-- Total Janji -->
                <div class="report-card">
                    <h3>📊 Total Janji</h3>
                    <div class="number"><?php echo $totalJanji; ?></div>
                    <div style="color:var(--text-muted);font-size:14px;margin-top:4px;">
                        Bulan <?php echo date('F', mktime(0,0,0, $bulan, 1)); ?> <?php echo $tahun; ?>
                    </div>
                </div>

                <!-- Statistik Status -->
                <div class="report-card">
                    <h3>📈 Statistik Status</h3>
                    <div class="stat-list">
                        <div class="stat-row">
                            <span class="label">⏳ Menunggu</span>
                            <span class="value"><?php echo $totalJanjiBulan['pending']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">✅ Dikonfirmasi</span>
                            <span class="value"><?php echo $totalJanjiBulan['confirmed']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">✔️ Selesai</span>
                            <span class="value"><?php echo $totalJanjiBulan['selesai']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">❌ Dibatalkan</span>
                            <span class="value"><?php echo $totalJanjiBulan['batal']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Layanan Terpopuler -->
                <div class="report-card">
                    <h3>🏆 Layanan Terpopuler</h3>
                    <?php if (empty($layananTerpopuler)): ?>
                        <div style="color:var(--text-muted);font-size:14px;">Belum ada data</div>
                    <?php else: ?>
                        <?php foreach ($layananTerpopuler as $layanan): ?>
                            <div class="layanan-item">
                                <span class="name"><?php echo htmlspecialchars($layanan['nama_layanan']); ?></span>
                                <span class="count"><?php echo $layanan['total']; ?>x</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grafik Statistik Bulanan -->
            <div class="report-card">
                <h3>📊 Statistik Bulanan <?php echo $tahun; ?></h3>
                <?php if (empty($statistikBulanan)): ?>
                    <div style="color:var(--text-muted);font-size:14px;">Belum ada data untuk tahun ini</div>
                <?php else: ?>
                    <?php 
                        $maxTotal = max(array_column($statistikBulanan, 'total'));
                        $bulanNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                    ?>
                    <?php foreach ($statistikBulanan as $data): ?>
                        <div style="margin-bottom:12px;">
                            <div style="display:flex;justify-content:space-between;font-size:13px;">
                                <span><?php echo $bulanNames[$data['bulan'] - 1]; ?></span>
                                <span><?php echo $data['total']; ?> janji (<?php echo $data['selesai']; ?> selesai)</span>
                            </div>
                            <div class="progress-bar">
                                <div class="fill" style="width: <?php echo $maxTotal > 0 ? ($data['total'] / $maxTotal * 100) : 0; ?>%;"></div>
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