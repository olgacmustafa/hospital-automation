<?php
session_start();
require_once "ortak/baglanti.php";
require_once "ortak/yardimci.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("doktor.html");
}

$tc_no = trim($_POST["tc_no"] ?? $_POST["tc"] ?? "");
$sifre = $_POST["sifre"] ?? "";

if ($tc_no === "" || $sifre === "") {
    yonlendir("doktor.html?durum=bos_alan");
}

try {
    $sorgu = $db->prepare("SELECT * FROM Doktorlar WHERE tc_no = ?");
    $sorgu->execute(array($tc_no));
    $doktor = $sorgu->fetch(PDO::FETCH_ASSOC);

    if (!$doktor || !password_verify($sifre, $doktor["sifre_hash"])) {
        yonlendir("doktor.html?durum=giris_hata");
    }

    $_SESSION["doktor_giris"] = true;
    $_SESSION["doktor_id"] = $doktor["doktor_id"];
    $_SESSION["doktor_ad"] = $doktor["isim"];
    $_SESSION["doktor_sifre_hash"] = $doktor["sifre_hash"];

    yonlendir("doktor/doktor_panel.php");
} catch (PDOException $e) {
    yonlendir("doktor.html?durum=sistem_hata");
}
?>
