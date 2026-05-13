<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["hasta_giris"]) || $_SESSION["hasta_giris"] !== true) {
    header("Location: ../hasta.html?durum=oturum_gecersiz");
    exit;
}

require_once __DIR__ . "/../../ortak/baglanti.php";
require_once __DIR__ . "/../../ortak/yardimci.php";

$hasta_id = $_SESSION["hasta_id"] ?? "";
$session_hash = $_SESSION["hasta_sifre_hash"] ?? "";

if ($hasta_id === "" || $session_hash === "") {
    session_unset();
    session_destroy();
    header("Location: ../hasta.html?durum=oturum_gecersiz");
    exit;
}

$sorgu = $db->prepare("SELECT sifre_hash FROM Hastalar WHERE hasta_id = ?");
$sorgu->execute(array($hasta_id));
$hastaOturum = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$hastaOturum || $hastaOturum["sifre_hash"] !== $session_hash) {
    session_unset();
    session_destroy();
    header("Location: ../hasta.html?durum=sifre_degisti");
    exit;
}
?>
