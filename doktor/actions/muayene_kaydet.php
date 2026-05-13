<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../doktor_panel.php");
}

$doktor_id = $_SESSION["doktor_id"];
$randevu_id = trim($_POST["randevu_id"] ?? "");
$muayene_tar = trim($_POST["muayene_tar"] ?? date("Y-m-d"));
$notlar = kisalt($_POST["notlar"] ?? "", 30);
$tani_adi = kisalt($_POST["tani_adi"] ?? "", 30);
$tani_aciklama = kisalt($_POST["tani_aciklama"] ?? "", 100);
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
        session_hata("Reçete satırlarında ilaç, doz ve talimat alanları birlikte doldurulmalıdır.");
        yonlendir("../doktor_panel.php?panel=muayene-islemleri");
    }
    $receteIlaclari[] = array($ilac_id, $doz, $talimat);
}

if ($randevu_id === "" || $muayene_tar === "" || $notlar === "" || $tani_adi === "") {
    session_hata("Muayene kaydı için randevu, tarih, not ve tanı alanları zorunludur.");
    yonlendir("../doktor_panel.php?panel=muayene-islemleri");
}

try {
    $db->beginTransaction();

    $randevuSorgu = $db->prepare("SELECT * FROM Randevular WHERE randevu_id = ? AND doktor_id = ?");
    $randevuSorgu->execute(array($randevu_id, $doktor_id));
    $randevu = $randevuSorgu->fetch(PDO::FETCH_ASSOC);

    if (!$randevu) {
        throw new Exception("Randevu bulunamadı.");
    }

    if (!randevu_islem_yapilabilir_mi($randevu["durum"] ?? "")) {
        throw new Exception("Bu randevu iptal edilmiş veya tamamlanmış olduğu için tekrar işleme alınamaz.");
    }

    $kontrol = $db->prepare("SELECT COUNT(*) FROM Muayene WHERE randevu_id = ? AND doktor_id = ?");
    $kontrol->execute(array($randevu_id, $doktor_id));
    if ((int)$kontrol->fetchColumn() > 0) {
        throw new Exception("Bu randevu için daha önce muayene kaydı oluşturulmuş.");
    }

    $muayene_id = sonraki_id($db, "Muayene", "muayene_id");
    $muayeneEkle = $db->prepare("INSERT INTO Muayene (muayene_id, randevu_id, doktor_id, hasta_id, muayene_tar, notlar) VALUES (?, ?, ?, ?, ?, ?)");
    $muayeneEkle->execute(array($muayene_id, $randevu_id, $doktor_id, $randevu["hasta_id"], $muayene_tar, $notlar));

    $tani_id = sonraki_id($db, "Tanı", "tani_id");
    $taniEkle = $db->prepare("INSERT INTO `Tanı` (tani_id, muayene_id, tani_adi, aciklama) VALUES (?, ?, ?, ?)");
    $taniEkle->execute(array($tani_id, $muayene_id, $tani_adi, $tani_aciklama));

    if (count($receteIlaclari) > 0) {
        $recete_id = sonraki_id($db, "Reçeteler", "recete_id");
        $receteEkle = $db->prepare("INSERT INTO `Reçeteler` (recete_id, randevu_id, hasta_id, doktor_id, recete_tar) VALUES (?, ?, ?, ?, ?)");
        $receteEkle->execute(array($recete_id, $randevu_id, $randevu["hasta_id"], $doktor_id, $muayene_tar));

        $r_ilac_id = sonraki_id($db, "Reçete İlaçları", "r_ilac_id");
        $ilacEkle = $db->prepare("INSERT INTO `Reçete İlaçları` (r_ilac_id, ilac_id, recete_id, doz, talimat) VALUES (?, ?, ?, ?, ?)");
        foreach ($receteIlaclari as $satir) {
            $ilacEkle->execute(array($r_ilac_id, $satir[0], $recete_id, $satir[1], $satir[2]));
            $r_ilac_id++;
        }
    }

    $durumGuncelle = $db->prepare("UPDATE Randevular SET durum = 'Tamamlandı' WHERE randevu_id = ? AND doktor_id = ?");
    $durumGuncelle->execute(array($randevu_id, $doktor_id));

    $db->commit();
    session_mesaj("Muayene, tanı ve varsa reçete bilgileri tek işlemde kaydedildi. Randevu tamamlandı olarak güncellendi.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    session_hata("Muayene kaydedilirken hata oluştu: " . $e->getMessage());
}

yonlendir("../doktor_panel.php?panel=muayene-gecmisi");
?>
