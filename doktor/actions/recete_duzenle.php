<?php
require_once "../includes/auth_kontrol.php";

$doktor_id = $_SESSION["doktor_id"];
$recete_id = $_GET["id"] ?? "";

if ($recete_id === "") {
    session_hata("Düzenlenecek reçete kaydı bulunamadı.");
    yonlendir("../doktor_panel.php?panel=recete-arsivi");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $recete_id = $_POST["recete_id"] ?? "";
    $recete_tar = trim($_POST["recete_tar"] ?? "");
    $ilaclar = $_POST["ilac_id"] ?? array();
    $dozlar = $_POST["doz"] ?? array();
    $talimatlar = $_POST["talimat"] ?? array();

    if (!is_array($ilaclar)) $ilaclar = array($ilaclar);
    if (!is_array($dozlar)) $dozlar = array($dozlar);
    if (!is_array($talimatlar)) $talimatlar = array($talimatlar);

    $receteIlaclari = array();
    foreach ($ilaclar as $i => $ilac_id) {
        $ilac_id = trim((string)$ilac_id);
        $doz = kisalt($dozlar[$i] ?? "", 10);
        $talimat = kisalt($talimatlar[$i] ?? "", 50);
        if ($ilac_id === "" && $doz === "" && $talimat === "") { continue; }
        if ($ilac_id === "" || $doz === "" || $talimat === "") {
            session_hata("Her ilaç satırında ilaç, doz ve talimat alanları birlikte doldurulmalıdır.");
            yonlendir("../doktor_panel.php?panel=recete-arsivi");
        }
        $receteIlaclari[] = array($ilac_id, $doz, $talimat);
    }

    if ($recete_id === "" || $recete_tar === "" || count($receteIlaclari) === 0) {
        session_hata("Reçete güncellenemedi: tarih ve en az bir ilaç satırı zorunludur.");
        yonlendir("../doktor_panel.php?panel=recete-arsivi");
    }

    try {
        $db->beginTransaction();
        $kontrol = $db->prepare("SELECT recete_id FROM `Reçeteler` WHERE recete_id = ? AND doktor_id = ?");
        $kontrol->execute(array($recete_id, $doktor_id));
        if (!$kontrol->fetch(PDO::FETCH_ASSOC)) { throw new Exception("Bu reçete kaydını düzenleme yetkiniz yok."); }

        $receteGuncelle = $db->prepare("UPDATE `Reçeteler` SET recete_tar = ? WHERE recete_id = ? AND doktor_id = ?");
        $receteGuncelle->execute(array($recete_tar, $recete_id, $doktor_id));

        $sil = $db->prepare("DELETE FROM `Reçete İlaçları` WHERE recete_id = ?");
        $sil->execute(array($recete_id));

        $r_ilac_id = sonraki_id($db, "Reçete İlaçları", "r_ilac_id");
        $ilacEkle = $db->prepare("INSERT INTO `Reçete İlaçları` (r_ilac_id, ilac_id, recete_id, doz, talimat) VALUES (?, ?, ?, ?, ?)");
        foreach ($receteIlaclari as $satir) {
            $ilacEkle->execute(array($r_ilac_id, $satir[0], $recete_id, $satir[1], $satir[2]));
            $r_ilac_id++;
        }
        $db->commit();
        session_mesaj("Reçete bilgileri başarıyla güncellendi.");
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        session_hata("Reçete güncellenirken hata oluştu: " . $e->getMessage());
    }
    yonlendir("../doktor_panel.php?panel=recete-arsivi");
}

$sorgu = $db->prepare("SELECT rec.*, h.isim AS hasta_adi, h.tc_no FROM `Reçeteler` rec JOIN Hastalar h ON rec.hasta_id = h.hasta_id WHERE rec.recete_id = ? AND rec.doktor_id = ?");
$sorgu->execute(array($recete_id, $doktor_id));
$recete = $sorgu->fetch(PDO::FETCH_ASSOC);
if (!$recete) {
    session_hata("Reçete kaydı bulunamadı.");
    yonlendir("../doktor_panel.php?panel=recete-arsivi");
}

$receteIlacSorgu = $db->prepare("SELECT * FROM `Reçete İlaçları` WHERE recete_id = ? ORDER BY r_ilac_id ASC");
$receteIlacSorgu->execute(array($recete_id));
$receteIlaclari = $receteIlacSorgu->fetchAll(PDO::FETCH_ASSOC);
if (count($receteIlaclari) === 0) { $receteIlaclari[] = array("ilac_id" => "", "doz" => "", "talimat" => ""); }
$ilaclar = $db->query("SELECT * FROM `İlaçlar` ORDER BY ilac_ad ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reçete Düzenle</title>
  <link rel="icon" type="image/png" href="../../hospital-icon.png">
  <link rel="stylesheet" href="../../assets/css/user_panel.css?v=20260511-son-istekler">
</head>
<body>
<div class="edit-page"><div class="content edit-card">
  <div class="panel active">
    <h2>Reçete Düzenle</h2>
    <div class="panel-desc"><?php echo e($recete["hasta_adi"]); ?> / <?php echo e($recete["tc_no"]); ?> için yazılan reçeteyi güncelleyin.</div>
    <form method="POST">
      <input type="hidden" name="recete_id" value="<?php echo e($recete["recete_id"]); ?>">
      <div class="form-grid">
        <label>Reçete Tarihi<input type="date" name="recete_tar" value="<?php echo e($recete["recete_tar"]); ?>" required></label>
        <div class="medicine-repeater" data-medicine-repeater>
          <div class="form-hint">Reçetede birden fazla ilaç bulunabilir. Satırları düzenleyebilir, silebilir veya yeni satır ekleyebilirsiniz.</div>
          <?php foreach ($receteIlaclari as $satir): ?>
            <div class="medicine-row">
              <label>İlaç
                <select name="ilac_id[]" required>
                  <option value="">İlaç seçiniz</option>
                  <?php foreach ($ilaclar as $ilac): ?>
                    <option value="<?php echo e($ilac["ilac_id"]); ?>" <?php echo ((string)$ilac["ilac_id"] === (string)($satir["ilac_id"] ?? "")) ? "selected" : ""; ?>><?php echo e($ilac["ilac_ad"]); ?> - <?php echo e($ilac["İlac_turu"]); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Doz<input type="text" name="doz[]" maxlength="10" value="<?php echo e($satir["doz"] ?? ""); ?>" required></label>
              <label>Talimat<input type="text" name="talimat[]" maxlength="50" value="<?php echo e($satir["talimat"] ?? ""); ?>" required></label>
              <button type="button" class="btn btn-secondary btn-small medicine-remove">Satırı Sil</button>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="full-field"><button type="button" class="btn btn-secondary" data-add-medicine>+ İlaç Satırı Ekle</button></div>
      </div>
      <div class="actions"><button class="btn btn-primary" type="submit">Güncelle</button><a href="../doktor_panel.php?panel=recete-arsivi" class="btn btn-secondary">Geri Dön</a></div>
    </form>
  </div>
</div></div>
<script src="../../assets/js/user_panel.js?v=20260511-son-istekler"></script>
</body>
</html>
