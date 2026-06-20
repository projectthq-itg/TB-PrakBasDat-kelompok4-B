<?php
require_once '../config.php';
requireLogin();

// Cek role perawat atau admin
if (!isPerawat() && !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$perawat = getPerawatByUserId($user_id);

if (!$perawat) {
    // Jika perawat tidak ditemukan, redirect ke login
    header('Location: ../login.php');
    exit;
}

$perawat_id = $perawat['id'];

// Statistik
$totalJanjiMenunggu = countJanjiByPerawatStatus($perawat_id, 'pending');
$totalJanjiDikonfirmasi = countJanjiByPerawatStatus($perawat_id, 'confirmed');
$totalJanjiSelesai = countJanjiByPerawatStatus($perawat_id, 'selesai');
$totalJanjiBatal = countJanjiByPerawatStatus($perawat_id, 'batal');

// Janji hari ini
$janjiHariIni = getJanjiByPerawatToday($perawat_id);

// Janji terbaru
$janjiTerbaru = getJanjiByPerawat($perawat_id, 5);

// Notifikasi untuk perawat
$notifUnread = countNotifikasiPerawatUnread($perawat_id);
$notifikasi = getNotifikasiPerawat($perawat_id, 5);

// Fungsi time ago
function timeAgo($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam yang lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari yang lalu';
    return date('d/m/Y', $timestamp);
}

// Fungsi untuk mendapatkan data perawat berdasarkan user_id
function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Fungsi untuk menghitung janji per status
function countJanjiByPerawatStatus($perawat_id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM janji_temu WHERE perawat_id = ? AND status = ?");
    $stmt->execute([$perawat_id, $status]);
    return $stmt->fetch()['total'];
}

// Fungsi untuk mendapatkan janji hari ini
function getJanjiByPerawatToday($perawat_id) {
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT j.*, h.nama_hewan, h.jenis_hewan, l.nama_layanan, 
               p.nama_lengkap as nama_pelanggan
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        WHERE j.perawat_id = ? AND j.tanggal = ?
        ORDER BY j.waktu ASC
    ");
    $stmt->execute([$perawat_id, $today]);
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan janji perawat
function getJanjiByPerawat($perawat_id, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT j.*, h.nama_hewan, h.jenis_hewan, l.nama_layanan, 
               p.nama_lengkap as nama_pelanggan
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        WHERE j.perawat_id = ?
        ORDER BY j.tanggal DESC, j.waktu DESC
        LIMIT ?
    ");
    $stmt->bindParam(1, $perawat_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk notifikasi perawat
function getNotifikasiPerawat($perawat_id, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM notifikasi_perawat 
        WHERE perawat_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bindParam(1, $perawat_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countNotifikasiPerawatUnread($perawat_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM notifikasi_perawat 
        WHERE perawat_id = ? AND dibaca = 0
    ");
    $stmt->execute([$perawat_id]);
    return $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perawat - Klinik Hewan</title>
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

        .stat-card.pending .stat-icon { background: #FFF6E5; }
        .stat-card.confirmed .stat-icon { background: #E5F0FF; }
        .stat-card.selesai .stat-icon { background: #E5F5E5; }
        .stat-card.batal .stat-icon { background: #FDEAEA; }

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

        .notification-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background: rgba(200,166,100,.06);
            border-radius: var(--radius-sm);
            padding: 14px;
            margin: 0 -14px;
        }

        .notif-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notif-content strong {
            display: block;
            font-size: 14px;
            color: var(--ink);
            margin-bottom: 2px;
        }

        .notif-content p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .notif-content small {
            font-size: 11px;
            color: var(--text-muted);
            opacity: .6;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state p {
            font-size: 14px;
        }

        .empty-state a {
            color: var(--gold-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .empty-state a:hover {
            color: var(--gold);
        }

        .today-badge {
            background: var(--gold);
            color: var(--ink);
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
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
            .appointment-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .status {
                align-self: flex-start;
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
            .card-header {
                padding: 14px 16px;
                flex-wrap: wrap;
                gap: 8px;
            }
            .card-header h3 {
                font-size: 16px;
            }
            .card-body {
                padding: 12px 16px 16px;
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
                    <img src="<?php echo $perawat['foto_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=100&background=C8A664&color=fff'; ?>" alt="Foto">
                </div>
                <div class="sidebar-user-info">
                    <h4><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></h4>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> • Perawat</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="profil.php">
                    <span class="nav-icon">👤</span>
                    <span>Profil Saya</span>
                </a>
                <a href="janji_temu.php">
                    <span class="nav-icon">📅</span>
                    <span>Janji Temu</span>
                    <?php if ($totalJanjiMenunggu > 0): ?>
                        <span class="badge"><?php echo $totalJanjiMenunggu; ?></span>
                    <?php endif; ?>
                </a>
                <a href="hewan_perawat.php">
                    <span class="nav-icon">🐾</span>
                    <span>Hewan Pasien</span>
                </a>
                <a href="riwayat_konsultasi.php">
                    <span class="nav-icon">📋</span>
                    <span>Riwayat Konsultasi</span>
                </a>
                <a href="notifikasi.php">
                    <span class="nav-icon">🔔</span>
                    <span>Notifikasi</span>
                    <?php if ($notifUnread > 0): ?>
                        <span class="badge"><?php echo $notifUnread; ?></span>
                    <?php endif; ?>
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
            <!-- Top Bar -->
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>Dashboard Perawat</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">
                        🔔
                        <?php if ($notifUnread > 0): ?>
                            <span class="badge"><?php echo $notifUnread; ?></span>
                        <?php endif; ?>
                    </a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card pending" onclick="window.location.href='janji_temu.php?status=pending'">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $totalJanjiMenunggu; ?></h3>
                        <p>Menunggu Konfirmasi</p>
                    </div>
                </div>
                <div class="stat-card confirmed" onclick="window.location.href='janji_temu.php?status=confirmed'">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $totalJanjiDikonfirmasi; ?></h3>
                        <p>Dikonfirmasi</p>
                    </div>
                </div>
                <div class="stat-card selesai" onclick="window.location.href='janji_temu.php?status=selesai'">
                    <div class="stat-icon">✔️</div>
                    <div class="stat-info">
                        <h3><?php echo $totalJanjiSelesai; ?></h3>
                        <p>Selesai</p>
                    </div>
                </div>
                <div class="stat-card batal" onclick="window.location.href='janji_temu.php?status=batal'">
                    <div class="stat-icon">❌</div>
                    <div class="stat-info">
                        <h3><?php echo $totalJanjiBatal; ?></h3>
                        <p>Dibatalkan</p>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Janji Hari Ini -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>📅 Janji Hari Ini <span class="today-badge"><?php echo date('d/m/Y'); ?></span></h3>
                        <a href="janji_temu.php" class="link-more">Lihat Semua →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($janjiHariIni)): ?>
                            <div class="empty-state">
                                <p>🎉 Tidak ada janji hari ini. Santai dulu!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($janjiHariIni as $janji): ?>
                                <div class="appointment-item">
                                    <div class="appointment-info">
                                        <strong><?php echo htmlspecialchars($janji['nama_hewan'] ?? 'Hewan'); ?></strong>
                                        <span><?php echo htmlspecialchars($janji['nama_pelanggan'] ?? 'Pelanggan'); ?> • <?php echo htmlspecialchars($janji['nama_layanan'] ?? 'Layanan'); ?></span>
                                        <small>⏰ <?php echo substr($janji['waktu'], 0, 5); ?> • <?php echo htmlspecialchars($janji['jenis_hewan'] ?? ''); ?></small>
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

                <!-- Notifikasi -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>🔔 Notifikasi Terbaru</h3>
                        <a href="notifikasi.php" class="link-more">Lihat Semua →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifikasi)): ?>
                            <div class="empty-state">
                                <p>Belum ada notifikasi</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifikasi as $notif): ?>
                                <div class="notification-item <?php echo $notif['dibaca'] ? 'read' : 'unread'; ?>">
                                    <div class="notif-icon notif-<?php echo $notif['jenis']; ?>">
                                        <?php 
                                            $iconMap = [
                                                'success' => '✅',
                                                'warning' => '⚠️',
                                                'danger' => '❌',
                                                'info' => 'ℹ️'
                                            ];
                                            echo $iconMap[$notif['jenis']] ?? 'ℹ️';
                                        ?>
                                    </div>
                                    <div class="notif-content">
                                        <strong><?php echo htmlspecialchars($notif['judul']); ?></strong>
                                        <p><?php echo htmlspecialchars($notif['pesan']); ?></p>
                                        <small><?php echo timeAgo(strtotime($notif['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('menuToggle');
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Auto refresh notifikasi setiap 30 detik
        setInterval(function() {
            fetch('notifikasi.php?check=1')
                .then(response => response.json())
                .then(data => {
                    if (data.unread > 0) {
                        const badges = document.querySelectorAll('.badge');
                        badges.forEach(badge => {
                            badge.textContent = data.unread;
                            if (data.unread > 0) {
                                badge.style.display = 'inline';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    }
                })
                .catch(err => console.log('Error checking notifications:', err));
        }, 30000);
    </script>
</body>
</html>