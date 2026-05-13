<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../hasta_panel.php");
}

$hasta_id = $_SESSION["hasta_id"];
$tel_no = trim($_POST["tel_no"] ?? "");
$email = trim($_POST["email"] ?? "");

if ($tel_no === "") {
    session_hata("Telefon numarası boş bırakılamaz.");
    yonlendir("../hasta_panel.php?panel=profilim");
}

try {
    $sorgu = $db->prepare("UPDATE Hastalar SET tel_no = ?, email = ? WHERE hasta_id = ?");
    $sorgu->execute(array($tel_no, $email === "" ? null : $email, $hasta_id));
    session_mesaj("Profil bilgileriniz başarıyla güncellendi.");
} catch (PDOException $e) {
    session_hata("Profil güncellenirken hata oluştu.");
}

yonlendir("../hasta_panel.php?panel=profilim");
?>
