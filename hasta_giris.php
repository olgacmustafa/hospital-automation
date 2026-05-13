<?php
session_start();
require_once "ortak/baglanti.php";
require_once "ortak/yardimci.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("hasta.html");
}

$tc_no = trim($_POST["tc_no"] ?? $_POST["tc"] ?? "");
$sifre = $_POST["sifre"] ?? "";

if ($tc_no === "" || $sifre === "") {
    yonlendir("hasta.html?durum=bos_alan");
}

try {
    $sorgu = $db->prepare("SELECT * FROM Hastalar WHERE tc_no = ?");
    $sorgu->execute(array($tc_no));
    $hasta = $sorgu->fetch(PDO::FETCH_ASSOC);

    if (!$hasta || !password_verify($sifre, $hasta["sifre_hash"])) {
        yonlendir("hasta.html?durum=giris_hata");
    }

    $_SESSION["hasta_giris"] = true;
    $_SESSION["hasta_id"] = $hasta["hasta_id"];
    $_SESSION["hasta_ad"] = $hasta["isim"];
    $_SESSION["hasta_sifre_hash"] = $hasta["sifre_hash"];

    yonlendir("hasta/hasta_panel.php");
} catch (PDOException $e) {
    yonlendir("hasta.html?durum=sistem_hata");
}
?>
