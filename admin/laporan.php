<?php
require_once '../config.php';
requireAdmin();

$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

// Data statistik
$totalPendapatan = getTotalPendapatanAdmin();
$totalJanji = countAllJanji();
$totalPelanggan = countAllPelanggan();
$totalPerawat = countAllPerawat();

// Statistik per bulan
$statistikBulanan = getStatistikBulananAdminLengkap($tahun);
$layananTerpopuler = getLayananTerpopulerAdmin(); // FUNGSI INI HARUS DITAMBAHKAN
$perawatKinerja = getPerawatKinerja($tahun);

// ============================================
// FUNGSI-FUNGSI
// ============================================

function getTotalPendapatanAdmin() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(l.harga), 0) as total FROM janji_temu j JOIN layanan l ON j.layanan_id = l.id WHERE j.status = 'selesai'");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function countAllJanji() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM janji_temu");
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

function getStatistikBulananAdminLengkap($tahun) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(tanggal) as bulan,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) as batal
        FROM janji_temu
        WHERE YEAR(tanggal) = ?
        GROUP BY MONTH(tanggal)
        ORDER BY bulan ASC
    ");
    $stmt->execute([$tahun]);
    return $stmt->fetchAll();
}

// ============================================
// FUNGSI YANG DITAMBAHKAN
// ============================================

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

function getPerawatKinerja($tahun) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nama_lengkap,
            p.keahlian,
            COUNT(j.id) as total_janji,
            SUM(CASE WHEN j.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN j.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN j.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN j.status = 'batal' THEN 1 ELSE 0 END) as batal
        FROM perawat p
        LEFT JOIN janji_temu j ON p.id = j.perawat_id AND YEAR(j.tanggal) = ?
        WHERE p.status = 'aktif'
        GROUP BY p.id
        ORDER BY selesai DESC
    ");
    $stmt->execute([$tahun]);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            margin-bottom: 8px;
        }

        .report-card .number {
            font-family: 'Fraunces', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--gold-dark);
        }

        .report-card .label {
            font-size: 14px;
            color: var(--text-muted);
        }

        .table-wrapper { overflow-x: auto; margin-top: 20px; }
        .data-table { width: 100%; border-collapse: collapse; background: var(--paper); border-radius: var(--radius-sm); overflow: hidden; box-shadow: var(--shadow-sm); }
        .data-table th { background: var(--cream); padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: var(--ink-soft); border-bottom: 2px solid rgba(22,36,27,.06); white-space: nowrap; }
        .data-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid rgba(22,36,27,.04); vertical-align: middle; }
        .data-table tr:hover td { background: rgba(247,243,234,.3); }

        .progress-bar { width: 100%; height: 6px; background: var(--cream); border-radius: 3px; overflow: hidden; margin-top: 4px; }
        .progress-bar .fill { height: 100%; background: var(--gold); border-radius: 3px; transition: width .6s; }

        .filter-bar { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; flex-wrap: wrap; }
        .filter-bar select { padding: 10px 16px; border: 1.5px solid var(--sage-light); border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter', sans-serif; background: var(--cream); color: var(--text); }
        .filter-bar select:focus { outline: none; border-color: var(--gold); }
        .filter-bar button { padding: 10px 24px; background: var(--gold); color: var(--ink); border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; font-size: 14px; transition: all .3s; }
        .filter-bar button:hover { background: var(--gold-dark); }

        .layanan-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .layanan-item:last-child {
            border-bottom: none;
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
            font-size: 13px;
        }

        .layanan-item .price {
            color: var(--gold-dark);
            font-weight: 600;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .report-grid { grid-template-columns: 1fr 1fr; }
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .filter-bar { flex-direction: column; align-items: stretch; }
        }
        @media (max-width: 480px) {
            .report-grid { grid-template-columns: 1fr; }
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
                    <img src="https://ui-avatars.com/api/?name=Admin&size=100&background=C8A664&color=fff" alt="Foto">
                </div>
                <div class="sidebar-user-info">
                    <h4>Administrator</h4>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> • Admin</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                <a href="users.php"><span class="nav-icon">👥</span><span>Kelola User</span></a>
                <a href="perawat.php"><span class="nav-icon">👨‍⚕️</span><span>Kelola Perawat</span></a>
                <a href="pelanggan.php"><span class="nav-icon">👤</span><span>Kelola Pelanggan</span></a>
                <a href="layanan.php"><span class="nav-icon">📋</span><span>Kelola Layanan</span></a>
                <a href="janji.php"><span class="nav-icon">📅</span><span>Kelola Janji</span></a>
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
                    <span class="greeting">👋 Halo, Admin</span>
                </div>
            </div>

            <form class="filter-bar" method="GET">
                <select name="tahun">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $tahun ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit">Tampilkan</button>
            </form>

            <!-- Stats Ringkasan -->
            <div class="report-grid">
                <div class="report-card">
                    <h3>💰 Total Pendapatan</h3>
                    <div class="number"><?php echo formatRupiah($totalPendapatan); ?></div>
                    <div class="label">Semua waktu</div>
                </div>
                <div class="report-card">
                    <h3>📅 Total Janji</h3>
                    <div class="number"><?php echo $totalJanji; ?></div>
                    <div class="label">Semua waktu</div>
                </div>
                <div class="report-card">
                    <h3>👤 Total Pelanggan</h3>
                    <div class="number"><?php echo $totalPelanggan; ?></div>
                    <div class="label">Semua waktu</div>
                </div>
                <div class="report-card">
                    <h3>👨‍⚕️ Total Perawat</h3>
                    <div class="number"><?php echo $totalPerawat; ?></div>
                    <div class="label">Aktif</div>
                </div>
            </div>

            <!-- Layanan Terpopuler -->
            <div class="report-card" style="margin-bottom:20px;">
                <h3>🏆 Layanan Terpopuler</h3>
                <?php if (empty($layananTerpopuler)): ?>
                    <div style="color:var(--text-muted);font-size:14px;">Belum ada data</div>
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

            <!-- Statistik Bulanan -->
            <div class="report-card" style="margin-bottom:20px;">
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
                                <span><strong><?php echo $bulanNames[$data['bulan'] - 1]; ?></strong></span>
                                <span>
                                    Total: <?php echo $data['total']; ?> | 
                                    ✅ Selesai: <?php echo $data['selesai']; ?> | 
                                    ⏳ Pending: <?php echo $data['pending']; ?>
                                </span>
                            </div>
                            <div class="progress-bar">
                                <div class="fill" style="width: <?php echo $maxTotal > 0 ? ($data['total'] / $maxTotal * 100) : 0; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Kinerja Perawat -->
            <div class="report-card">
                <h3>⭐ Kinerja Perawat <?php echo $tahun; ?></h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Perawat</th>
                                <th>Keahlian</th>
                                <th>Total Janji</th>
                                <th>✅ Selesai</th>
                                <th>⏳ Pending</th>
                                <th>✅ Confirmed</th>
                                <th>❌ Batal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($perawatKinerja)): ?>
                                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada data</td></tr>
                            <?php else: ?>
                                <?php foreach ($perawatKinerja as $perawat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($perawat['keahlian'] ?? '-'); ?></td>
                                        <td><?php echo $perawat['total_janji']; ?></td>
                                        <td style="color:#27ae60;font-weight:600;"><?php echo $perawat['selesai']; ?></td>
                                        <td style="color:#f39c12;"><?php echo $perawat['pending']; ?></td>
                                        <td style="color:#2980b9;"><?php echo $perawat['confirmed']; ?></td>
                                        <td style="color:#e74c3c;"><?php echo $perawat['batal']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>