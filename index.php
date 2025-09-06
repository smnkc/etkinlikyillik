<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Ay ve yıl parametrelerini al
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Geçerli ay ve yıl kontrolü
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = date('n');
}
if ($currentYear < 1900 || $currentYear > 2100) {
    $currentYear = date('Y');
}

// Ay bilgileri
$monthNames = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

// Takvim hesaplamaları
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay); // 0=Pazar, 1=Pazartesi...
$startDay = ($startDay == 0) ? 7 : $startDay; // Pazartesi başlangıç için

// Bu ay için etkinlikleri getir
$stmt = $pdo->prepare("
    SELECT e.*, u.username 
    FROM events e 
    JOIN users u ON e.user_id = u.id 
    WHERE YEAR(e.event_date) = ? AND MONTH(e.event_date) = ?
    ORDER BY e.event_date, e.event_time
");
$stmt->execute([$currentYear, $currentMonth]);
$events = $stmt->fetchAll();

// Etkinlikleri tarihe göre grupla
$eventsByDate = [];
foreach ($events as $event) {
    $day = date('j', strtotime($event['event_date']));
    $eventsByDate[$day][] = $event;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1 class="site-title">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo SITE_NAME; ?>
                </h1>
                <div class="user-section">
                    <?php if (isLoggedIn()): ?>
                        <div class="user-info">
                            <span class="welcome-text">
                                <i class="fas fa-user"></i>
                                Hoş geldin, <?php echo sanitizeInput($_SESSION['username']); ?>
                                <?php if (isAdmin()): ?>
                                    <span class="admin-badge">Admin</span>
                                <?php endif; ?>
                            </span>
                            <div class="user-menu">
                                <?php if (isAdmin()): ?>
                                    <a href="admin.php" class="btn btn-admin">
                                        <i class="fas fa-cog"></i> Admin Panel
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="btn btn-logout">
                                    <i class="fas fa-sign-out-alt"></i> Çıkış
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="login-section">
                            <a href="login.php" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Calendar Navigation -->
        <div class="calendar-nav">
            <div class="nav-controls">
                <div class="month-year-selector">
                    <select id="monthSelect" onchange="changeDate()">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $currentMonth ? 'selected' : ''; ?>>
                                <?php echo $monthNames[$i]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    
                    <select id="yearSelect" onchange="changeDate()">
                        <?php for ($i = date('Y') - 5; $i <= date('Y') + 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $currentYear ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="nav-arrows">
                    <a href="?month=<?php echo $currentMonth == 1 ? 12 : $currentMonth - 1; ?>&year=<?php echo $currentMonth == 1 ? $currentYear - 1 : $currentYear; ?>" class="nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <a href="?month=<?php echo $currentMonth == 12 ? 1 : $currentMonth + 1; ?>&year=<?php echo $currentMonth == 12 ? $currentYear + 1 : $currentYear; ?>" class="nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="today-btn">
                    <a href="index.php" class="btn btn-today">
                        <i class="fas fa-calendar-day"></i> Bugün
                    </a>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="calendar">
            <div class="calendar-header">
                <div class="day-name">Pzt</div>
                <div class="day-name">Sal</div>
                <div class="day-name">Çar</div>
                <div class="day-name">Per</div>
                <div class="day-name">Cum</div>
                <div class="day-name">Cmt</div>
                <div class="day-name">Paz</div>
            </div>
            
            <div class="calendar-body">
                <?php
                // Boş hücreler (önceki ayın son günleri)
                for ($i = 1; $i < $startDay; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                
                // Ayın günleri
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $isToday = ($day == date('j') && $currentMonth == date('n') && $currentYear == date('Y'));
                    $hasEvents = isset($eventsByDate[$day]);
                    
                    echo '<div class="calendar-day' . ($isToday ? ' today' : '') . ($hasEvents ? ' has-events' : '') . '" data-date="' . $currentYear . '-' . sprintf('%02d', $currentMonth) . '-' . sprintf('%02d', $day) . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    if ($hasEvents) {
                        $eventCount = count($eventsByDate[$day]);
                        echo '<div class="event-count">' . $eventCount . '</div>';
                        echo '<div class="events" style="display: none;">';
                        foreach ($eventsByDate[$day] as $event) {
                            $timeStr = $event['event_time'] ? formatTime($event['event_time']) : '';
                            echo '<div class="event" data-event-id="' . $event['id'] . '" title="' . sanitizeInput($event['title']) . ' - ' . sanitizeInput($event['username']) . '">';
                            echo '<div class="event-time">' . $timeStr . '</div>';
                            echo '<div class="event-title">' . sanitizeInput($event['title']) . '</div>';
                            echo '<div class="event-user">(' . sanitizeInput($event['username']) . ')</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    
                    if (isLoggedIn()) {
                        echo '<div class="add-event-btn" onclick="openEventModal(\'' . $currentYear . '-' . sprintf('%02d', $currentMonth) . '-' . sprintf('%02d', $day) . '\')">';
                        echo '<i class="fas fa-plus"></i>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Event Modal -->
    <?php if (isLoggedIn()): ?>
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Etkinlik Ekle</h3>
                <span class="close" onclick="closeEventModal()">&times;</span>
            </div>
            <form id="eventForm">
                <input type="hidden" id="eventDate" name="event_date">
                <div class="form-group">
                    <label for="eventTitle">Etkinlik Başlığı:</label>
                    <input type="text" id="eventTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="eventDescription">Açıklama:</label>
                    <textarea id="eventDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="eventTime">Saat:</label>
                    <input type="time" id="eventTime" name="event_time">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeEventModal()" class="btn btn-cancel">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Event List Modal -->
    <div id="eventListModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="eventListTitle">Etkinlikler</h3>
                <div class="modal-header-actions">
                    <?php if (isLoggedIn()): ?>
                    <span class="add-event-header-btn" onclick="openAddEventFromList()" title="Yeni Etkinlik Ekle">+ Yeni</span>
                    <?php endif; ?>
                    <span class="close" onclick="closeEventListModal()">&times;</span>
                </div>
            </div>
            <div class="modal-body">
                <div id="eventListContent" class="event-list-content">
                    <!-- Etkinlikler buraya yüklenecek -->
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>