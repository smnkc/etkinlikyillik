# Ortak Takvim Sistemi

Modern, responsive ve kullanıcı dostu bir ortak takvim sistemi. PHP, MySQL, HTML, CSS ve JavaScript kullanılarak geliştirilmiştir.

## Özellikler

### 🗓️ Takvim Özellikleri
- **Responsive Tasarım**: Mobil ve masaüstü uyumlu
- **Ay/Yıl Seçimi**: Kolay navigasyon
- **Etkinlik Görüntüleme**: Tüm kullanıcıların etkinliklerini görme
- **Bugün Vurgulama**: Güncel tarihin otomatik vurgulanması

### 👥 Kullanıcı Yönetimi
- **Güvenli Giriş Sistemi**: Session tabanlı kimlik doğrulama
- **Kullanıcı Rolleri**: Admin ve normal kullanıcı ayrımı
- **Şifre Güvenliği**: Bcrypt ile şifreleme

### 📅 Etkinlik Yönetimi
- **Etkinlik Ekleme**: Tarih, saat ve açıklama ile
- **Etkinlik Silme**: Sadece kendi etkinliklerini silme
- **Etkinlik Detayları**: Tam bilgi görüntüleme
- **Gerçek Zamanlı Güncelleme**: AJAX ile anlık işlemler

### 🔧 Admin Paneli
- **Kullanıcı Yönetimi**: Ekleme, silme, şifre değiştirme
- **Admin Yetkileri**: Kullanıcı rolü değiştirme
- **Etkinlik Kontrolü**: Tüm etkinlikleri yönetme
- **İstatistikler**: Sistem kullanım bilgileri

## Kurulum

### Gereksinimler
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucusu (Apache/Nginx)
- cPanel destekli hosting

### Adım 1: Dosyaları Yükleme
1. Tüm dosyaları hosting hesabınızın public_html klasörüne yükleyin
2. Dosya izinlerinin doğru olduğundan emin olun

### Adım 2: Veritabanı Kurulumu
1. cPanel'de MySQL Databases bölümüne gidin
2. Yeni bir veritabanı oluşturun (örn: `calendar_system`)
3. Veritabanı kullanıcısı oluşturun ve gerekli yetkileri verin
4. phpMyAdmin'e gidin ve `database.sql` dosyasını import edin

### Adım 3: Konfigürasyon
1. `config.php` dosyasını düzenleyin:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('SITE_URL', 'http://yourdomain.com');
```

### Adım 4: Test
1. Tarayıcınızda sitenizi açın
2. Demo hesaplarla giriş yapın:
   - **Admin**: `admin` / `admin123`
   - **Kullanıcı**: `kullanici1` / `admin123`

## Kullanım

### Temel Kullanım
1. **Giriş Yapma**: Sağ üstteki "Giriş Yap" butonuna tıklayın
2. **Etkinlik Ekleme**: Takvimde bir tarihe tıklayın ve "+" butonuna basın
3. **Etkinlik Görüntüleme**: Etkinliklere tıklayarak detayları görün
4. **Etkinlik Silme**: Ctrl+Click ile kendi etkinliklerinizi silin

### Admin İşlemleri
1. **Admin Paneli**: Giriş yaptıktan sonra "Admin Panel" butonuna tıklayın
2. **Kullanıcı Ekleme**: Kullanıcı Yönetimi sekmesinde form doldurun
3. **Şifre Değiştirme**: Kullanıcı listesinde "Şifre" butonuna tıklayın
4. **Etkinlik Yönetimi**: Tüm etkinlikleri görüntüleyin ve silin

## Güvenlik Özellikleri

- **SQL Injection Koruması**: Prepared statements kullanımı
- **XSS Koruması**: Input sanitization
- **Session Güvenliği**: Güvenli session yönetimi
- **Şifre Güvenliği**: Bcrypt hash algoritması
- **Yetki Kontrolü**: Rol tabanlı erişim kontrolü

## Responsive Tasarım

### Masaüstü (1200px+)
- Tam özellikli takvim görünümü
- Yan yana form elemanları
- Geniş tablo görünümleri

### Tablet (768px - 1199px)
- Orta boyut takvim
- Esnek form düzeni
- Optimize edilmiş navigasyon

### Mobil (767px ve altı)
- Kompakt takvim görünümü
- Dikey form düzeni
- Touch-friendly butonlar
- Swipe navigasyon

## API Endpoints

### Etkinlik İşlemleri
- `POST /api/add_event.php` - Etkinlik ekleme
- `POST /api/delete_event.php` - Etkinlik silme
- `GET /api/get_event.php?id={id}` - Etkinlik detayları

### Parametreler
```javascript
// Etkinlik Ekleme
{
    "title": "Etkinlik Başlığı",
    "description": "Açıklama",
    "event_date": "2024-02-15",
    "event_time": "14:30"
}

// Etkinlik Silme
{
    "event_id": 123
}
```

## Veritabanı Yapısı

### users Tablosu
```sql
id (INT, AUTO_INCREMENT, PRIMARY KEY)
username (VARCHAR(50), UNIQUE)
password (VARCHAR(255))
is_admin (TINYINT(1), DEFAULT 0)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

### events Tablosu
```sql
id (INT, AUTO_INCREMENT, PRIMARY KEY)
title (VARCHAR(255))
description (TEXT)
event_date (DATE)
event_time (TIME)
user_id (INT, FOREIGN KEY)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

## Sorun Giderme

### Yaygın Sorunlar

1. **Veritabanı Bağlantı Hatası**
   - `config.php` dosyasındaki veritabanı bilgilerini kontrol edin
   - Veritabanı kullanıcısının yetkileri olduğundan emin olun

2. **404 Hataları**
   - Dosya yollarının doğru olduğunu kontrol edin
   - .htaccess dosyası varsa kontrol edin

3. **Session Sorunları**
   - PHP session ayarlarını kontrol edin
   - Hosting sağlayıcınızın session desteğini kontrol edin

4. **CSS/JS Yüklenmiyor**
   - Dosya yollarını kontrol edin
   - Dosya izinlerini kontrol edin

### Log Dosyaları
- PHP hataları için hosting control panelindeki error logs'u kontrol edin
- Tarayıcı console'unda JavaScript hatalarını kontrol edin

## Özelleştirme

### Tema Değişiklikleri
- `style.css` dosyasını düzenleyerek renkleri değiştirebilirsiniz
- CSS değişkenleri kullanarak kolay özelleştirme yapabilirsiniz

### Dil Desteği
- Tüm metinler Türkçe olarak hazırlanmıştır
- Farklı dil desteği için string'leri değiştirin

### Ek Özellikler
- Etkinlik kategorileri eklenebilir
- Takvim export/import özellikleri eklenebilir

## Lisans

Bu proje açık kaynak kodludur ve MIT lisansı altında dağıtılmaktadır.

## Destek

Sorularınız için:
1. README dosyasını kontrol edin
2. Kod yorumlarını inceleyin
3. Hosting sağlayıcınızın desteğine başvurun

## Sürüm Geçmişi

### v1.0.0 (İlk Sürüm)
- Temel takvim işlevselliği
- Kullanıcı yönetimi
- Admin paneli
- Responsive tasarım
- API endpoints
- Güvenlik özellikleri

---

**Not**: Bu sistem cPanel destekli hosting ortamları için optimize edilmiştir. Farklı hosting türleri için konfigürasyon ayarları gerekebilir.