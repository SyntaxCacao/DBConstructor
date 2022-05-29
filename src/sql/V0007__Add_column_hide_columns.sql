ALTER TABLE `dbc_column_relational`
  ADD `hide` BOOLEAN NOT NULL DEFAULT FALSE AFTER `nullable`;

ALTER TABLE `dbc_column_textual`
  ADD `hide` BOOLEAN NOT NULL DEFAULT FALSE AFTER `rules`
