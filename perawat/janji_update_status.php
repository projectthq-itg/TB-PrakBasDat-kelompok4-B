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
$status = isset($_GET['status']) ? $_GET['status'] : '';

$allowedStatus = ['pending', 'confirmed', 'selesai', 'batal'];

if (!in_array($status, $allowedStatus)) {
    header('Location: janji_temu.php');
    exit;
}

// Cek kepemilikan janji
$stmt = $pdo->prepare("SELECT j.*, p.nama_lengkap as nama_pelanggan, h.nama_hewan FROM janji_temu j 
                       LEFT JOIN pelanggan p ON j.pelanggan_id = p.id 
                       LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id 
                       WHERE j.id = ? AND j.perawat_id = ?");
$stmt->execute([$id, $perawat_id]);
$janji = $stmt->fetch();

if (!$janji) {
    header('Location: janji_temu.php');
    exit;
}

// Update status
try {
    $stmt = $pdo->prepare("UPDATE janji_temu SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // Tambah notifikasi ke pelanggan
    $statusMap = [
        'pending' => 'Menunggu Konfirmasi',
        'confirmed' => 'Dikonfirmasi',
        'selesai' => 'Selesai',
        'batal' => 'Dibatalkan'
    ];
    
    $statusLabel = $statusMap[$status] ?? $status;
    $judul = "Status Janji Diperbarui";
    $pesan = "Janji untuk hewan {$janji['nama_hewan']} Anda sekarang berstatus: {$statusLabel}";
    
    $stmt2 = $pdo->prepare("INSERT INTO notifikasi (pelanggan_id, judul, pesan, jenis) VALUES (?, ?, ?, ?)");
    $stmt2->execute([$janji['pelanggan_id'], $judul, $pesan, 'info']);

    // Redirect dengan pesan sukses
    header('Location: janji_detail.php?id=' . $id . '&success=status_updated');
    exit;
} catch (PDOException $e) {
    header('Location: janji_detail.php?id=' . $id . '&error=' . urlencode($e->getMessage()));
    exit;
}

function getPerawatByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
?>