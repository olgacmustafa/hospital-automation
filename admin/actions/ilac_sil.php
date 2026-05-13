<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php?panel=ilac-liste");
    exit;
}

$ilac_id = $_POST["ilac_id"] ?? "";

if ($ilac_id === "") {
    $_SESSION["hata"] = "Silinecek ilaç bulunamadı.";
    header("Location: ../admin_panel.php?panel=ilac-liste");
    exit;
}

try {
    $sorgu = $db->prepare("DELETE FROM `İlaçlar` WHERE ilac_id = ?");
    $sorgu->execute(array($ilac_id));

    $_SESSION["mesaj"] = "İlaç başarıyla silindi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "İlaç silinemedi. Bu ilaç bir reçetede kullanılmış olabilir.";
}

header("Location: ../admin_panel.php?panel=ilac-liste");
exit;
?>
