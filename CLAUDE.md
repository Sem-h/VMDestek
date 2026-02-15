# VMDestek - Canlı Destek Sistemi

PHP tabanlı, web siteleri için gerçek zamanlı canlı destek (live chat) sistemi.
GitHub: https://github.com/Sem-h/VMDestek (Public)
Geliştirici: Semih AKBAŞ - semihakbas.com.tr

## Teknoloji
- **Backend**: PHP (Native), MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Vanilla JS
- **Tema**: Dark glassmorphism UI (Inter font, Font Awesome 6.5)

## Yapı
```
admin/          → Admin paneli (index.php, login.php, settings.php)
api/            → API (admin.php, chat.php, auth.php, update.php)
widget/         → Ziyaretçi chat widget'ı
config.php      → DB ve site ayarları (gitignore'da, install.php oluşturur)
db.php          → PDO veritabanı sınıfı
embed.js        → Widget embed betiği
install.php     → 4 adımlı kurulum sihirbazı
version.json    → Sürüm takibi (otomatik güncelleme için)
```

## Veritabanı Tabloları
admins, conversations, messages, canned_responses, settings, conversation_notes, conversation_reminders

## Önemli Akışlar
- **Kurulum**: install.php → DB oluşturur → admin hesabı → config.php yazar → install.lock kilitler
- **Güncelleme**: Ayarlar → Sistem Güncellemesi → GitHub'dan ZIP indir → dosyaları güncelle (config.php/uploads korunur)
- **Güncelleme gönderme**: version.json artır → git push (`/guncelleme-gonder` workflow)

## Mevcut Sürüm
- XAMPP üzerinde çalışıyor: http://localhost/LiveSupport/
- DB: livesupport, user: root, pass: rexe2026
