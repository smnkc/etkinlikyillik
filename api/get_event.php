<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Sadece GET isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece GET istekleri kabul edilir.']);
    exit;
}

try {
    $event_id = (int)($_GET['id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz etkinlik ID.']);
        exit;
    }
    
    // Etkinlik detaylarını getir
    $stmt = $pdo->prepare("
        SELECT e.*, u.username 
        FROM events e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik bulunamadı.']);
        exit;
    }
    
    // Kullanıcının bu etkinliği silip silemeyeceğini kontrol et
    $can_delete = false;
    if (isLoggedIn()) {
        $can_delete = ($event['user_id'] == $_SESSION['user_id']) || isAdmin();
    }
    
    // Tarih ve saat formatla
    $formatted_event = [
        'id' => $event['id'],
        'title' => $event['title'],
        'description' => $event['description'],
        'event_date' => $event['event_date'],
        'event_time' => $event['event_time'],
        'formatted_date' => formatDate($event['event_date']),
        'formatted_time' => $event['event_time'] ? formatTime($event['event_time']) : null,
        'username' => $event['username'],
        'user_id' => $event['user_id'],
        'created_at' => $event['created_at'],
        'updated_at' => $event['updated_at'],
        'can_delete' => $can_delete
    ];
    
    echo json_encode([
        'success' => true,
        'event' => $formatted_event
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in get_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
} catch (Exception $e) {
    error_log('General error in get_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
}
?>