<?php
require_once '../config.php';

// Gunakan requireAdmin
requireAdmin();

$user_id = $_SESSION['user_id'];

// Statistik Global - Gunakan fungsi dari config.php
$totalUsers = countAllUsers();
$totalPelanggan = countAllPelanggan();
$totalPerawat = countAllPerawat();
$totalHewan = countAllHewan();
$totalJanji = countAllJanji();

// Gunakan fungsi countJanjiByStatus dari config.php
$janjiPending = countJanjiByStatusGlobal('pending');
$janjiConfirmed = countJanjiByStatusGlobal('confirmed');
$janjiSelesai = countJanjiByStatusGlobal('selesai');
$janjiBatal = countJanjiByStatusGlobal('batal');

$totalPendapatan = getTotalPendapatan();
$pendapatanBulanIni = getPendapatanBulanIni();
$pendapatanBulanLalu = getPendapatanBulanLalu();
$persentaseKenaikan = $pendapatanBulanLalu > 0 ? round((($pendapatanBulanIni - $pendapatanBulanLalu) / $pendapatanBulanLalu) * 100, 1) : 0;

// Data untuk grafik
$statistikBulanan = getStatistikBulananAdmin(date('Y'));
$layananTerpopuler = getLayananTerpopulerAdmin();
$perawatTerbaik = getPerawatTerbaik();
$janjiTerbaru = getJanjiTerbaruAdmin(5);

// ============================================
// FUNGSI UNTUK ADMIN (TIDAK ADA DI CONFIG.PHP)
// ============================================

function countAllUsers() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function countAllPelanggan() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pelanggan");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function countAllPerawat() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM perawat WHERE status = 'aktif'");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function countAllHewan() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM hewan_peliharaan");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function countAllJanji() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM janji_temu");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

// Fungsi countJanjiByStatus GLOBAL - untuk admin
function countJanjiByStatusGlobal($status) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM janji_temu WHERE status = ?");
    $stmt->execute([$status]);
    return $stmt->fetch()['total'];
}

function getTotalPendapatan() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.harga), 0) as total 
        FROM janji_temu j 
        JOIN layanan l ON j.layanan_id = l.id 
        WHERE j.status = 'selesai'
    ");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function getPendapatanBulanIni() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.harga), 0) as total 
        FROM janji_temu j 
        JOIN layanan l ON j.layanan_id = l.id 
        WHERE j.status = 'selesai' 
        AND MONTH(j.tanggal) = MONTH(CURRENT_DATE()) 
        AND YEAR(j.tanggal) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function getPendapatanBulanLalu() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.harga), 0) as total 
        FROM janji_temu j 
        JOIN layanan l ON j.layanan_id = l.id 
        WHERE j.status = 'selesai' 
        AND MONTH(j.tanggal) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        AND YEAR(j.tanggal) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
    ");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function getStatistikBulananAdmin($tahun) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(tanggal) as bulan,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
        FROM janji_temu
        WHERE YEAR(tanggal) = ?
        GROUP BY MONTH(tanggal)
        ORDER BY bulan ASC
    ");
    $stmt->execute([$tahun]);
    return $stmt->fetchAll();
}

function getLayananTerpopulerAdmin() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.nama_layanan, COUNT(j.id) as total, l.harga, l.deskripsi
        FROM janji_temu j
        JOIN layanan l ON j.layanan_id = l.id
        WHERE j.status IN ('confirmed', 'selesai')
        GROUP BY j.layanan_id
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getPerawatTerbaik() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nama_lengkap, 
            p.keahlian,
            p.foto_profile,
            COUNT(j.id) as total_janji,
            SUM(CASE WHEN j.status = 'selesai' THEN 1 ELSE 0 END) as selesai
        FROM perawat p
        LEFT JOIN janji_temu j ON p.id = j.perawat_id
        WHERE p.status = 'aktif'
        GROUP BY p.id
        ORDER BY selesai DESC
        LIMIT 5
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getJanjiTerbaruAdmin($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT j.*, 
               h.nama_hewan, 
               l.nama_layanan,
               p.nama_lengkap as nama_pelanggan,
               pw.nama_lengkap as nama_perawat
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        LEFT JOIN perawat pw ON j.perawat_id = pw.id
        ORDER BY j.created_at DESC
        LIMIT ?
    ");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Klinik Hewan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: var(--shadow-sm);
            transition: transform .3s var(--ease), box-shadow .3s var(--ease);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            font-size: 32px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cream);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-family: 'Fraunces', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }

        .stat-info p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-info .trend {
            font-size: 11px;
            font-weight: 600;
        }

        .stat-info .trend.up { color: #27ae60; }
        .stat-info .trend.down { color: #e74c3c; }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 28px;
        }

        .content-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid rgba(22,36,27,.06);
        }

        .card-header h3 {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
        }

        .link-more {
            color: var(--gold-dark);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color .3s;
        }

        .link-more:hover {
            color: var(--gold);
        }

        .card-body {
            padding: 16px 24px 24px;
            max-height: 400px;
            overflow-y: auto;
        }

        .card-body::-webkit-scrollbar {
            width: 4px;
        }
        .card-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .card-body::-webkit-scrollbar-thumb {
            background: var(--sage-light);
            border-radius: 4px;
        }

        .appointment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .appointment-info strong {
            font-size: 15px;
            color: var(--ink);
        }

        .appointment-info span {
            font-size: 13px;
            color: var(--text-muted);
        }

        .appointment-info small {
            font-size: 12px;
            color: var(--text-muted);
            opacity: .7;
        }

        .status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
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

        .progress-bar {
            width: 100%;
            height: 6px;
            background: var(--cream);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-bar .fill {
            height: 100%;
            background: var(--gold);
            border-radius: 3px;
            transition: width .6s var(--ease);
        }

        .layanan-item, .perawat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .layanan-item .name, .perawat-item .name {
            color: var(--text);
            font-size: 14px;
        }

        .layanan-item .count, .perawat-item .count {
            font-weight: 600;
            color: var(--ink);
            background: var(--cream);
            padding: 0 12px;
            border-radius: 12px;
            font-size: 13px;
        }

        .layanan-item .price {
            color: var(--gold-dark);
            font-weight: 600;
            font-size: 13px;
        }

        .perawat-item .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }

        .perawat-item .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .perawat-item .info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .perawat-item .info .detail {
            display: flex;
            flex-direction: column;
        }

        .perawat-item .info .detail .name {
            font-weight: 600;
            color: var(--ink);
        }

        .perawat-item .info .detail .specialty {
            font-size: 12px;
            color: var(--text-muted);
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .stat-card {
                padding: 16px;
            }
            .stat-info h3 {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .stat-card {
                padding: 14px;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            .stat-info h3 {
                font-size: 18px;
            }
            .stat-info p {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="https://img.pikbest.com/png-images/20241028/cuty-cat-simple-logo-_11020834.png!sw800" alt="Logo" width="40">
                <span>Klinik Hewan</span>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <img src="https://ui-avatars.com/api/?name=Admin&size=100&background=C8A664&color=fff" alt="Foto">
                </div>
                <div class="sidebar-user-info">
                    <h4>Administrator</h4>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> • Admin</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="users.php">
                    <span class="nav-icon">👥</span>
                    <span>Kelola User</span>
                </a>
                <a href="perawat.php">
                    <span class="nav-icon">👨‍⚕️</span>
                    <span>Kelola Perawat</span>
                </a>
                <a href="pelanggan.php">
                    <span class="nav-icon">👤</span>
                    <span>Kelola Pelanggan</span>
                </a>
                <a href="layanan.php">
                    <span class="nav-icon">📋</span>
                    <span>Kelola Layanan</span>
                </a>
                <a href="janji.php">
                    <span class="nav-icon">📅</span>
                    <span>Kelola Janji</span>
                </a>
                <a href="laporan.php">
                    <span class="nav-icon">📄</span>
                    <span>Laporan</span>
                </a>
                <a href="pengaturan.php">
                    <span class="nav-icon">⚙️</span>
                    <span>Pengaturan</span>
                </a>
                <a href="../logout.php" class="logout">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>Dashboard Admin</h2>
                </div>
                <div class="topbar-right">
                    <span class="greeting">👋 Selamat datang, Admin</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='users.php'">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Total Pengguna</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='pelanggan.php'">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <h3><?php echo $totalPelanggan; ?></h3>
                        <p>Pelanggan</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='perawat.php'">
                    <div class="stat-icon">👨‍⚕️</div>
                    <div class="stat-info">
                        <h3><?php echo $totalPerawat; ?></h3>
                        <p>Perawat Aktif</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='janji.php'">
                    <div class="stat-icon">🐾</div>
                    <div class="stat-info">
                        <h3><?php echo $totalHewan; ?></h3>
                        <p>Hewan Peliharaan</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='janji.php'">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo $totalJanji; ?></h3>
                        <p>Total Janji</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='janji.php?status=pending'">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $janjiPending; ?></h3>
                        <p>Menunggu Konfirmasi</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='laporan.php'">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?php echo formatRupiah($totalPendapatan); ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='laporan.php'">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <h3><?php echo formatRupiah($pendapatanBulanIni); ?></h3>
                        <p>Pendapatan Bulan Ini</p>
                        <?php if ($persentaseKenaikan != 0): ?>
                            <span class="trend <?php echo $persentaseKenaikan > 0 ? 'up' : 'down'; ?>">
                                <?php echo $persentaseKenaikan > 0 ? '↑' : '↓'; ?> <?php echo abs($persentaseKenaikan); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Janji Terbaru & Layanan Terpopuler -->
                <div>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📋 Janji Temu Terbaru</h3>
                            <a href="janji.php" class="link-more">Lihat Semua →</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($janjiTerbaru)): ?>
                                <div style="text-align:center;padding:30px 0;color:var(--text-muted);">
                                    <p>Belum ada janji temu</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($janjiTerbaru as $janji): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-info">
                                            <strong><?php echo htmlspecialchars($janji['nama_hewan'] ?? 'Hewan'); ?></strong>
                                            <span><?php echo htmlspecialchars($janji['nama_pelanggan'] ?? 'Pelanggan'); ?> • <?php echo htmlspecialchars($janji['nama_layanan'] ?? 'Layanan'); ?></span>
                                            <small><?php echo date('d/m/Y', strtotime($janji['tanggal'])); ?> • <?php echo substr($janji['waktu'], 0, 5); ?> • <?php echo htmlspecialchars($janji['nama_perawat'] ?? 'Perawat'); ?></small>
                                        </div>
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
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-card" style="margin-top:20px;">
                        <div class="card-header">
                            <h3>🏆 Layanan Terpopuler</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($layananTerpopuler)): ?>
                                <div style="text-align:center;padding:20px 0;color:var(--text-muted);">
                                    <p>Belum ada data</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($layananTerpopuler as $layanan): ?>
                                    <div class="layanan-item">
                                        <div>
                                            <div class="name"><?php echo htmlspecialchars($layanan['nama_layanan']); ?></div>
                                            <div class="price"><?php echo formatRupiah($layanan['harga']); ?></div>
                                        </div>
                                        <div class="count"><?php echo $layanan['total']; ?>x</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Perawat Terbaik & Statistik -->
                <div>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>⭐ Perawat Terbaik</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($perawatTerbaik)): ?>
                                <div style="text-align:center;padding:20px 0;color:var(--text-muted);">
                                    <p>Belum ada data</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($perawatTerbaik as $perawat): ?>
                                    <div class="perawat-item">
                                        <div class="info">
                                            <div class="avatar">
                                                <img src="<?php echo !empty($perawat['foto_profile']) ? '../' . $perawat['foto_profile'] : 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=100&background=C8A664&color=fff'; ?>" alt="Foto">
                                            </div>
                                            <div class="detail">
                                                <span class="name"><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></span>
                                                <span class="specialty"><?php echo htmlspecialchars($perawat['keahlian'] ?? 'Umum'); ?></span>
                                            </div>
                                        </div>
                                        <div class="count">✅ <?php echo $perawat['selesai']; ?> selesai</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-card" style="margin-top:20px;">
                        <div class="card-header">
                            <h3>📊 Statistik Bulanan <?php echo date('Y'); ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($statistikBulanan)): ?>
                                <div style="text-align:center;padding:20px 0;color:var(--text-muted);">
                                    <p>Belum ada data</p>
                                </div>
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