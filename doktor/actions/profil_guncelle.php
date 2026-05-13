<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../doktor_panel.php");
}

$doktor_id = $_SESSION["doktor_id"];
$tel_no = trim($_POST["tel_no"] ?? "");
$email = trim($_POST["email"] ?? "");

if ($tel_no === "") {
    session_hata("Telefon numarası boş bırakılamaz.");
    yonlendir("../doktor_panel.php?panel=profilim");
}

try {
    $sorgu = $db->prepare("UPDATE Doktorlar SET tel_no = ?, email = ? WHERE doktor_id = ?");
    $sorgu->execute(array($tel_no, $email === "" ? null : $email, $doktor_id));
    session_mesaj("Profil bilgileriniz başarıyla güncellendi.");
} catch (PDOException $e) {
    session_hata("Profil güncellenirken hata oluştu.");
}

yonlendir("../doktor_panel.php?panel=profilim");
?>
