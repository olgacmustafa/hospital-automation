<?php
require_once "../includes/auth_kontrol.php";
require_once "../../ortak/baglanti.php";

$poliklinik_id = $_GET["id"] ?? "";

if ($poliklinik_id === "") {
    $_SESSION["hata"] = "Poliklinik ID bulunamadı.";
    header("Location: ../admin_panel.php?panel=poliklinik-liste");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $poliklinik_id = $_POST["poliklinik_id"] ?? "";
    $poliklinik_ad = trim($_POST["poliklinik_ad"] ?? "");
    $lokasyon = trim($_POST["lokasyon"] ?? "");
    $aciklama = trim($_POST["aciklama"] ?? "");

    if ($poliklinik_id === "" || $poliklinik_ad === "" || $lokasyon === "" || $aciklama === "") {
        $_SESSION["hata"] = "Poliklinik güncellenemedi: Zorunlu alanları doldurun.";
        header("Location: ../admin_panel.php?panel=poliklinik-liste");
        exit;
    }

    try {
        $sorgu = $db->prepare("\n            UPDATE Poliklinikler\n            SET poliklinik_ad = ?, lokasyon = ?, aciklama = ?\n            WHERE poliklinik_id = ?\n        ");

        $sorgu->execute([
            $poliklinik_ad,
            $lokasyon,
            $aciklama,
            $poliklinik_id
        ]);

        $_SESSION["mesaj"] = "Poliklinik bilgileri başarıyla güncellendi.";
    } catch (PDOException $e) {
        $_SESSION["hata"] = "Poliklinik güncellenirken hata oluştu: " . $e->getMessage();
    }

    header("Location: ../admin_panel.php?panel=poliklinik-liste");
    exit;
}

$sorgu = $db->prepare("SELECT * FROM Poliklinikler WHERE poliklinik_id = ?");
$sorgu->execute([$poliklinik_id]);
$poliklinik = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$poliklinik) {
    $_SESSION["hata"] = "Poliklinik bulunamadı.";
    header("Location: ../admin_panel.php?panel=poliklinik-liste");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Poliklinik Düzenle</title>
  <link rel="stylesheet" href="../../assets/css/admin_panel.css?v=20260508-poliklinik">
</head>
<body>

<div class="edit-page"><div class="content edit-card">
  <div class="panel active">
    <h2>Poliklinik Düzenle</h2>
    <div class="panel-desc">Poliklinik bilgilerini güncelleyin.</div>

    <form method="POST">
      <input type="hidden" name="poliklinik_id" value="<?php echo htmlspecialchars($poliklinik["poliklinik_id"]); ?>">

      <div class="form-grid">
        <label>
          Poliklinik Adı
          <input type="text" name="poliklinik_ad" value="<?php echo htmlspecialchars($poliklinik["poliklinik_ad"]); ?>" required>
        </label>

        <label>
          Lokasyon
          <input type="text" name="lokasyon" value="<?php echo htmlspecialchars($poliklinik["lokasyon"]); ?>" required>
        </label>

        <label class="full-field">
          Açıklama
          <textarea name="aciklama" rows="5" required><?php echo htmlspecialchars($poliklinik["aciklama"]); ?></textarea>
        </label>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">Güncelle</button>
        <a href="../admin_panel.php?panel=poliklinik-liste" class="btn btn-secondary">Geri Dön</a>
      </div>
    </form>
  </div>
</div></div>

</body>
</html>
