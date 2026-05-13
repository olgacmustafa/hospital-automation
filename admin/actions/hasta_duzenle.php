<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

$hasta_id = $_GET["id"] ?? "";

if ($hasta_id === "") {
    $_SESSION["hata"] = "Hasta ID bulunamadı.";
    header("Location: ../admin_panel.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hasta_id = $_POST["hasta_id"] ?? "";
    $tc_no = trim($_POST["tc_no"] ?? "");
    $sifre = $_POST["sifre"] ?? "";
    $isim = trim($_POST["isim"] ?? "");
    $dogum_tar = $_POST["dogum_tar"] ?? "";
    $cinsiyet = $_POST["cinsiyet"] ?? "";
    $tel_no = trim($_POST["tel_no"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($tc_no === "" || $isim === "" || $dogum_tar === "" || $cinsiyet === "" || $tel_no === "") {
        $_SESSION["hata"] = "Hasta güncellenemedi: Zorunlu alanları doldurun.";
        header("Location: ../admin_panel.php");
        exit;
    }

    try {
        if ($sifre !== "") {
            $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
            $sorgu = $db->prepare("UPDATE Hastalar SET tc_no = ?, sifre_hash = ?, isim = ?, dogum_tar = ?, cinsiyet = ?, tel_no = ?, email = ? WHERE hasta_id = ?");
            $sorgu->execute(array($tc_no, $sifre_hash, $isim, $dogum_tar, $cinsiyet, $tel_no, $email === "" ? null : $email, $hasta_id));
        } else {
            $sorgu = $db->prepare("UPDATE Hastalar SET tc_no = ?, isim = ?, dogum_tar = ?, cinsiyet = ?, tel_no = ?, email = ? WHERE hasta_id = ?");
            $sorgu->execute(array($tc_no, $isim, $dogum_tar, $cinsiyet, $tel_no, $email === "" ? null : $email, $hasta_id));
        }
        $_SESSION["mesaj"] = "Hasta bilgileri başarıyla güncellendi.";
    } catch (PDOException $e) {
        $_SESSION["hata"] = "Hasta güncellenirken hata oluştu: " . $e->getMessage();
    }

    header("Location: ../admin_panel.php?panel=hasta-liste");
    exit;
}

$sorgu = $db->prepare("SELECT * FROM Hastalar WHERE hasta_id = ?");
$sorgu->execute(array($hasta_id));
$hasta = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$hasta) {
    $_SESSION["hata"] = "Hasta bulunamadı.";
    header("Location: ../admin_panel.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><title>Hasta Düzenle</title><link rel="stylesheet" href="../../assets/css/admin_panel.css?v=20260511-db-final"></head>
<body>
<div class="edit-page"><div class="content edit-card"><div class="panel active">
  <h2>Hasta Düzenle</h2><div class="panel-desc">Hasta bilgilerini güncelleyin.</div>
  <form method="POST">
    <input type="hidden" name="hasta_id" value="<?php echo htmlspecialchars($hasta["hasta_id"]); ?>">
    <div class="form-grid">
      <label>TC No<input type="text" name="tc_no" maxlength="11" value="<?php echo htmlspecialchars($hasta["tc_no"]); ?>" required></label>
      <label>Yeni Şifre<input type="password" name="sifre" placeholder="Boş bırakırsan şifre değişmez"></label>
      <label>İsim<input type="text" name="isim" value="<?php echo htmlspecialchars($hasta["isim"]); ?>" required></label>
      <label>Doğum Tarihi<input type="date" name="dogum_tar" value="<?php echo htmlspecialchars($hasta["dogum_tar"]); ?>" required></label>
      <label>Cinsiyet<select name="cinsiyet" required><option value="Erkek" <?php if ($hasta["cinsiyet"] === "Erkek") echo "selected"; ?>>Erkek</option><option value="Kadın" <?php if ($hasta["cinsiyet"] === "Kadın") echo "selected"; ?>>Kadın</option></select></label>
      <label>Telefon No<input type="text" name="tel_no" value="<?php echo htmlspecialchars($hasta["tel_no"]); ?>" required></label>
      <label class="full-field">E-posta<input type="email" name="email" value="<?php echo htmlspecialchars($hasta["email"] ?? ""); ?>"></label>
    </div>
    <div class="actions"><button type="submit" class="btn btn-primary">Güncelle</button><a href="../admin_panel.php?panel=hasta-liste" class="btn btn-secondary">Geri Dön</a></div>
  </form>
</div></div></div>
</body>
</html>
