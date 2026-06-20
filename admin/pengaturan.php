<?php
require_once '../config.php';
requireAdmin();

$message = '';
$error = '';

// Handle update pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pengaturan'])) {
    $nama_klinik = trim($_POST['nama_klinik'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $jam_buka_senin_jumat = trim($_POST['jam_buka_senin_jumat'] ?? '');
    $jam_buka_sabtu = trim($_POST['jam_buka_sabtu'] ?? '');
    $jam_buka_minggu = trim($_POST['jam_buka_minggu'] ?? '');

    try {
        $settings = [
            'nama_klinik' => $nama_klinik,
            'alamat' => $alamat,
            'telepon' => $telepon,
            'email' => $email,
            'jam_buka_senin_jumat' => $jam_buka_senin_jumat,
            'jam_buka_sabtu' => $jam_buka_sabtu,
            'jam_buka_minggu' => $jam_buka_minggu
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("UPDATE pengaturan SET value_setting = ? WHERE key_setting = ?");
            $stmt->execute([$value, $key]);
        }

        $message = 'Pengaturan berhasil diperbarui!';
    } catch (PDOException $e) {
        $error = 'Gagal memperbarui pengaturan: ' . $e->getMessage();
    }
}

// Ambil data pengaturan
$pengaturan = [];
$stmt = $pdo->prepare("SELECT key_setting, value_setting FROM pengaturan");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $pengaturan[$row['key_setting']] = $row['value_setting'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .settings-wrapper { max-width: 700px; }
        .settings-card { background: var(--paper); border-radius: var(--radius-sm); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .settings-card h3 { font-family: 'Fraunces', serif; font-size: 20px; color: var(--ink); margin-bottom: 6px; }
        .settings-card .desc { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--ink-soft); margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--sage-light); border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter', sans-serif; background: var(--cream); color: var(--text); transition: border-color .3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--gold); }
        
        .btn-submit { padding: 12px 32px; background: var(--gold); color: var(--ink); border: none; border-radius: var(--radius-sm); font-size: 15px; font-weight: 600; cursor: pointer; transition: all .3s; }
        .btn-submit:hover { background: var(--gold-dark); transform: translateY(-2px); }
        
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 16px; }
        .alert-success { background: #E5F5E5; color: #1A6A3A; border: 1px solid #B8D9B8; }
        .alert-danger { background: #FDEAEA; color: #B23B3B; border: 1px solid #F5C6CB; }
        
        @media (max-width: 768px) {
            .settings-card { padding: 20px; }
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
                <a href="laporan.php"><span class="nav-icon">📄</span><span>Laporan</span></a>
                <a href="pengaturan.php" class="active"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>⚙️ Pengaturan</h2>
                </div>
                <div class="topbar-right">
                    <span class="greeting">👋 Halo, Admin</span>
                </div>
            </div>

            <div class="settings-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="settings-card">
                    <h3>🏥 Pengaturan Klinik</h3>
                    <p class="desc">Atur informasi dasar klinik hewan Anda</p>
                    <form method="POST">
                        <input type="hidden" name="update_pengaturan" value="1">
                        <div class="form-group">
                            <label>Nama Klinik</label>
                            <input type="text" name="nama_klinik" value="<?php echo htmlspecialchars($pengaturan['nama_klinik'] ?? 'Klinik Hewan'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <input type="text" name="alamat" value="<?php echo htmlspecialchars($pengaturan['alamat'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Telepon</label>
                            <input type="text" name="telepon" value="<?php echo htmlspecialchars($pengaturan['telepon'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($pengaturan['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Jam Buka (Senin - Jumat)</label>
                            <input type="text" name="jam_buka_senin_jumat" value="<?php echo htmlspecialchars($pengaturan['jam_buka_senin_jumat'] ?? '08:00 - 20:00'); ?>" placeholder="08:00 - 20:00">
                        </div>
                        <div class="form-group">
                            <label>Jam Buka (Sabtu)</label>
                            <input type="text" name="jam_buka_sabtu" value="<?php echo htmlspecialchars($pengaturan['jam_buka_sabtu'] ?? '08:00 - 17:00'); ?>" placeholder="08:00 - 17:00">
                        </div>
                        <div class="form-group">
                            <label>Jam Buka (Minggu)</label>
                            <input type="text" name="jam_buka_minggu" value="<?php echo htmlspecialchars($pengaturan['jam_buka_minggu'] ?? 'Tutup'); ?>" placeholder="Tutup">
                        </div>
                        <button type="submit" class="btn-submit">💾 Simpan Pengaturan</button>
                    </form>
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