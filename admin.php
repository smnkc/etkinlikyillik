<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Admin kontrolü
requireAdmin();

$message = '';
$error = '';

// İşlem türünü belirle
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'add_user':
                $username = sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                
                if (empty($username) || empty($password)) {
                    $error = 'Tüm alanlar gereklidir.';
                } elseif (strlen($password) < 6) {
                    $error = 'Şifre en az 6 karakter olmalıdır.';
                } else {
                    // Kullanıcı adı kontrolü
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Bu kullanıcı adı zaten kullanılıyor.';
                    } else {
                        $hashed_password = password_hash($password, HASH_ALGO);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
                        $stmt->execute([$username, $hashed_password, $is_admin]);
                        $message = 'Kullanıcı başarıyla eklendi.';
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                if ($user_id <= 0) {
                    $error = 'Geçersiz kullanıcı ID.';
                } elseif ($user_id == $_SESSION['user_id']) {
                    $error = 'Kendi hesabınızı silemezsiniz.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Kullanıcı başarıyla silindi.';
                    } else {
                        $error = 'Kullanıcı bulunamadı.';
                    }
                }
                break;
                
            case 'change_password':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $new_password = $_POST['new_password'] ?? '';
                
                if ($user_id <= 0) {
                    $error = 'Geçersiz kullanıcı ID.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Şifre en az 6 karakter olmalıdır.';
                } else {
                    $hashed_password = password_hash($new_password, HASH_ALGO);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Şifre başarıyla değiştirildi.';
                    } else {
                        $error = 'Kullanıcı bulunamadı.';
                    }
                }
                break;
                
            case 'toggle_admin':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $is_admin = (int)($_POST['is_admin'] ?? 0);
                
                if ($user_id <= 0) {
                    $error = 'Geçersiz kullanıcı ID.';
                } elseif ($user_id == $_SESSION['user_id']) {
                    $error = 'Kendi admin durumunuzu değiştiremezsiniz.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
                    $stmt->execute([$is_admin, $user_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Kullanıcı durumu başarıyla güncellendi.';
                    } else {
                        $error = 'Kullanıcı bulunamadı.';
                    }
                }
                break;
                
            case 'edit_username':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $new_username = sanitizeInput($_POST['new_username'] ?? '');
                
                if ($user_id <= 0) {
                    $error = 'Geçersiz kullanıcı ID.';
                } elseif (empty($new_username)) {
                    $error = 'Kullanıcı adı boş olamaz.';
                } else {
                    // Kullanıcı adı kontrolü (başka kullanıcıda var mı?)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$new_username, $user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Bu kullanıcı adı zaten kullanılıyor.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                        $stmt->execute([$new_username, $user_id]);
                        if ($stmt->rowCount() > 0) {
                            // Eğer kendi kullanıcı adını değiştirdiyse session'ı güncelle
                            if ($user_id == $_SESSION['user_id']) {
                                $_SESSION['username'] = $new_username;
                            }
                            $message = 'Kullanıcı adı başarıyla güncellendi.';
                        } else {
                            $error = 'Kullanıcı bulunamadı.';
                        }
                    }
                }
                break;
                
            case 'delete_event':
                $event_id = (int)($_POST['event_id'] ?? 0);
                if ($event_id <= 0) {
                    $error = 'Geçersiz etkinlik ID.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->execute([$event_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Etkinlik başarıyla silindi.';
                    } else {
                        $error = 'Etkinlik bulunamadı.';
                    }
                }
                break;
                
            case 'delete_multiple_events':
                $event_ids = $_POST['event_ids'] ?? [];
                if (empty($event_ids) || !is_array($event_ids)) {
                    $error = 'Silinecek etkinlik seçilmedi.';
                } else {
                    $placeholders = str_repeat('?,', count($event_ids) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id IN ($placeholders)");
                    $stmt->execute($event_ids);
                    $deleted_count = $stmt->rowCount();
                    if ($deleted_count > 0) {
                        $message = "$deleted_count etkinlik başarıyla silindi.";
                    } else {
                        $error = 'Hiçbir etkinlik silinemedi.';
                    }
                }
                break;
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Kullanıcıları getir
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Etkinlikleri getir
$stmt = $pdo->prepare("
    SELECT e.*, u.username 
    FROM events e 
    JOIN users u ON e.user_id = u.id 
    ORDER BY e.event_date DESC, e.event_time DESC
");
$stmt->execute();
$events = $stmt->fetchAll();

// İstatistikler
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM events");
$stmt->execute();
$total_events = $stmt->fetchColumn();

// Yaklaşan etkinlik sayısı kaldırıldı
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .admin-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-tab:hover, .nav-tab.active {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #4a5568;
            font-weight: 500;
        }
        
        .admin-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            display: none;
        }
        
        .admin-section.active {
            display: block;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #4a5568;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .admin-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        .admin-table tr:hover {
            background: #f7fafc;
        }
        
        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #38a169;
            color: white;
        }
        
        .btn-delete {
            background: #e53e3e;
            color: white;
        }
        
        .btn-toggle {
            background: #ed8936;
            color: white;
        }
        
        .action-btn:hover {
            transform: scale(1.05);
        }
        
        .admin-badge {
            background: #e53e3e;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .user-badge {
            background: #38a169;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        @media (max-width: 768px) {
            .admin-nav {
                justify-content: center;
            }
            
            .nav-tab {
                font-size: 0.9rem;
                padding: 8px 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-table {
                font-size: 0.9rem;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="header-content">
                <h1 class="site-title">
                    <i class="fas fa-cog"></i>
                    Admin Panel
                </h1>
                <div class="user-section">
                    <span class="welcome-text">
                        <i class="fas fa-user-shield"></i>
                        <?php echo sanitizeInput($_SESSION['username']); ?>
                    </span>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> Takvime Dön
                    </a>
                    <a href="logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </div>
            </div>
        </div>
        
        <!-- İstatistikler -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Toplam Kullanıcı</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_events; ?></div>
                <div class="stat-label">Toplam Etkinlik</div>
            </div>
        </div>
        
        <!-- Mesajlar -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Navigasyon -->
        <div class="admin-nav">
            <button class="nav-tab active" onclick="showSection('users')">
                <i class="fas fa-users"></i> Kullanıcı Yönetimi
            </button>
            <button class="nav-tab" onclick="showSection('events')">
                <i class="fas fa-calendar-check"></i> Etkinlik Yönetimi
            </button>
        </div>
        
        <!-- Kullanıcı Yönetimi -->
        <div id="users" class="admin-section active">
            <h2 class="section-title">
                <i class="fas fa-users"></i>
                Kullanıcı Yönetimi
            </h2>
            
            <!-- Kullanıcı Ekleme Formu -->
            <form method="POST" action="?tab=users">
                <input type="hidden" name="action" value="add_user">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Kullanıcı Adı:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Şifre:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_admin" name="is_admin">
                            <label for="is_admin">Admin Yetkisi</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Kullanıcı Ekle
                </button>
            </form>
            
            <!-- Kullanıcı Listesi -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kullanıcı Adı</th>
                            <th>Durum</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo sanitizeInput($user['username']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php else: ?>
                                        <span class="user-badge">Kullanıcı</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <button class="action-btn btn-edit" onclick="editUsername(<?php echo $user['id']; ?>, '<?php echo sanitizeInput($user['username']); ?>')">
                                        <i class="fas fa-edit"></i> Kullanıcı Adı
                                    </button>
                                    <button class="action-btn btn-edit" onclick="changePassword(<?php echo $user['id']; ?>, '<?php echo sanitizeInput($user['username']); ?>')">
                                        <i class="fas fa-key"></i> Şifre
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="action-btn btn-toggle" onclick="toggleAdmin(<?php echo $user['id']; ?>, <?php echo $user['is_admin'] ? 0 : 1; ?>, '<?php echo sanitizeInput($user['username']); ?>')">
                                            <i class="fas fa-user-cog"></i> <?php echo $user['is_admin'] ? 'Admin Kaldır' : 'Admin Yap'; ?>
                                        </button>
                                        <button class="action-btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo sanitizeInput($user['username']); ?>')">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Etkinlik Yönetimi -->
        <div id="events" class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-calendar-check"></i>
                Etkinlik Yönetimi
            </h2>
            
            <!-- Toplu İşlemler -->
            <div class="bulk-actions" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
                        <input type="checkbox" id="selectAll" onchange="toggleAllEvents()" style="width: auto;">
                        Tümünü Seç
                    </label>
                    <button type="button" class="btn btn-danger" onclick="deleteSelectedEvents()" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-trash"></i> Seçilenleri Sil
                    </button>
                    <span id="selectedCount" style="color: #6c757d; font-size: 0.9rem;">0 etkinlik seçildi</span>
                </div>
            </div>
            
            <form id="bulkDeleteForm" method="POST" action="?tab=events">
                 <input type="hidden" name="action" value="delete_multiple_events">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">Seç</th>
                                <th>ID</th>
                                <th>Başlık</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Ekleyen</th>
                                <th>Oluşturma Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="event_ids[]" value="<?php echo $event['id']; ?>" class="event-checkbox" onchange="updateSelectedCount()" style="width: auto;">
                                    </td>
                                    <td><?php echo $event['id']; ?></td>
                                    <td><?php echo sanitizeInput($event['title']); ?></td>
                                    <td><?php echo formatDate($event['event_date']); ?></td>
                                    <td><?php echo $event['event_time'] ? formatTime($event['event_time']) : '-'; ?></td>
                                    <td><?php echo sanitizeInput($event['username']); ?></td>
                                    <td><?php echo formatDate($event['created_at']); ?></td>
                                    <td>
                                        <button type="button" class="action-btn btn-delete" onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo sanitizeInput($event['title']); ?>')">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showSection(sectionId) {
            // Tüm seksiyon ve tabları gizle/pasif yap
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Seçilen seksiyonu göster
            document.getElementById(sectionId).classList.add('active');
            
            // Doğru tab butonunu aktif yap
            document.querySelector(`[onclick="showSection('${sectionId}')"]`).classList.add('active');
        }
        
        // Sayfa yüklendiğinde URL parametresine göre sekme aç
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            
            if (tab === 'events') {
                showSection('events');
            } else {
                showSection('users'); // Varsayılan olarak kullanıcı yönetimi
            }
        });
        
        function deleteUser(userId, username) {
            if (confirm(`"${username}" kullanıcısını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?tab=users';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editUsername(userId, currentUsername) {
            const newUsername = prompt(`"${currentUsername}" kullanıcısı için yeni kullanıcı adını girin:`, currentUsername);
            if (newUsername && newUsername.trim() !== '' && newUsername !== currentUsername) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?tab=users';
                form.innerHTML = `
                    <input type="hidden" name="action" value="edit_username">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_username" value="${newUsername.trim()}">
                `;
                document.body.appendChild(form);
                form.submit();
            } else if (newUsername !== null && newUsername.trim() === '') {
                alert('Kullanıcı adı boş olamaz.');
            }
        }
        
        function changePassword(userId, username) {
            const newPassword = prompt(`"${username}" kullanıcısı için yeni şifre girin (en az 6 karakter):`);
            if (newPassword && newPassword.length >= 6) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?tab=users';
                form.innerHTML = `
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_password" value="${newPassword}">
                `;
                document.body.appendChild(form);
                form.submit();
            } else if (newPassword !== null) {
                alert('Şifre en az 6 karakter olmalıdır.');
            }
        }
        
        function toggleAdmin(userId, isAdmin, username) {
            const action = isAdmin ? 'admin yapmak' : 'admin yetkisini kaldırmak';
            if (confirm(`"${username}" kullanıcısını ${action} istediğinizden emin misiniz?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?tab=users';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_admin">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="is_admin" value="${isAdmin}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteEvent(eventId, title) {
            if (confirm(`"${title}" etkinliğini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?tab=events';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="event_id" value="${eventId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleAllEvents() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.event-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.event-checkbox:checked');
            const count = checkboxes.length;
            const countElement = document.getElementById('selectedCount');
            const selectAll = document.getElementById('selectAll');
            const totalCheckboxes = document.querySelectorAll('.event-checkbox');
            
            countElement.textContent = `${count} etkinlik seçildi`;
            
            // "Tümünü Seç" checkbox durumunu güncelle
            if (count === 0) {
                selectAll.indeterminate = false;
                selectAll.checked = false;
            } else if (count === totalCheckboxes.length) {
                selectAll.indeterminate = false;
                selectAll.checked = true;
            } else {
                selectAll.indeterminate = true;
                selectAll.checked = false;
            }
        }
        
        function deleteSelectedEvents() {
            const checkboxes = document.querySelectorAll('.event-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('Lütfen silinecek etkinlikleri seçin.');
                return;
            }
            
            if (confirm(`Seçilen ${checkboxes.length} etkinliği silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }
    </script>
</body>
</html>