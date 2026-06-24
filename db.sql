-- =============================================================================
-- WB API Test Project — схема MySQL
-- Источник полей: Statistics API Wildberries (snake_case)
-- https://github.com/cy322666/wb-api
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `wb_api_test`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `wb_api_test`;

-- ---------------------------------------------------------------------------
-- companies — компании
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `account_sync_states`;
DROP TABLE IF EXISTS `account_tokens`;
DROP TABLE IF EXISTS `api_service_token_types`;
DROP TABLE IF EXISTS `stocks`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `incomes`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `token_types`;
DROP TABLE IF EXISTS `api_services`;
DROP TABLE IF EXISTS `companies`;

CREATE TABLE `companies` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255)    NOT NULL,
    `created_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_companies_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- api_services — внешние API-сервисы
-- ---------------------------------------------------------------------------
CREATE TABLE `api_services` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(64)     NOT NULL,
    `name`       VARCHAR(255)    NOT NULL,
    `base_url`   VARCHAR(512)    NOT NULL,
    `created_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_api_services_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- token_types — типы токенов (bearer, api_key, login_password, …)
-- ---------------------------------------------------------------------------
CREATE TABLE `token_types` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(64)     NOT NULL,
    `name`       VARCHAR(255)    NOT NULL,
    `created_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token_types_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- api_service_token_types — допустимые типы токенов для API-сервиса
-- ---------------------------------------------------------------------------
CREATE TABLE `api_service_token_types` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `api_service_id`  BIGINT UNSIGNED NOT NULL,
    `token_type_id`   BIGINT UNSIGNED NOT NULL,
    `created_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_api_service_token_type` (`api_service_id`, `token_type_id`),
    CONSTRAINT `fk_astt_api_service` FOREIGN KEY (`api_service_id`) REFERENCES `api_services` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_astt_token_type` FOREIGN KEY (`token_type_id`) REFERENCES `token_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- accounts — аккаунты компании
-- ---------------------------------------------------------------------------
CREATE TABLE `accounts` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(255)    NOT NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_accounts_company_name` (`company_id`, `name`),
    CONSTRAINT `fk_accounts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- account_tokens — токены аккаунта для API-сервиса
-- ---------------------------------------------------------------------------
CREATE TABLE `account_tokens` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`      BIGINT UNSIGNED NOT NULL,
    `api_service_id`  BIGINT UNSIGNED NOT NULL,
    `token_type_id`   BIGINT UNSIGNED NOT NULL,
    `credentials`     TEXT            NOT NULL COMMENT 'Зашифрованный JSON с credentials',
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_account_token` (`account_id`, `api_service_id`, `token_type_id`),
    CONSTRAINT `fk_account_tokens_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_account_tokens_api_service` FOREIGN KEY (`api_service_id`) REFERENCES `api_services` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_account_tokens_token_type` FOREIGN KEY (`token_type_id`) REFERENCES `token_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- account_sync_states — состояние синхронизации по аккаунту и сущности
-- ---------------------------------------------------------------------------
CREATE TABLE `account_sync_states` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`      BIGINT UNSIGNED NOT NULL,
    `entity`          VARCHAR(32)     NOT NULL COMMENT 'incomes|orders|sales|stocks',
    `last_synced_at`  TIMESTAMP       NULL,
    `last_date_from`  DATE            NULL,
    `created_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_account_sync_entity` (`account_id`, `entity`),
    CONSTRAINT `fk_sync_states_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- incomes — поставки
-- GET /api/incomes?dateFrom=&dateTo=
-- ---------------------------------------------------------------------------
CREATE TABLE `incomes` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`        BIGINT UNSIGNED NOT NULL,
    `income_id`         BIGINT UNSIGNED NOT NULL COMMENT 'Номер поставки (ID из API)',
    `number`            VARCHAR(64)     NULL     COMMENT 'Номер УПД',
    `date`              DATETIME        NOT NULL COMMENT 'Дата поступления',
    `last_change_date`  DATETIME        NULL     COMMENT 'Дата обновления в API',
    `supplier_article`  VARCHAR(255)    NOT NULL COMMENT 'Артикул поставщика',
    `tech_size`         VARCHAR(64)     NULL     COMMENT 'Размер',
    `barcode`           VARCHAR(32)     NULL     COMMENT 'Штрихкод',
    `quantity`          INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Количество',
    `total_price`       DECIMAL(12, 2)  NOT NULL DEFAULT 0.00 COMMENT 'Цена из УПД',
    `date_close`        DATETIME        NULL     COMMENT 'Дата закрытия поставки',
    `warehouse_name`    VARCHAR(255)    NOT NULL COMMENT 'Склад',
    `nm_id`             BIGINT          NULL     COMMENT 'Код товара WB',
    `status`            VARCHAR(64)     NULL     COMMENT 'Статус поставки',
    `created_at`        TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_incomes_business` (`account_id`, `income_id`, `supplier_article`, `barcode`, `tech_size`),
    KEY `idx_incomes_account_date` (`account_id`, `date`),
    KEY `idx_incomes_last_change_date` (`last_change_date`),
    KEY `idx_incomes_nm_id` (`nm_id`),
    CONSTRAINT `fk_incomes_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- orders — заказы
-- GET /api/orders?dateFrom=&dateTo=
-- ---------------------------------------------------------------------------
CREATE TABLE `orders` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`          BIGINT UNSIGNED NOT NULL,
    `g_number`            VARCHAR(64)     NULL     COMMENT 'Номер заказа',
    `date`                DATETIME        NOT NULL COMMENT 'Дата и время заказа',
    `last_change_date`    DATETIME        NULL     COMMENT 'Дата обновления в API',
    `supplier_article`    VARCHAR(255)    NULL     COMMENT 'Артикул поставщика',
    `tech_size`           VARCHAR(64)     NULL     COMMENT 'Размер',
    `barcode`             VARCHAR(32)     NULL     COMMENT 'Штрихкод',
    `total_price`         DECIMAL(12, 2)  NULL     COMMENT 'Цена до скидок',
    `discount_percent`    INT             NULL     COMMENT 'Скидка, %',
    `warehouse_name`      VARCHAR(255)    NULL     COMMENT 'Склад отгрузки',
    `warehouse_type`      VARCHAR(128)    NULL     COMMENT 'Тип склада',
    `country_name`        VARCHAR(128)    NULL     COMMENT 'Страна',
    `oblast`              VARCHAR(255)    NULL     COMMENT 'Область (legacy)',
    `oblast_okrug_name`   VARCHAR(255)    NULL     COMMENT 'Федеральный округ',
    `region_name`         VARCHAR(255)    NULL     COMMENT 'Регион',
    `income_id`           BIGINT UNSIGNED NULL     COMMENT 'Номер поставки',
    `odid`                VARCHAR(64)     NULL     COMMENT 'ID позиции заказа (legacy)',
    `srid`                VARCHAR(128)    NULL     COMMENT 'Уникальный ID заказа',
    `nm_id`               BIGINT          NULL     COMMENT 'Код товара WB',
    `subject`             VARCHAR(255)    NULL     COMMENT 'Предмет',
    `category`            VARCHAR(255)    NULL     COMMENT 'Категория',
    `brand`               VARCHAR(255)    NULL     COMMENT 'Бренд',
    `is_supply`           TINYINT(1)      NULL     COMMENT 'Договор поставки',
    `is_realization`      TINYINT(1)      NULL     COMMENT 'Договор реализации',
    `spp`                 DECIMAL(12, 2)  NULL     COMMENT 'Скидка постоянного покупателя',
    `finished_price`      DECIMAL(12, 2)  NULL     COMMENT 'Итоговая цена',
    `price_with_disc`     DECIMAL(12, 2)  NULL     COMMENT 'Цена со скидкой',
    `is_cancel`           TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Заказ отменён',
    `cancel_dt`           DATETIME        NULL     COMMENT 'Дата отмены',
    `sticker`             VARCHAR(64)     NULL     COMMENT 'Стикер сборки',
    `created_at`          TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_account_odid` (`account_id`, `odid`),
    UNIQUE KEY `uq_orders_account_srid` (`account_id`, `srid`),
    KEY `idx_orders_account_date` (`account_id`, `date`),
    KEY `idx_orders_last_change_date` (`last_change_date`),
    KEY `idx_orders_g_number` (`g_number`),
    KEY `idx_orders_nm_id` (`nm_id`),
    KEY `idx_orders_income_id` (`income_id`),
    CONSTRAINT `fk_orders_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- sales — продажи и возвраты
-- GET /api/sales?dateFrom=&dateTo=
-- ---------------------------------------------------------------------------
CREATE TABLE `sales` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`            BIGINT UNSIGNED NOT NULL,
    `g_number`              VARCHAR(64)     NULL     COMMENT 'Номер заказа',
    `date`                  DATETIME        NOT NULL COMMENT 'Дата продажи',
    `last_change_date`      DATETIME        NULL     COMMENT 'Дата обновления в API',
    `supplier_article`      VARCHAR(255)    NULL     COMMENT 'Артикул поставщика',
    `tech_size`             VARCHAR(64)     NULL     COMMENT 'Размер',
    `barcode`               VARCHAR(32)     NULL     COMMENT 'Штрихкод',
    `total_price`           DECIMAL(12, 2)  NULL     COMMENT 'Цена до скидок',
    `discount_percent`      INT             NULL     COMMENT 'Скидка, %',
    `is_supply`             TINYINT(1)      NULL     COMMENT 'Договор поставки',
    `is_realization`        TINYINT(1)      NULL     COMMENT 'Договор реализации',
    `promo_code_discount`   DECIMAL(12, 2)  NULL     COMMENT 'Скидка по промокоду',
    `warehouse_name`        VARCHAR(255)    NULL     COMMENT 'Склад отгрузки',
    `country_name`          VARCHAR(128)    NULL     COMMENT 'Страна',
    `oblast_okrug_name`     VARCHAR(255)    NULL     COMMENT 'Федеральный округ',
    `region_name`           VARCHAR(255)    NULL     COMMENT 'Регион',
    `income_id`             BIGINT UNSIGNED NULL     COMMENT 'Номер поставки',
    `sale_id`               VARCHAR(32)     NOT NULL COMMENT 'ID продажи/возврата (S..., R...)',
    `odid`                  VARCHAR(64)     NULL     COMMENT 'ID позиции заказа',
    `srid`                  VARCHAR(128)    NULL     COMMENT 'Уникальный ID заказа',
    `spp`                   DECIMAL(12, 2)  NULL     COMMENT 'Скидка постоянного покупателя',
    `for_pay`               DECIMAL(12, 2)  NULL     COMMENT 'К перечислению продавцу',
    `finished_price`        DECIMAL(12, 2)  NULL     COMMENT 'Итоговая цена',
    `price_with_disc`       DECIMAL(12, 2)  NULL     COMMENT 'Цена со скидкой',
    `nm_id`                 BIGINT          NULL     COMMENT 'Код товара WB',
    `subject`               VARCHAR(255)    NULL     COMMENT 'Предмет',
    `category`              VARCHAR(255)    NULL     COMMENT 'Категория',
    `brand`                 VARCHAR(255)    NULL     COMMENT 'Бренд',
    `is_storno`             TINYINT(1)      NULL     DEFAULT 0 COMMENT 'Сторно-операция',
    `sticker`               VARCHAR(64)     NULL     COMMENT 'Стикер сборки',
    `created_at`            TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sales_account_sale_id` (`account_id`, `sale_id`),
    KEY `idx_sales_account_date` (`account_id`, `date`),
    KEY `idx_sales_last_change_date` (`last_change_date`),
    KEY `idx_sales_g_number` (`g_number`),
    KEY `idx_sales_nm_id` (`nm_id`),
    KEY `idx_sales_odid` (`odid`),
    CONSTRAINT `fk_sales_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- stocks — остатки на складах
-- GET /api/stocks?dateFrom=  (только за указанный день)
-- ---------------------------------------------------------------------------
CREATE TABLE `stocks` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`              BIGINT UNSIGNED NOT NULL,
    `date`                    DATE            NOT NULL COMMENT 'Дата среза (dateFrom запроса)',
    `last_change_date`        DATETIME        NULL     COMMENT 'Дата обновления в API',
    `supplier_article`        VARCHAR(255)    NULL     COMMENT 'Артикул поставщика',
    `tech_size`               VARCHAR(64)     NULL     COMMENT 'Размер',
    `barcode`                 VARCHAR(32)     NULL     COMMENT 'Штрихкод',
    `quantity`                INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Доступно к продаже',
    `quantity_full`           INT UNSIGNED    NULL     COMMENT 'Полный остаток на складе',
    `quantity_not_in_orders`  INT UNSIGNED    NULL     COMMENT 'Не в заказах',
    `is_supply`               TINYINT(1)      NULL     COMMENT 'Договор поставки',
    `is_realization`          TINYINT(1)      NULL     COMMENT 'Договор реализации',
    `warehouse`               INT UNSIGNED    NULL     COMMENT 'ID склада',
    `warehouse_name`          VARCHAR(255)    NOT NULL COMMENT 'Название склада',
    `in_way_to_client`        INT UNSIGNED    NULL     DEFAULT 0 COMMENT 'В пути к клиенту',
    `in_way_from_client`      INT UNSIGNED    NULL     DEFAULT 0 COMMENT 'В пути от клиента',
    `nm_id`                   BIGINT          NOT NULL COMMENT 'Код товара WB',
    `subject`                 VARCHAR(255)    NULL     COMMENT 'Предмет',
    `category`                VARCHAR(255)    NULL     COMMENT 'Категория',
    `brand`                   VARCHAR(255)    NULL     COMMENT 'Бренд',
    `days_on_site`            INT UNSIGNED    NULL     COMMENT 'Дней на сайте',
    `sc_code`                 VARCHAR(64)     NULL     COMMENT 'Код контракта',
    `price`                   DECIMAL(12, 2)  NULL     COMMENT 'Цена',
    `discount`                DECIMAL(12, 2)  NULL     COMMENT 'Скидка',
    `created_at`              TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP       NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_stocks_snapshot` (`account_id`, `date`, `nm_id`, `warehouse_name`, `barcode`, `tech_size`),
    KEY `idx_stocks_account_date` (`account_id`, `date`),
    KEY `idx_stocks_last_change_date` (`last_change_date`),
    KEY `idx_stocks_supplier_article` (`supplier_article`),
    CONSTRAINT `fk_stocks_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Начальные данные: WB Test API
-- ---------------------------------------------------------------------------
INSERT INTO `api_services` (`slug`, `name`, `base_url`) VALUES
    ('wb_test', 'WB Test API', 'http://109.73.206.144:6969');

INSERT INTO `token_types` (`slug`, `name`) VALUES
    ('api_key', 'API Key (query parameter)'),
    ('bearer', 'Bearer Token'),
    ('login_password', 'Login and Password');

INSERT INTO `api_service_token_types` (`api_service_id`, `token_type_id`)
SELECT s.id, t.id FROM `api_services` s, `token_types` t WHERE s.slug = 'wb_test' AND t.slug = 'api_key';

INSERT INTO `companies` (`id`, `name`) VALUES (1, 'Legacy');

INSERT INTO `accounts` (`id`, `company_id`, `name`, `is_active`) VALUES (1, 1, 'default', 1);

SET FOREIGN_KEY_CHECKS = 1;
