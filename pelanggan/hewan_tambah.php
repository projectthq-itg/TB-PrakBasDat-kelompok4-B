<?php
require_once '../config.php';
requirePelanggan();

$user_id = $_SESSION['user_id'];
$pelanggan = getPelangganByUserId($user_id);
$pelanggan_id = $pelanggan['id'];
$notifUnread = countNotifikasiUnread($pelanggan_id);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_hewan = trim($_POST['nama_hewan'] ?? '');
    $jenis_hewan = trim($_POST['jenis_hewan'] ?? '');
    $ras = trim($_POST['ras'] ?? '');
    $umur = intval($_POST['umur'] ?? 0);
    $berat = floatval($_POST['berat'] ?? 0);
    $warna = trim($_POST['warna'] ?? '');

    if (empty($nama_hewan) || empty($jenis_hewan)) {
        $error = 'Nama hewan dan jenis hewan harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO hewan_peliharaan (pelanggan_id, nama_hewan, jenis_hewan, ras, umur, berat, warna) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pelanggan_id, $nama_hewan, $jenis_hewan, $ras, $umur, $berat, $warna]);
            $hewan_id = $pdo->lastInsertId();

            addNotifikasi($pelanggan_id, 'Hewan Baru Ditambahkan', "Hewan peliharaan $nama_hewan telah ditambahkan ke daftar.", 'success');
            header('Location: hewan_saya.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan hewan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Hewan - Polwan</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-container {
            max-width: 600px;
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
            margin-bottom: 24px;
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

        .form-group input,
        .form-group select {
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
        .form-group select:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(200,166,100,.12);
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

        .alert-danger {
            background: #FDEAEA;
            color: #B23B3B;
            border: 1px solid #F5C6CB;
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
                <a href="hewan_saya.php" class="active"><span class="nav-icon">🐾</span><span>Hewan Saya</span></a>
                <a href="buat_janji.php"><span class="nav-icon">📅</span><span>Buat Janji</span></a>
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
                    <h2>➕ Tambah Hewan</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($pelanggan['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <div class="form-container">
                <h3>🐾 Data Hewan Peliharaan</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Nama Hewan *</label>
                        <input type="text" name="nama_hewan" placeholder="Contoh: Milo, Luna, Coki" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Hewan *</label>
                            <select name="jenis_hewan" required>
                                <option value="">Pilih...</option>
                                <option value="Kucing">Kucing</option>
                                <option value="Anjing">Anjing</option>
                                <option value="Kelinci">Kelinci</option>
                                <option value="Burung">Burung</option>
                                <option value="Hamster">Hamster</option>
                                <option value="Ikan">Ikan</option>
                                <option value="Reptil">Reptil</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ras</label>
                            <input type="text" name="ras" placeholder="Contoh: Persia, Golden Retriever">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Umur (tahun)</label>
                            <input type="number" name="umur" min="0" max="50" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Berat (kg)</label>
                            <input type="number" name="berat" min="0" step="0.1" placeholder="0.0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Warna</label>
                        <input type="text" name="warna" placeholder="Contoh: Putih, Hitam, Coklat">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">💾 Simpan Hewan</button>
                        <a href="hewan_saya.php" class="btn-cancel">Batal</a>
                    </div>
                </form>
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