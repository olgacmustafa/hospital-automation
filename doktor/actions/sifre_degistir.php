<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../doktor_panel.php");
}

$doktor_id = $_SESSION["doktor_id"];
$mevcut_sifre = $_POST["mevcut_sifre"] ?? "";
$yeni_sifre = $_POST["yeni_sifre"] ?? "";
$yeni_sifre_tekrar = $_POST["yeni_sifre_tekrar"] ?? "";

if ($mevcut_sifre === "" || $yeni_sifre === "" || $yeni_sifre_tekrar === "") {
    session_hata("Şifre değiştirmek için tüm alanları doldurmalısınız.");
    yonlendir("../doktor_panel.php?panel=profilim");
}

if ($yeni_sifre !== $yeni_sifre_tekrar) {
    session_hata("Yeni şifreler birbiriyle uyuşmuyor.");
    yonlendir("../doktor_panel.php?panel=profilim");
}

if (strlen($yeni_sifre) < 3) {
    session_hata("Yeni şifre en az 3 karakter olmalıdır.");
    yonlendir("../doktor_panel.php?panel=profilim");
}

try {
    $sorgu = $db->prepare("SELECT sifre_hash FROM Doktorlar WHERE doktor_id = ?");
    $sorgu->execute(array($doktor_id));
    $kayit = $sorgu->fetch(PDO::FETCH_ASSOC);

    if (!$kayit || !password_verify($mevcut_sifre, $kayit["sifre_hash"])) {
        session_hata("Mevcut şifre hatalı.");
        yonlendir("../doktor_panel.php?panel=profilim");
    }

    $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
    $guncelle = $db->prepare("UPDATE Doktorlar SET sifre_hash = ? WHERE doktor_id = ?");
    $guncelle->execute(array($yeni_hash, $doktor_id));
    $_SESSION["doktor_sifre_hash"] = $yeni_hash;

    session_mesaj("Şifreniz başarıyla güncellendi.");
} catch (PDOException $e) {
    session_hata("Şifre güncellenirken hata oluştu.");
}

yonlendir("../doktor_panel.php?panel=profilim");
?>
