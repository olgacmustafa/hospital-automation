<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$hasta_id = $_POST["hasta_id"] ?? "";

if ($hasta_id === "") {
    $_SESSION["hata"] = "Hasta silinemedi: Hasta ID bulunamadı.";
    header("Location: ../admin_panel.php");
    exit;
}

try {
    $sorgu = $db->prepare("DELETE FROM Hastalar WHERE hasta_id = ?");
    $sorgu->execute([$hasta_id]);

    $_SESSION["mesaj"] = "Hasta başarıyla silindi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Hasta silinirken hata oluştu. Bu hastaya bağlı randevu, reçete veya muayene kaydı olabilir.";
}

header("Location: ../admin_panel.php");
exit;
?>
