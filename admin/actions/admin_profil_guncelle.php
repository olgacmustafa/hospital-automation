<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

function profil_sayfasina_don() {
    header("Location: ../admin_panel.php?panel=profilim");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$admin_id = $_SESSION["admin_id"] ?? "";
$admin_ad = trim($_POST["admin_ad"] ?? "");

if ($admin_id === "") {
    $_SESSION["hata"] = "Kullanıcı adı güncellenemedi: Oturum bilgisi bulunamadı.";
    profil_sayfasina_don();
}

if ($admin_ad === "") {
    $_SESSION["hata"] = "Kullanıcı adı boş bırakılamaz.";
    profil_sayfasina_don();
}

if (mb_strlen($admin_ad, "UTF-8") > 30) {
    $_SESSION["hata"] = "Kullanıcı adı en fazla 30 karakter olabilir.";
    profil_sayfasina_don();
}

try {
    $kontrol = $db->prepare("SELECT admin_id FROM Adminler WHERE admin_ad = ? AND admin_id <> ? LIMIT 1");
    $kontrol->execute([$admin_ad, $admin_id]);

    if ($kontrol->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION["hata"] = "Bu kullanıcı adı başka bir admin tarafından kullanılıyor.";
        profil_sayfasina_don();
    }

    $guncelle = $db->prepare("UPDATE Adminler SET admin_ad = ? WHERE admin_id = ?");
    $guncelle->execute([$admin_ad, $admin_id]);

    $_SESSION["admin_ad"] = $admin_ad;
    $_SESSION["mesaj"] = "Admin kullanıcı adı başarıyla güncellendi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Kullanıcı adı güncellenirken hata oluştu: " . $e->getMessage();
}

profil_sayfasina_don();
?>
