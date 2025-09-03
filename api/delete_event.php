<?php
require_once '../config.php';

header('Content-Type: application/json');

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
    // JSON verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['event_id'])) {
        echo json_encode(['success' => false, 'message' => 'Etkinlik ID gereklidir.']);
        exit;
    }
    
    $event_id = (int)$input['event_id'];
    $user_id = $_SESSION['user_id'];
    $is_admin = isAdmin();
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz etkinlik ID.']);
        exit;
    }
    
    // Etkinliği ve sahibini kontrol et
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
    
    // Yetki kontrolü: Sadece etkinlik sahibi veya admin silebilir
    if ($event['user_id'] != $user_id && !$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Bu etkinliği silme yetkiniz yok.']);
        exit;
    }
    
    // Etkinliği sil
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $result = $stmt->execute([$event_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Etkinlik başarıyla silindi.',
            'deleted_event' => [
                'id' => $event['id'],
                'title' => $event['title'],
                'event_date' => $event['event_date']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Etkinlik silinemedi.']);
    }
    
} catch (PDOException $e) {
    error_log('Database error in delete_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
} catch (Exception $e) {
    error_log('General error in delete_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
}
?>