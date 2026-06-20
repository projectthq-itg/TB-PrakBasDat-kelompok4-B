<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$notifUnread = countNotifikasiUnread($pelanggan_id);
$error = '';
$success = '';

$hewanList = getHewanByPelangganId($pelanggan_id);
$layananList = getLayananAktif();
$perawatList = getPerawatAktif();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hewan_id = intval($_POST['hewan_id'] ?? 0);
    $layanan_id = intval($_POST['layanan_id'] ?? 0);
    $perawat_id = intval($_POST['perawat_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $waktu = $_POST['waktu'] ?? '';
    $keluhan = trim($_POST['keluhan'] ?? '');

    if (empty($hewan_id) || empty($layanan_id) || empty($perawat_id) || empty($tanggal) || empty($waktu)) {
        $error = 'Semua field wajib diisi!';
    } else {
        try {
            // Insert dengan layanan_id
            $stmt = $pdo->prepare("INSERT INTO janji_temu (pelanggan_id, hewan_id, layanan_id, perawat_id, tanggal, waktu, keluhan, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$pelanggan_id, $hewan_id, $layanan_id, $perawat_id, $tanggal, $waktu, $keluhan]);

            $hewan = getHewanById($hewan_id);
            $layanan = getLayananById($layanan_id);
            $perawat = getPerawatById($perawat_id);

            addNotifikasi($pelanggan_id, 'Janji Temu Dibuat', "Janji temu untuk {$hewan['nama_hewan']} dengan layanan {$layanan['nama_layanan']} telah dibuat dan menunggu konfirmasi.", 'info');

            $success = 'Janji temu berhasil dibuat! Menunggu konfirmasi dari perawat.';
        } catch (PDOException $e) {
            $error = 'Gagal membuat janji: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Janji - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: var(--paper);
            padding: 40px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .form-container h3 {
            font-family: 'Fraunces', serif;
            font-size: 24px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .form-container .subtitle {
            color: var(--text-muted);
            margin-bottom: 24px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: 6px;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--sage-light);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color .3s, box-shadow .3s;
            background: var(--cream);
            color: var(--text);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(200,166,100,.12);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn-submit {
            padding: 14px 32px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s var(--ease);
            width: 100%;
        }

        .btn-submit:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-cancel {
            display: inline-block;
            padding: 12px 28px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color .3s;
        }

        .btn-cancel:hover {
            color: var(--ink);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #E5F5E5;
            color: #1A6A3A;
            border: 1px solid #B8D9B8;
        }

        .alert-danger {
            background: #FDEAEA;
            color: #B23B3B;
            border: 1px solid #F5C6CB;
        }

        .alert-info {
            background: #E5F0FF;
            color: #1A5A8A;
            border: 1px solid #B8D4F0;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 24px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
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
                <a href="dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                <a href="profil.php"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="hewan_saya.php"><span class="nav-icon">🐾</span><span>Hewan Saya</span></a>
                <a href="buat_janji.php" class="active"><span class="nav-icon">📅</span><span>Buat Janji</span></a>
                <a href="riwayat_janji.php"><span class="nav-icon">📋</span><span>Riwayat Janji</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>📅 Buat Janji Temu</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div class="form-container">
                <h3>🐾 Buat Janji Temu</h3>
                <p class="subtitle">Isi data berikut untuk membuat janji temu dengan dokter hewan</p>

                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">❌ <?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (empty($hewanList)): ?>
                    <div class="alert alert-info">
                        ℹ️ Anda belum memiliki hewan peliharaan. <a href="hewan_tambah.php" style="color:var(--gold-dark);font-weight:600;">Tambah hewan dulu</a> sebelum membuat janji.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Hewan Peliharaan <span class="required">*</span></label>
                                <select name="hewan_id" required>
                                    <option value="">Pilih hewan...</option>
                                    <?php foreach ($hewanList as $h): ?>
                                        <option value="<?php echo $h['id']; ?>">
                                            <?php echo htmlspecialchars($h['nama_hewan']); ?> (<?php echo htmlspecialchars($h['jenis_hewan']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Layanan <span class="required">*</span></label>
                                <select name="layanan_id" required>
                                    <option value="">Pilih layanan...</option>
                                    <?php foreach ($layananList as $l): ?>
                                        <option value="<?php echo $l['id']; ?>">
                                            <?php echo htmlspecialchars($l['nama_layanan']); ?> (<?php echo formatRupiah($l['harga']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Pilih Perawat <span class="required">*</span></label>
                            <select name="perawat_id" required>
                                <option value="">Pilih perawat...</option>
                                <?php foreach ($perawatList as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['nama_lengkap']); ?> - <?php echo htmlspecialchars($p['keahlian'] ?? 'Umum'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal <span class="required">*</span></label>
                                <input type="date" name="tanggal" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Waktu <span class="required">*</span></label>
                                <input type="time" name="waktu" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Keluhan / Catatan</label>
                            <textarea name="keluhan" placeholder="Ceritakan keluhan atau kondisi hewan Anda..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">📨 Buat Janji</button>
                            <a href="dashboard.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
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

        // Set default date to today
        document.querySelector('input[name="tanggal"]').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>