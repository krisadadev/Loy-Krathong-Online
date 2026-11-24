-- ลอยกระทงออนไลน์ — โครงสร้างฐานข้อมูล
-- วิธีใช้: สร้างฐานข้อมูล (collation: utf8mb4_unicode_ci) แล้วเลือกฐานข้อมูลนั้น จากนั้น Import ไฟล์นี้
-- (หรือเอาเครื่องหมาย -- หน้า 2 บรรทัดล่างออก หากต้องการให้สร้างฐานข้อมูลให้เลย)

-- CREATE DATABASE IF NOT EXISTS `loy_krathong` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `loy_krathong`;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `loy_krathong` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `theme`      VARCHAR(16)  NOT NULL,
  `wish`       VARCHAR(500) NOT NULL DEFAULT '',
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_ip_created` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
