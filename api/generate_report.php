<?php
require_once '../config.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    die('Bu işlem için admin yetkisi gereklidir.');
}

// Parametreleri al
$year = (int)($_GET['year'] ?? date('Y'));
$month = !empty($_GET['month']) ? (int)$_GET['month'] : null;
$format = $_GET['format'] ?? 'pdf';

// Tarih aralığını belirle
if ($month) {
    $startDate = "$year-" . sprintf('%02d', $month) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate)); // Ayın son günü
    $periodText = formatMonthYearTurkish($startDate);
} else {
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
    $periodText = $year . ' Yılı';
}

try {
    // Etkinlikleri getir
    $stmt = $pdo->prepare("
        SELECT e.*, u.username 
        FROM events e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.event_date >= ? AND e.event_date <= ?
        ORDER BY e.event_date ASC, e.event_time ASC
    ");
    $stmt->execute([$startDate, $endDate]);
    $events = $stmt->fetchAll();
    
    // PDF oluştur (basit HTML to PDF)
    if ($format === 'pdf') {
        // PDF başlıkları
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="etkinlik_raporu_' . $year . ($month ? '_' . sprintf('%02d', $month) : '') . '.pdf"');
        
        // Basit PDF oluşturma (HTML to PDF)
        // Not: Gerçek PDF için TCPDF veya FPDF kullanılabilir, ancak statik host için basit çözüm
        
        // HTML çıktısı (PDF olarak gösterilecek)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="etkinlik_raporu_' . $year . ($month ? '_' . sprintf('%02d', $month) : '') . '.html"');
        
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etkinlik Raporu - ' . $periodText . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .event { margin-bottom: 5px; padding: 8px; border-bottom: 1px solid #eee; }
        .event-row { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        .event-date { color: #666; min-width: 80px; }
        .event-time { color: #666; min-width: 60px; }
        .event-title { font-weight: bold; color: #333; min-width: 150px; }
        .event-description { color: #555; flex: 1; }
        .event-user { color: #888; font-style: italic; min-width: 100px; text-align: right; }
        .table-header { margin-bottom: 10px; }
        .header-row { display: flex; align-items: center; gap: 15px; font-size: 14px; font-weight: bold; background: #f0f0f0; padding: 10px 8px; border: 1px solid #ddd; }
        .header-date { color: #333; min-width: 80px; }
        .header-time { color: #333; min-width: 60px; }
        .header-title { color: #333; min-width: 150px; }
        .header-description { color: #333; flex: 1; }
        .header-user { color: #333; min-width: 100px; text-align: right; }
        .no-events { text-align: center; color: #666; margin: 50px 0; }
        .summary { margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Etkinlik Raporu</h1>
        <h2>' . $periodText . '</h2>
        <p>Rapor Tarihi: ' . date('d.m.Y H:i') . '</p>
    </div>
';
        
        if (empty($events)) {
            echo '<div class="no-events">
                <h3>Bu dönemde hiç etkinlik bulunmuyor.</h3>
            </div>';
        } else {
            // Tablo başlıkları
            echo '<div class="table-header">';
            echo '    <div class="header-row">';
            echo '        <div class="header-date">Tarih</div>';
            echo '        <div class="header-time">Saat</div>';
            echo '        <div class="header-title">Etkinlik Başlığı</div>';
            echo '        <div class="header-description">Açıklama</div>';
            echo '        <div class="header-user">Ekleyen</div>';
            echo '    </div>';
            echo '</div>';
            
            foreach ($events as $event) {
                echo '<div class="event">
';
                echo '    <div class="event-row">
';
                echo '        <div class="event-date">' . formatDateTurkish($event['event_date']) . '</div>';
                echo '        <div class="event-time">' . ($event['event_time'] ? formatTime($event['event_time']) : '-') . '</div>';
                echo '        <div class="event-title">' . htmlspecialchars($event['title']) . '</div>';
                echo '        <div class="event-description">' . ($event['description'] ? htmlspecialchars($event['description']) : '-') . '</div>';
                echo '        <div class="event-user">' . htmlspecialchars($event['username']) . '</div>';
                echo '    </div>';
                echo '</div>';            }
            
            // Özet bilgiler
            echo '<div class="summary">';
            echo '    <h3>Özet</h3>';
            echo '    <p><strong>Toplam Etkinlik:</strong> ' . count($events) . '</p>';
            echo '    <p><strong>Dönem:</strong> ' . $periodText . '</p>';
            echo '</div>';        }
        
        echo '</body>
</html>';
    }
    
} catch (PDOException $e) {
    error_log('Database error in generate_report.php: ' . $e->getMessage());
    http_response_code(500);
    die('Rapor oluşturulurken veritabanı hatası oluştu.');
} catch (Exception $e) {
    error_log('General error in generate_report.php: ' . $e->getMessage());
    http_response_code(500);
    die('Rapor oluşturulurken bir hata oluştu.');
}
?>