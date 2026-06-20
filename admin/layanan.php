<?php
require_once '../config.php';
requireAdmin();

$message = '';
$error = '';

// Handle tambah layanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_layanan'])) {
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = floatval($_POST['harga'] ?? 0);
    $durasi = intval($_POST['durasi'] ?? 30);
    $status = $_POST['status'] ?? 'aktif';

    if (empty($nama_layanan) || $harga <= 0) {
        $error = 'Nama layanan dan harga harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO layanan (nama_layanan, deskripsi, harga, durasi, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama_layanan, $deskripsi, $harga, $durasi, $status]);
            $message = 'Layanan berhasil ditambahkan!';
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan layanan: ' . $e->getMessage();
        }
    }
}

// Handle edit layanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_layanan'])) {
    $id = intval($_POST['layanan_id'] ?? 0);
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = floatval($_POST['harga'] ?? 0);
    $durasi = intval($_POST['durasi'] ?? 30);
    $status = $_POST['status'] ?? 'aktif';

    if (empty($nama_layanan) || $harga <= 0) {
        $error = 'Nama layanan dan harga harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE layanan SET nama_layanan = ?, deskripsi = ?, harga = ?, durasi = ?, status = ? WHERE id = ?");
            $stmt->execute([$nama_layanan, $deskripsi, $harga, $durasi, $status, $id]);
            $message = 'Layanan berhasil diperbarui!';
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui layanan: ' . $e->getMessage();
        }
    }
}

// Handle hapus layanan
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM layanan WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Layanan berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus layanan: ' . $e->getMessage();
    }
}

// Ambil data layanan
$layananList = getLayananAll();

function getLayananAll() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM layanan ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Layanan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Style sama seperti sebelumnya */
        .table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; background: var(--paper); border-radius: var(--radius-sm); overflow: hidden; box-shadow: var(--shadow-sm); }
        .data-table th { background: var(--cream); padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: var(--ink-soft); border-bottom: 2px solid rgba(22,36,27,.06); white-space: nowrap; }
        .data-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid rgba(22,36,27,.04); vertical-align: middle; }
        .data-table tr:hover td { background: rgba(247,243,234,.3); }
        
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-aktif { background: #E5F5E5; color: #1A6A3A; }
        .status-nonaktif { background: #FDEAEA; color: #B23B3B; }
        
        .btn-action { padding: 4px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; text-decoration: none; transition: all .3s; display: inline-block; border: none; cursor: pointer; }
        .btn-edit { background: var(--cream); color: var(--ink); }
        .btn-edit:hover { background: var(--gold-soft); }
        .btn-delete { background: #FDEAEA; color: #B23B3B; }
        .btn-delete:hover { background: #F5C6CB; }
        .btn-add { padding: 10px 24px; background: var(--gold); color: var(--ink); border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: all .3s; }
        .btn-add:hover { background: var(--gold-dark); transform: translateY(-2px); }
        .btn-submit { padding: 10px 28px; background: var(--gold); color: var(--ink); border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: all .3s; }
        .btn-submit:hover { background: var(--gold-dark); transform: translateY(-2px); }
        
        .modal { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--paper); border-radius: var(--radius-lg); padding: 32px; max-width: 560px; width: 90%; max-height: 90vh; overflow-y: auto; animation: slideUp .3s var(--ease); }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-content h3 { font-family: 'Fraunces', serif; font-size: 22px; color: var(--ink); margin-bottom: 20px; }
        .modal-close { float: right; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); transition: color .3s; }
        .modal-close:hover { color: var(--ink); }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--ink-soft); margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--sage-light); border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter', sans-serif; background: var(--cream); color: var(--text); transition: border-color .3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--gold); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 16px; }
        .alert-success { background: #E5F5E5; color: #1A6A3A; border: 1px solid #B8D9B8; }
        .alert-danger { background: #FDEAEA; color: #B23B3B; border: 1px solid #F5C6CB; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .price-tag { font-weight: 600; color: var(--gold-dark); }
        
        @media (max-width: 768px) {
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .header-actions { flex-direction: column; align-items: stretch; }
            .form-row { grid-template-columns: 1fr; }
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
                <a href="layanan.php" class="active"><span class="nav-icon">📋</span><span>Kelola Layanan</span></a>
                <a href="janji.php"><span class="nav-icon">📅</span><span>Kelola Janji</span></a>
                <a href="laporan.php"><span class="nav-icon">📄</span><span>Laporan</span></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>📋 Kelola Layanan</h2>
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

            <div class="header-actions">
                <div style="color:var(--text-muted);font-size:14px;">
                    Total layanan: <strong><?php echo count($layananList); ?></strong>
                </div>
                <button class="btn-add" onclick="openModal('tambahModal')">➕ Tambah Layanan</button>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Layanan</th>
                            <th>Deskripsi</th>
                            <th>Harga</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($layananList)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">
                                    Belum ada layanan terdaftar
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($layananList as $layanan): ?>
                                <tr>
                                    <td>#<?php echo str_pad($layanan['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><strong><?php echo htmlspecialchars($layanan['nama_layanan']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($layanan['deskripsi'] ?? '', 0, 50)); ?><?php echo strlen($layanan['deskripsi'] ?? '') > 50 ? '...' : ''; ?></td>
                                    <td class="price-tag"><?php echo formatRupiah($layanan['harga']); ?></td>
                                    <td><?php echo $layanan['durasi']; ?> menit</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $layanan['status']; ?>">
                                            <?php echo $layanan['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editLayanan(<?php echo htmlspecialchars(json_encode($layanan)); ?>)">✏️</button>
                                        <a href="layanan.php?delete=<?php echo $layanan['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus layanan ini?')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal Tambah Layanan -->
    <div class="modal" id="tambahModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('tambahModal')">✕</button>
            <h3>➕ Tambah Layanan</h3>
            <form method="POST">
                <input type="hidden" name="tambah_layanan" value="1">
                <div class="form-group">
                    <label>Nama Layanan *</label>
                    <input type="text" name="nama_layanan" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Harga *</label>
                        <input type="number" name="harga" step="1000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Durasi (menit)</label>
                        <input type="number" name="durasi" min="5" value="30">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">💾 Simpan</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Layanan -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
            <h3>✏️ Edit Layanan</h3>
            <form method="POST">
                <input type="hidden" name="edit_layanan" value="1">
                <input type="hidden" name="layanan_id" id="edit_layanan_id">
                <div class="form-group">
                    <label>Nama Layanan *</label>
                    <input type="text" name="nama_layanan" id="edit_nama_layanan" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Harga *</label>
                        <input type="number" name="harga" id="edit_harga" step="1000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Durasi (menit)</label>
                        <input type="number" name="durasi" id="edit_durasi" min="5" value="30">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">💾 Update</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }

        function editLayanan(layanan) {
            document.getElementById('edit_layanan_id').value = layanan.id;
            document.getElementById('edit_nama_layanan').value = layanan.nama_layanan;
            document.getElementById('edit_deskripsi').value = layanan.deskripsi || '';
            document.getElementById('edit_harga').value = layanan.harga;
            document.getElementById('edit_durasi').value = layanan.durasi;
            document.getElementById('edit_status').value = layanan.status;
            openModal('editModal');
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>