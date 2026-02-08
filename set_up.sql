-- 0. DATABASE CREATION
-- This ensures the environment is ready for the ERP tables
CREATE DATABASE IF NOT EXISTS `erp_commercial` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `erp_commercial`;

-- 1. USERS & PERMISSIONS
-- Defines roles like 'Admin' and 'Operator' [cite: 8]
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `roles` (`id`, `name`) VALUES (1, 'Admin'), (2, 'Operator');

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB;

-- 2. PARTNERS (Clients & Suppliers) 
-- Centralizes management to eliminate data duplication [cite: 2]
CREATE TABLE `partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` ENUM('Client', 'Supplier') NOT NULL, 
  `name` varchar(100) NOT NULL,
  `email` varchar(100),
  `tax_id` varchar(20),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- 3. PRODUCTS (The Catalog) 
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL UNIQUE, 
  `name` varchar(100) NOT NULL,
  `price_sell` DECIMAL(10,2) NOT NULL,
  `price_buy` DECIMAL(10,2) NOT NULL,
  `min_stock` int(11) DEFAULT 10, -- Triggers low stock alerts [cite: 3]
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- 4. WAREHOUSES
-- Configures initial storage locations 
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `warehouses` (`name`) VALUES ('Main Warehouse'), ('Production Floor');

-- 5. STOCK LOTS (The Inventory Logic) 
-- Manages batches and expiration dates for quality control [cite: 4, 11]
CREATE TABLE `stock_lots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `lot_number` varchar(50) NOT NULL,
  `expiration_date` DATE, 
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB;