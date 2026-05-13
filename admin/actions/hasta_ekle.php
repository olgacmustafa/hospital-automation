<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$tc_no = trim($_POST["tc_no"] ?? "");
$sifre = $_POST["sifre"] ?? "";
$isim = trim($_POST["isim"] ?? "");
$dogum_tar = $_POST["dogum_tar"] ?? "";
$cinsiyet = $_POST["cinsiyet"] ?? "";
$tel_no = trim($_POST["tel_no"] ?? "");
$email = trim($_POST["email"] ?? "");

if ($tc_no === "" || $sifre === "" || $isim === "" || $dogum_tar === "" || $cinsiyet === "" || $tel_no === "") {
    $_SESSION["hata"] = "Hasta eklenemedi: Zorunlu alanları doldurun.";
    header("Location: ../admin_panel.php");
    exit;
}

if (!preg_match('/^[0-9]{11}$/', $tc_no)) {
    $_SESSION["hata"] = "Hasta eklenemedi: TC No 11 haneli sayı olmalıdır.";
    header("Location: ../admin_panel.php");
    exit;
}

if (!preg_match('/^[0-9]{11}$/', $tel_no)) {
    $_SESSION["hata"] = "Hasta eklenemedi: Tel No 11 haneli sayı olmalıdır.";
    header("Location: ../admin_panel.php");
    exit;
}

$sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

try {
    $sorgu = $db->prepare("INSERT INTO Hastalar (tc_no, sifre_hash, isim, dogum_tar, cinsiyet, tel_no, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sorgu->execute(array($tc_no, $sifre_hash, $isim, $dogum_tar, $cinsiyet, $tel_no, $email === "" ? null : $email));
    $_SESSION["mesaj"] = "Hasta başarıyla eklendi. Şifre veritabanına hash olarak kaydedildi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Hasta eklenirken hata oluştu: " . $e->getMessage();
}

header("Location: ../admin_panel.php");
exit;
?>
