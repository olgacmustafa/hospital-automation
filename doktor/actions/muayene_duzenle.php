<?php
require_once "../includes/auth_kontrol.php";

$doktor_id = $_SESSION["doktor_id"];
$muayene_id = $_GET["id"] ?? "";

if ($muayene_id === "") {
    session_hata("Düzenlenecek muayene kaydı bulunamadı.");
    yonlendir("../doktor_panel.php?panel=muayene-gecmisi");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $muayene_id = $_POST["muayene_id"] ?? "";
    $muayene_tar = trim($_POST["muayene_tar"] ?? "");
    $notlar = kisalt($_POST["notlar"] ?? "", 30);
    $tani_adi = kisalt($_POST["tani_adi"] ?? "", 30);
    $tani_aciklama = kisalt($_POST["tani_aciklama"] ?? "", 100);

    if ($muayene_id === "" || $muayene_tar === "" || $notlar === "" || $tani_adi === "") {
        session_hata("Muayene güncellenemedi: Tarih, not ve tanı alanları zorunludur.");
        yonlendir("../doktor_panel.php?panel=muayene-gecmisi");
    }

    try {
        $db->beginTransaction();

        $kontrol = $db->prepare("SELECT muayene_id FROM Muayene WHERE muayene_id = ? AND doktor_id = ?");
        $kontrol->execute(array($muayene_id, $doktor_id));
        if (!$kontrol->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("Bu muayene kaydını düzenleme yetkiniz yok.");
        }

        $muayeneGuncelle = $db->prepare("UPDATE Muayene SET muayene_tar = ?, notlar = ? WHERE muayene_id = ? AND doktor_id = ?");
        $muayeneGuncelle->execute(array($muayene_tar, $notlar, $muayene_id, $doktor_id));

        $taniKontrol = $db->prepare("SELECT tani_id FROM `Tanı` WHERE muayene_id = ? LIMIT 1");
        $taniKontrol->execute(array($muayene_id));
        $tani = $taniKontrol->fetch(PDO::FETCH_ASSOC);

        if ($tani) {
            $taniGuncelle = $db->prepare("UPDATE `Tanı` SET tani_adi = ?, aciklama = ? WHERE tani_id = ?");
            $taniGuncelle->execute(array($tani_adi, $tani_aciklama, $tani["tani_id"]));
        } else {
            $tani_id = sonraki_id($db, "Tanı", "tani_id");
            $taniEkle = $db->prepare("INSERT INTO `Tanı` (tani_id, muayene_id, tani_adi, aciklama) VALUES (?, ?, ?, ?)");
            $taniEkle->execute(array($tani_id, $muayene_id, $tani_adi, $tani_aciklama));
        }

        $db->commit();
        session_mesaj("Muayene ve tanı bilgileri başarıyla güncellendi.");
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        session_hata("Muayene güncellenirken hata oluştu: " . $e->getMessage());
    }

    yonlendir("../doktor_panel.php?panel=muayene-gecmisi");
}

$sorgu = $db->prepare("SELECT m.*, h.isim AS hasta_adi, h.tc_no, t.tani_adi, t.aciklama AS tani_aciklama, r.randevu_tar, r.randevu_saat FROM Muayene m JOIN Hastalar h ON m.hasta_id = h.hasta_id LEFT JOIN `Tanı` t ON t.muayene_id = m.muayene_id LEFT JOIN Randevular r ON r.randevu_id = m.randevu_id WHERE m.muayene_id = ? AND m.doktor_id = ?");
$sorgu->execute(array($muayene_id, $doktor_id));
$muayene = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$muayene) {
    session_hata("Muayene kaydı bulunamadı.");
    yonlendir("../doktor_panel.php?panel=muayene-gecmisi");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Muayene Düzenle</title>
  <link rel="icon" type="image/png" href="../../hospital-icon.png">
  <link rel="stylesheet" href="../../assets/css/user_panel.css?v=20260511-ilac-edit">
</head>
<body>
<div class="edit-page"><div class="content edit-card">
  <div class="panel active">
    <h2>Muayene Düzenle</h2>
    <div class="panel-desc">
      <?php echo e($muayene["hasta_adi"]); ?> / <?php echo e($muayene["tc_no"]); ?> için tamamlanan muayene kaydını güncelleyin.
    </div>

    <form method="POST">
      <input type="hidden" name="muayene_id" value="<?php echo e($muayene["muayene_id"]); ?>">

      <div class="form-grid">
        <label>
          Randevu Bilgisi
          <input type="text" value="<?php echo tarih_saat_yaz($muayene["randevu_tar"], $muayene["randevu_saat"]); ?>" disabled>
        </label>

        <label>
          Muayene Tarihi
          <input type="date" name="muayene_tar" value="<?php echo e($muayene["muayene_tar"]); ?>" required>
        </label>

        <label>
          Tanı Adı
          <input type="text" name="tani_adi" maxlength="30" value="<?php echo e($muayene["tani_adi"] ?? ""); ?>" required>
        </label>

        <label class="full-field">
          Muayene Notu
          <textarea name="notlar" maxlength="30" required><?php echo e($muayene["notlar"]); ?></textarea>
        </label>

        <label class="full-field">
          Tanı Açıklaması
          <textarea name="tani_aciklama" maxlength="100"><?php echo e($muayene["tani_aciklama"] ?? ""); ?></textarea>
        </label>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Güncelle</button>
        <a href="../doktor_panel.php?panel=muayene-gecmisi" class="btn btn-secondary">Geri Dön</a>
      </div>
    </form>
  </div>
</div></div>
</body>
</html>
