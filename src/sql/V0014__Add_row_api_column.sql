ALTER TABLE `dbc_row`
  ADD `api` BOOLEAN NOT NULL DEFAULT FALSE AFTER `deleted`;

UPDATE `dbc_row` r SET r.`api` = (SELECT a.`api` FROM `dbc_row_action` a WHERE a.`row_id`=r.`id` AND a.`action`='creation');

ALTER TABLE `dbc_row`
  CHANGE `api` `api` BOOLEAN NOT NULL,
  ADD INDEX `table_api` (`table_id`, `api`);
