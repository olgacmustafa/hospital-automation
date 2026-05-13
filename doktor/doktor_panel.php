<?php
require_once "includes/auth_kontrol.php";

$mesaj = $_SESSION["mesaj"] ?? "";
$hata = $_SESSION["hata"] ?? "";
unset($_SESSION["mesaj"], $_SESSION["hata"]);

$doktor_id = $_SESSION["doktor_id"];

$doktorSorgu = $db->prepare("SELECT d.*, p.poliklinik_ad, p.lokasyon FROM Doktorlar d LEFT JOIN Poliklinikler p ON d.poliklinik_id = p.poliklinik_id WHERE d.doktor_id = ?");
$doktorSorgu->execute(array($doktor_id));
$doktor = $doktorSorgu->fetch(PDO::FETCH_ASSOC);

$randevuSorgu = $db->prepare("SELECT r.*, h.isim AS hasta_adi, h.tc_no, h.tel_no, h.email, p.poliklinik_ad FROM Randevular r JOIN Hastalar h ON r.hasta_id = h.hasta_id JOIN Poliklinikler p ON r.poliklinik_id = p.poliklinik_id WHERE r.doktor_id = ? ORDER BY r.randevu_tar DESC, r.randevu_saat DESC");
$randevuSorgu->execute(array($doktor_id));
$randevular = $randevuSorgu->fetchAll(PDO::FETCH_ASSOC);

$hastaSorgu = $db->prepare("SELECT DISTINCT h.* FROM Hastalar h JOIN Randevular r ON r.hasta_id = h.hasta_id WHERE r.doktor_id = ? ORDER BY h.isim ASC");
$hastaSorgu->execute(array($doktor_id));
$hastalar = $hastaSorgu->fetchAll(PDO::FETCH_ASSOC);

$muayeneSorgu = $db->prepare("SELECT m.*, h.isim AS hasta_adi, h.tc_no, t.tani_id, t.tani_adi, t.aciklama AS tani_aciklama, r.randevu_tar, r.randevu_saat FROM Muayene m JOIN Hastalar h ON m.hasta_id = h.hasta_id LEFT JOIN `Tanı` t ON t.muayene_id = m.muayene_id LEFT JOIN Randevular r ON r.randevu_id = m.randevu_id WHERE m.doktor_id = ? ORDER BY m.muayene_tar DESC, m.muayene_id DESC");
$muayeneSorgu->execute(array($doktor_id));
$muayeneler = $muayeneSorgu->fetchAll(PDO::FETCH_ASSOC);

$receteSorgu = $db->prepare("SELECT rec.*, h.isim AS hasta_adi, h.tc_no, GROUP_CONCAT(CONCAT(il.ilac_ad, '||', COALESCE(il.`İlac_turu`, ''), '||', ri.doz, '||', ri.talimat) ORDER BY ri.r_ilac_id SEPARATOR '##') AS ilac_listesi FROM `Reçeteler` rec JOIN Hastalar h ON rec.hasta_id = h.hasta_id LEFT JOIN `Reçete İlaçları` ri ON ri.recete_id = rec.recete_id LEFT JOIN `İlaçlar` il ON il.ilac_id = ri.ilac_id WHERE rec.doktor_id = ? GROUP BY rec.recete_id, rec.randevu_id, rec.hasta_id, rec.doktor_id, rec.recete_tar, h.isim, h.tc_no ORDER BY rec.recete_tar DESC, rec.recete_id DESC");
$receteSorgu->execute(array($doktor_id));
$receteler = $receteSorgu->fetchAll(PDO::FETCH_ASSOC);

$ilaclar = $db->query("SELECT * FROM `İlaçlar` ORDER BY ilac_ad ASC")->fetchAll(PDO::FETCH_ASSOC);

$bugun = date("Y-m-d");
$bugunRandevu = 0;
$bekleyen = 0;
$tamamlanan = 0;
foreach ($randevular as $r) {
    if (($r["randevu_tar"] ?? "") === $bugun) $bugunRandevu++;
    if (randevu_bekliyor_mu($r["durum"] ?? "")) $bekleyen++;
    if (randevu_tamam_mi($r["durum"] ?? "")) $tamamlanan++;
}

$muayeneRandevuIdleri = array();
$muayeneRandevuSorgu = $db->prepare("SELECT randevu_id FROM Muayene WHERE doktor_id = ?");
$muayeneRandevuSorgu->execute(array($doktor_id));
foreach ($muayeneRandevuSorgu->fetchAll(PDO::FETCH_ASSOC) as $satir) {
    $muayeneRandevuIdleri[(string)$satir["randevu_id"]] = true;
}

$muayeneRandevular = array();
$receteRandevular = array();
foreach ($randevular as $r) {
    $randevuId = (string)($r["randevu_id"] ?? "");

    if (!randevu_iptal_mi($r["durum"] ?? "")) {
        $receteRandevular[] = $r;
    }

    if (!randevu_islem_yapilabilir_mi($r["durum"] ?? "") || isset($muayeneRandevuIdleri[$randevuId])) {
        continue;
    }

    $muayeneRandevular[] = $r;
}

$seciliRandevuId = trim($_GET["randevu_id"] ?? "");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doktor Paneli</title>
  <link rel="icon" type="image/png" href="../hospital-icon.png">
  <link rel="stylesheet" href="../assets/css/user_panel.css?v=20260511-tek-cati-muayene">
</head>
<body>
<div class="layout">
  <aside class="sidebar doctor-sidebar">
    <div class="brand">
      <div class="brand-icon user-visual">
        <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
          <circle cx="32" cy="22" r="12"></circle>
          <path d="M14 54c2.8-12 11.2-19 18-19s15.2 7 18 19"></path>
          <path d="M21 48c4 4 18 4 22 0"></path>
        </svg>
      </div>
      <div><h2>Doktor Paneli</h2><p>Muayene ve reçete yönetimi</p></div>
    </div>

    <div class="menu">
      <div class="menu-title">Profil İşlemleri</div>
      <button class="menu-btn active" data-icon="👤" data-target="profilim">Profilim</button>

      <div class="menu-title">Hasta Yönetimi</div>
      <button class="menu-btn" data-icon="👥" data-target="hastalarim">Hastalarımı Listele</button>

      <div class="menu-title">Muayene Yönetimi</div>
      <button class="menu-btn" data-icon="🩺" data-target="muayene-islemleri">Muayene İşlemleri</button>
      <button class="menu-btn" data-icon="📁" data-target="muayene-gecmisi">Muayene Geçmişi</button>

      <div class="menu-title">Randevu İşlemleri</div>
      <button class="menu-btn" data-icon="📅" data-target="randevularim">Randevu Takibi</button>

      <div class="menu-title">Reçete İşlemleri</div>
      <button class="menu-btn" data-icon="📋" data-target="recete-arsivi">Reçete Arşivi</button>
    </div>

    <a class="logout" href="../cikis.php">Çıkış Yap</a>
  </aside>

  <main class="content">
    <div class="topbar">
      <div>
        <h1>Hoş geldiniz, Dr. <?php echo e($doktor["isim"] ?? $_SESSION["doktor_ad"]); ?></h1>
        <p>Hasta randevularını, muayene notlarını, tanı ve reçete işlemlerini tek akışta yönetebilirsiniz.</p>
      </div>
      <div class="user-box">Oturum: Doktor</div>
    </div>

    <div class="dashboard-summary">
      <div class="summary-card summary-blue"><span class="summary-label">Bugünkü Randevu</span><strong><?php echo $bugunRandevu; ?></strong><small>Bugün planlanan randevu</small></div>
      <div class="summary-card summary-orange"><span class="summary-label">Bekleyen</span><strong><?php echo $bekleyen; ?></strong><small>Onay bekleyen randevu</small></div>
      <div class="summary-card summary-green"><span class="summary-label">Tamamlanan</span><strong><?php echo $tamamlanan; ?></strong><small>Tamamlanan muayene</small></div>
      <div class="summary-card summary-slate"><span class="summary-label">Uzmanlık</span><strong><?php echo e($doktor["uzmanlık"] ?? "-"); ?></strong><small><?php echo e($doktor["poliklinik_ad"] ?? "Poliklinik"); ?></small></div>
    </div>

    <?php if ($mesaj): ?><div class="message success"><?php echo e($mesaj); ?></div><?php endif; ?>
    <?php if ($hata): ?><div class="message error"><?php echo e($hata); ?></div><?php endif; ?>

    <section class="panel active" id="profilim">
      <div class="panel-head"><div><h2>Profil İşlemleri</h2><div class="panel-desc">Mesleki kayıt bilgileriniz sabit tutulur; telefon, e-posta ve şifre bilgilerinizi buradan yönetebilirsiniz.</div></div></div>

      <div class="profile-grid account-overview">
        <div class="info-card"><span>TC No</span><strong><?php echo e($doktor["tc_no"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Ad Soyad</span><strong><?php echo e($doktor["isim"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Uzmanlık</span><strong><?php echo e($doktor["uzmanlık"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Poliklinik</span><strong><?php echo e($doktor["poliklinik_ad"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>Telefon</span><strong><?php echo e($doktor["tel_no"] ?? "-"); ?></strong></div>
        <div class="info-card"><span>E-posta</span><strong><?php echo e($doktor["email"] ?? "-"); ?></strong></div>
      </div>

      <div class="locked-note">TC no, ad soyad, uzmanlık ve poliklinik bilgileri yönetim kaydı olduğu için doktor panelinden değiştirilemez.</div>

      <div class="profile-account-layout">
        <form action="actions/profil_guncelle.php" method="POST" class="profile-form-card">
          <div class="profile-section-title"><h3>İletişim Bilgilerini Güncelle</h3><p>Hasta ve yönetim tarafından ulaşılabilecek telefon ve e-posta bilgilerinizi güncel tutabilirsiniz.</p></div>
          <div class="form-grid">
            <label>Telefon No<input type="text" name="tel_no" maxlength="11" value="<?php echo e($doktor["tel_no"] ?? ""); ?>" required></label>
            <label>E-posta<input type="email" name="email" value="<?php echo e($doktor["email"] ?? ""); ?>" placeholder="ornek@mail.com"></label>
          </div>
          <div class="actions"><button class="btn btn-primary" type="submit">İletişim Bilgilerini Güncelle</button><button class="btn btn-secondary" type="reset">Temizle</button></div>
        </form>

        <form action="actions/sifre_degistir.php" method="POST" class="profile-form-card">
          <div class="profile-section-title"><h3>Şifre Değiştir</h3><p>Hesap güvenliğiniz için mevcut şifrenizi doğrulayarak yeni şifre belirleyin.</p></div>
          <p class="password-panel-note">Şifre değiştirildiğinde bu hesabın eski şifreyle açılmış diğer oturumları bir sonraki kontrolde geçersiz olur.</p>
          <div class="form-grid password-grid">
            <label>Mevcut Şifre<input type="password" name="mevcut_sifre" autocomplete="current-password" required></label>
            <label>Yeni Şifre<input type="password" name="yeni_sifre" autocomplete="new-password" required></label>
            <label>Yeni Şifre Tekrar<input type="password" name="yeni_sifre_tekrar" autocomplete="new-password" required></label>
          </div>
          <div class="actions"><button class="btn btn-primary" type="submit">Şifreyi Güncelle</button><button class="btn btn-secondary" type="reset">Temizle</button></div>
        </form>
      </div>
    </section>

    <section class="panel" id="hastalarim">
      <div class="panel-head"><div><h2>Hastalarımı Listele</h2><div class="panel-desc">Size randevu almış hastalar aşağıda listelenir.</div></div>
        <div class="table-tools"><label class="table-search"><input data-table-search="#hasta-table" data-search-column="1" type="search" placeholder="Hasta TC ara..."></label></div>
      </div>
      <div class="table-wrap"><table id="hasta-table"><thead><tr><th>Hasta</th><th>TC No</th><th>Telefon</th><th>E-posta</th></tr></thead><tbody>
        <?php if (count($hastalar) === 0): ?><tr><td colspan="4">Henüz hasta kaydı bulunmuyor.</td></tr><?php endif; ?>
        <?php foreach ($hastalar as $h): ?><tr><td><?php echo e($h["isim"]); ?></td><td><?php echo e($h["tc_no"]); ?></td><td><?php echo e($h["tel_no"]); ?></td><td><?php echo e($h["email"] ?? "-"); ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </section>

    <section class="panel" id="randevularim">
      <div class="panel-head"><div><h2>Randevu Takibi</h2><div class="panel-desc">Hastalar tarafından oluşturulan randevuları buradan takip edebilirsiniz. Muayene, tanı ve reçete işlemi “Muayene İşlemleri” bölümünde tek adımda tamamlanır.</div></div></div>
      <div class="table-wrap"><table><thead><tr><th>Tarih / Saat</th><th>Hasta</th><th>TC</th><th>Telefon</th><th>Poliklinik</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>
        <?php if (count($randevular) === 0): ?><tr><td colspan="7">Henüz randevu bulunmuyor.</td></tr><?php endif; ?>
        <?php foreach ($randevular as $r): ?>
          <?php $randevuId = (string)($r["randevu_id"] ?? ""); $islemYapilabilir = randevu_islem_yapilabilir_mi($r["durum"] ?? "") && !isset($muayeneRandevuIdleri[$randevuId]); ?>
          <tr>
            <td><?php echo tarih_saat_yaz($r["randevu_tar"], $r["randevu_saat"]); ?></td>
            <td><?php echo e($r["hasta_adi"]); ?></td>
            <td><?php echo e($r["tc_no"]); ?></td>
            <td><?php echo e($r["tel_no"]); ?></td>
            <td><?php echo e($r["poliklinik_ad"]); ?></td>
            <td><span class="status <?php echo randevu_durum_class($r["durum"]); ?>"><?php echo e($r["durum"]); ?></span></td>
            <td><div class="table-actions">
              <?php if ($islemYapilabilir): ?>
                <a class="btn btn-primary btn-small" href="?panel=muayene-islemleri&randevu_id=<?php echo e($r["randevu_id"]); ?>">Muayeneye Al</a>
                <form class="inline-form delete-form" action="actions/randevu_durum.php" method="POST" data-confirm-message="Bu randevuyu iptal etmek istediğine emin misin?"><input type="hidden" name="randevu_id" value="<?php echo e($r["randevu_id"]); ?>"><input type="hidden" name="durum" value="İptal Edildi"><button class="btn btn-danger btn-small" type="submit">İptal</button></form>
              <?php else: ?>
                <span class="muted-text">İşlem yok</span>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </section>

    <section class="panel" id="muayene-islemleri">
      <div class="panel-head"><div><h2>Muayene İşlemleri</h2><div class="panel-desc">Randevu onayı, muayene notu, tanı ve reçete bilgilerini tek ekranda tamamlayın.</div></div></div>

   

      <?php if (count($muayeneRandevular) === 0): ?>
        <div class="empty-box">Muayene bekleyen randevu bulunmuyor. </div>
      <?php else: ?>
      <form action="actions/muayene_kaydet.php" method="POST">
        <div class="form-grid">
          <label class="full-field">Randevu / Hasta
            <select name="randevu_id" required>
              <option value="">Randevu seçiniz</option>
              <?php foreach ($muayeneRandevular as $r): ?>
                <option value="<?php echo e($r["randevu_id"]); ?>" <?php echo ((string)$seciliRandevuId === (string)$r["randevu_id"]) ? "selected" : ""; ?>><?php echo tarih_saat_yaz($r["randevu_tar"], $r["randevu_saat"]); ?> - <?php echo e($r["hasta_adi"]); ?> / <?php echo e($r["tc_no"]); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Muayene Tarihi<input type="date" name="muayene_tar" value="<?php echo date('Y-m-d'); ?>" required></label>
          <label>Tanı Adı<input type="text" name="tani_adi" maxlength="30" placeholder="Örn: Grip" required></label>
          <label class="full-field">Muayene Notu<textarea name="notlar" maxlength="30" placeholder="Kısa muayene notu yazınız. Maksimum 30 karakter." required></textarea></label>
          <label class="full-field">Tanı Açıklaması<textarea name="tani_aciklama" maxlength="100" placeholder="Tanıya ilişkin kısa açıklama yazınız."></textarea></label>
          <div class="medicine-repeater" data-medicine-repeater>
            <div class="form-hint">Reçete yazmak istemiyorsanız ilaç satırını boş bırakabilirsiniz. Birden fazla ilaç için “İlaç Satırı Ekle” butonunu kullanın.</div>
            <div class="medicine-row">
              <label>İlaç Seçimi
                <select name="ilac_id[]">
                  <option value="">Reçete yazılmayacak</option>
                  <?php foreach ($ilaclar as $ilac): ?>
                    <option value="<?php echo e($ilac["ilac_id"]); ?>"><?php echo e($ilac["ilac_ad"]); ?> - <?php echo e($ilac["İlac_turu"]); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Doz<input type="text" name="doz[]" maxlength="10" placeholder="Örn: 2x1"></label>
              <label>Kullanım Talimatı<input type="text" name="talimat[]" maxlength="50" placeholder="Örn: Tok karnına"></label>
              <button type="button" class="btn btn-secondary btn-small medicine-remove">Satırı Sil</button>
            </div>
          </div>
          <div class="full-field"><button type="button" class="btn btn-secondary" data-add-medicine>+ İlaç Satırı Ekle</button></div>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit">Kaydet ve Muayeneyi Tamamla</button><button class="btn btn-secondary" type="reset">Temizle</button></div>
      </form>
      <?php endif; ?>
    </section>

    <section class="panel" id="muayene-gecmisi">
      <div class="panel-head"><div><h2>Muayene Geçmişi</h2><div class="panel-desc">Daha önce girdiğiniz muayene ve tanı kayıtları.</div></div></div>
      <div class="record-grid">
        <?php if (count($muayeneler) === 0): ?><div class="empty-box">Henüz muayene kaydı bulunmuyor.</div><?php endif; ?>
        <?php foreach ($muayeneler as $m): ?>
          <article class="record-card">
            <div class="record-top"><strong><?php echo e($m["hasta_adi"]); ?></strong><span><?php echo tarih_yaz($m["muayene_tar"]); ?></span></div>
            <p><b>TC:</b> <?php echo e($m["tc_no"]); ?></p>
            <p><b>Tanı:</b> <?php echo e($m["tani_adi"] ?? "-"); ?></p>
            <p><b>Not:</b> <?php echo e($m["notlar"]); ?></p>
            <p><b>Açıklama:</b> <?php echo e($m["tani_aciklama"] ?? "-"); ?></p>
            <div class="record-actions"><a class="edit-btn" href="actions/muayene_duzenle.php?id=<?php echo e($m["muayene_id"]); ?>">Düzenle</a></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel" id="recete-arsivi">
      <div class="panel-head"><div><h2>Reçete Arşivi</h2><div class="panel-desc">Yazdığınız reçete kayıtları.</div></div></div>
      <div class="table-wrap"><table><thead><tr><th>Tarih</th><th>Hasta</th><th>TC</th><th>İlaçlar</th><th>İşlem</th></tr></thead><tbody>
        <?php if (count($receteler) === 0): ?><tr><td colspan="5">Henüz reçete kaydı yok.</td></tr><?php endif; ?>
        <?php foreach ($receteler as $rec): ?>
          <tr>
            <td><?php echo tarih_yaz($rec["recete_tar"]); ?></td>
            <td><?php echo e($rec["hasta_adi"]); ?></td>
            <td><?php echo e($rec["tc_no"]); ?></td>
            <td>
              <?php if (!empty($rec["ilac_listesi"])): ?>
                <?php foreach (explode("##", $rec["ilac_listesi"]) as $satir): ?>
                  <?php $parca = explode("||", $satir); ?>
                  <div class="medicine-line"><b><?php echo e($parca[0] ?? "-"); ?></b> <small><?php echo e($parca[1] ?? ""); ?></small><br><span><?php echo e($parca[2] ?? "-"); ?> - <?php echo e($parca[3] ?? "-"); ?></span></div>
                <?php endforeach; ?>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><a class="edit-btn" href="actions/recete_duzenle.php?id=<?php echo e($rec["recete_id"]); ?>">Düzenle</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
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

<script src="../assets/js/user_panel.js?v=20260511-tek-cati-muayene"></script>
</body>
</html>
