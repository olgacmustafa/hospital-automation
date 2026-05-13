<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

function sifre_sayfasina_don() {
    header("Location: ../admin_panel.php?panel=profilim");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin_panel.php");
    exit;
}

$admin_id = $_SESSION["admin_id"] ?? "";
$mevcut_sifre = $_POST["mevcut_sifre"] ?? "";
$yeni_sifre = $_POST["yeni_sifre"] ?? "";
$yeni_sifre_tekrar = $_POST["yeni_sifre_tekrar"] ?? "";

if ($admin_id === "") {
    $_SESSION["hata"] = "Şifre değiştirilemedi: Oturum bilgisi bulunamadı.";
    sifre_sayfasina_don();
}

if ($mevcut_sifre === "" || $yeni_sifre === "" || $yeni_sifre_tekrar === "") {
    $_SESSION["hata"] = "Şifre değiştirilemedi: Tüm alanları doldurun.";
    sifre_sayfasina_don();
}

if ($yeni_sifre !== $yeni_sifre_tekrar) {
    $_SESSION["hata"] = "Şifre değiştirilemedi: Yeni şifreler birbiriyle aynı değil.";
    sifre_sayfasina_don();
}

try {
    $sorgu = $db->prepare("SELECT sifre_hash FROM Adminler WHERE admin_id = ?");
    $sorgu->execute([$admin_id]);
    $admin = $sorgu->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $_SESSION["hata"] = "Şifre değiştirilemedi: Admin hesabı bulunamadı.";
        sifre_sayfasina_don();
    }

    if (!password_verify($mevcut_sifre, $admin["sifre_hash"])) {
        $_SESSION["hata"] = "Şifre değiştirilemedi: Mevcut şifre hatalı.";
        sifre_sayfasina_don();
    }

    $yeni_sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);

    $guncelle = $db->prepare("UPDATE Adminler SET sifre_hash = ? WHERE admin_id = ?");
    $guncelle->execute([$yeni_sifre_hash, $admin_id]);

    $_SESSION["admin_sifre_hash"] = $yeni_sifre_hash;

    $_SESSION["mesaj"] = "Admin şifresi başarıyla güncellendi.";
} catch (PDOException $e) {
    $_SESSION["hata"] = "Şifre değiştirilirken hata oluştu: " . $e->getMessage();
}

sifre_sayfasina_don();
?>
