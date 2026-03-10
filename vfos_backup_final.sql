-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: vfos
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('bank','ewallet','cash','investment','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` enum('afin','pacar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `initial_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `icon_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,1,'BCA DEBIT','bank','afin',2068346.00,1030346.00,'account_icons/JR61S9erOWVGFXcO72b0ArGGxkT0jHfNG8YNH9JC.png',1,'2026-02-17 04:22:18','2026-03-07 06:46:33'),(2,1,'Cash','cash','afin',245000.00,331500.00,'account_icons/jkZ70y1WLaqKYYnt9TFDckuYq6EtTIhXDNBf2ob2.png',1,'2026-02-17 04:22:19','2026-03-07 07:07:08'),(3,1,'Gopay','ewallet','afin',36530.00,36530.00,'account_icons/METGHcKbr9jsi9WNu28j5q3VXRoqOXn5qTwoXh5y.jpg',1,'2026-02-17 04:22:19','2026-03-02 10:38:07'),(4,1,'MANDIRI PAYROLL','bank','afin',5580710.00,17080710.00,'account_icons/OMuQheEHAes6lQg1hJK5gdzLE0POL9925xXiaobW.png',1,'2026-03-02 09:54:43','2026-03-03 08:57:33'),(5,1,'MANDIRI KREDIT','bank','afin',14409978.00,14409978.00,'account_icons/5n682lnHWWgktps5FqT42BnNz6LcDqlQ43u6az6V.png',1,'2026-03-02 09:55:49','2026-03-02 10:38:06'),(6,1,'MANDIRI SIMUDA','bank','afin',3215675.00,3215675.00,'account_icons/qfNy2mJXxXXBNSmKgtd5oizVfQJhXWVdaUuad996.jpg',1,'2026-03-02 10:00:10','2026-03-02 10:38:06'),(7,1,'SHOPEE PAY','ewallet','afin',66231.00,66231.00,'account_icons/0Yvg53Hx0X5lIcxkielgoHOrGEOq4XBeOy0SPNQm.png',1,'2026-03-02 10:02:56','2026-03-02 10:38:06'),(8,1,'BCA SEKURITAS','investment','afin',3970302.00,3585102.00,'account_icons/BDnqAa5TAMEheu418A5pwT3fI4sXa4TJgzqwc03M.png',1,'2026-03-02 10:07:16','2026-03-02 17:50:02'),(9,1,'DANA','ewallet','afin',13384.00,13384.00,'account_icons/3TBhecIaTcubz2UVoagIw0B2wzPOpchLwu5TfU4N.jpg',1,'2026-03-02 10:11:30','2026-03-02 10:38:07'),(10,1,'BANK JAGO','bank','afin',308959.00,308959.00,'account_icons/90wURZ2xoB6Wa4zA9GG3jW535F45fTVboMhQpjAS.png',1,'2026-03-02 10:13:20','2026-03-02 10:38:06'),(11,1,'PAYPAL','bank','afin',0.00,0.00,'account_icons/mVTYRsbbW1vbM5gbtjRkbEDBdFZpbRDXDnYRsrqx.png',1,'2026-03-02 10:16:20','2026-03-02 10:29:07'),(12,1,'CASH 2','cash','pacar',0.00,385200.00,'account_icons/JBFL35SAMKj2TnDNwerPSR1psVYfW1XUS4JHB6pj.png',1,'2026-03-02 17:36:10','2026-03-02 17:50:02');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `current_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assets_user_id_foreign` (`user_id`),
  CONSTRAINT `assets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (1,1,'Rumah 11 x 50 m','Rumah didesa Gambirono, Jember','Real Estate',163000000.00,200000000.00,'2026-03-01 18:45:18','2026-03-01 18:45:18'),(2,1,'Tanah','Tanah sawah 1/2 Hektar','Real Estate',40000000.00,40000000.00,'2026-03-01 18:47:03','2026-03-01 18:47:03'),(3,1,'Motor Nmax','Nmax generasi ke 2 tahun 2017','Vehicle',23000000.00,18000000.00,'2026-03-01 18:49:49','2026-03-01 18:52:38'),(4,1,'Laptop','merk Advan Workplus','Electronics',7036600.00,5000000.00,'2026-03-01 18:51:29','2026-03-01 18:52:27'),(5,1,'Iphone','Iphone 13','Electronics',9100000.00,7000000.00,'2026-03-01 18:52:16','2026-03-01 18:52:16');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `subcategory_id` bigint unsigned DEFAULT NULL,
  `month` tinyint NOT NULL,
  `year` int NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budgets_category_id_foreign` (`category_id`),
  KEY `budgets_subcategory_id_foreign` (`subcategory_id`),
  KEY `budgets_user_id_foreign` (`user_id`),
  CONSTRAINT `budgets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `budgets_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES (1,1,4,NULL,2,2026,1000000.00,'2026-02-26 09:42:31','2026-02-26 09:42:31'),(2,1,4,11,2,2026,500000.00,'2026-02-26 09:42:31','2026-02-26 09:42:31'),(3,1,4,11,3,2026,1000000.00,'2026-03-03 08:48:47','2026-03-03 08:48:47'),(4,1,4,13,3,2026,150000.00,'2026-03-03 08:49:13','2026-03-03 08:49:13'),(5,1,4,12,3,2026,100000.00,'2026-03-03 08:49:36','2026-03-03 08:49:36'),(6,1,5,41,3,2026,350000.00,'2026-03-03 09:10:26','2026-03-03 09:10:26'),(7,1,5,43,3,2026,40000.00,'2026-03-03 09:10:56','2026-03-03 09:10:56'),(8,1,6,19,3,2026,400000.00,'2026-03-03 09:11:11','2026-03-03 09:11:11'),(9,1,10,32,3,2026,400000.00,'2026-03-03 09:12:57','2026-03-03 09:12:57');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_user_id_foreign` (`user_id`),
  CONSTRAINT `categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,1,'Income','income',1,'2026-02-17 04:22:19','2026-02-17 04:22:19'),(4,1,'makan','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(5,1,'bill','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(6,1,'transportasi','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(7,1,'hiburan','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(8,1,'kesehatan','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(9,1,'belanja','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(10,1,'travel','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(11,1,'investasi','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(12,1,'education','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(13,1,'donasi','expense',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(14,1,'gaji','income',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(15,1,'pemberian orang tua','income',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(16,1,'bisnis','income',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(17,1,'hutang','income',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(18,1,'menjual barang','income',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(19,1,'hadiah','income',1,'2026-02-26 09:26:36','2026-02-26 09:26:36');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `debts`
--

DROP TABLE IF EXISTS `debts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `remaining_amount` decimal(15,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('active','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `debts_user_id_foreign` (`user_id`),
  CONSTRAINT `debts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debts`
--

LOCK TABLES `debts` WRITE;
/*!40000 ALTER TABLE `debts` DISABLE KEYS */;
/*!40000 ALTER TABLE `debts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investments`
--

DROP TABLE IF EXISTS `investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `investments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ticker` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scraping_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `price_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `quantity` decimal(15,4) NOT NULL,
  `average_cost` decimal(15,2) NOT NULL,
  `current_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investments_user_id_foreign` (`user_id`),
  CONSTRAINT `investments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investments`
--

LOCK TABLES `investments` WRITE;
/*!40000 ALTER TABLE `investments` DISABLE KEYS */;
INSERT INTO `investments` VALUES (1,1,'Bank Central Asia','BBCA','Stock',NULL,'IDR','unit',800.0000,6500.00,7000.00,'2026-03-01 01:11:09','2026-03-07 06:44:41'),(2,1,'Bank Rakyat Indonesia','BBRI','Stock',NULL,'IDR','unit',1100.0000,3100.00,3670.00,'2026-03-01 01:11:09','2026-03-07 06:44:42'),(3,1,'GoTo Gojek Tokopedia','GOTO','Stock',NULL,'IDR','unit',3300.0000,50.00,56.00,'2026-03-01 01:11:09','2026-03-07 06:44:42'),(5,1,'EMAS','GC=F','Commodity',NULL,'USD','gram',1.1214,620000.00,2809548.49,'2026-03-01 01:28:58','2026-03-07 06:44:46'),(6,1,'Sucorinvest Money Market Fund','SUCOR-MMF','Mutual Fund','https://pusatdata.kontan.co.id/reksadana/produk/1241/Sucorinvest-Money-Market-Fund','IDR','unit',2726.3480,1921.99,1950.31,'2026-03-01 01:44:30','2026-03-07 06:44:47'),(7,1,'TRIM Kas 2 Kelas A','TRIM-KAS','Mutual Fund','https://pusatdata.kontan.co.id/reksadana/produk/16585/Reksa-Dana-TRIM-Kas-2-Kelas-A','IDR','unit',955.5314,1967.49,2011.83,'2026-03-01 01:44:30','2026-03-07 06:44:47'),(8,1,'ABF Indonesia Bond Index Fund','ABF-BOND','Mutual Fund','https://pusatdata.kontan.co.id/reksadana/produk/1221/ABF-Indonesia-Bond-Index-Fund','IDR','unit',109.5627,58961.67,59975.21,'2026-03-01 02:33:39','2026-03-01 02:48:10'),(9,1,'Grow SRI KEHATI Kelas O','GROW-SRI','Mutual Fund','https://pusatdata.kontan.co.id/reksadana/produk/1164/Grow-SRI-KEHATI-Kelas-O','IDR','unit',982.3923,1038.28,1091.65,'2026-03-01 02:33:39','2026-03-01 02:48:10'),(10,1,'BRI Indeks Syariah','BRI-SYAR','Mutual Fund','https://pusatdata.kontan.co.id/reksadana/produk/1404/BRI-Indeks-Syariah','IDR','unit',165.2981,2419.87,2555.55,'2026-03-01 02:33:39','2026-03-01 02:48:10');
/*!40000 ALTER TABLE `investments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_02_17_110756_create_accounts_table',1),(5,'2026_02_17_110757_create_categories_table',1),(6,'2026_02_17_110758_create_subcategories_table',1),(7,'2026_02_17_110758_create_transactions_table',1),(8,'2026_02_17_110759_create_transaction_items_table',1),(9,'2026_02_17_110800_create_budgets_table',1),(10,'2026_02_17_110800_create_debts_table',1),(11,'2026_02_17_110801_create_receivables_table',1),(12,'2026_02_17_111157_create_personal_access_tokens_table',1),(13,'2026_02_24_161926_create_investments_table',1),(14,'2026_02_24_163007_create_assets_table',1),(15,'2026_02_26_163837_add_subcategory_id_to_budgets_table',1),(16,'2026_02_26_170346_add_icon_path_to_accounts_table',1),(17,'2026_03_01_094730_add_scraping_url_to_investments_table',1),(18,'2026_03_01_160000_update_investments_table_precision',1),(19,'2026_03_02_131300_update_trim_kas_2_scraping_url',1),(20,'2026_03_03_000001_add_currency_to_investments_table',1),(21,'2026_03_03_100000_fix_account_fk_on_transactions',2),(22,'2026_03_02_172314_add_balance_to_accounts_table',3),(23,'2026_03_03_004433_add_to_account_id_to_transactions_table',4),(24,'2026_03_03_221700_add_user_id_to_accounts_table',5),(25,'2026_03_03_221701_add_user_id_to_categories_table',5),(26,'2026_03_03_221702_add_user_id_to_budgets_table',5),(27,'2026_03_03_221703_add_user_id_to_debts_table',5),(28,'2026_03_03_221704_add_user_id_to_receivables_table',5),(29,'2026_03_03_221705_add_user_id_to_investments_table',5),(30,'2026_03_03_221706_add_user_id_to_assets_table',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receivables`
--

DROP TABLE IF EXISTS `receivables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receivables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `remaining_amount` decimal(15,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('active','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receivables_user_id_foreign` (`user_id`),
  CONSTRAINT `receivables_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receivables`
--

LOCK TABLES `receivables` WRITE;
/*!40000 ALTER TABLE `receivables` DISABLE KEYS */;
INSERT INTO `receivables` VALUES (1,1,'Bayu Pratama',500000.00,500000.00,'2026-03-06','active','bayar setelah gajian','2026-03-02 08:56:36','2026-03-02 08:56:36'),(2,1,'Bagus Suryanandika',1500000.00,1500000.00,'2026-09-01','active','pinjam teman','2026-03-02 09:06:38','2026-03-02 09:06:38'),(3,1,'Mama',15000000.00,15000000.00,'2027-01-01','active','pinjam beli mobil','2026-03-02 09:09:12','2026-03-02 09:09:12'),(4,1,'Faisal',150000.00,0.00,'2026-03-07','active','uang makan ikan bakar','2026-03-05 22:39:19','2026-03-07 06:43:27'),(5,1,'fandik',500000.00,100000.00,'2026-03-09','active','sisa pembayaran','2026-03-05 22:39:50','2026-03-05 22:39:50');
/*!40000 ALTER TABLE `receivables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategories`
--

DROP TABLE IF EXISTS `subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcategories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subcategories_category_id_foreign` (`category_id`),
  CONSTRAINT `subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategories`
--

LOCK TABLES `subcategories` WRITE;
/*!40000 ALTER TABLE `subcategories` DISABLE KEYS */;
INSERT INTO `subcategories` VALUES (1,1,'Salary',1,'2026-02-17 04:22:19','2026-02-17 04:22:19'),(2,1,'Bonus',1,'2026-02-17 04:22:19','2026-02-17 04:22:19'),(3,1,'Gift',1,'2026-02-17 04:22:19','2026-02-17 04:22:19'),(11,4,'makanan',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(12,4,'cemilan',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(13,4,'cafe',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(14,5,'listrik',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(15,5,'gas',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(16,5,'internet',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(17,5,'air',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(18,6,'perawatan',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(19,6,'bensin',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(20,6,'parkir',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(21,6,'umum',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(22,7,'game',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(23,7,'film',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(24,7,'konser',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(25,8,'dokter',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(26,8,'personal care',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(27,8,'obat',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(28,8,'olahraga',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(29,9,'aksesoris',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(30,9,'baju',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(31,9,'elektronik',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(32,10,'tiket',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(33,10,'kebutuhan',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(34,11,'emas',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(35,11,'reksadana',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(36,12,'buku',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(37,12,'fotokopi',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(38,13,'amal',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(39,13,'pernikahan',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(40,13,'pemakaman',1,'2026-02-26 09:26:36','2026-02-26 09:26:36'),(41,5,'Sewa',1,'2026-03-03 08:53:12','2026-03-03 08:53:12'),(42,5,'laundry',1,'2026-03-03 09:01:41','2026-03-03 09:01:41'),(43,5,'Air minum',1,'2026-03-03 09:02:52','2026-03-03 09:03:14'),(44,5,'iuran',1,'2026-03-03 09:03:46','2026-03-03 09:03:46'),(45,6,'pajak',1,'2026-03-03 09:04:09','2026-03-03 09:04:09'),(46,9,'hadiah',1,'2026-03-03 09:04:42','2026-03-03 09:04:42'),(47,9,'utilitas',1,'2026-03-03 09:05:11','2026-03-03 09:05:11'),(48,11,'saham',1,'2026-03-03 09:05:51','2026-03-03 09:05:51'),(49,11,'Crypto',1,'2026-03-03 09:06:11','2026-03-03 09:06:11'),(50,11,'lain',1,'2026-03-03 09:06:31','2026-03-03 09:06:31'),(51,12,'Seminar',1,'2026-03-03 09:06:51','2026-03-03 09:06:51'),(52,12,'pelatihan',1,'2026-03-03 09:07:02','2026-03-03 09:07:02');
/*!40000 ALTER TABLE `subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_items`
--

DROP TABLE IF EXISTS `transaction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `subcategory_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_items_transaction_id_foreign` (`transaction_id`),
  KEY `transaction_items_category_id_foreign` (`category_id`),
  KEY `transaction_items_subcategory_id_foreign` (`subcategory_id`),
  CONSTRAINT `transaction_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `transaction_items_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`),
  CONSTRAINT `transaction_items_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_items`
--

LOCK TABLES `transaction_items` WRITE;
/*!40000 ALTER TABLE `transaction_items` DISABLE KEYS */;
INSERT INTO `transaction_items` VALUES (1,1,6,19,'bensin',40000.00,'2026-03-01 13:47:11','2026-03-01 13:47:11'),(2,2,4,11,'buka puasa nasi padang porong',63000.00,'2026-03-01 13:48:03','2026-03-01 13:48:03'),(4,4,4,11,'bukber dengan fajar',74000.00,'2026-03-02 06:11:20','2026-03-02 06:11:20'),(5,5,4,11,'makan pecel',12000.00,'2026-03-02 17:25:54','2026-03-02 17:25:54'),(8,6,4,12,'beli kopi',10000.00,'2026-03-02 17:27:34','2026-03-02 17:27:34'),(9,7,4,11,'nasi Lodeh',17000.00,'2026-03-02 17:27:42','2026-03-02 17:27:42'),(11,9,19,NULL,NULL,385200.00,'2026-03-02 17:49:25','2026-03-02 17:49:25'),(12,10,1,1,'Gaji Maret',11500000.00,'2026-03-03 08:57:33','2026-03-03 08:57:33'),(14,11,4,11,'buka puasa madura',37000.00,'2026-03-03 09:08:22','2026-03-03 09:08:22'),(15,12,4,12,'pisang keju',15000.00,'2026-03-03 09:08:52','2026-03-03 09:08:52'),(16,3,5,41,'KOST maret',350000.00,'2026-03-03 09:15:16','2026-03-03 09:15:16'),(17,13,5,43,'aqua kost',20000.00,'2026-03-03 09:15:38','2026-03-03 09:15:38'),(18,14,4,11,'makan sahur',12000.00,'2026-03-04 13:52:44','2026-03-04 13:52:44'),(19,15,4,11,'buka puasa',30000.00,'2026-03-04 13:53:19','2026-03-04 13:53:19'),(20,16,4,12,'jajan jadul',5000.00,'2026-03-04 13:53:45','2026-03-04 13:53:45'),(22,17,4,11,'makan sahur',11000.00,'2026-03-05 22:32:12','2026-03-05 22:32:12'),(23,18,8,26,'pijat ulil',10000.00,'2026-03-05 22:33:24','2026-03-05 22:33:24'),(24,19,4,13,'makan dilippo',79000.00,'2026-03-05 22:34:25','2026-03-05 22:34:25'),(25,20,4,12,'es krim',17500.00,'2026-03-05 22:35:05','2026-03-05 22:35:05'),(26,21,6,19,'bensin',40000.00,'2026-03-05 22:35:38','2026-03-05 22:35:38'),(27,22,4,12,'risoles',10000.00,'2026-03-05 22:36:01','2026-03-05 22:36:01'),(28,23,19,NULL,'atm ngoro indomaret',200000.00,'2026-03-05 22:38:24','2026-03-05 22:38:24'),(29,24,4,12,'nimz',38000.00,'2026-03-07 06:40:41','2026-03-07 06:40:41'),(30,25,19,NULL,'atm indomaret',300000.00,'2026-03-07 06:46:33','2026-03-07 06:46:33'),(31,26,4,11,'makan buka',16000.00,'2026-03-07 07:05:59','2026-03-07 07:05:59'),(32,27,4,11,'belanja indomaret',20000.00,'2026-03-07 07:06:36','2026-03-07 07:06:36'),(33,28,4,11,'susu indomaret',25000.00,'2026-03-07 07:07:08','2026-03-07 07:07:08');
/*!40000 ALTER TABLE `transaction_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint unsigned DEFAULT NULL,
  `to_account_id` bigint unsigned DEFAULT NULL,
  `type` enum('income','expense','transfer','withdrawal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_account_id_foreign` (`account_id`),
  KEY `transactions_to_account_id_foreign` (`to_account_id`),
  CONSTRAINT `transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_to_account_id_foreign` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,2,NULL,'expense','2026-03-01',40000.00,NULL,'2026-03-01 13:47:11','2026-03-01 13:47:11'),(2,2,NULL,'expense','2026-03-01',63000.00,NULL,'2026-03-01 13:48:03','2026-03-01 13:48:03'),(3,1,NULL,'expense','2026-03-02',350000.00,NULL,'2026-03-02 06:10:57','2026-03-03 09:15:16'),(4,1,NULL,'expense','2026-03-02',74000.00,NULL,'2026-03-02 06:11:20','2026-03-02 09:48:57'),(5,2,NULL,'expense','2026-03-02',12000.00,NULL,'2026-03-02 17:25:54','2026-03-02 17:25:54'),(6,2,NULL,'expense','2026-03-02',10000.00,NULL,'2026-03-02 17:26:26','2026-03-02 17:27:34'),(7,2,NULL,'expense','2026-03-02',17000.00,NULL,'2026-03-02 17:27:20','2026-03-02 17:27:42'),(9,8,12,'transfer','2026-03-03',385200.00,'pindah dana','2026-03-02 17:49:25','2026-03-02 17:49:25'),(10,4,NULL,'income','2026-03-05',11500000.00,NULL,'2026-03-03 08:57:33','2026-03-03 08:57:33'),(11,2,NULL,'expense','2026-03-03',37000.00,NULL,'2026-03-03 09:08:22','2026-03-03 09:08:22'),(12,2,NULL,'expense','2026-03-03',15000.00,NULL,'2026-03-03 09:08:52','2026-03-03 09:08:52'),(13,1,NULL,'expense','2026-03-03',20000.00,NULL,'2026-03-03 09:15:38','2026-03-03 09:15:38'),(14,2,NULL,'expense','2026-03-05',12000.00,NULL,'2026-03-04 13:52:44','2026-03-04 13:52:44'),(15,2,NULL,'expense','2026-03-04',30000.00,NULL,'2026-03-04 13:53:19','2026-03-04 13:53:19'),(16,1,NULL,'expense','2026-03-04',5000.00,NULL,'2026-03-04 13:53:45','2026-03-04 13:53:45'),(17,2,NULL,'expense','2026-03-06',11000.00,NULL,'2026-03-05 22:32:00','2026-03-05 22:32:12'),(18,1,NULL,'expense','2026-03-05',10000.00,NULL,'2026-03-05 22:33:23','2026-03-05 22:33:23'),(19,1,NULL,'expense','2026-03-05',79000.00,NULL,'2026-03-05 22:34:25','2026-03-05 22:34:25'),(20,2,NULL,'expense','2026-03-05',17500.00,NULL,'2026-03-05 22:35:05','2026-03-05 22:35:05'),(21,2,NULL,'expense','2026-03-05',40000.00,NULL,'2026-03-05 22:35:38','2026-03-05 22:35:38'),(22,2,NULL,'expense','2026-03-06',10000.00,NULL,'2026-03-05 22:36:01','2026-03-05 22:36:01'),(23,1,2,'transfer','2026-03-06',200000.00,NULL,'2026-03-05 22:38:24','2026-03-05 22:38:24'),(24,2,NULL,'expense','2026-03-07',38000.00,NULL,'2026-03-07 06:40:41','2026-03-07 06:40:41'),(25,1,2,'transfer','2026-03-07',300000.00,NULL,'2026-03-07 06:46:33','2026-03-07 06:46:33'),(26,2,NULL,'expense','2026-03-07',16000.00,NULL,'2026-03-07 07:05:59','2026-03-07 07:05:59'),(27,2,NULL,'expense','2026-03-07',20000.00,NULL,'2026-03-07 07:06:36','2026-03-07 07:06:36'),(28,2,NULL,'expense','2026-03-05',25000.00,NULL,'2026-03-07 07:07:08','2026-03-07 07:07:08');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Afini Fathurrorzi','afini.fathurrozi@gmail.com','2026-03-03 08:27:29','$2y$12$dyCRpvRnW4NkTpMbf70kIOAL1TwtxDNMAHoxXwrVFRNicm/bQfP5i',NULL,'2026-03-03 08:27:29','2026-03-03 08:29:50'),(2,'Alifia Ninulil','alifianinulil@gmail.com','2026-03-03 08:27:29','$2y$12$GvGX96KVh2Isu.Npm5irbepqvLRomzFCVeQHrGR0rT6zdIc9yu9eC',NULL,'2026-03-03 08:27:29','2026-03-03 08:27:29');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-08  0:09:30
