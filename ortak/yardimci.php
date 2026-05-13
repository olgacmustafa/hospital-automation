<?php
/* Ortak yardımcı fonksiyonlar - PHP 7.2 uyumlu */

date_default_timezone_set('Europe/Istanbul');

if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null) {
        return strtolower((string)$string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) {
        return $length === null ? substr((string)$string, $start) : substr((string)$string, $start, $length);
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || strpos((string)$haystack, (string)$needle) !== false;
    }
}

function e($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, 'UTF-8');
}

function yonlendir($adres) {
    header('Location: ' . $adres);
    exit;
}

function session_mesaj($mesaj) {
    $_SESSION['mesaj'] = $mesaj;
}

function session_hata($hata) {
    $_SESSION['hata'] = $hata;
}

function tablo_adi($tablo) {
    return '`' . str_replace('`', '``', $tablo) . '`';
}

function kolon($sutun) {
    return '`' . str_replace('`', '``', $sutun) . '`';
}

function tablo_var_mi($db, $tablo) {
    try {
        $sorgu = $db->prepare('SHOW TABLES LIKE ?');
        $sorgu->execute(array($tablo));
        return (bool)$sorgu->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function tablo_sutunlari($db, $tablo) {
    static $cache = array();
    if (isset($cache[$tablo])) {
        return $cache[$tablo];
    }

    try {
        $sorgu = $db->query('DESCRIBE ' . tablo_adi($tablo));
        $satirlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
        $sutunlar = array();
        foreach ($satirlar as $satir) {
            $sutunlar[] = $satir['Field'];
        }
        $cache[$tablo] = $sutunlar;
        return $sutunlar;
    } catch (PDOException $e) {
        $cache[$tablo] = array();
        return array();
    }
}

function sutun_var_mi($db, $tablo, $sutun) {
    return in_array($sutun, tablo_sutunlari($db, $tablo), true);
}

function ilk_var_olan_sutun($db, $tablo, $adaylar) {
    $sutunlar = tablo_sutunlari($db, $tablo);
    foreach ($adaylar as $aday) {
        if (in_array($aday, $sutunlar, true)) {
            return $aday;
        }
    }
    return null;
}

function sonraki_id($db, $tablo, $id_kolonu) {
    $sorgu = $db->query('SELECT COALESCE(MAX(' . kolon($id_kolonu) . '), 0) + 1 FROM ' . tablo_adi($tablo));
    return (int)$sorgu->fetchColumn();
}

function kisalt($metin, $limit) {
    $metin = trim((string)$metin);
    if ($limit <= 0) return $metin;
    if (function_exists('mb_strlen') && mb_strlen($metin, 'UTF-8') > $limit) {
        return mb_substr($metin, 0, $limit, 'UTF-8');
    }
    if (!function_exists('mb_strlen') && strlen($metin) > $limit) {
        return substr($metin, 0, $limit);
    }
    return $metin;
}

function tarih_yaz($tarih) {
    if (!$tarih) return '-';
    $zaman = strtotime($tarih);
    return $zaman ? date('d.m.Y', $zaman) : e($tarih);
}

function tarih_saat_yaz($tarih, $saat = '') {
    if (!$tarih) return '-';
    $tarihMetin = tarih_yaz($tarih);
    return $saat ? $tarihMetin . ' - ' . e($saat) : $tarihMetin;
}

function metin_kucult($metin) {
    $metin = (string)$metin;

    /*
      Türkçe karakterlerden dolayı özellikle "İptal" kelimesi bazı sunucularda
      mb_strtolower sonrası "i̇ptal" gibi farklı bir karakter dizisine dönebiliyor.
      Bu da str_contains($durum, "iptal") kontrollerinin kaçmasına sebep oluyor.
      Durum kontrollerinde önce Türkçe karakterleri sadeleştiriyoruz.
    */
    $metin = str_replace(
        array('İ', 'I', 'ı', 'Ş', 'ş', 'Ğ', 'ğ', 'Ü', 'ü', 'Ö', 'ö', 'Ç', 'ç'),
        array('i', 'i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'),
        $metin
    );

    return mb_strtolower($metin, 'UTF-8');
}

function randevu_durum_normalize($durum) {
    return metin_kucult($durum);
}

function randevu_iptal_mi($durum) {
    return str_contains(randevu_durum_normalize($durum), 'iptal');
}

function randevu_tamam_mi($durum) {
    return str_contains(randevu_durum_normalize($durum), 'tamam');
}

function randevu_bekliyor_mu($durum) {
    return str_contains(randevu_durum_normalize($durum), 'bek');
}

function randevu_islem_yapilabilir_mi($durum) {
    return !randevu_iptal_mi($durum) && !randevu_tamam_mi($durum);
}

function randevu_durum_class($durum) {
    if (randevu_tamam_mi($durum)) return 'status-done';
    if (randevu_iptal_mi($durum)) return 'status-cancel';
    if (str_contains(randevu_durum_normalize($durum), 'onay')) return 'status-ok';
    return 'status-wait';
}

function aktif_panel_ekle($adres, $panel) {
    return $adres . '?panel=' . urlencode($panel);
}
?>
