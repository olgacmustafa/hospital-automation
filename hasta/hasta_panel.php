<?php
require_once "includes/auth_kontrol.php";

$mesaj = $_SESSION["mesaj"] ?? "";
$hata = $_SESSION["hata"] ?? "";
unset($_SESSION["mesaj"], $_SESSION["hata"]);

$hasta_id = $_SESSION["hasta_id"];

$hastaSorgu = $db->prepare("SELECT * FROM Hastalar WHERE hasta_id = ?");
$hastaSorgu->execute(array($hasta_id));
$hasta = $hastaSorgu->fetch(PDO::FETCH_ASSOC);

$poliklinikler = $db->query("SELECT * FROM Poliklinikler ORDER BY poliklinik_ad ASC")->fetchAll(PDO::FETCH_ASSOC);
$doktorlar = $db->query("SELECT d.*, p.poliklinik_ad FROM Doktorlar d LEFT JOIN Poliklinikler p ON d.poliklinik_id = p.poliklinik_id ORDER BY d.isim ASC")->fetchAll(PDO::FETCH_ASSOC);

$randevuSaatleri = array();
$baslangicSaat = strtotime('09:00');
$bitisSaat = strtotime('16:45');
for ($zaman = $baslangicSaat; $zaman <= $bitisSaat; $zaman += 30 * 60) {
    $randevuSaatleri[] = date('H:i', $zaman);
}

$doluSlotSorgu = $db->query("SELECT doktor_id, randevu_tar, randevu_saat FROM Randevular WHERE durum NOT LIKE '%İptal%' AND durum NOT LIKE '%iptal%'");
$doluRandevuSlotlari = array();
foreach ($doluSlotSorgu->fetchAll(PDO::FETCH_ASSOC) as $slot) {
    $anahtar = $slot['doktor_id'] . '|' . $slot['randevu_tar'];
    if (!isset($doluRandevuSlotlari[$anahtar])) {
        $doluRandevuSlotlari[$anahtar] = array();
    }
    $doluRandevuSlotlari[$anahtar][] = substr($slot['randevu_saat'], 0, 5);
}

$randevuSorgu = $db->prepare("SELECT r.*, d.isim AS doktor_adi, d.`uzmanlık` AS uzmanlik, p.poliklinik_ad, p.lokasyon FROM Randevular r JOIN Doktorlar d ON r.doktor_id = d.doktor_id JOIN Poliklinikler p ON r.poliklinik_id = p.poliklinik_id WHERE r.hasta_id = ? ORDER BY r.randevu_tar DESC, r.randevu_saat DESC");
$randevuSorgu->execute(array($hasta_id));
$randevular = $randevuSorgu->fetchAll(PDO::FETCH_ASSOC);

$muayeneSorgu = $db->prepare("SELECT m.*, d.isim AS doktor_adi, d.`uzmanlık` AS uzmanlik, p.poliklinik_ad, t.tani_adi, t.aciklama AS tani_aciklama, r.randevu_tar, r.randevu_saat FROM Muayene m JOIN Doktorlar d ON m.doktor_id = d.doktor_id LEFT JOIN Poliklinikler p ON d.poliklinik_id = p.poliklinik_id LEFT JOIN `Tanı` t ON t.muayene_id = m.muayene_id LEFT JOIN Randevular r ON r.randevu_id = m.randevu_id WHERE m.hasta_id = ? ORDER BY m.muayene_tar DESC, m.muayene_id DESC");
$muayeneSorgu->execute(array($hasta_id));
$muayeneler = $muayeneSorgu->fetchAll(PDO::FETCH_ASSOC);

$receteSorgu = $db->prepare("SELECT rec.*, d.isim AS doktor_adi, d.`uzmanlık` AS uzmanlik, GROUP_CONCAT(CONCAT(il.ilac_ad, '||', COALESCE(il.`İlac_turu`, ''), '||', ri.doz, '||', ri.talimat) ORDER BY ri.r_ilac_id SEPARATOR '##') AS ilac_listesi FROM `Reçeteler` rec JOIN Doktorlar d ON rec.doktor_id = d.doktor_id LEFT JOIN `Reçete İlaçları` ri ON ri.recete_id = rec.recete_id LEFT JOIN `İlaçlar` il ON il.ilac_id = ri.ilac_id WHERE rec.hasta_id = ? GROUP BY rec.recete_id, rec.randevu_id, rec.hasta_id, rec.doktor_id, rec.recete_tar, d.isim, d.`uzmanlık` ORDER BY rec.recete_tar DESC, rec.recete_id DESC");
$receteSorgu->execute(array($hasta_id));
$receteler = $receteSorgu->fetchAll(PDO::FETCH_ASSOC);

$receteMap = array();
foreach ($receteler as $receteSatiri) {
    $mapKey = (string)($receteSatiri["randevu_id"] ?? "");
    if (!isset($receteMap[$mapKey])) {
        $receteMap[$mapKey] = array();
    }
    $receteMap[$mapKey][] = $receteSatiri;
}

$aktifRandevu = 0;
$bekleyen = 0;
$tamamlanan = 0;
foreach ($randevular as $r) {
    if (randevu_islem_yapilabilir_mi($r["durum"] ?? "")) $aktifRandevu++;
    if (randevu_bekliyor_mu($r["durum"] ?? "")) $bekleyen++;
    if (randevu_tamam_mi($r["durum"] ?? "")) $tamamlanan++;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hasta Paneli</title>
  <link rel="icon" type="image/png" href="../hospital-icon.png">
  <link rel="stylesheet" href="../assets/css/user_panel.css?v=20260511-tek-cati-muayene">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-icon user-visual">
        <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
          <circle cx="32" cy="22" r="12"></circle>
          <path d="M14 54c2.8-12 11.2-19 18-19s15.2 7 18 19"></path>
          <path d="M21 48c4 4 18 4 22 0"></path>
        </svg>
      </div>
      <div><h2>Hasta Paneli</h2><p>Randevu ve sağlık bilgileri</p></div>
    </div>

    <div class="menu">
      <div class="menu-title">Profil İşlemleri</div>
      <button class="menu-btn active" data-icon="👤" data-target="profilim">Profilim</button>

      <div class="menu-title">Randevu İşlemleri</div>
      <button class="menu-btn" data-icon="📅" data-target="randevu-al">Randevu Al</button>
      <button class="menu-btn" data-icon="📋" data-target="randevularim">Randevularım</button>

      <div class="menu-title">Sağlık Kayıtları</div>
      <button class="menu-btn" data-icon="🩺" data-target="muayene-sonuclari">Muayene Sonuçlarım</button>
    </div>

    <a class="logout" href="../cikis.php">Çıkış Yap</a>
  </aside>

  <main class="content">
    <div class="topbar">
      <div>
        <h1>Hoş geldiniz, <?php echo e($hasta["isim"] ?? $_SESSION["hasta_ad"]); ?></h1>
        <p>Randevularınızı takip edebilir, tanı ve reçete bilgilerinizi görüntüleyebilirsiniz.</p>
      </div>
      <div class="user-box">Oturum: Hasta</div>
    </div>

    <div class="dashboard-summary">
      <div class="summary-card summary-blue"><span class="summary-label">Aktif Randevu</span><strong><?php echo $aktifRandevu; ?></strong><small>İptal/tamamlandı olmayan kayıt</small></div>
      <div class="summary-card summary-orange"><span class="summary-label">Bekleyen</span><strong><?php echo $bekleyen; ?></strong><small>Onay bekleyen randevu</small></div>
      <div class="summary-card summary-green"><span class="summary-label">Tamamlanan</span><strong><?php echo $tamamlanan; ?></strong><small>Tamamlanan muayene</small></div>
      <div class="summary-card summary-slate"><span class="summary-label">Reçete</span><strong><?php echo count($receteler); ?></strong><small>Doktor tarafından yazılan ilaç</small></div>
    </div>

    <?php if ($mesaj): ?><div class="message success"><?php echo e($mesaj); ?></div><?php endif; ?>
    <?php if ($hata): ?><div class="message error"><?php echo e($hata); ?></div><?php endif; ?>

    <section class="panel active" id="profilim">
      <div class="panel-head"><div><h2>Profil İşlemleri</h2><div class="panel-desc">Kimlik bilgileriniz sabit tutulur; telefon, e-posta ve şifre bilgilerinizi buradan yönetebilirsiniz.</div></div></div>

      <div class="profile-grid account-overview">
        <div class="info-card"><span>TC No</span><strong><?php echo e($hasta["tc_no"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Ad Soyad</span><strong><?php echo e($hasta["isim"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Doğum Tarihi</span><strong><?php echo tarih_yaz($hasta["dogum_tar"] ?? ""); ?></strong></div>
        <div class="info-card"><span>Cinsiyet</span><strong><?php echo e($hasta["cinsiyet"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Telefon</span><strong><?php echo e($hasta["tel_no"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>E-posta</span><strong><?php echo e($hasta["email"] ?? "-"); ?></strong></div>
      </div>

      <div class="locked-note">TC no, ad soyad, doğum tarihi ve cinsiyet bilgileri hastane kaydı olarak tutulduğu için hasta panelinden değiştirilemez.</div>

      <div class="profile-account-layout">
        <form action="actions/profil_guncelle.php" method="POST" class="profile-form-card">
          <div class="profile-section-title"><h3>İletişim Bilgilerini Güncelle</h3><p>Size ulaşılabilecek telefon ve e-posta bilgilerinizi güncel tutabilirsiniz.</p></div>
          <div class="form-grid">
            <label>Telefon No<input type="text" name="tel_no" maxlength="11" value="<?php echo e($hasta["tel_no"] ?? ""); ?>" required></label>
            <label>E-posta<input type="email" name="email" value="<?php echo e($hasta["email"] ?? ""); ?>" placeholder="ornek@mail.com"></label>
          </div>
          <div class="actions"><button class="btn btn-primary" type="submit">İletişim Bilgilerini Güncelle</button><button class="btn btn-secondary" type="reset">Temizle</button></div>
        </form>

        <form action="actions/sifre_degistir.php" method="POST" class="profile-form-card">
          <div class="profile-section-title"><h3>Şifre Değiştir</h3><p>Hesap güvenliğiniz için mevcut şifrenizi doğrulayarak yeni şifre belirleyin.</p></div>
          <p class="password-panel-note">Şifre değiştirildikten sonra bu hesaba ait eski oturumlar bir sonraki kontrolde geçersiz sayılır.</p>
          <div class="form-grid password-grid">
            <label>Mevcut Şifre<input type="password" name="mevcut_sifre" autocomplete="current-password" required></label>
            <label>Yeni Şifre<input type="password" name="yeni_sifre" autocomplete="new-password" required></label>
            <label>Yeni Şifre Tekrar<input type="password" name="yeni_sifre_tekrar" autocomplete="new-password" required></label>
          </div>
          <div class="actions"><button class="btn btn-primary" type="submit">Şifreyi Güncelle</button><button class="btn btn-secondary" type="reset">Temizle</button></div>
        </form>
      </div>
    </section>

    <section class="panel" id="randevu-al">
      <div class="panel-head"><div><h2>Randevu Al</h2><div class="panel-desc">Poliklinik ve doktor seçerek istediğiniz tarih ve saatte randevu talebi oluşturun.</div></div></div>
      <form action="actions/randevu_ekle.php" method="POST" class="smart-form">
        <div class="form-grid">
          <label>Poliklinik
            <select name="poliklinik_id" id="poliklinikSec" required>
              <option value="">Poliklinik seçiniz</option>
              <?php foreach ($poliklinikler as $p): ?>
                <option value="<?php echo e($p["poliklinik_id"]); ?>"><?php echo e($p["poliklinik_ad"]); ?> - <?php echo e($p["lokasyon"]); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Doktor
            <select name="doktor_id" id="doktorSec" required>
              <option value="">Doktor seçiniz</option>
              <?php foreach ($doktorlar as $d): ?>
                <option value="<?php echo e($d["doktor_id"]); ?>" data-poliklinik="<?php echo e($d["poliklinik_id"]); ?>"><?php echo e($d["isim"]); ?> - <?php echo e($d["uzmanlık"]); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Randevu Tarihi<input type="date" name="randevu_tar" min="<?php echo date('Y-m-d'); ?>" required></label>
          <label>Randevu Saati
            <select name="randevu_saat" id="randevuSaatSec" required>
              <option value="">Saat seçiniz</option>
              <?php foreach ($randevuSaatleri as $saat): ?>
                <option value="<?php echo e($saat); ?>"><?php echo e($saat); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="slot-note">Randevu saatleri 09:00–16:45 aralığında 30 dakikalık sistemle listelenir. Aynı doktorun dolu saatleri otomatik kapatılır.</div>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit">Randevu Oluştur</button><button class="btn btn-secondary" type="reset">Temizle</button></div>
      </form>
    </section>

    <section class="panel" id="randevularim">
      <div class="panel-head"><div><h2>Randevularım</h2><div class="panel-desc">Tüm randevu talepleriniz ve durumları.</div></div></div>
      <div class="table-wrap"><table><thead><tr><th>Tarih / Saat</th><th>Doktor</th><th>Poliklinik</th><th>Lokasyon</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>
        <?php if (count($randevular) === 0): ?><tr><td colspan="6">Henüz randevunuz yok.</td></tr><?php endif; ?>
        <?php foreach ($randevular as $r): ?>
          <tr>
            <td><?php echo tarih_saat_yaz($r["randevu_tar"], $r["randevu_saat"]); ?></td>
            <td><?php echo e($r["doktor_adi"]); ?><br><small><?php echo e($r["uzmanlik"]); ?></small></td>
            <td><?php echo e($r["poliklinik_ad"]); ?></td>
            <td><?php echo e($r["lokasyon"]); ?></td>
            <td><span class="status <?php echo randevu_durum_class($r["durum"]); ?>"><?php echo e($r["durum"]); ?></span></td>
            <td>
              <?php if (randevu_tamam_mi($r["durum"] ?? "")): ?>
                <a class="btn btn-primary btn-small" href="?panel=muayene-sonuclari">Sonuçları Gör</a>
              <?php elseif (randevu_islem_yapilabilir_mi($r["durum"] ?? "")): ?>
                <form class="inline-form delete-form" action="actions/randevu_iptal.php" method="POST" data-confirm-message="Bu randevuyu iptal etmek istediğine emin misin?">
                  <input type="hidden" name="randevu_id" value="<?php echo e($r["randevu_id"]); ?>">
                  <button class="btn btn-danger btn-small" type="submit">İptal Et</button>
                </form>
              <?php else: ?>
                <span class="muted-text">İşlem yok</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </section>

    <section class="panel" id="muayene-sonuclari">
      <div class="panel-head"><div><h2>Muayene Sonuçlarım</h2><div class="panel-desc">Tamamlanan randevularınıza ait muayene notu, tanı ve reçete bilgilerini tek ekranda görüntüleyebilirsiniz.</div></div></div>
      <div class="record-grid result-grid">
        <?php if (count($muayeneler) === 0): ?><div class="empty-box">Henüz tamamlanmış muayene sonucunuz bulunmuyor.</div><?php endif; ?>
        <?php foreach ($muayeneler as $m): ?>
          <?php $randevuKey = (string)($m["randevu_id"] ?? ""); $ilgiliReceteler = $receteMap[$randevuKey] ?? array(); ?>
          <article class="record-card result-card">
            <div class="record-top"><strong><?php echo e($m["tani_adi"] ?? "Tanı girilmedi"); ?></strong><span><?php echo tarih_yaz($m["muayene_tar"]); ?></span></div>
            <p><b>Doktor:</b> <?php echo e($m["doktor_adi"]); ?> / <?php echo e($m["uzmanlik"]); ?></p>
            <p><b>Poliklinik:</b> <?php echo e($m["poliklinik_ad"] ?? "-"); ?></p>
            <p><b>Randevu:</b> <?php echo tarih_saat_yaz($m["randevu_tar"], $m["randevu_saat"] ?? ""); ?></p>
            <p><b>Muayene Notu:</b> <?php echo e($m["notlar"]); ?></p>
            <p><b>Tanı Açıklaması:</b> <?php echo e($m["tani_aciklama"] ?? "-"); ?></p>

            <div class="result-prescription">
              <h3>Reçete Bilgisi</h3>
              <?php if (count($ilgiliReceteler) === 0): ?>
                <p class="muted-text">Bu muayene için reçete kaydı bulunmuyor.</p>
              <?php else: ?>
                <?php foreach ($ilgiliReceteler as $recete): ?>
                  <div class="prescription-block">
                    <small>Reçete Tarihi: <?php echo tarih_yaz($recete["recete_tar"]); ?></small>
                    <?php if (!empty($recete["ilac_listesi"])): ?>
                      <?php foreach (explode("##", $recete["ilac_listesi"]) as $satir): ?>
                        <?php $parca = explode("||", $satir); ?>
                        <div class="medicine-line"><b><?php echo e($parca[0] ?? "İlaç"); ?></b> <small><?php echo e($parca[1] ?? ""); ?></small><br><span><?php echo e($parca[2] ?? "-"); ?> - <?php echo e($parca[3] ?? "-"); ?></span></div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <p class="muted-text">İlaç bilgisi bulunmuyor.</p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</div>

<div class="confirm-overlay" id="confirmOverlay" aria-hidden="true">
  <div class="confirm-box" role="dialog" aria-modal="true">
    <div class="confirm-icon">!</div>
    <h3>İşlem Onayı</h3>
    <p id="confirmMessage">Bu işlemi yapmak istediğine emin misin?</p>
    <div class="confirm-actions"><button type="button" class="confirm-cancel" id="confirmCancel">Vazgeç</button><button type="button" class="confirm-delete" id="confirmDelete">Evet, devam et</button></div>
  </div>
</div>

<script>
  window.doluRandevuSlotlari = <?php echo json_encode($doluRandevuSlotlari, JSON_UNESCAPED_UNICODE); ?>;
  window.guncelRandevuTarihi = "<?php echo date('Y-m-d'); ?>";
  window.guncelRandevuSaati = "<?php echo date('H:i'); ?>";
</script>
<script src="../assets/js/user_panel.js?v=20260511-tek-cati-muayene"></script>
</body>
</html>
