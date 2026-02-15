# VMDestek - Canlı Destek Sistemi

<p align="center">
  <img src="https://img.shields.io/badge/Versiyon-1.0.16-blue" alt="Version">
  <img src="https://img.shields.io/badge/PHP-7.4+-green" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-orange" alt="MySQL">
  <img src="https://img.shields.io/badge/Lisans-MIT-yellow" alt="License">
</p>

Web sitelerinize kolayca entegre edebileceğiniz, hafif ve modern bir canlı destek chat sistemi.

## ✨ Özellikler

- 💬 **Gerçek Zamanlı Chat** — Ziyaretçi-admin arası anlık mesajlaşma (long polling)
- 🖼️ **Resim Gönderme** — Ziyaretçiler sohbet sırasında resim paylaşabilir (5MB limit, jpg/png/gif/webp)
- 👥 **Anlık Ziyaretçi Takibi** — Sitedeki aktif ziyaretçileri canlı izleme
- 🎨 **Özelleştirilebilir Widget** — Renk, gradient, şirket adı, logo ayarları
- 🔄 **Temsilci Aktarma** — Konuşmaları başka temsilcilere aktarma
- 📝 **Not & Hatırlatıcı** — Konuşmalara not ekle, hatırlatıcı kur
- 📋 **Hazır Yanıtlar** — Sık kullanılan cevapları tek tıkla gönder (`/` kısayolları)
- 🔔 **Bildirimler** — Yeni mesaj ses bildirimi
- 📱 **Responsive Tasarım** — Mobil ve masaüstü uyumlu
- 🔄 **Otomatik Güncelleme** — Admin panelinden tek tıkla güncelle
- 🌐 **Kolay Entegrasyon** — Tek satır `<script>` kodu ile web sitesine ekle
- ⭐ **Konuşma Değerlendirme** — Sohbet sonrası 5 yıldızlı puanlama

## 📁 Proje Yapısı

```
LiveSupport/
├── admin/                  # Admin panel
│   ├── index.php           # Dashboard (sohbetler, ziyaretçiler)
│   ├── settings.php        # Ayarlar sayfası (sekmeli yapı)
│   ├── login.php           # Giriş sayfası
│   ├── js/app.js           # Admin JS mantığı
│   └── css/style.css       # Admin stilleri
├── api/                    # API endpoint'leri
│   ├── admin.php           # Admin işlemleri (sohbet, transfer, notlar, hatırlatıcılar)
│   ├── chat.php            # Widget tarafı chat API (mesaj, upload, typing, rating)
│   ├── visitor.php         # Ziyaretçi takibi (heartbeat + liste)
│   └── update.php          # Otomatik güncelleme API
├── uploads/                # Kullanıcı yüklemeleri
│   └── chat/               # Sohbet resimleri (.htaccess korumalı)
├── widget/                 # Eski widget (iframe, deprecated)
│   ├── index.php
│   └── js/widget.js
├── embed.js                # Ana widget script — sitelere eklenen kod
│                           # İçerisinde: CSS, HTML, chat mantığı, resim upload,
│                           # lightbox, polling, heartbeat — hepsi tek dosyada
├── db.php                  # Veritabanı bağlantı sınıfı (PDO wrapper)
├── config.php              # Yapılandırma dosyası (DB bilgileri, site URL)
├── install.php             # Web tabanlı kurulum sihirbazı
├── database.sql            # Veritabanı şeması + varsayılan veriler
└── version.json            # Sürüm bilgisi (güncelleme sistemi bunu okur)
```

## 🏗️ Mimari

### Widget (embed.js)
Widget artık **iframe kullanmaz**. `embed.js` tek bir IIFE içinde çalışır ve doğrudan host sayfaya enjekte edilir:
- **CSS** `<style>` etiketi ile `document.head`'e eklenir, tüm selektörler `#vmd-widget` altında scope edilir
- **HTML** DOM API ile oluşturulur (`createElement`)
- **State yönetimi** closure variable'ları ile yapılır (`sessionId`, `lastMsgId`, `pendingFile` vb.)
- **Mesajlaşma** REST API + polling ile çalışır
- Host sayfanın CSS'inden **tamamen izole** — tüm stiller `!important` ile korunur

### API Endpoint'leri

| Endpoint | Method | Açıklama |
|----------|--------|----------|
| `api/chat.php?action=status` | GET | Widget config + online durumu |
| `api/chat.php?action=start` | POST | Yeni konuşma başlat |
| `api/chat.php?action=send` | POST | Mesaj gönder (text) |
| `api/chat.php?action=upload` | POST | Resim yükle (multipart/form-data) |
| `api/chat.php?action=messages` | GET | Mesajları getir (after_id ile delta) |
| `api/chat.php?action=typing` | POST | Typing indicator |
| `api/chat.php?action=end` | POST | Konuşmayı bitir |
| `api/chat.php?action=rate` | POST | Konuşma puanla (1-5) |
| `api/admin.php?action=conversations` | GET | Konuşma listesi |
| `api/admin.php?action=send` | POST | Admin mesaj gönder |
| `api/admin.php?action=transfer` | POST | Konuşma aktar |
| `api/admin.php?action=leave` | POST | Konuşmadan ayrıl |
| `api/admin.php?action=close` | POST | Konuşmayı kapat |
| `api/admin.php?action=admin_list` | GET | Admin listesi (transfer modal) |
| `api/visitor.php?action=heartbeat` | POST | Ziyaretçi heartbeat |
| `api/visitor.php?action=list` | GET | Aktif ziyaretçi listesi |

### Veritabanı Tabloları

| Tablo | Açıklama |
|-------|----------|
| `admins` | Admin kullanıcıları (username, password_hash, role, is_online) |
| `conversations` | Konuşmalar (visitor bilgileri, session_id, status, rating) |
| `messages` | Mesajlar (sender_type, message, message_type: text/image/system) |
| `canned_responses` | Hazır yanıtlar (title, message, shortcut, category) |
| `settings` | Sistem ayarları (key-value) |
| `conversation_notes` | Konuşma notları |
| `conversation_reminders` | Hatırlatmalar |

## 🚀 Kurulum

### Gereksinimler

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx web sunucusu

### Hızlı Kurulum

1. Dosyaları web sunucunuza yükleyin
2. Tarayıcıda `http://siteadresiniz/install.php` adresine gidin
3. Kurulum sihirbazını takip edin (DB bilgileri, admin hesabı)
4. Kurulum tamamlandığında `install.php` otomatik silinir

### Manuel Kurulum

1. `database.sql` dosyasını MySQL'e import edin
2. `config.php` dosyasını örnek alarak veritabanı bilgilerinizi girin:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'livesupport');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('SITE_URL', 'http://siteadresiniz');
   ```
3. `admin/login.php` adresinden giriş yapın (varsayılan: `admin` / `admin123`)

## 🔗 Web Sitesine Ekleme

Sitenizin `</body>` etiketinden önce:

```html
<script src="https://siteadresiniz/embed.js"></script>
```

Bu kod:
- Sağ alt köşeye bir sohbet butonu ekler
- 3 saniye sonra karşılama baloncuğu gösterir
- Butona tıklandığında chat widget'ı açılır
- Online/offline durumuna göre farklı form gösterir
- Ziyaretçi heartbeat ile otomatik takip başlatır

## ⚙️ Ayarlar

Admin panelinden (`/admin/settings.php`) aşağıdaki ayarlar yapılabilir:

| Sekme | Ayarlar |
|-------|---------|
| **Görünüm** | Widget rengi, gradient, şirket adı, logo |
| **Mesajlar** | Karşılama mesajı, çevrimdışı mesajı, otomatik yanıt |
| **Hazır Yanıtlar** | Kısayollu cevap şablonları ekleme/düzenleme |
| **Entegrasyon** | JS embed kodu, iframe embed kodu |
| **Kullanıcılar** | Admin/operatör ekleme, şifre değiştirme |
| **Sistem** | Güncelleme kontrolü, sürüm bilgisi |

## 🔄 Güncelleme Sistemi

1. Admin panelinde **Ayarlar** → **Sistem** sekmesine gidin
2. **Güncelleme Kontrol Et** butonuna tıklayın
3. Yeni sürüm varsa **Güncelle** butonuyla tek tıkla uygulayın

> **Not:** `config.php`, `install.lock`, `install.php` ve `uploads/` güncelleme sırasında korunur.

Güncelleme mekanizması GitHub raw content üzerinden çalışır:
- `version.json` indirilerek sürüm karşılaştırılır
- Dosya listesi `api/update.php` üzerinden sunulur
- Her dosya tek tek indirilip üzerine yazılır

## 🛡️ Güvenlik

- Resim yüklemelerde çift katmanlı doğrulama (MIME type + extension)
- `uploads/chat/.htaccess` ile PHP çalıştırma engeli
- Tüm SQL sorguları prepared statement ile çalışır
- Admin oturumları PHP session ile yönetilir
- XSS koruması: tüm mesajlar `escapeHtml()` ile sanitize edilir
- Widget CSS host sayfadan tamamen izole (`#vmd-widget` scope + `!important`)

## 📊 Sürüm Geçmişi

| Sürüm | Tarih | Değişiklikler |
|-------|-------|--------------|
| 1.0.16 | 2026-02-15 | Ziyaretçi resim gönderme (upload, önizleme, lightbox) |
| 1.0.15 | 2026-02-15 | Widget CSS scoped selectors, host sayfa izolasyonu |
| 1.0.14 | 2026-02-15 | Widget tasarım iyileştirmeleri, form field düzeltmeleri |
| 1.0.13 | 2026-02-15 | Konuşma aktarma/ayrılma, not ve hatırlatıcı sistemi |
| 1.0.12 | 2026-02-15 | Temsilci aktarma modalı, UTF-8 Türkçe karakter düzeltmesi |
| 1.0.11 | 2026-02-15 | Admin şifre değiştirme, kullanıcı yönetimi |
| 1.0.10 | 2026-02-15 | Embed.js tamamen yeniden yazıldı (iframe-free widget) |
| 1.0.9 | 2026-02-15 | Greeting bubble mantığı düzeltmesi |
| 1.0.8 | 2026-02-15 | Widget açıkken buton gizleme |
| 1.0.7 | 2026-02-15 | Anlık ziyaretçi takibi, greeting bubble |
| 1.0.6 | 2026-02-15 | Ayarlar sayfası sekmeli yapı |
| 1.0.5 | 2026-02-15 | Widget minimize, güncelleme mekanizması |
| 1.0.4 | 2026-02-15 | Türkçe karakter desteği |
| 1.0.3 | 2026-02-15 | İlk kararlı sürüm |

## 🔧 Geliştirme Notları

### Yerel Geliştirme
- XAMPP/WAMP üzerinde çalıştırılabilir
- `config.php` içindeki `SITE_URL` değerini `http://localhost/LiveSupport` yapın
- `test.html` dosyası widget test sayfası olarak kullanılır

### Dosya Düzenleme Rehberi

| Değişiklik | Dosya(lar) |
|-----------|-----------|
| Widget görünüm/davranış | `embed.js` (CSS + JS + HTML tek dosyada) |
| Widget API'si | `api/chat.php` |
| Admin panel UI | `admin/index.php` + `admin/css/style.css` |
| Admin panel davranış | `admin/js/app.js` |
| Admin API'si | `api/admin.php` |
| Ayarlar sayfası | `admin/settings.php` |
| Veritabanı şeması | `database.sql` |
| Güncelleme sistemi | `api/update.php` + `version.json` |

### Önemli Ayrıntılar
- **embed.js** çok büyük bir dosyadır (~50KB) — CSS, HTML ve JS'in tamamı tek IIFE içinde
- Widget CSS'i `#vmd-widget` altında scope edilmiştir, yeni CSS eklerken buna dikkat edin
- Mesaj türleri: `text`, `image`, `file`, `system` (DB ENUM)
- `message_type: 'image'` olan mesajlarda `message` alanı dosya yolunu içerir (`uploads/chat/...`)
- Admin panel `SITE_URL` ve `ADMIN_ID` PHP'den JS'e inline olarak geçirilir

## 📝 Lisans

MIT License — Serbestçe kullanabilir, değiştirebilir ve dağıtabilirsiniz.

## 👨‍💻 Geliştirici

**Semih AKBAŞ** — [semihakbas.com.tr](https://semihakbas.com.tr)
