<?php
require_once '../config.php';
requireAdmin();

$message = '';
$error = '';
$search = $_GET['search'] ?? '';

// Handle tambah perawat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_perawat'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $keahlian = trim($_POST['keahlian'] ?? '');
    $pengalaman = intval($_POST['pengalaman'] ?? 0);
    $status = $_POST['status'] ?? 'aktif';

    if (empty($username) || empty($email) || empty($password) || empty($nama_lengkap)) {
        $error = 'Field wajib diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        try {
            $pdo->beginTransaction();

            // Insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'perawat', ?)");
            $stmt->execute([$username, $hashed, $email, $status]);
            $user_id = $pdo->lastInsertId();

            // Insert perawat
            $stmt = $pdo->prepare("INSERT INTO perawat (user_id, nama_lengkap, no_telepon, keahlian, pengalaman, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $nama_lengkap, $no_telepon, $keahlian, $pengalaman, $status]);

            $pdo->commit();
            $message = 'Perawat berhasil ditambahkan!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $error = 'Username atau email sudah terdaftar!';
            } else {
                $error = 'Gagal menambahkan perawat: ' . $e->getMessage();
            }
        }
    }
}

// Handle edit perawat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_perawat'])) {
    $id = intval($_POST['perawat_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $keahlian = trim($_POST['keahlian'] ?? '');
    $pengalaman = intval($_POST['pengalaman'] ?? 0);
    $status = $_POST['status'] ?? 'aktif';

    if (empty($username) || empty($email) || empty($nama_lengkap)) {
        $error = 'Field wajib diisi!';
    } else {
        try {
            $pdo->beginTransaction();

            // Update users
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, status = ? WHERE id = (SELECT user_id FROM perawat WHERE id = ?)");
            $stmt->execute([$username, $email, $status, $id]);

            // Update perawat
            $stmt = $pdo->prepare("UPDATE perawat SET nama_lengkap = ?, no_telepon = ?, keahlian = ?, pengalaman = ?, status = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $no_telepon, $keahlian, $pengalaman, $status, $id]);

            $pdo->commit();
            $message = 'Perawat berhasil diperbarui!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal memperbarui perawat: ' . $e->getMessage();
        }
    }
}

// Handle reset password
if (isset($_GET['reset_password']) && is_numeric($_GET['reset_password'])) {
    $id = intval($_GET['reset_password']);
    $new_password = '123456';
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = (SELECT user_id FROM perawat WHERE id = ?)");
        $stmt->execute([$hashed, $id]);
        $message = 'Password berhasil direset menjadi: 123456';
    } catch (PDOException $e) {
        $error = 'Gagal reset password: ' . $e->getMessage();
    }
}

// Handle hapus perawat
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        // Hapus user (cascade akan menghapus perawat)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM perawat WHERE id = ?)");
        $stmt->execute([$id]);
        $message = 'Perawat berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus perawat: ' . $e->getMessage();
    }
}

// Ambil data perawat
$perawatList = getPerawatList($search);

function getPerawatList($search = '') {
    global $pdo;
    $sql = "
        SELECT p.*, u.username, u.email, u.status as user_status
        FROM perawat p
        JOIN users u ON p.user_id = u.id
        WHERE 1=1
    ";
    
    if (!empty($search)) {
        $sql .= " AND (p.nama_lengkap LIKE ? OR u.username LIKE ? OR p.keahlian LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    } else {
        $params = [];
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
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
    <title>Kelola Perawat - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; background: var(--paper); border-radius: var(--radius-sm); overflow: hidden; box-shadow: var(--shadow-sm); }
        .data-table th { background: var(--cream); padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: var(--ink-soft); border-bottom: 2px solid rgba(22,36,27,.06); white-space: nowrap; }
        .data-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid rgba(22,36,27,.04); vertical-align: middle; }
        .data-table tr:hover td { background: rgba(247,243,234,.3); }
        
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-aktif { background: #E5F5E5; color: #1A6A3A; }
        .status-tidak_aktif { background: #FDEAEA; color: #B23B3B; }
        
        .btn-action { padding: 4px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; text-decoration: none; transition: all .3s; display: inline-block; border: none; cursor: pointer; }
        .btn-edit { background: var(--cream); color: var(--ink); }
        .btn-edit:hover { background: var(--gold-soft); }
        .btn-delete { background: #FDEAEA; color: #B23B3B; }
        .btn-delete:hover { background: #F5C6CB; }
        .btn-reset { background: #E5F0FF; color: #1A5A8A; }
        .btn-reset:hover { background: #B8D4F0; }
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
        
        .search-box { display: flex; gap: 8px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px 16px; border: 1.5px solid var(--sage-light); border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter', sans-serif; background: var(--cream); }
        .search-box input:focus { outline: none; border-color: var(--gold); }
        .search-box button { padding: 10px 24px; background: var(--gold); color: var(--ink); border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .avatar-mini { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        
        @media (max-width: 768px) {
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .header-actions { flex-direction: column; align-items: stretch; }
            .search-box { flex-direction: column; }
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
                <a href="perawat.php" class="active"><span class="nav-icon">👨‍⚕️</span><span>Kelola Perawat</span></a>
                <a href="pelanggan.php"><span class="nav-icon">👤</span><span>Kelola Pelanggan</span></a>
                <a href="layanan.php"><span class="nav-icon">📋</span><span>Kelola Layanan</span></a>
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
                    <h2>👨‍⚕️ Kelola Perawat</h2>
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
                <form class="search-box" method="GET">
                    <input type="text" name="search" placeholder="🔍 Cari perawat..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Cari</button>
                </form>
                <button class="btn-add" onclick="openModal('tambahModal')">➕ Tambah Perawat</button>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Keahlian</th>
                            <th>Pengalaman</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($perawatList)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                                    Belum ada perawat terdaftar
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($perawatList as $perawat): ?>
                                <tr>
                                    <td>#<?php echo str_pad($perawat['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <img src="<?php echo !empty($perawat['foto_profile']) ? '../' . $perawat['foto_profile'] : 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=50&background=C8A664&color=fff'; ?>" class="avatar-mini" alt="Foto">
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($perawat['username']); ?></td>
                                    <td><?php echo htmlspecialchars($perawat['email']); ?></td>
                                    <td><?php echo htmlspecialchars($perawat['keahlian'] ?? '-'); ?></td>
                                    <td><?php echo $perawat['pengalaman'] ?? 0; ?> tahun</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $perawat['status']; ?>">
                                            <?php echo $perawat['status'] === 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editPerawat(<?php echo htmlspecialchars(json_encode($perawat)); ?>)">✏️</button>
                                        <a href="perawat.php?reset_password=<?php echo $perawat['id']; ?>" class="btn-action btn-reset" onclick="return confirm('Reset password perawat ini menjadi 123456?')">🔑</a>
                                        <a href="perawat.php?delete=<?php echo $perawat['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus perawat ini?')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal Tambah Perawat -->
    <div class="modal" id="tambahModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('tambahModal')">✕</button>
            <h3>➕ Tambah Perawat</h3>
            <form method="POST">
                <input type="hidden" name="tambah_perawat" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="text" name="password" required minlength="6" placeholder="Minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon">
                    </div>
                    <div class="form-group">
                        <label>Keahlian</label>
                        <input type="text" name="keahlian" placeholder="Contoh: Kardiologi Hewan">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pengalaman (tahun)</label>
                        <input type="number" name="pengalaman" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="aktif">Aktif</option>
                            <option value="tidak_aktif">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-submit">💾 Simpan</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Perawat -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
            <h3>✏️ Edit Perawat</h3>
            <form method="POST">
                <input type="hidden" name="edit_perawat" value="1">
                <input type="hidden" name="perawat_id" id="edit_perawat_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" id="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="edit_nama_lengkap" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon" id="edit_no_telepon">
                    </div>
                    <div class="form-group">
                        <label>Keahlian</label>
                        <input type="text" name="keahlian" id="edit_keahlian" placeholder="Contoh: Kardiologi Hewan">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pengalaman (tahun)</label>
                        <input type="number" name="pengalaman" id="edit_pengalaman" min="0">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status">
                            <option value="aktif">Aktif</option>
                            <option value="tidak_aktif">Tidak Aktif</option>
                        </select>
                    </div>
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

        function editPerawat(perawat) {
            document.getElementById('edit_perawat_id').value = perawat.id;
            document.getElementById('edit_username').value = perawat.username;
            document.getElementById('edit_email').value = perawat.email;
            document.getElementById('edit_nama_lengkap').value = perawat.nama_lengkap;
            document.getElementById('edit_no_telepon').value = perawat.no_telepon || '';
            document.getElementById('edit_keahlian').value = perawat.keahlian || '';
            document.getElementById('edit_pengalaman').value = perawat.pengalaman || 0;
            document.getElementById('edit_status').value = perawat.status;
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