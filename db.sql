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
-- incomes — поставки
-- GET /api/incomes?dateFrom=&dateTo=
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `incomes`;

CREATE TABLE `incomes` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
    UNIQUE KEY `uq_incomes_business` (`income_id`, `supplier_article`, `barcode`, `tech_size`),
    KEY `idx_incomes_date` (`date`),
    KEY `idx_incomes_last_change_date` (`last_change_date`),
    KEY `idx_incomes_nm_id` (`nm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- orders — заказы
-- GET /api/orders?dateFrom=&dateTo=
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
    UNIQUE KEY `uq_orders_odid` (`odid`),
    UNIQUE KEY `uq_orders_srid` (`srid`),
    KEY `idx_orders_date` (`date`),
    KEY `idx_orders_last_change_date` (`last_change_date`),
    KEY `idx_orders_g_number` (`g_number`),
    KEY `idx_orders_nm_id` (`nm_id`),
    KEY `idx_orders_income_id` (`income_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- sales — продажи и возвраты
-- GET /api/sales?dateFrom=&dateTo=
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `sales`;

CREATE TABLE `sales` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
    UNIQUE KEY `uq_sales_sale_id` (`sale_id`),
    KEY `idx_sales_date` (`date`),
    KEY `idx_sales_last_change_date` (`last_change_date`),
    KEY `idx_sales_g_number` (`g_number`),
    KEY `idx_sales_nm_id` (`nm_id`),
    KEY `idx_sales_odid` (`odid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- stocks — остатки на складах
-- GET /api/stocks?dateFrom=  (только за указанный день)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `stocks`;

CREATE TABLE `stocks` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
    UNIQUE KEY `uq_stocks_snapshot` (`date`, `nm_id`, `warehouse_name`, `barcode`, `tech_size`),
    KEY `idx_stocks_last_change_date` (`last_change_date`),
    KEY `idx_stocks_supplier_article` (`supplier_article`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
