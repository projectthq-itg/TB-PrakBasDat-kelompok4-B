<?php
require_once '../config.php';
requireLogin();

if (!isPerawat() && !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$perawat = getPerawatByUserId($user_id);

if (!$perawat) {
    header('Location: ../login.php');
    exit;
}

$perawat_id = $perawat['id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$janji = getJanjiDetailById($id);

if (!$janji || $janji['perawat_id'] != $perawat_id) {
    header('Location: janji_temu.php');
    exit;
}

$notifUnread = countNotifikasiPerawatUnread($perawat_id);

// Handle update catatan dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_catatan'])) {
    $catatan_dokter = trim($_POST['catatan_dokter'] ?? '');
    
    try {
        $stmt = $pdo->prepare("UPDATE janji_temu SET catatan_dokter = ? WHERE id = ?");
        $stmt->execute([$catatan_dokter, $id]);
        
        // Tambah notifikasi ke pelanggan
        $stmt2 = $pdo->prepare("INSERT INTO notifikasi (pelanggan_id, judul, pesan, jenis) VALUES (?, ?, ?, ?)");
        $stmt2->execute([
            $janji['pelanggan_id'],
            'Catatan Dokter Ditambahkan',
            'Dokter telah menambahkan catatan untuk konsultasi hewan ' . $janji['nama_hewan'],
            'info'
        ]);
        
        $success = 'Catatan dokter berhasil disimpan!';
        $janji = getJanjiDetailById($id);
    } catch (PDOException $e) {
        $error = 'Gagal menyimpan catatan: ' . $e->getMessage();
    }
}

function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getJanjiDetailById($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT j.*, 
               h.nama_hewan, h.jenis_hewan, h.ras, h.umur, h.berat,
               l.nama_layanan, l.harga, l.durasi,
               p.nama_lengkap as nama_pelanggan, p.no_telepon, p.alamat,
               pw.nama_lengkap as nama_perawat, pw.keahlian
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN pelanggan p ON j.pelanggan_id = p.id
        LEFT JOIN perawat pw ON j.perawat_id = pw.id
        WHERE j.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function countNotifikasiPerawatUnread($perawat_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifikasi_perawat WHERE perawat_id = ? AND dibaca = 0");
    $stmt->execute([$perawat_id]);
    return $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Janji Temu - Perawat</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .detail-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 28px;
        }

        .detail-card {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .detail-card h3 {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            color: var(--ink);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(22,36,27,.06);
        }

        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid rgba(22,36,27,.04);
        }

        .detail-row .label {
            font-weight: 600;
            color: var(--ink-soft);
            min-width: 140px;
            font-size: 14px;
        }

        .detail-row .value {
            color: var(--text);
            font-size: 14px;
            flex: 1;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
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

        .btn-back {
            display: inline-block;
            padding: 10px 24px;
            background: var(--cream);
            color: var(--ink);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: all .3s var(--ease);
            border: 1px solid var(--sage-light);
        }

        .btn-back:hover {
            background: var(--gold-soft);
        }

        .btn-submit {
            padding: 12px 28px;
            background: var(--gold);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s var(--ease);
        }

        .btn-submit:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: 6px;
        }

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
            resize: vertical;
            min-height: 120px;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(200,166,100,.12);
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

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .detail-wrapper {
                grid-template-columns: 1fr;
            }
            .detail-row {
                flex-direction: column;
                gap: 4px;
            }
            .detail-row .label {
                min-width: auto;
            }
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
                    <img src="<?php echo $perawat['foto_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=100&background=C8A664&color=fff'; ?>" alt="Foto">
                </div>
                <div class="sidebar-user-info">
                    <h4><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></h4>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> • Perawat</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                <a href="profil.php"><span class="nav-icon">👤</span><span>Profil Saya</span></a>
                <a href="janji_temu.php" class="active"><span class="nav-icon">📅</span><span>Janji Temu</span></a>
                <a href="hewan_perawat.php"><span class="nav-icon">🐾</span><span>Hewan Pasien</span></a>
                <a href="riwayat_konsultasi.php"><span class="nav-icon">📋</span><span>Riwayat Konsultasi</span></a>
                <a href="notifikasi.php"><span class="nav-icon">🔔</span><span>Notifikasi</span><?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                <a href="laporan.php"><span class="nav-icon">📄</span><span>Laporan</span></a>
                <a href="pengaturan.php"><span class="nav-icon">⚙️</span><span>Pengaturan</span></a>
                <a href="../logout.php" class="logout"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h2>📋 Detail Janji Temu</h2>
                </div>
                <div class="topbar-right">
                    <a href="notifikasi.php" class="notif-btn">🔔<?php if ($notifUnread > 0): ?><span class="badge"><?php echo $notifUnread; ?></span><?php endif; ?></a>
                    <span class="greeting">Halo, <?php echo htmlspecialchars($perawat['nama_lengkap']); ?> 👋</span>
                </div>
            </div>

            <a href="janji_temu.php" class="btn-back" style="margin-bottom:20px;">← Kembali ke Daftar Janji</a>

            <div class="detail-wrapper">
                <div>
                    <!-- Detail Janji -->
                    <div class="detail-card">
                        <h3>📋 Informasi Janji Temu</h3>
                        
                        <div class="detail-row">
                            <span class="label">ID Janji</span>
                            <span class="value">#<?php echo str_pad($janji['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Status</span>
                            <span class="value">
                                <span class="status-badge status-<?php echo $janji['status']; ?>">
                                    <?php 
                                        $statusMap = [
                                            'pending' => '⏳ Menunggu',
                                            'confirmed' => '✅ Dikonfirmasi',
                                            'selesai' => '✔️ Selesai',
                                            'batal' => '❌ Dibatalkan'
                                        ];
                                        echo $statusMap[$janji['status']] ?? ucfirst($janji['status']);
                                    ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Tanggal & Waktu</span>
                            <span class="value"><?php echo date('d F Y', strtotime($janji['tanggal'])); ?> • <?php echo substr($janji['waktu'], 0, 5); ?> WIB</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Layanan</span>
                            <span class="value"><?php echo htmlspecialchars($janji['nama_layanan']); ?> (<?php echo formatRupiah($janji['harga']); ?>)</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Durasi</span>
                            <span class="value"><?php echo $janji['durasi']; ?> menit</span>
                        </div>
                    </div>

                    <!-- Detail Pelanggan -->
                    <div class="detail-card" style="margin-top:20px;">
                        <h3>👤 Informasi Pelanggan</h3>
                        <div class="detail-row">
                            <span class="label">Nama</span>
                            <span class="value"><strong><?php echo htmlspecialchars($janji['nama_pelanggan']); ?></strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Telepon</span>
                            <span class="value"><?php echo htmlspecialchars($janji['no_telepon'] ?? '-'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Alamat</span>
                            <span class="value"><?php echo htmlspecialchars($janji['alamat'] ?? '-'); ?></span>
                        </div>
                    </div>

                    <!-- Detail Hewan -->
                    <div class="detail-card" style="margin-top:20px;">
                        <h3>🐾 Informasi Hewan</h3>
                        <div class="detail-row">
                            <span class="label">Nama Hewan</span>
                            <span class="value"><strong><?php echo htmlspecialchars($janji['nama_hewan']); ?></strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Jenis</span>
                            <span class="value"><?php echo htmlspecialchars($janji['jenis_hewan'] ?? '-'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Ras</span>
                            <span class="value"><?php echo htmlspecialchars($janji['ras'] ?? '-'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Umur</span>
                            <span class="value"><?php echo $janji['umur'] ? $janji['umur'] . ' tahun' : '-'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Berat</span>
                            <span class="value"><?php echo $janji['berat'] ? $janji['berat'] . ' kg' : '-'; ?></span>
                        </div>
                    </div>

                    <!-- Keluhan -->
                    <div class="detail-card" style="margin-top:20px;">
                        <h3>💬 Keluhan</h3>
                        <p style="color:var(--text);line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($janji['keluhan'] ?? 'Tidak ada keluhan')); ?>
                        </p>
                    </div>
                </div>

                <div>
                    <!-- Catatan Dokter -->
                    <div class="detail-card">
                        <h3>📝 Catatan Dokter</h3>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label>Catatan Konsultasi</label>
                                <textarea name="catatan_dokter" placeholder="Tulis catatan konsultasi di sini..."><?php echo htmlspecialchars($janji['catatan_dokter'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_catatan" class="btn-submit">💾 Simpan Catatan</button>
                        </form>
                    </div>

                    <!-- Aksi -->
                    <div class="detail-card" style="margin-top:20px;">
                        <h3>⚡ Aksi</h3>
                        <div class="action-buttons">
                            <?php if ($janji['status'] === 'pending'): ?>
                                <a href="janji_update_status.php?id=<?php echo $janji['id']; ?>&status=confirmed" class="btn-submit" style="background:#E5F0FF;color:#1A5A8A;" onclick="return confirm('Konfirmasi janji ini?')">✅ Konfirmasi</a>
                            <?php endif; ?>
                            
                            <?php if ($janji['status'] === 'confirmed'): ?>
                                <a href="janji_update_status.php?id=<?php echo $janji['id']; ?>&status=selesai" class="btn-submit" style="background:#E5F5E5;color:#1A6A3A;" onclick="return confirm('Tandai janji ini selesai?')">✔️ Selesai</a>
                            <?php endif; ?>
                            
                            <?php if ($janji['status'] === 'pending' || $janji['status'] === 'confirmed'): ?>
                                <a href="janji_update_status.php?id=<?php echo $janji['id']; ?>&status=batal" class="btn-submit" style="background:#FDEAEA;color:#B23B3B;" onclick="return confirm('Batalkan janji ini?')">❌ Batal</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Info Perawat -->
                    <div class="detail-card" style="margin-top:20px;">
                        <h3>👨‍⚕️ Perawat</h3>
                        <div class="detail-row">
                            <span class="label">Nama</span>
                            <span class="value"><strong><?php echo htmlspecialchars($janji['nama_perawat']); ?></strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Keahlian</span>
                            <span class="value"><?php echo htmlspecialchars($janji['keahlian'] ?? '-'); ?></span>
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