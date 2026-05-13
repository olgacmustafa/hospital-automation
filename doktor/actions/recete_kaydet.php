<?php
require_once "../includes/auth_kontrol.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    yonlendir("../doktor_panel.php");
}

$doktor_id = $_SESSION["doktor_id"];
$randevu_id = trim($_POST["randevu_id"] ?? "");
$recete_tar = trim($_POST["recete_tar"] ?? date("Y-m-d"));
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

    if ($ilac_id === "" && $doz === "" && $talimat === "") {
        continue;
    }

    if ($ilac_id === "" || $doz === "" || $talimat === "") {
        session_hata("Her ilaç satırında ilaç, doz ve talimat alanları birlikte doldurulmalıdır.");
        yonlendir("../doktor_panel.php?panel=recete-yaz");
    }

    $receteIlaclari[] = array($ilac_id, $doz, $talimat);
}

if ($randevu_id === "" || $recete_tar === "" || count($receteIlaclari) === 0) {
    session_hata("Reçete için randevu, tarih ve en az bir ilaç satırı zorunludur.");
    yonlendir("../doktor_panel.php?panel=recete-yaz");
}

try {
    $db->beginTransaction();
    $randevuSorgu = $db->prepare("SELECT * FROM Randevular WHERE randevu_id = ? AND doktor_id = ?");
    $randevuSorgu->execute(array($randevu_id, $doktor_id));
    $randevu = $randevuSorgu->fetch(PDO::FETCH_ASSOC);
    if (!$randevu) { throw new Exception("Randevu bulunamadı."); }

    $recete_id = sonraki_id($db, "Reçeteler", "recete_id");
    $receteEkle = $db->prepare("INSERT INTO `Reçeteler` (recete_id, randevu_id, hasta_id, doktor_id, recete_tar) VALUES (?, ?, ?, ?, ?)");
    $receteEkle->execute(array($recete_id, $randevu_id, $randevu["hasta_id"], $doktor_id, $recete_tar));

    $r_ilac_id = sonraki_id($db, "Reçete İlaçları", "r_ilac_id");
    $ilacEkle = $db->prepare("INSERT INTO `Reçete İlaçları` (r_ilac_id, ilac_id, recete_id, doz, talimat) VALUES (?, ?, ?, ?, ?)");
    foreach ($receteIlaclari as $satir) {
        $ilacEkle->execute(array($r_ilac_id, $satir[0], $recete_id, $satir[1], $satir[2]));
        $r_ilac_id++;
    }
    $db->commit();
    session_mesaj("Reçete başarıyla kaydedildi.");
} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    session_hata("Reçete kaydedilirken hata oluştu: " . $e->getMessage());
}

yonlendir("../doktor_panel.php?panel=recete-arsivi");
?>
