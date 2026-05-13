<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$doktor_id = $_POST["doktor_id"] ?? "";

if ($doktor_id === "") {
    $_SESSION["hata"] = "Doktor silinemedi: Doktor ID bulunamadı.";
    header("Location: ../admin_panel.php");
    exit;
}

try {
    $sorgu = $db->prepare("DELETE FROM Doktorlar WHERE doktor_id = ?");
    $sorgu->execute([$doktor_id]);

    $_SESSION["mesaj"] = "Doktor başarıyla silindi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Doktor silinirken hata oluştu. Bu doktora bağlı randevu, reçete veya muayene kaydı olabilir.";
}

header("Location: ../admin_panel.php");
exit;
?>
