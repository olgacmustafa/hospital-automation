-- Hastane Otomasyonu Veritabanı Şeması
-- Bu dosya demo/kurulum amaçlıdır. Gerçek hasta/doktor verisi içermez.
-- Varsayılan demo şifreleri: 123456

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `hastane_otomasyonu`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;

USE `hastane_otomasyonu`;

DROP TABLE IF EXISTS `Reçete İlaçları`;
DROP TABLE IF EXISTS `Reçeteler`;
DROP TABLE IF EXISTS `Tanı`;
DROP TABLE IF EXISTS `Muayene`;
DROP TABLE IF EXISTS `Randevular`;
DROP TABLE IF EXISTS `İlaçlar`;
DROP TABLE IF EXISTS `Doktorlar`;
DROP TABLE IF EXISTS `Hastalar`;
DROP TABLE IF EXISTS `Poliklinikler`;
DROP TABLE IF EXISTS `Adminler`;

CREATE TABLE `Adminler` (
  `admin_id` INT NOT NULL AUTO_INCREMENT,
  `admin_ad` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `sifre_hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uk_admin_ad` (`admin_ad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Poliklinikler` (
  `poliklinik_id` INT NOT NULL AUTO_INCREMENT,
  `poliklinik_ad` VARCHAR(100) NOT NULL,
  `lokasyon` VARCHAR(100) DEFAULT NULL,
  `aciklama` TEXT DEFAULT NULL,
  PRIMARY KEY (`poliklinik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Hastalar` (
  `hasta_id` INT NOT NULL AUTO_INCREMENT,
  `tc_no` VARCHAR(11) NOT NULL,
  `sifre_hash` VARCHAR(255) NOT NULL,
  `isim` VARCHAR(100) NOT NULL,
  `dogum_tar` DATE NOT NULL,
  `cinsiyet` VARCHAR(20) NOT NULL,
  `tel_no` VARCHAR(11) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  PRIMARY KEY (`hasta_id`),
  UNIQUE KEY `uk_hasta_tc` (`tc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Doktorlar` (
  `doktor_id` INT NOT NULL AUTO_INCREMENT,
  `tc_no` VARCHAR(11) NOT NULL,
  `sifre_hash` VARCHAR(255) NOT NULL,
  `isim` VARCHAR(100) NOT NULL,
  `tel_no` VARCHAR(11) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `uzmanlık` VARCHAR(100) NOT NULL,
  `poliklinik_id` INT DEFAULT NULL,
  PRIMARY KEY (`doktor_id`),
  UNIQUE KEY `uk_doktor_tc` (`tc_no`),
  KEY `idx_doktor_poliklinik` (`poliklinik_id`),
  CONSTRAINT `fk_doktor_poliklinik`
    FOREIGN KEY (`poliklinik_id`) REFERENCES `Poliklinikler` (`poliklinik_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `İlaçlar` (
  `ilac_id` INT NOT NULL AUTO_INCREMENT,
  `ilac_ad` VARCHAR(50) NOT NULL,
  `İlac_turu` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`ilac_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Randevular` (
  `randevu_id` INT NOT NULL AUTO_INCREMENT,
  `hasta_id` INT NOT NULL,
  `doktor_id` INT NOT NULL,
  `poliklinik_id` INT NOT NULL,
  `randevu_tar` DATE NOT NULL,
  `randevu_saat` TIME NOT NULL,
  `durum` VARCHAR(30) NOT NULL DEFAULT 'Bekliyor',
  PRIMARY KEY (`randevu_id`),
  KEY `idx_randevu_hasta` (`hasta_id`),
  KEY `idx_randevu_doktor` (`doktor_id`),
  KEY `idx_randevu_poliklinik` (`poliklinik_id`),
  KEY `idx_randevu_tarih_saat` (`randevu_tar`, `randevu_saat`),
  CONSTRAINT `fk_randevu_hasta`
    FOREIGN KEY (`hasta_id`) REFERENCES `Hastalar` (`hasta_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_randevu_doktor`
    FOREIGN KEY (`doktor_id`) REFERENCES `Doktorlar` (`doktor_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_randevu_poliklinik`
    FOREIGN KEY (`poliklinik_id`) REFERENCES `Poliklinikler` (`poliklinik_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Muayene` (
  `muayene_id` INT NOT NULL AUTO_INCREMENT,
  `randevu_id` INT NOT NULL,
  `doktor_id` INT NOT NULL,
  `hasta_id` INT NOT NULL,
  `muayene_tar` DATE NOT NULL,
  `notlar` TEXT DEFAULT NULL,
  PRIMARY KEY (`muayene_id`),
  UNIQUE KEY `uk_muayene_randevu` (`randevu_id`),
  KEY `idx_muayene_doktor` (`doktor_id`),
  KEY `idx_muayene_hasta` (`hasta_id`),
  CONSTRAINT `fk_muayene_randevu`
    FOREIGN KEY (`randevu_id`) REFERENCES `Randevular` (`randevu_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_muayene_doktor`
    FOREIGN KEY (`doktor_id`) REFERENCES `Doktorlar` (`doktor_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_muayene_hasta`
    FOREIGN KEY (`hasta_id`) REFERENCES `Hastalar` (`hasta_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Tanı` (
  `tani_id` INT NOT NULL AUTO_INCREMENT,
  `muayene_id` INT NOT NULL,
  `tani_adi` VARCHAR(30) NOT NULL,
  `aciklama` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`tani_id`),
  KEY `idx_tani_muayene` (`muayene_id`),
  CONSTRAINT `fk_tani_muayene`
    FOREIGN KEY (`muayene_id`) REFERENCES `Muayene` (`muayene_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Reçeteler` (
  `recete_id` INT NOT NULL AUTO_INCREMENT,
  `randevu_id` INT NOT NULL,
  `hasta_id` INT NOT NULL,
  `doktor_id` INT NOT NULL,
  `recete_tar` DATE NOT NULL,
  PRIMARY KEY (`recete_id`),
  KEY `idx_recete_randevu` (`randevu_id`),
  KEY `idx_recete_hasta` (`hasta_id`),
  KEY `idx_recete_doktor` (`doktor_id`),
  CONSTRAINT `fk_recete_randevu`
    FOREIGN KEY (`randevu_id`) REFERENCES `Randevular` (`randevu_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recete_hasta`
    FOREIGN KEY (`hasta_id`) REFERENCES `Hastalar` (`hasta_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recete_doktor`
    FOREIGN KEY (`doktor_id`) REFERENCES `Doktorlar` (`doktor_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE `Reçete İlaçları` (
  `r_ilac_id` INT NOT NULL AUTO_INCREMENT,
  `ilac_id` INT NOT NULL,
  `recete_id` INT NOT NULL,
  `doz` VARCHAR(10) NOT NULL,
  `talimat` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`r_ilac_id`),
  KEY `idx_recete_ilac_ilac` (`ilac_id`),
  KEY `idx_recete_ilac_recete` (`recete_id`),
  CONSTRAINT `fk_recete_ilac_ilac`
    FOREIGN KEY (`ilac_id`) REFERENCES `İlaçlar` (`ilac_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_recete_ilac_recete`
    FOREIGN KEY (`recete_id`) REFERENCES `Reçeteler` (`recete_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Demo kayıtlar
INSERT INTO `Adminler` (`admin_id`, `admin_ad`, `sifre_hash`) VALUES
(1, 'admin', '$2y$12$VhHMsdlFa.XImekBFF61rOHGT90ENUdHtUZN4c/RldMIZqC.ZFSJ2');

INSERT INTO `Poliklinikler` (`poliklinik_id`, `poliklinik_ad`, `lokasyon`, `aciklama`) VALUES
(1, 'Dahiliye', 'A Blok / Kat 1', 'Genel iç hastalıkları polikliniği'),
(2, 'Kardiyoloji', 'B Blok / Kat 2', 'Kalp ve damar hastalıkları polikliniği');

INSERT INTO `Hastalar` (`hasta_id`, `tc_no`, `sifre_hash`, `isim`, `dogum_tar`, `cinsiyet`, `tel_no`, `email`) VALUES
(1, '11111111111', '$2y$12$VhHMsdlFa.XImekBFF61rOHGT90ENUdHtUZN4c/RldMIZqC.ZFSJ2', 'Demo Hasta', '2000-01-01', 'Erkek', '05555555555', 'demo.hasta@example.com');

INSERT INTO `Doktorlar` (`doktor_id`, `tc_no`, `sifre_hash`, `isim`, `tel_no`, `email`, `uzmanlık`, `poliklinik_id`) VALUES
(1, '22222222222', '$2y$12$VhHMsdlFa.XImekBFF61rOHGT90ENUdHtUZN4c/RldMIZqC.ZFSJ2', 'Demo Doktor', '05555555556', 'demo.doktor@example.com', 'Dahiliye Uzmanı', 1);

INSERT INTO `İlaçlar` (`ilac_id`, `ilac_ad`, `İlac_turu`) VALUES
(1, 'Parasetamol', 'Tablet'),
(2, 'Ateş Düşürücü Şurup', 'Şurup');

-- Demo giriş bilgileri:
-- Admin:  admin / 123456
-- Hasta:  11111111111 / 123456
-- Doktor: 22222222222 / 123456
