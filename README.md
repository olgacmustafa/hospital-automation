# Hastane Otomasyonu

PHP ve MySQL kullanılarak geliştirilen bu proje; admin, doktor ve hasta panelleri üzerinden hastane süreçlerinin yönetilmesini amaçlayan bir web tabanlı hastane otomasyon sistemidir.

## Proje Özellikleri

- Admin girişi
- Hasta ekleme, listeleme, silme ve düzenleme
- Doktor ekleme, listeleme, silme ve düzenleme
- Poliklinik ekleme, listeleme ve düzenleme
- İlaç ekleme, listeleme ve düzenleme
- Hasta paneli
- Doktor paneli
- Randevu alma ve randevu iptal işlemleri
- Doktor tarafından randevu durum yönetimi
- Muayene işlemleri
- Muayene geçmişi
- Reçete yazma ve reçete arşivi
- Profil güncelleme
- Şifre değiştirme
- PHP `password_hash` ve `password_verify` ile hashlenmiş şifre kontrolü
- PDO ile MySQL bağlantısı

## Kullanılan Teknolojiler

- HTML5
- CSS3
- JavaScript
- PHP
- MySQL
- PDO
- phpMyAdmin

## Klasör Yapısı

```text
hastane-otomasyonu/
├── admin/              # Admin paneli ve admin işlemleri
├── hasta/              # Hasta paneli ve hasta işlemleri
├── doktor/             # Doktor paneli ve doktor işlemleri
├── assets/             # CSS, JavaScript ve görseller
├── ortak/              # Ortak bağlantı ve yardımcı fonksiyonlar
├── database/           # Veritabanı SQL dosyası
├── docs/screenshots/   # Proje ekran görüntüleri için ayrılmış klasör
├── index.html
├── admin.html
├── hasta.html
├── doktor.html
├── README.md
└── .gitignore
```

## Kurulum

### 1. Projeyi indirin

```bash
git clone https://github.com/kullanici-adin/hastane-otomasyonu.git
```

veya bu klasörü XAMPP `htdocs` içine kopyalayın.

### 2. Veritabanını oluşturun

phpMyAdmin üzerinden şu dosyayı içe aktarın:

```text
database/hospital_schema.sql
```

Bu dosya `hastane_otomasyonu` adlı veritabanını ve gerekli tabloları oluşturur.

### 3. Veritabanı bağlantısını ayarlayın

`ortak/baglanti.example.php` dosyasını kopyalayıp adını `baglanti.php` yapın.

```bash
cp ortak/baglanti.example.php ortak/baglanti.php
```

Sonra `ortak/baglanti.php` içindeki bilgileri kendi ortamınıza göre düzenleyin:

```php
$host = "localhost";
$dbname = "hastane_otomasyonu";
$username = "root";
$password = "";
```

XAMPP kullanıyorsanız çoğu durumda bu bilgiler direkt çalışır. Hosting kullanıyorsanız veritabanı adı, kullanıcı adı ve şifreyi hosting panelinizden almanız gerekir.

### 4. Projeyi çalıştırın

XAMPP kullanıyorsanız Apache ve MySQL servislerini başlatın. Ardından tarayıcıdan açın:

```text
http://localhost/hastane-otomasyonu/
```

## Demo Giriş Bilgileri

Veritabanı SQL dosyasındaki örnek kayıtlar için varsayılan şifre `123456` olarak ayarlanmıştır.

| Rol | Kullanıcı | Şifre |
| --- | --- | --- |
| Admin | admin | 123456 |
| Hasta | 11111111111 | 123456 |
| Doktor | 22222222222 | 123456 |

## GitHub'a Yükleme

Bu klasör GitHub'a yüklenmeye hazır hale getirilmiştir.

Terminal üzerinden yüklemek için:

```bash
git init
git add .
git commit -m "Initial hospital automation project"
git branch -M main
git remote add origin https://github.com/kullanici-adin/hastane-otomasyonu.git
git push -u origin main
```

> `kullanici-adin` kısmını kendi GitHub kullanıcı adınızla değiştirin.

## Güvenlik Notu

`ortak/baglanti.php` dosyası `.gitignore` içine eklenmiştir. Bu dosyaya gerçek hosting/veritabanı şifrenizi yazarsanız GitHub'a gönderilmemesi gerekir.

GitHub'a public repo açmadan önce şunları kontrol edin:

- Gerçek veritabanı şifresi hiçbir dosyada bulunmamalı.
- Gerçek hasta/doktor kişisel bilgileri SQL dosyasında bulunmamalı.
- Kişisel fotoğraf veya özel görsel varsa paylaşmadan önce kontrol edilmeli.

## Not

Bu proje eğitim amacıyla geliştirilmiştir.
