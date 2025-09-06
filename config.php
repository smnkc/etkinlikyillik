<?php
// Veritabanı Bağlantı Konfigürasyonu
// cPanel MySQL ayarlarınıza göre düzenleyin

define('DB_HOST', 'localhost'); // XAMPP için localhost
define('DB_NAME', 'deneme'); // Veritabanı adınız
define('DB_USER', 'root'); // cPanel'deki MySQL kullanıcı adınız
define('DB_PASS', ''); // XAMPP varsayılan şifre boş
define('DB_CHARSET', 'utf8mb4');

// Session ayarları
define('SESSION_LIFETIME', 3600); // 1 saat

// Site ayarları
define('SITE_URL', 'http://localhost/deneme'); // XAMPP için URL
define('SITE_NAME', 'Ortak Takvim Sistemi');

// Güvenlik
define('HASH_ALGO', PASSWORD_DEFAULT);

// Veritabanı bağlantısı
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // UTF-8 karakter seti ayarları
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET character_set_connection=utf8mb4");
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}

// Session başlat
session_start();

// Yardımcı fonksiyonlar
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatDateTurkish($date) {
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = (int)date('m', $timestamp);
    $year = date('Y', $timestamp);
    
    return $day . ' ' . $months[$month] . ' ' . $year;
}

function formatMonthYearTurkish($date) {
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    $timestamp = strtotime($date);
    $month = (int)date('m', $timestamp);
    $year = date('Y', $timestamp);
    
    return $months[$month] . ' ' . $year;
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}
?>