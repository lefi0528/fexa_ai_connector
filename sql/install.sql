CREATE TABLE IF NOT EXISTS `PREFIX_mcp_server_tools`
(
    `module_id`        INT(10)          NOT NULL,
    `name`             VARCHAR(100)     NOT NULL,
    `description`      TEXT             NOT NULL,
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`module_id`, `name`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_mcp_server_modules_registered`
(
    `module_id`        INT(10)          NOT NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`module_id`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_mcp_server_allowed_users`
(
    `email`            VARCHAR(255)     NOT NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`email`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;
