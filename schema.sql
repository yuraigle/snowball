DROP TABLE IF EXISTS `user_holdings`;
DROP TABLE IF EXISTS `user_categories`;
DROP TABLE IF EXISTS `asset_history`;
DROP TABLE IF EXISTS `assets`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users`
(
    `id`                bigint(20)   NOT NULL AUTO_INCREMENT,
    `name`              varchar(255) NOT NULL,
    `email`             varchar(255) NOT NULL,
    `email_verified_at` timestamp    NULL DEFAULT NULL,
    `password`          varchar(255) NOT NULL,
    `remember_token`    varchar(100)      DEFAULT NULL,
    `created_at`        timestamp    NULL DEFAULT NULL,
    `updated_at`        timestamp    NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `IX_USERS_EMAIL_UNIQUE` (`email`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

CREATE TABLE `assets`
(
    `id`         bigint(20)   NOT NULL AUTO_INCREMENT,
    `ticker`     varchar(10)  NOT NULL,
    `name`       varchar(100) NOT NULL,
    `price`      decimal(20, 6)        DEFAULT NULL,
    `currency`   varchar(3)   NOT NULL DEFAULT 'USD',
    `updated_at` datetime              DEFAULT NULL,
    `icon`       varchar(20)           DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `IX_ASSETS_TICKER_UNIQUE` (`ticker`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

CREATE TABLE `user_holdings`
(
    `id`         bigint(20)     NOT NULL AUTO_INCREMENT,
    `user_id`    bigint(20)     NOT NULL,
    `asset_id`   bigint(20)     NOT NULL,
    `deal_type`  smallint(6)    NOT NULL DEFAULT 0,
    `deal_date`  date           NOT NULL DEFAULT current_timestamp(),
    `amount`     decimal(20, 6) NOT NULL,
    `price`      decimal(20, 6) NOT NULL,
    `currency`   varchar(3)              DEFAULT 'USD',
    `commission` decimal(20, 6) NOT NULL DEFAULT 0.000000,
    `created_at` datetime       NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_USER_HOLDINGS_ON_ASSET` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `FK_USER_HOLDINGS_ON_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

CREATE TABLE `user_categories`
(
    `id`            bigint(20)    NOT NULL AUTO_INCREMENT,
    `user_id`       bigint(20)    NOT NULL,
    `parent_id`     bigint(20)    NULL,
    `asset_id`      bigint(20)    NULL,
    `name`          nvarchar(100) NULL,
    `target_weight` decimal(5, 2) NULL,
    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_CATEGORIES_ON_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `FK_CATEGORIES_ON_ASSET` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `FK_CATEGORIES_ON_PARENT` FOREIGN KEY (`parent_id`) REFERENCES `user_categories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

CREATE TABLE `asset_history`
(
    `id`         bigint(20)     NOT NULL AUTO_INCREMENT,
    `asset_id`   bigint(20)     NOT NULL,
    `date`       date           NOT NULL,
    `open`       decimal(20, 6) NULL,
    `high`       decimal(20, 6) NULL,
    `low`        decimal(20, 6) NULL,
    `close`      decimal(20, 6) NULL,
    `updated_at` datetime       NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `IX_ASSET_HISTORY_DATE_UNIQUE` (`asset_id`, `date`) USING BTREE,
    CONSTRAINT `FK_ASSET_HISTORY_ON_ASSET` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;
