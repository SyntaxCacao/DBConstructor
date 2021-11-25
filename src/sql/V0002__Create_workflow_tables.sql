CREATE TABLE `dbc_workflow` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `lasteditor_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(60) NOT NULL,
  `description` VARCHAR(150) NULL,
  `active` BOOLEAN NOT NULL DEFAULT FALSE,
  `lastupdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `project_active` (`project_id`, `active`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_workflow_step` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `workflow_id` INT UNSIGNED NOT NULL,
  `table_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(60) NULL,
  `description` MEDIUMTEXT NULL,
  `position` INT UNSIGNED NOT NULL,
  `relcoldata` LONGTEXT NULL DEFAULT NULL,
  `txtcoldata` LONGTEXT NULL DEFAULT NULL,
  INDEX `workflow` (`workflow_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_workflow_execution` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `workflow_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `workflow` (`workflow_id`),
  INDEX `workflow_user` (`workflow_id`, `user_id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `dbc_workflow_execution_row` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `execution_id` INT UNSIGNED NOT NULL,
  `step_id` INT UNSIGNED NOT NULL,
  `row_id` INT UNSIGNED NOT NULL,
  INDEX `execution` (`execution_id`),
  INDEX `row` (`row_id`)
)
