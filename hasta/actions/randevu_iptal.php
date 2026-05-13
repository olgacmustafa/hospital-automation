<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../hasta_panel.php");
}

$randevu_id = trim($_POST["randevu_id"] ?? "");
$hasta_id = $_SESSION["hasta_id"];

if ($randevu_id === "") {
    session_hata("Randevu bilgisi bulunamadı.");
    yonlendir("../hasta_panel.php?panel=randevularim");
}

try {
    $kontrol = $db->prepare("SELECT durum FROM Randevular WHERE randevu_id = ? AND hasta_id = ?");
    $kontrol->execute(array($randevu_id, $hasta_id));
    $randevu = $kontrol->fetch(PDO::FETCH_ASSOC);

    if (!$randevu) {
        session_hata("Randevu bulunamadı.");
    } elseif (!randevu_islem_yapilabilir_mi($randevu["durum"] ?? "")) {
        session_hata("Bu randevu zaten iptal edilmiş veya tamamlanmış.");
    } else {
        $sorgu = $db->prepare("UPDATE Randevular SET durum = 'İptal Edildi' WHERE randevu_id = ? AND hasta_id = ?");
        $sorgu->execute(array($randevu_id, $hasta_id));
        session_mesaj("Randevu iptal edildi.");
    }
} catch (PDOException $e) {
    session_hata("Randevu iptal edilirken hata oluştu.");
}

yonlendir("../hasta_panel.php?panel=randevularim");
?>
