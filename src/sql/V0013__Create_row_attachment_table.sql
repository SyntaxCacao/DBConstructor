CREATE TABLE `dbc_row_attachment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `row_id` INT UNSIGNED NOT NULL,
  `uploader_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(70) NOT NULL,
  `size` BIGINT UNSIGNED NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `row` (`row_id`),
  INDEX `row_filename` (`row_id`, `filename`)
) DEFAULT CHARSET=utf8mb4;
