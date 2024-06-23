-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.1.72-community - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for sewa_in
DROP DATABASE IF EXISTS `sewa_in`;
CREATE DATABASE IF NOT EXISTS `sewa_in` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `sewa_in`;

-- Dumping structure for table sewa_in.admin
DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.

-- Dumping structure for table sewa_in.barang
DROP TABLE IF EXISTS `barang`;
CREATE TABLE IF NOT EXISTS `barang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idUser` int(10) unsigned NOT NULL,
  `namaBarang` varchar(50) DEFAULT NULL,
  `tarifHarian` int(11) DEFAULT NULL,
  `fotoBarang` varchar(50) DEFAULT NULL,
  `status` enum('ready','booking','onGuest','noRent') NOT NULL DEFAULT 'ready',
  PRIMARY KEY (`id`),
  KEY `idUser` (`idUser`),
  CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`idUser`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.

-- Dumping structure for table sewa_in.transaksi
DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idBarang` int(10) unsigned NOT NULL,
  `idGuest` int(10) unsigned NOT NULL,
  `idHost` int(10) unsigned NOT NULL,
  `tglPinjam` date NOT NULL,
  `tglKembali` date NOT NULL,
  `tarif` varchar(15) NOT NULL,
  `status` enum('bookingReject','bookingConfirm','booking','onGuest','dikembalikan','selesai') DEFAULT 'booking',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaksi_ibfk_1` (`idBarang`),
  KEY `transaksi_ibfk_2` (`idGuest`),
  KEY `transaksi_ibfk_3` (`idHost`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`idBarang`) REFERENCES `barang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`idGuest`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`idHost`) REFERENCES `barang` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.

-- Dumping structure for table sewa_in.user
DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(30) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `namaLengkap` varchar(50) NOT NULL,
  `kota` varchar(50) NOT NULL,
  `alamat` text,
  `noTelf` varchar(18) DEFAULT NULL,
  `fotoProfil` varchar(50) DEFAULT NULL,
  `fotoKTP` varchar(50) DEFAULT NULL,
  `fotoSKTP` varchar(50) DEFAULT NULL,
  `status` enum('verifikasi','unverifikasi') NOT NULL DEFAULT 'unverifikasi',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
