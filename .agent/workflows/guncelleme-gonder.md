---
description: VMDestek projesine güncelleme gönderme (GitHub üzerinden)
---

# VMDestek Güncelleme Gönderme

Bu workflow, VMDestek projesinde değişiklik yaptıktan sonra kurulum yapanlara güncelleme göndermek için kullanılır.

## Adımlar

1. **Kod değişikliklerini yap** — Gerekli düzenlemeleri tamamla.

2. **version.json dosyasını otomatik güncelle** — `b:\Projeler\LiveSupport\version.json` dosyasını oku, mevcut sürümün **patch** numarasını (son rakam) 1 artır.
   - Örnek: `1.0.8` → `1.0.9`, `1.0.9` → `1.0.10`
   - Kullanıcı açıkça farklı bir sürüm belirtmedikçe her zaman patch artır
   - `build` alanını bugünün tarihine güncelle (YYYYMMDD formatı)

3. **Git'e ekle**
// turbo
```
git add -A
```
Çalışma dizini: `b:\Projeler\LiveSupport`

4. **Commit at**
```
git commit -m "v{SÜRÜM} - {Değişiklik açıklaması}"
```
Çalışma dizini: `b:\Projeler\LiveSupport`

5. **GitHub'a push et**
```
git push origin main
```
Çalışma dizini: `b:\Projeler\LiveSupport`

6. **Masaüstüne güncel ZIP oluştur** (opsiyonel, dağıtım için)
```powershell
Compress-Archive -Path 'b:\Projeler\LiveSupport\admin', 'b:\Projeler\LiveSupport\api', 'b:\Projeler\LiveSupport\widget', 'b:\Projeler\LiveSupport\db.php', 'b:\Projeler\LiveSupport\embed.js', 'b:\Projeler\LiveSupport\install.php', 'b:\Projeler\LiveSupport\database.sql', 'b:\Projeler\LiveSupport\oku.txt', 'b:\Projeler\LiveSupport\version.json' -DestinationPath 'C:\Users\SemihAKBAS\Desktop\VMDestek-v{SÜRÜM}.zip' -Force
```

## Notlar

- **GitHub Repo**: https://github.com/Sem-h/VMDestek (Public)
- **Korunan dosyalar** (güncelleme sırasında kullanıcıda üzerine yazılmaz): `config.php`, `install.lock`, `install.php`, `uploads/`
- Kurulum yapan kişiler **Ayarlar → Sistem Güncellemesi → Güncelleme Kontrol Et** butonuyla güncellemeyi alabilir
- `version.json`'ı artırmayı **unutma**, yoksa kullanıcılar güncelleme göremez!
