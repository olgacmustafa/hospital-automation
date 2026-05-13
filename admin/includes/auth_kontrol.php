<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["admin_giris"]) || $_SESSION["admin_giris"] !== true) {
    header("Location: /admin.html");
    exit;
}

require_once __DIR__ . "/../../ortak/baglanti.php";

$admin_id = $_SESSION["admin_id"] ?? "";
$session_hash = $_SESSION["admin_sifre_hash"] ?? "";

if ($admin_id === "" || $session_hash === "") {
    session_unset();
    session_destroy();
    header("Location: /admin.html?durum=oturum_gecersiz");
    exit;
}

$sorgu = $db->prepare("SELECT sifre_hash FROM Adminler WHERE admin_id = ?");
$sorgu->execute(array($admin_id));
$admin = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$admin || $admin["sifre_hash"] !== $session_hash) {
    session_unset();
    session_destroy();
    header("Location: /admin.html?durum=sifre_degisti");
    exit;
}
?>
