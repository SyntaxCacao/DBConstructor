CREATE TABLE `dbc_user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT UNSIGNED NULL DEFAULT NULL,
  `username` VARCHAR(30) NOT NULL,
  `firstname` VARCHAR(30) NOT NULL,
  `lastname` VARCHAR(30) NOT NULL,
  `password` CHAR(60) NOT NULL,
  `admin` BOOLEAN NOT NULL DEFAULT FALSE,
  `locked` BOOLEAN NOT NULL DEFAULT FALSE,
  `firstlogin` TIMESTAMP NULL DEFAULT NULL,
  `lastlogin` TIMESTAMP NULL DEFAULT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`username`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_project` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `mainpage_id` INT UNSIGNED NULL DEFAULT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` VARCHAR(150) NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_participant` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED NOT NULL,
  `manager` BOOLEAN NOT NULL DEFAULT FALSE,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `user` (`user_id`),
  INDEX `project` (`project_id`),
  INDEX `user_project` (`user_id`, `project_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_page` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `position` INT UNSIGNED NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `project` (`project_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_page_attachment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_id` INT UNSIGNED NOT NULL,
  `uploader_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(100),
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `page` (`page_id`),
  INDEX `page_filename` (`page_id`, `filename`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_page_state` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_id` INT UNSIGNED NOT NULL,
  `creator_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `text` MEDIUMTEXT NOT NULL,
  `comment` VARCHAR(1000) NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `page` (`page_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_table` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(30) NOT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `position` INT UNSIGNED NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `project` (`project_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_row` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `creator_id` INT UNSIGNED NOT NULL,
  `lasteditor_id` INT UNSIGNED NOT NULL,
  `assignee_id` INT UNSIGNED NULL DEFAULT NULL,
  `valid` BOOLEAN NOT NULL DEFAULT FALSE,
  `flagged` BOOLEAN NOT NULL DEFAULT FALSE,
  `deleted` BOOLEAN NOT NULL DEFAULT FALSE,
  `exportid` INT UNSIGNED NULL DEFAULT NULL,
  `lastupdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `table` (`table_id`),
  INDEX `table_creator` (`table_id`, `creator_id`),
  INDEX `table_assignee` (`table_id`, `assignee_id`),
  INDEX `table_valid` (`table_id`, `valid`),
  INDEX `table_flagged` (`table_id`, `flagged`),
  INDEX `table_deleted` (`table_id`, `deleted`),
  INDEX `table_exportid` (`table_id`, `exportid`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_row_action` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `action` ENUM('creation', 'change', 'comment', 'flag', 'unflag', 'assignment', 'deletion', 'restoration') NOT NULL,
  `data` TEXT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `row` (`row_id`),
  INDEX `user` (`user_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_column_textual` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(30) NOT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `position` INT UNSIGNED NOT NULL,
  `type` ENUM('bool', 'date', 'dec', 'int', 'select', 'text') NOT NULL,
  `rules` LONGTEXT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `table` (`table_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_column_relational` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `target_table_id` INT UNSIGNED NOT NULL,
  `label_column_id` INT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(30) NOT NULL,
  `label` VARCHAR(30) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `position` INT UNSIGNED NOT NULL,
  `nullable` BOOLEAN NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `table` (`table_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_field_textual` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `column_id` INT UNSIGNED NOT NULL,
  `value` VARCHAR(10000) NULL,
  `valid` BOOLEAN NULL DEFAULT NULL,
  INDEX `row` (`row_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_field_relational` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `column_id` INT UNSIGNED NOT NULL,
  `target_row_id` INT UNSIGNED NULL,
  `valid` BOOLEAN NOT NULL,
  INDEX `row` (`row_id`),
  INDEX `targetrow` (`target_row_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_export` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `format` ENUM('csv') NOT NULL,
  `note` VARCHAR(1000) NULL,
  `deleted` BOOLEAN NOT NULL DEFAULT FALSE,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `project` (`project_id`),
  INDEX `user` (`user_id`)
) DEFAULT CHARSET=utf8mb4;
