<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../doktor_panel.php");
}

$doktor_id = $_SESSION["doktor_id"];
$randevu_id = trim($_POST["randevu_id"] ?? "");
$durum = trim($_POST["durum"] ?? "");

$izinli = array("Onaylandı", "İptal Edildi", "Tamamlandı");
if ($randevu_id === "" || !in_array($durum, $izinli, true)) {
    session_hata("Randevu durumu güncellenemedi.");
    yonlendir("../doktor_panel.php?panel=randevularim");
}

try {
    $kontrol = $db->prepare("SELECT durum FROM Randevular WHERE randevu_id = ? AND doktor_id = ?");
    $kontrol->execute(array($randevu_id, $doktor_id));
    $randevu = $kontrol->fetch(PDO::FETCH_ASSOC);

    if (!$randevu) {
        session_hata("Randevu bulunamadı.");
    } elseif (!randevu_islem_yapilabilir_mi($randevu["durum"] ?? "")) {
        session_hata("Bu randevu zaten iptal edilmiş veya tamamlanmış.");
    } else {
        $sorgu = $db->prepare("UPDATE Randevular SET durum = ? WHERE randevu_id = ? AND doktor_id = ?");
        $sorgu->execute(array($durum, $randevu_id, $doktor_id));
        session_mesaj("Randevu durumu güncellendi.");
    }
} catch (PDOException $e) {
    session_hata("Randevu durumu güncellenirken hata oluştu.");
}

yonlendir("../doktor_panel.php?panel=randevularim");
?>
