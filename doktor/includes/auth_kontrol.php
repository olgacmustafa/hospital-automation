<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["doktor_giris"]) || $_SESSION["doktor_giris"] !== true) {
    header("Location: ../doktor.html?durum=oturum_gecersiz");
    exit;
}

require_once __DIR__ . "/../../ortak/baglanti.php";
require_once __DIR__ . "/../../ortak/yardimci.php";

$doktor_id = $_SESSION["doktor_id"] ?? "";
$session_hash = $_SESSION["doktor_sifre_hash"] ?? "";

if ($doktor_id === "" || $session_hash === "") {
    session_unset();
    session_destroy();
    header("Location: ../doktor.html?durum=oturum_gecersiz");
    exit;
}

$sorgu = $db->prepare("SELECT sifre_hash FROM Doktorlar WHERE doktor_id = ?");
$sorgu->execute(array($doktor_id));
$doktorOturum = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$doktorOturum || $doktorOturum["sifre_hash"] !== $session_hash) {
    session_unset();
    session_destroy();
    header("Location: ../doktor.html?durum=sifre_degisti");
    exit;
}
?>
