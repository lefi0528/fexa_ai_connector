CREATE TABLE IF NOT EXISTS `PREFIX_fexa_ai_connector_tools`
(
    `id`               INT(10) UNSIGNED AUTO_INCREMENT,
    `module_id`        INT(10)          NOT NULL,
    `name`             VARCHAR(100)     NOT NULL,
    `description`      TEXT             NOT NULL,
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `module_tool` (`module_id`, `name`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_fexa_ai_connector_modules_reg`
(
    `module_id`        INT(10)          NOT NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`module_id`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_fexa_ai_connector_allowed_users`
(
    `email`            VARCHAR(255)     NOT NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`email`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_fexa_ai_structured_data`
(
    `id`               INT(10) UNSIGNED AUTO_INCREMENT,
    `entity_type`      VARCHAR(20)      NOT NULL,
    `entity_id`        INT(10) UNSIGNED NOT NULL,
    `id_lang`          INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `id_shop`          INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `schema_type`      VARCHAR(40)      NOT NULL,
    `jsonld`           LONGTEXT         NOT NULL,
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_entity_schema` (`entity_type`, `entity_id`, `id_lang`, `id_shop`, `schema_type`),
    KEY `idx_lookup` (`entity_type`, `entity_id`, `is_active`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;
