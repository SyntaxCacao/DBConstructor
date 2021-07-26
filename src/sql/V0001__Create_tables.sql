CREATE TABLE `dbc_user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(20) NOT NULL,
  `firstname` VARCHAR(30) NOT NULL,
  `lastname` VARCHAR(30) NOT NULL,
  `password` CHAR(60) NOT NULL,
  `admin` BOOLEAN NOT NULL DEFAULT FALSE,
  `locked` BOOLEAN NOT NULL DEFAULT FALSE,
  `firstlogin` TIMESTAMP NULL DEFAULT NULL,
  `lastlogin` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE (`username`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_project` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(30) NOT NULL,
  `description` VARCHAR(1000) NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_participant` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED NOT NULL,
  `manager` BOOLEAN NOT NULL DEFAULT FALSE,
  `added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`project_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_table` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(30) NOT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` VARCHAR(1000) NULL,
  `position` INT UNSIGNED NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`project_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_row` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `creator_id` INT UNSIGNED NOT NULL,
  `assignee_id` INT UNSIGNED NULL DEFAULT NULL,
  `lasteditor_id` INT UNSIGNED NULL DEFAULT NULL,
  `validity` ENUM('invalid', 'unchecked', 'valid') NOT NULL DEFAULT 'unchecked',
  `flagged` BOOLEAN NOT NULL DEFAULT FALSE,
  `deleted` BOOLEAN NOT NULL DEFAULT FALSE,
  `exportid` INT UNSIGNED NULL DEFAULT NULL,
  `lastupdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`table_id`),
  INDEX (`table_id`, `creator_id`),
  INDEX (`table_id`, `assignee_id`),
  INDEX (`table_id`, `validity`),
  INDEX (`table_id`, `flagged`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_row_action` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `actor_id` INT UNSIGNED NOT NULL,
  `action` ENUM('creation', 'update', 'comment', 'flag', 'unflag', 'deletion', 'restoration') NOT NULL,
  `data` TEXT(21000) NULL,
  `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`row_id`),
  INDEX (`actor_id`)
) DEFAULT CHARSET=utf8mb4;

/*
CREATE TABLE `dbc_row_issue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `author_id` INT UNSIGNED NOT NULL,
  `resolved` BOOLEAN NOT NULL DEFAULT FALSE,
  `resolvedat` TIMESTAMP NULL DEFAULT NULL,
  INDEX (`row_id`)
) DEFAULT CHARSET=utf8mb4;
*/

/*
CREATE TABLE `dbc_row_issue_comment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `issue_id` INT UNSIGNED NOT NULL,
  `author_id` INT UNSIGNED NOT NULL,
  `text` VARCHAR(1000) NULL,
  `action` ENUM('resolves', 'reopens') NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`issue_id`)
) DEFAULT CHARSET=utf8mb4;
*/

CREATE TABLE `dbc_column_textual` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(30) NOT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` VARCHAR(1000) NULL,
  `position` INT UNSIGNED NOT NULL,
  `type` ENUM('text', 'boolean', 'integer', 'double', 'date', 'enum', 'set') NOT NULL,
  `rules` LONGTEXT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`table_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_column_relational` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `target_table_id` INT UNSIGNED NOT NULL,
  `label_column_id` INT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(30) NOT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` VARCHAR(1000) NULL,
  `position` INT UNSIGNED NOT NULL,
  `rules` LONGTEXT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`table_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_field_textual` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `column_id` INT UNSIGNED NOT NULL,
  `value` VARCHAR(10000) NULL,
  `validity` ENUM ('invalid', 'unchecked', 'valid') NOT NULL DEFAULT 'unchecked',
  INDEX (`row_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_field_relational` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `column_id` INT UNSIGNED NOT NULL,
  `target_row_id` INT UNSIGNED NOT NULL,
  `validity` ENUM ('invalid', 'unchecked', 'valid') NOT NULL DEFAULT 'unchecked',
  INDEX (`row_id`),
  INDEX (`target_row_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_export` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `format` ENUM('csv') NOT NULL,
  `note` VARCHAR(1000) NULL,
  `deleted` BOOLEAN NOT NULL DEFAULT FALSE,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`project_id`),
  INDEX (`user_id`)
) DEFAULT CHARSET=utf8mb4;
