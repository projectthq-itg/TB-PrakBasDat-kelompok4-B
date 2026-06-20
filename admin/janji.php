<?php
require_once '../config.php';
requireAdmin();

$message = '';
$error = '';
$statusFilter = $_GET['status'] ?? 'semua';
$search = $_GET['search'] ?? '';

// Handle update status
if (isset($_GET['update_status']) && is_numeric($_GET['update_status'])) {
    $id = intval($_GET['update_status']);
    $status = $_GET['status'] ?? '';
    $allowedStatus = ['pending', 'confirmed', 'selesai', 'batal'];
    
    if (in_array($status, $allowedStatus)) {
        try {
            $stmt = $pdo->prepare("UPDATE janji_temu SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $message = 'Status janji berhasil diperbarui!';
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui status: ' . $e->getMessage();
        }
    }
}

// Handle hapus janji
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM janji_temu WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Janji berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus janji: ' . $e->getMessage();
    }
}

// Ambil data janji
$janjiList = getJanjiListAdmin($statusFilter, $search);

function getJanjiListAdmin($status = 'semua', $search = '') {
    global $pdo;
    $sql = "
        SELECT j.*, 
               h.nama_hewan, h.jenis_hewan,
               l.nama_layanan, l.harga,
               p.nama_lengkap as nama_pelanggan,
               pw.nama_lengkap as nama_perawat
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        LEFT JOIN perawat pw ON j.perawat_id = pw.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status !== 'semua') {
        $sql .= " AND j.status = ?";
        $params[] = $status;
    }
    
    if (!empty($search)) {
        $sql .= " AND (h.nama_hewan LIKE ? OR p.nama_lengkap LIKE ? OR pw.nama_lengkap LIKE ?)";
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Janji - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; background: var(--paper); border-radius: var(--radius-sm); overflow: hidden; box-shadow: var(--shadow-sm); }
        .data-table th { background: var(--cream); padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: var(--ink-soft); border-bottom: 2px solid rgba(22,36,27,.06); white-space: nowrap; }
        .data-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid rgba(22,36,27,.04); vertical-align: middle; }
        .data-table tr:hover td { background: rgba(247,243,234,.3); }
        
        .status { padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize; white-space: nowrap; }
        .status-pending { background: #FFF6E5; color: #8A6D1F; }
        .status-confirmed { background: #E5F0FF; color: #1A5A8A; }
        .status-selesai { background: #E5F5E5; color: #1A6A3A; }
        .status-batal { background: #FDEAEA; color: #B23B3B; }
        
        .btn-action { padding: 4px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; text-decoration: none; transition: all .3s; display: inline-block; border: none; cursor: pointer; }
        .btn-edit { background: var(--cream); color: var(--ink); }
        .btn-edit:hover { background: var(--gold-soft); }
        .btn-delete { background: #FDEAEA; color: #B23B3B; }
        .btn-delete:hover { background: #F5C6CB; }
        .btn-confirm { background: #E5F0FF; color: #1A5A8A; }
        .btn-confirm:hover { background: #B8D4F0; }
        .btn-selesai { background: #E5F5E5; color: #1A6A3A; }
        .btn-selesai:hover { background: #B8D9B8; }
        .btn-batal { background: #FDEAEA; color: #B23B3B; }
        .btn-batal:hover { background: #F5C6CB; }
        
        .filter-bar { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filter-btn { padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all .3s; background: var(--cream); color: var(--text-muted); border: 1px solid transparent; }
        .filter-btn:hover { background: var(--gold-soft); color: var(--ink); }
        .filter-btn.active { background: var(--gold); color: var(--ink); border-color: var(--gold); }
        
        .search-box { display: flex; gap: 8px; margin-left: auto; }
        .search-box input { padding: 8px 16px; border: 1.5px solid var(--sage-light); border-radius: 20px; font-size: 13px; font-family: 'Inter', sans-serif; background: var(--cream); color: var(--text); min-width: 200px; }
        .search-box input:focus { outline: none; border-color: var(--gold); }
        .search-box button { padding: 8px 20px; background: var(--gold); color: var(--ink); border: none; border-radius: 20px; font-weight: 600; cursor: pointer; font-size: 13px; transition: all .3s; }
        .search-box button:hover { background: var(--gold-dark); }
        
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 16px; }
        .alert-success { background: #E5F5E5; color: #1A6A3A; border: 1px solid #B8D9B8; }
        .alert-danger { background: #FDEAEA; color: #B23B3B; border: 1px solid #F5C6CB; }
        
        .action-group { display: flex; gap: 4px; flex-wrap: wrap; }
        
        @media (max-width: 768px) {
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .search-box { margin-left: 0; }
            .search-box input { min-width: auto; flex: 1; }
            .action-group { flex-direction: column; gap: 4px; }
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
                <a href="janji.php" class="active"><span class="nav-icon">📅</span><span>Kelola Janji</span></a>
                <a href="laporan.php"><span class="nav-icon">📄</span><span>Laporan</span></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>📅 Kelola Janji Temu</h2>
                </div>
                <div class="topbar-right">
                    <span class="greeting">👋 Halo, Admin</span>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="janji.php?status=semua" class="filter-btn <?php echo $statusFilter === 'semua' ? 'active' : ''; ?>">Semua</a>
                <a href="janji.php?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">⏳ Menunggu</a>
                <a href="janji.php?status=confirmed" class="filter-btn <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">✅ Dikonfirmasi</a>
                <a href="janji.php?status=selesai" class="filter-btn <?php echo $statusFilter === 'selesai' ? 'active' : ''; ?>">✔️ Selesai</a>
                <a href="janji.php?status=batal" class="filter-btn <?php echo $statusFilter === 'batal' ? 'active' : ''; ?>">❌ Dibatalkan</a>
                
                <form class="search-box" method="GET">
                    <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                    <input type="text" name="search" placeholder="🔍 Cari hewan/pelanggan..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Cari</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Hewan</th>
                            <th>Layanan</th>
                            <th>Perawat</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($janjiList)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                                    Belum ada janji temu
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($janjiList as $janji): ?>
                                <tr>
                                    <td>#<?php echo str_pad($janji['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($janji['nama_pelanggan'] ?? '-'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($janji['nama_hewan'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($janji['nama_layanan'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($janji['nama_perawat'] ?? '-'); ?></td>
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
                                            <?php if ($janji['status'] === 'pending'): ?>
                                                <a href="janji.php?update_status=<?php echo $janji['id']; ?>&status=confirmed" class="btn-action btn-confirm" onclick="return confirm('Konfirmasi janji ini?')">✅</a>
                                            <?php endif; ?>
                                            <?php if ($janji['status'] === 'confirmed'): ?>
                                                <a href="janji.php?update_status=<?php echo $janji['id']; ?>&status=selesai" class="btn-action btn-selesai" onclick="return confirm('Tandai janji ini selesai?')">✔️</a>
                                            <?php endif; ?>
                                            <?php if ($janji['status'] === 'pending' || $janji['status'] === 'confirmed'): ?>
                                                <a href="janji.php?update_status=<?php echo $janji['id']; ?>&status=batal" class="btn-action btn-batal" onclick="return confirm('Batalkan janji ini?')">❌</a>
                                            <?php endif; ?>
                                            <a href="janji.php?delete=<?php echo $janji['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus janji ini?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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