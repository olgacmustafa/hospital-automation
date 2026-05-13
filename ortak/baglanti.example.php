<?php
/*
  Örnek veritabanı bağlantı dosyası
  --------------------------------------------------
  GitHub'a gerçek hosting/veritabanı şifrenizi yüklemeyin.
  Kendi bilgisayarınızda veya sunucunuzda bu bilgileri kendi ortamınıza göre düzenleyin.
*/

$host = "localhost";
$dbname = "hastane_otomasyonu";
$username = "root";
$password = "";

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("SET NAMES utf8mb4");

} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
