<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir.']);
    exit;
}

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

try {
    // Form verilerini al ve temizle
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    // Validasyon
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik başlığı gereklidir.']);
        exit;
    }
    
    if (empty($event_date)) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik tarihi gereklidir.']);
        exit;
    }
    
    // Tarih formatını kontrol et
    $date = DateTime::createFromFormat('Y-m-d', $event_date);
    if (!$date) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz tarih formatı.']);
        exit;
    }
    
    // Saat formatını kontrol et (eğer girilmişse)
    if (!empty($event_time)) {
        $time = DateTime::createFromFormat('H:i', $event_time);
        if (!$time) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz saat formatı.']);
            exit;
        }
    } else {
        $event_time = null;
    }
    
    // Başlık uzunluğu kontrolü
    if (strlen($title) > 255) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik başlığı çok uzun (maksimum 255 karakter).']);
        exit;
    }
    
    // Açıklama uzunluğu kontrolü
    if (strlen($description) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Açıklama çok uzun (maksimum 1000 karakter).']);
        exit;
    }
    
    // Aynı tarih ve saatte başka etkinlik var mı kontrol et
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM events 
        WHERE user_id = ? AND event_date = ? AND event_time = ?
    ");
    $stmt->execute([$user_id, $event_date, $event_time]);
    $existing_count = $stmt->fetchColumn();
    
    if ($existing_count > 0 && !empty($event_time)) {
        echo json_encode(['success' => false, 'message' => 'Bu tarih ve saatte zaten bir etkinliğiniz var.']);
        exit;
    }
    
    // Etkinliği veritabanına ekle
    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, event_date, event_time, user_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $title,
        $description,
        $event_date,
        $event_time,
        $user_id
    ]);
    
    if ($result) {
        $event_id = $pdo->lastInsertId();
        
        // Eklenen etkinliği geri döndür
        $stmt = $pdo->prepare("
            SELECT e.*, u.username 
            FROM events e 
            JOIN users u ON e.user_id = u.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Etkinlik başarıyla eklendi.',
            'event' => $event
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Etkinlik eklenirken bir hata oluştu.']);
    }
    
} catch (PDOException $e) {
    error_log('Database error in add_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
} catch (Exception $e) {
    error_log('General error in add_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
}
?>