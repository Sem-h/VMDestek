# VMDestek - Canlı Destek Sistemi

<p align="center">
  <img src="https://img.shields.io/badge/Versiyon-1.0.8-blue" alt="Version">
  <img src="https://img.shields.io/badge/PHP-7.4+-green" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-orange" alt="MySQL">
  <img src="https://img.shields.io/badge/Lisans-MIT-yellow" alt="License">
</p>

Web sitelerinize kolayca entegre edebileceğiniz, hafif ve modern bir canlı destek chat sistemi.

## ✨ Özellikler

- 💬 **Gerçek Zamanlı Chat** — Ziyaretçi-admin arası anlık mesajlaşma
- 👥 **Anlık Ziyaretçi Takibi** — Sitedeki aktif ziyaretçileri canlı izleme
- 🎨 **Özelleştirilebilir Widget** — Renk, gradient, şirket adı ayarları
- 📝 **Not & Hatırlatıcı** — Konuşmalara not ekle, hatırlatıcı kur
- 📋 **Hazır Yanıtlar** — Sık kullanılan cevapları tek tıkla gönder
- 🔔 **Bildirimler** — Yeni mesaj ses bildirimi
- 📱 **Responsive Tasarım** — Mobil ve masaüstü uyumlu
- 🔄 **Otomatik Güncelleme** — Admin panelinden tek tıkla güncelle
- 🌐 **Kolay Entegrasyon** — Tek satır `<script>` kodu ile web sitesine ekle

## 📁 Proje Yapısı

```
LiveSupport/
├── admin/              # Admin panel
│   ├── index.php       # Dashboard (sohbetler, ziyaretçiler)
│   ├── settings.php    # Ayarlar sayfası
│   ├── login.php       # Giriş sayfası
│   ├── js/app.js       # Admin JS mantığı
│   └── css/style.css   # Admin stilleri
├── api/                # API endpoint'leri
│   ├── admin.php       # Admin işlemleri (sohbet, notlar, hatırlatıcılar)
│   ├── chat.php        # Widget tarafı chat API
│   ├── visitor.php     # Ziyaretçi takibi (heartbeat + liste)
│   └── update.php      # Otomatik güncelleme API
├── widget/             # Chat widget (iframe)
│   ├── index.php       # Widget HTML
│   ├── js/widget.js    # Widget JS mantığı
│   └── css/widget.css  # Widget stilleri
├── embed.js            # Embed script (sitelere eklenen kod)
├── db.php              # Veritabanı bağlantı sınıfı
├── config.php          # Yapılandırma dosyası
├── install.php         # Web tabanlı kurulum sihirbazı
├── database.sql        # Veritabanı şeması
└── version.json        # Sürüm bilgisi
```

## 🚀 Kurulum

### Gereksinimler

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx web sunucusu

### Hızlı Kurulum

1. Dosyaları web sunucunuza yükleyin
2. Tarayıcıda `http://siteadresiniz/install.php` adresine gidin
3. Kurulum sihirbazını takip edin (DB bilgileri, admin hesabı)
4. Kurulum tamamlandığında `install.php` otomatik silinir

### Manuel Kurulum

1. `database.sql` dosyasını MySQL'e import edin
2. `config.php` dosyasını veritabanı bilgilerinizle düzenleyin
3. `admin/login.php` adresinden giriş yapın

## 🔗 Web Sitesine Ekleme

Sitenizin `</body>` etiketinden önce aşağıdaki kodu ekleyin:

```html
<script src="https://siteadresiniz/embed.js"></script>
```

Bu kod:
- Sağ alt köşeye bir sohbet baloncuğu ekler
- Baloncuğa tıklandığında chat widget'ı açılır
- Widget açıkken baloncuk gizlenir
- Widget kapatıldığında baloncuk tekrar görünür

## 👥 Anlık Ziyaretçi Takibi

Embed kodu yüklü sitelerdeki ziyaretçiler otomatik olarak takip edilir:
- Admin panelinde **Ziyaretçiler** sekmesinden aktif ziyaretçileri görün
- IP adresi, tarayıcı, sayfa URL, site süresi bilgileri
- 15 saniyelik heartbeat ile canlı takip
- 30 saniye inaktif ziyaretçiler listeden düşer

## ⚙️ Ayarlar

Admin panelinden (`/admin/settings.php`) aşağıdaki ayarlar yapılabilir:

| Ayar | Açıklama |
|------|----------|
| Şirket Adı | Widget başlığında görünür |
| Widget Rengi | Ana renk ve gradient bitiş rengi |
| Karşılama Mesajı | Ziyaretçiye gösterilen ilk mesaj |
| Hazır Yanıtlar | Sık kullanılan cevap şablonları |
| Admin Bilgileri | Kullanıcı adı ve şifre değiştirme |

## 🔄 Güncelleme

1. Admin panelinde **Ayarlar** → **Sistem** sekmesine gidin
2. **Güncelleme Kontrol Et** butonuna tıklayın
3. Yeni sürüm varsa **Güncelle** butonuyla tek tıkla uygulayın

> **Not:** `config.php`, `install.lock` ve `uploads/` dosyaları güncelleme sırasında korunur.

## 📊 Sürüm Geçmişi

| Sürüm | Tarih | Değişiklikler |
|-------|-------|--------------|
| 1.0.8 | 2026-02-15 | Widget açıkken buton gizleme, baloncuk davranışı |
| 1.0.7 | 2026-02-15 | Anlık ziyaretçi takibi, greeting bubble düzeltmeleri |
| 1.0.6 | 2026-02-15 | Ayarlar sayfası yeniden tasarımı (sekmeli yapı) |
| 1.0.5 | 2026-02-15 | Widget minimize düzeltmesi, güncelleme mekanizması |
| 1.0.4 | 2026-02-15 | Türkçe karakter desteği düzeltmesi |
| 1.0.3 | 2026-02-15 | İlk kararlı sürüm |

## 📝 Lisans

MIT License — Serbestçe kullanabilir, değiştirebilir ve dağıtabilirsiniz.

## 👨‍💻 Geliştirici

**Semih AKBAŞ** — [semihakbas.com.tr](https://semihakbas.com.tr)
