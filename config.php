<?php
// ============================================
// KONFIGURASI DATABASE
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'klinik_hewan');
define('DB_USER', 'root');
define('DB_PASS', 'cokaberul123');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ============================================
// KONFIGURASI SESSION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// FUNGSI HELPER
// ============================================

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Cek role user
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Cek apakah user adalah pelanggan
function isPelanggan() {
    return isLoggedIn() && getUserRole() === 'pelanggan';
}

// Cek apakah user adalah perawat
function isPerawat() {
    return isLoggedIn() && getUserRole() === 'perawat';
}

// Cek apakah user adalah admin
function isAdmin() {
    return isLoggedIn() && getUserRole() === 'admin';
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

// Redirect jika bukan pelanggan
function requirePelanggan() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    if (!isPelanggan()) {
        header('Location: ../login.php');
        exit;
    }
}

// Redirect jika bukan perawat
function requirePerawat() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    if (!isPerawat() && !isAdmin()) {
        header('Location: ../login.php');
        exit;
    }
}

// ============================================
// FUNGSI KHUSUS ADMIN - DITAMBAHKAN
// ============================================

// Redirect jika bukan admin
function requireAdmin() {
    // Cek login dulu
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    // Cek role admin
    if (!isAdmin()) {
        // Jika bukan admin, redirect ke halaman sesuai role
        if (isPelanggan()) {
            header('Location: ../pelanggan/dashboard.php');
        } elseif (isPerawat()) {
            header('Location: ../perawat/dashboard.php');
        } else {
            header('Location: ../login.php');
        }
        exit;
    }
}

// ============================================
// FUNGSI UNTUK PELANGGAN
// ============================================

// Ambil data pelanggan berdasarkan user_id
function getPelangganByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Ambil data pelanggan berdasarkan ID
function getPelangganById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.email FROM pelanggan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Ambil semua hewan peliharaan pelanggan
function getHewanByPelangganId($pelanggan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM hewan_peliharaan WHERE pelanggan_id = ? ORDER BY created_at DESC");
    $stmt->execute([$pelanggan_id]);
    return $stmt->fetchAll();
}

// Ambil semua janji temu pelanggan
function getJanjiByPelangganId($pelanggan_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT j.*, h.nama_hewan, h.jenis_hewan, l.nama_layanan, l.harga, p.nama_lengkap as nama_perawat
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN perawat p ON j.perawat_id = p.id
        WHERE j.pelanggan_id = ?
        ORDER BY j.tanggal DESC, j.waktu DESC
    ");
    $stmt->execute([$pelanggan_id]);
    return $stmt->fetchAll();
}

// Hitung jumlah janji temu berdasarkan status
function countJanjiByStatus($pelanggan_id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM janji_temu WHERE pelanggan_id = ? AND status = ?");
    $stmt->execute([$pelanggan_id, $status]);
    return $stmt->fetch()['total'];
}

// Ambil notifikasi pelanggan
function getNotifikasi($pelanggan_id, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifikasi WHERE pelanggan_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindParam(1, $pelanggan_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Hitung notifikasi belum dibaca
function countNotifikasiUnread($pelanggan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifikasi WHERE pelanggan_id = ? AND dibaca = 0");
    $stmt->execute([$pelanggan_id]);
    return $stmt->fetch()['total'];
}

// Format rupiah
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Ambil semua layanan aktif
function getLayananAktif() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM layanan WHERE status = 'aktif' ORDER BY nama_layanan");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Ambil semua perawat aktif
function getPerawatAktif() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE status = 'aktif' ORDER BY nama_lengkap");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Ambil data hewan berdasarkan ID
function getHewanById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM hewan_peliharaan WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Ambil data janji temu berdasarkan ID
function getJanjiById($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT j.*, h.nama_hewan, h.jenis_hewan, l.nama_layanan, l.harga, p.nama_lengkap as nama_perawat
        FROM janji_temu j
        LEFT JOIN hewan_peliharaan h ON j.hewan_id = h.id
        LEFT JOIN layanan l ON j.layanan_id = l.id
        LEFT JOIN perawat p ON j.perawat_id = p.id
        WHERE j.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Ambil data layanan berdasarkan ID
function getLayananById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM layanan WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Ambil data perawat berdasarkan ID
function getPerawatById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perawat WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Tambah notifikasi
function addNotifikasi($pelanggan_id, $judul, $pesan, $jenis = 'info', $link = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifikasi (pelanggan_id, judul, pesan, jenis, link) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$pelanggan_id, $judul, $pesan, $jenis, $link]);
}

// Fungsi untuk membuat password hash (untuk registrasi)
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>