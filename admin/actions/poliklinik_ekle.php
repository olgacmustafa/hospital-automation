<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

function poliklinik_sayfasina_don() {
    header("Location: ../admin_panel.php?panel=poliklinik-ekle");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$poliklinik_ad = trim($_POST["poliklinik_ad"] ?? "");
$lokasyon = trim($_POST["lokasyon"] ?? "");
$aciklama = trim($_POST["aciklama"] ?? "");

if ($poliklinik_ad === "" || $lokasyon === "" || $aciklama === "") {
    $_SESSION["hata"] = "Poliklinik eklenemedi: Tüm alanları doldurun.";
    poliklinik_sayfasina_don();
}

try {
    $sorgu = $db->prepare("\n        INSERT INTO Poliklinikler\n        (poliklinik_ad, lokasyon, aciklama)\n        VALUES (?, ?, ?)\n    ");

    $sorgu->execute([
        $poliklinik_ad,
        $lokasyon,
        $aciklama
    ]);

    $_SESSION["mesaj"] = "Poliklinik başarıyla eklendi.";
    header("Location: ../admin_panel.php?panel=poliklinik-liste");
    exit;
} catch (PDOException $e) {
    $_SESSION["hata"] = "Poliklinik eklenirken hata oluştu: " . $e->getMessage();
    poliklinik_sayfasina_don();
}
?>
