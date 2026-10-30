-- Production schema (structure only) of the shared ceskakruta DB, MariaDB 10.11 — source of truth, do not edit by hand.
-- Regenerate: import a production dump into a throwaway mariadb:10.11 container, then
--   mariadb-dump --no-data --skip-comments --skip-dump-date <db> | sed -E 's/ AUTO_INCREMENT=[0-9]+//'

/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `aa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aa` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actuality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `actuality` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `text_full` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actuality_newsletter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `actuality_newsletter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `actuality_id` int(11) unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_sent_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`,`actuality_id`),
  KEY `actuality_id` (`actuality_id`),
  CONSTRAINT `actuality_newsletter_ibfk_1` FOREIGN KEY (`actuality_id`) REFERENCES `actuality` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_alive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_alive` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_tp_id` int(11) unsigned DEFAULT NULL,
  `farma_rychvald` tinyint(1) unsigned DEFAULT 0,
  `farma_doubrava` tinyint(1) unsigned DEFAULT 0,
  `week_number` int(3) unsigned DEFAULT NULL,
  `year` int(4) unsigned DEFAULT 2014,
  `weight_from` float unsigned DEFAULT NULL,
  `weight_to` float unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_tp_id` (`product_tp_id`),
  CONSTRAINT `calendar_alive_ibfk_1` FOREIGN KEY (`product_tp_id`) REFERENCES `lst_product_tp` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_cold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_cold` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_tp_id` int(11) unsigned DEFAULT NULL,
  `week_number` int(3) unsigned DEFAULT NULL,
  `year` int(4) unsigned DEFAULT 2014,
  `weight_from` float unsigned DEFAULT NULL,
  `weight_to` float unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_tp_id` (`product_tp_id`),
  CONSTRAINT `calendar_cold_ibfk_1` FOREIGN KEY (`product_tp_id`) REFERENCES `lst_product_tp` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_dead`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_dead` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `farma_doubrava` tinyint(1) unsigned DEFAULT 0,
  `farma_rychvald` tinyint(1) unsigned DEFAULT 0,
  `week_number` int(2) unsigned DEFAULT NULL,
  `year` int(4) unsigned DEFAULT 2014,
  `week_age` int(2) DEFAULT NULL,
  `price_mix` varchar(20) DEFAULT NULL,
  `weight` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `price_man` varchar(20) DEFAULT NULL,
  `price_woman` varchar(20) DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(11) unsigned DEFAULT NULL,
  `code` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `text` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `usage_limit` int(11) unsigned DEFAULT NULL,
  `until_date` date DEFAULT NULL,
  `delivery_until_date` date DEFAULT NULL,
  `percent_value` int(10) unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lst_product_tp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lst_product_tp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lst_thumbnail_tp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lst_thumbnail_tp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `width` int(11) unsigned DEFAULT NULL,
  `height` int(11) unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `newsletter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `place_id` int(11) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `pay_by_card` tinyint(1) DEFAULT NULL,
  `delivery_street` varchar(255) DEFAULT NULL,
  `delivery_city` varchar(255) DEFAULT NULL,
  `delivery_postal_code` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `company_vat_id` varchar(255) DEFAULT NULL,
  `invoicing_street` varchar(255) DEFAULT NULL,
  `invoicing_city` varchar(255) DEFAULT NULL,
  `invoicing_postal_code` varchar(255) DEFAULT NULL,
  `note` text NOT NULL,
  `price_total` float unsigned NOT NULL,
  `pohoda_sync_dt` datetime DEFAULT NULL,
  `cool_balik_carrier_pickup_date` date DEFAULT NULL,
  `cool_balik_sync_dt` datetime DEFAULT NULL,
  `cool_balik_price` float unsigned DEFAULT NULL,
  `cool_balik_weight` float unsigned DEFAULT NULL,
  `cool_balik_box_count` int(10) unsigned DEFAULT NULL,
  `boxxi_sync_dt` datetime DEFAULT NULL,
  `boxxi_weight` float unsigned DEFAULT NULL,
  `boxxi_shipment_number` varchar(64) DEFAULT NULL,
  `source` text DEFAULT NULL,
  `coupon_id` int(11) unsigned DEFAULT NULL,
  `coupon_code` varchar(100) DEFAULT NULL,
  `coupon_percent_value` int(11) unsigned DEFAULT NULL,
  `price_total_before_discount` float DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `place_id` (`place_id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `date` (`date`),
  KEY `ins_dt` (`ins_dt`),
  CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `order_ibfk_3` FOREIGN KEY (`place_id`) REFERENCES `place` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `order_ibfk_4` FOREIGN KEY (`coupon_id`) REFERENCES `coupon` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `order_ibfk_5` FOREIGN KEY (`coupon_id`) REFERENCES `coupon` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `product_id` int(11) unsigned NOT NULL,
  `product_tp_id` int(11) unsigned DEFAULT NULL,
  `is_sliced` tinyint(1) unsigned DEFAULT NULL,
  `is_packed` tinyint(1) unsigned DEFAULT NULL,
  `amount` float NOT NULL,
  `price_per_unit` int(11) unsigned NOT NULL,
  `price_packing` int(11) unsigned NOT NULL,
  `price_total` int(11) unsigned NOT NULL,
  `weight_from` float unsigned DEFAULT NULL,
  `weight_to` float unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `product_tp_id` (`product_tp_id`),
  CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  CONSTRAINT `order_item_ibfk_3` FOREIGN KEY (`product_tp_id`) REFERENCES `lst_product_tp` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_token` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `token` text NOT NULL,
  `valid_until` datetime NOT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_reset_token_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `photo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `width` int(4) unsigned DEFAULT NULL,
  `height` int(4) unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `place`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `place` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `is_cool_balik` tinyint(1) unsigned DEFAULT NULL,
  `order` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `force_delivery_address` tinyint(1) unsigned NOT NULL,
  `force_packing` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `allow_only_wholesale` tinyint(1) DEFAULT 0,
  `allow_order_day_1_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_1_time` time DEFAULT '23:59:00',
  `allow_order_day_2_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_2_time` time DEFAULT '23:59:00',
  `allow_order_day_3_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_3_time` time DEFAULT '23:59:00',
  `allow_order_day_4_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_4_time` time DEFAULT '23:59:00',
  `allow_order_day_5_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_5_time` time DEFAULT '23:59:00',
  `allow_order_day_6_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_6_time` time DEFAULT '23:59:00',
  `allow_order_day_7_days_before` tinyint(2) unsigned DEFAULT NULL,
  `allow_order_day_7_time` time DEFAULT '23:59:00',
  `address_1` text DEFAULT NULL,
  `address_2` text DEFAULT NULL,
  `address_3` text DEFAULT NULL,
  `map_url` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `opening_hours` text DEFAULT NULL,
  `opening_hours_email` text DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `show_on_old_web` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `hide_in_admin` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `place_closed_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `place_closed_day` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `place_id` int(11) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `place_id` (`place_id`),
  CONSTRAINT `place_closed_day_ibfk_1` FOREIGN KEY (`place_id`) REFERENCES `place` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hide_on_old_web` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `order` int(11) unsigned DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `price` int(11) unsigned DEFAULT NULL,
  `half_of_product_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` int(10) unsigned DEFAULT NULL,
  `can_be_packed_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `can_be_sliced_flag` tinyint(1) unsigned DEFAULT 0,
  `price_pack` int(11) unsigned DEFAULT NULL,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `text_additional` text DEFAULT NULL,
  `force_packing` tinyint(4) NOT NULL DEFAULT 0,
  `packages_size` varchar(1000) NOT NULL DEFAULT '[]',
  `unit` varchar(10) NOT NULL COMMENT 'kg',
  `weight_per_unit` float NOT NULL DEFAULT 1,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `half_of_product_id` (`half_of_product_id`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`half_of_product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_place`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_place` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) unsigned NOT NULL,
  `place_id` int(11) unsigned DEFAULT NULL,
  `price` int(11) unsigned NOT NULL,
  `active_flag` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ins_dt` timestamp NULL DEFAULT NULL,
  `updt_dt` timestamp NULL DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `ins_process` varchar(200) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `upd_process` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `place_id` (`place_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_place_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  CONSTRAINT `product_place_ibfk_3` FOREIGN KEY (`place_id`) REFERENCES `place` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recipe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) unsigned DEFAULT NULL,
  `photo_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `photo_id` (`photo_id`),
  CONSTRAINT `recipe_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `recipe_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recipe_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_rating` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) unsigned DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `rating` int(11) unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`),
  CONSTRAINT `recipe_rating_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recurring_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_order` (
  `last_ordered_at` datetime DEFAULT NULL,
  `id` char(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recurring_order_extra_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_order_extra_item` (
  `id` char(36) NOT NULL COMMENT '(DC2Type:uuid)',
  `order_id` char(36) NOT NULL COMMENT '(DC2Type:uuid)',
  `product_id` int(11) NOT NULL,
  `packages` longtext NOT NULL COMMENT '(DC2Type:packages_amounts)',
  `other_package_size_amount` double NOT NULL,
  `is_sliced` tinyint(1) NOT NULL DEFAULT 0,
  `is_packed` tinyint(1) NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_RECURRING_ORDER_EXTRA_ORDER` (`order_id`),
  CONSTRAINT `FK_RECURRING_ORDER_EXTRA_ORDER` FOREIGN KEY (`order_id`) REFERENCES `recurring_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recurring_order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_order_item` (
  `id` char(36) NOT NULL,
  `product_id` int(11) NOT NULL,
  `packages` text NOT NULL,
  `other_package_size_amount` double NOT NULL,
  `order_id` char(36) NOT NULL,
  `note` varchar(255) NOT NULL,
  `is_sliced` tinyint(1) NOT NULL DEFAULT 0,
  `is_packed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E4E616A88D9F6D38` (`order_id`),
  CONSTRAINT `FK_E4E616A88D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `recurring_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recurring_order_skip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_order_skip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `skip_until` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_day` (`user_id`,`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sortiment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sortiment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned DEFAULT NULL,
  `order` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `sortiment_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `sortiment_category` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sortiment_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sortiment_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `thumbnail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `thumbnail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(11) unsigned DEFAULT NULL,
  `photo_id` int(11) unsigned DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `width` int(4) unsigned DEFAULT NULL,
  `height` int(4) unsigned DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  KEY `photo_id` (`photo_id`),
  CONSTRAINT `thumbnail_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `lst_thumbnail_tp` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `thumbnail_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `preferred_place_id` int(11) unsigned DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `company_vat_id` varchar(255) DEFAULT NULL,
  `delivery_name` varchar(255) DEFAULT NULL,
  `delivery_street` varchar(255) DEFAULT NULL,
  `delivery_city` varchar(255) DEFAULT NULL,
  `delivery_zip` varchar(255) DEFAULT NULL,
  `company_invoicing` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `invoicing_street` varchar(255) DEFAULT NULL,
  `invoicing_city` varchar(255) DEFAULT NULL,
  `invoicing_zip` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `allow_wholesale` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_ceskakruta` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `preferred_place_id` (`preferred_place_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`preferred_place_id`) REFERENCES `place` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_banned_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_banned_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `active_flag` int(11) NOT NULL DEFAULT 1,
  `del_flag` int(11) NOT NULL DEFAULT 0,
  `ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(11) unsigned DEFAULT NULL,
  `place` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `name` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `address` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `city` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zip` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `web` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ins_dt` datetime NOT NULL,
  `ins_process` varchar(255) DEFAULT NULL,
  `ins_user_id` varchar(100) DEFAULT NULL,
  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upd_process` varchar(255) DEFAULT NULL,
  `upd_user_id` varchar(100) DEFAULT NULL,
  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

