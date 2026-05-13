<?php
session_start();
require_once "ortak/baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.html");
    exit;
}

$admin_ad = trim($_POST["admin_ad"] ?? "");
$sifre = $_POST["sifre"] ?? "";

if ($admin_ad === "" || $sifre === "") {
    header("Location: admin.html?durum=bos_alan");
    exit;
}

$sorgu = $db->prepare("SELECT * FROM Adminler WHERE admin_ad = ?");
$sorgu->execute([$admin_ad]);

$admin = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header("Location: admin.html?durum=kullanici_hata");
    exit;
}

if ($admin_ad !== $admin["admin_ad"]) {
    header("Location: admin.html?durum=kullanici_hata");
    exit;
}

if (!password_verify($sifre, $admin["sifre_hash"])) {
    header("Location: admin.html?durum=sifre_hata");
    exit;
}

$_SESSION["admin_giris"] = true;
$_SESSION["admin_id"] = $admin["admin_id"];
$_SESSION["admin_ad"] = $admin["admin_ad"];
$_SESSION["admin_sifre_hash"] = $admin["sifre_hash"];

header("Location: admin/admin_panel.php");
exit;
?>
