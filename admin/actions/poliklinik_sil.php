<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$poliklinik_id = $_POST["poliklinik_id"] ?? "";

if ($poliklinik_id === "") {
    $_SESSION["hata"] = "Poliklinik silinemedi: Poliklinik ID bulunamadı.";
    header("Location: ../admin_panel.php?panel=poliklinik-liste");
    exit;
}

try {
    $sorgu = $db->prepare("DELETE FROM Poliklinikler WHERE poliklinik_id = ?");
    $sorgu->execute([$poliklinik_id]);

    $_SESSION["mesaj"] = "Poliklinik başarıyla silindi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Poliklinik silinirken hata oluştu. Bu polikliniğe bağlı doktor veya randevu kaydı olabilir.";
}

header("Location: ../admin_panel.php?panel=poliklinik-liste");
exit;
?>
