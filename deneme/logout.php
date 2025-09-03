<?php
require_once 'config.php';

// Session'ı temizle
session_destroy();

// Yeni session başlat
session_start();

// Başarı mesajı
$_SESSION['logout_message'] = 'Başarıyla çıkış yaptınız.';

// Ana sayfaya yönlendir
header('Location: index.php');
exit;
?>