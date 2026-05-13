<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";
require_once "../../ortak/yardimci.php";

function ilac_ekle_sayfasina_don() {
    header("Location: ../admin_panel.php?panel=ilac-ekle");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$ilac_ad = kisalt($_POST["ilac_ad"] ?? "", 50);
$ilac_turu = kisalt($_POST["ilac_turu"] ?? "", 10);

if ($ilac_ad === "" || $ilac_turu === "") {
    $_SESSION["hata"] = "İlaç eklenemedi: İlaç adı ve ilaç türü zorunludur.";
    ilac_ekle_sayfasina_don();
}

try {
    $ilac_id = sonraki_id($db, "İlaçlar", "ilac_id");

    $sorgu = $db->prepare("INSERT INTO `İlaçlar` (ilac_id, ilac_ad, `İlac_turu`) VALUES (?, ?, ?)");
    $sorgu->execute(array($ilac_id, $ilac_ad, $ilac_turu));

    $_SESSION["mesaj"] = "İlaç başarıyla eklendi.";
    header("Location: ../admin_panel.php?panel=ilac-liste");
    exit;
} catch (PDOException $e) {
    $_SESSION["hata"] = "İlaç eklenirken hata oluştu: " . $e->getMessage();
    ilac_ekle_sayfasina_don();
}
?>
