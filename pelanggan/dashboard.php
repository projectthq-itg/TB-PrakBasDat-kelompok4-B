<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];

// Statistik
$totalHewan = count(getHewanByPelangganId($pelanggan_id));
$janjiMenunggu = countJanjiByStatus($pelanggan_id, 'pending');
$janjiDikonfirmasi = countJanjiByStatus($pelanggan_id, 'confirmed');
$janjiSelesai = countJanjiByStatus($pelanggan_id, 'selesai');
$notifUnread = countNotifikasiUnread($pelanggan_id);

// Janji terbaru
$janjiTerbaru = getJanjiByPelangganId($pelanggan_id);
$janjiTerbaru = array_slice($janjiTerbaru, 0, 5);

// Notifikasi terbaru
$notifikasi = getNotifikasi($pelanggan_id, 5);

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Additional styles for dashboard */
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
                <a href="dashboard.php" class="active">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="profil.php">
                    <span class="nav-icon">👤</span>
                    <span>Profil Saya</span>
                </a>
                <a href="hewan_saya.php">
                    <span class="nav-icon">🐾</span>
                    <span>Hewan Saya</span>
                </a>
                <a href="buat_janji.php">
                    <span class="nav-icon">📅</span>
                    <span>Buat Janji</span>
                </a>
                <a href="riwayat_janji.php">
                    <span class="nav-icon">📋</span>
                    <span>Riwayat Janji</span>
                </a>
                <a href="notifikasi.php">
                    <span class="nav-icon">🔔</span>
                    <span>Notifikasi</span>
                    <?php if ($notifUnread > 0): ?>
                        <span class="badge"><?php echo $notifUnread; ?></span>
                    <?php endif; ?>
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
                    <h2>Dashboard</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">
                        🔔
                        <?php if ($notifUnread > 0): ?>
                            <span class="badge"><?php echo $notifUnread; ?></span>
                        <?php endif; ?>
                    </a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🐾</div>
                    <div class="stat-info">
                        <h3><?php echo $totalHewan; ?></h3>
                        <p>Hewan Peliharaan</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $janjiMenunggu; ?></h3>
                        <p>Menunggu Konfirmasi</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $janjiDikonfirmasi; ?></h3>
                        <p>Dikonfirmasi</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✔️</div>
                    <div class="stat-info">
                        <h3><?php echo $janjiSelesai; ?></h3>
                        <p>Selesai</p>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments & Notifications -->
            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h3>📋 Janji Temu Terbaru</h3>
                        <a href="riwayat_janji.php" class="link-more">Lihat Semua →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($janjiTerbaru)): ?>
                            <div class="empty-state">
                                <p>Belum ada janji temu. <a href="buat_janji.php">Buat janji sekarang</a></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($janjiTerbaru as $janji): ?>
                                <div class="appointment-item">
                                    <div class="appointment-info">
                                        <strong><?php echo htmlspecialchars($janji['nama_hewan'] ?? 'Hewan'); ?></strong>
                                        <span><?php echo htmlspecialchars($janji['nama_layanan'] ?? 'Layanan'); ?></span>
                                        <small><?php echo date('d/m/Y', strtotime($janji['tanggal'])); ?> • <?php echo substr($janji['waktu'], 0, 5); ?></small>
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
                        // Update badge
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