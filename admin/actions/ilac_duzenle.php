<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";
require_once "../../ortak/yardimci.php";

$ilac_id = $_GET["id"] ?? "";

if ($ilac_id === "") {
    $_SESSION["hata"] = "İlaç ID bulunamadı.";
    header("Location: ../admin_panel.php?panel=ilac-liste");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ilac_id = $_POST["ilac_id"] ?? "";
    $ilac_ad = kisalt($_POST["ilac_ad"] ?? "", 50);
    $ilac_turu = kisalt($_POST["ilac_turu"] ?? "", 10);

    if ($ilac_id === "" || $ilac_ad === "" || $ilac_turu === "") {
        $_SESSION["hata"] = "İlaç güncellenemedi: Zorunlu alanları doldurun.";
        header("Location: ../admin_panel.php?panel=ilac-liste");
        exit;
    }

    try {
        $sorgu = $db->prepare("UPDATE `İlaçlar` SET ilac_ad = ?, `İlac_turu` = ? WHERE ilac_id = ?");
        $sorgu->execute(array($ilac_ad, $ilac_turu, $ilac_id));

        $_SESSION["mesaj"] = "İlaç bilgileri başarıyla güncellendi.";
    } catch (PDOException $e) {
        $_SESSION["hata"] = "İlaç güncellenirken hata oluştu: " . $e->getMessage();
    }

    header("Location: ../admin_panel.php?panel=ilac-liste");
    exit;
}

$sorgu = $db->prepare("SELECT * FROM `İlaçlar` WHERE ilac_id = ?");
$sorgu->execute(array($ilac_id));
$ilac = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$ilac) {
    $_SESSION["hata"] = "İlaç bulunamadı.";
    header("Location: ../admin_panel.php?panel=ilac-liste");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>İlaç Düzenle</title>
  <link rel="stylesheet" href="../../assets/css/admin_panel.css?v=20260511-ilac-edit">
</head>
<body>
<div class="edit-page"><div class="content edit-card">
  <div class="panel active">
    <h2>İlaç Düzenle</h2>
    <div class="panel-desc">İlaç adını ve türünü güncelleyin.</div>

    <form method="POST">
      <input type="hidden" name="ilac_id" value="<?php echo htmlspecialchars($ilac["ilac_id"]); ?>">

      <div class="form-grid">
        <label>
          İlaç Adı
          <input type="text" name="ilac_ad" maxlength="50" value="<?php echo htmlspecialchars($ilac["ilac_ad"]); ?>" required>
        </label>

        <label>
          İlaç Türü
          <input type="text" name="ilac_turu" maxlength="10" value="<?php echo htmlspecialchars($ilac["İlac_turu"]); ?>" required>
        </label>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">Güncelle</button>
        <a href="../admin_panel.php?panel=ilac-liste" class="btn btn-secondary">Geri Dön</a>
      </div>
    </form>
  </div>
</div></div>
</body>
</html>
