<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../hasta_panel.php");
}

$hasta_id = $_SESSION["hasta_id"];
$doktor_id = trim($_POST["doktor_id"] ?? "");
$poliklinik_id = trim($_POST["poliklinik_id"] ?? "");
$randevu_tar = trim($_POST["randevu_tar"] ?? "");
$randevu_saat = trim($_POST["randevu_saat"] ?? "");

if ($doktor_id === "" || $poliklinik_id === "" || $randevu_tar === "" || $randevu_saat === "") {
    session_hata("Randevu için tüm alanları doldurmalısınız.");
    yonlendir("../hasta_panel.php?panel=randevu-al");
}

$randevu_saat = substr($randevu_saat, 0, 5);
$seciliZaman = strtotime($randevu_saat);
$baslangic = strtotime("09:00");
$bitis = strtotime("16:45");
$dakika = (int)date("i", $seciliZaman);

if ($seciliZaman === false || $seciliZaman < $baslangic || $seciliZaman > $bitis || !in_array($dakika, array(0, 30), true)) {
    session_hata("Randevu saati 09:00 ile 16:45 arasında ve 30 dakikalık aralıklarla seçilmelidir.");
    yonlendir("../hasta_panel.php?panel=randevu-al");
}

$bugun = date("Y-m-d");
if ($randevu_tar < $bugun) {
    session_hata("Geçmiş tarihe randevu alınamaz.");
    yonlendir("../hasta_panel.php?panel=randevu-al");
}

$seciliRandevuZamani = DateTime::createFromFormat("Y-m-d H:i", $randevu_tar . " " . $randevu_saat);
$simdi = new DateTime();
if (!$seciliRandevuZamani || $seciliRandevuZamani <= $simdi) {
    session_hata("Geçmiş saate randevu alınamaz. Lütfen ileri bir saat seçin.");
    yonlendir("../hasta_panel.php?panel=randevu-al");
}

try {
    $doktorKontrol = $db->prepare("SELECT COUNT(*) FROM Doktorlar WHERE doktor_id = ? AND poliklinik_id = ?");
    $doktorKontrol->execute(array($doktor_id, $poliklinik_id));
    if ((int)$doktorKontrol->fetchColumn() === 0) {
        session_hata("Seçilen doktor bu polikliniğe bağlı değil.");
        yonlendir("../hasta_panel.php?panel=randevu-al");
    }

    $kontrol = $db->prepare("SELECT COUNT(*) FROM Randevular WHERE doktor_id = ? AND randevu_tar = ? AND randevu_saat = ? AND durum NOT LIKE '%İptal%' AND durum NOT LIKE '%iptal%'");
    $kontrol->execute(array($doktor_id, $randevu_tar, $randevu_saat));

    if ((int)$kontrol->fetchColumn() > 0) {
        session_hata("Seçilen doktorun bu tarih ve saatte başka randevusu var.");
        yonlendir("../hasta_panel.php?panel=randevu-al");
    }

    $randevu_id = sonraki_id($db, "Randevular", "randevu_id");
    $sorgu = $db->prepare("INSERT INTO Randevular (randevu_id, hasta_id, doktor_id, poliklinik_id, randevu_tar, randevu_saat, durum) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sorgu->execute(array($randevu_id, $hasta_id, $doktor_id, $poliklinik_id, $randevu_tar, $randevu_saat, "Bekliyor"));

    session_mesaj("Randevu talebiniz başarıyla oluşturuldu.");
} catch (PDOException $e) {
    session_hata("Randevu oluşturulurken hata oluştu: " . $e->getMessage());
}

yonlendir("../hasta_panel.php?panel=randevularim");
?>
