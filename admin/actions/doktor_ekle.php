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
$tel_no = trim($_POST["tel_no"] ?? "");
$email = trim($_POST["email"] ?? "");
$uzmanlik = trim($_POST["uzmanlik"] ?? "");
$poliklinik_id = trim($_POST["poliklinik_id"] ?? "");

if ($tc_no === "" || $sifre === "" || $isim === "" || $tel_no === "" || $uzmanlik === "" || $poliklinik_id === "") {
    $_SESSION["hata"] = "Doktor eklenemedi: Zorunlu alanları doldurun.";
    header("Location: ../admin_panel.php");
    exit;
}

if (!preg_match('/^[0-9]{11}$/', $tc_no)) {
    $_SESSION["hata"] = "Doktor eklenemedi: TC No 11 haneli sayı olmalıdır.";
    header("Location: ../admin_panel.php");
    exit;
}

$sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

try {
    $sorgu = $db->prepare("INSERT INTO Doktorlar (tc_no, sifre_hash, isim, tel_no, email, `uzmanlık`, poliklinik_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sorgu->execute(array($tc_no, $sifre_hash, $isim, $tel_no, $email === "" ? null : $email, $uzmanlik, $poliklinik_id));
    $_SESSION["mesaj"] = "Doktor başarıyla eklendi. Şifre veritabanına hash olarak kaydedildi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Doktor eklenirken hata oluştu: " . $e->getMessage();
}

header("Location: ../admin_panel.php");
exit;
?>
