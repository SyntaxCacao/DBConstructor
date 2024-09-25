ALTER TABLE `dbc_column_relational`
  CHANGE `description` `instructions` MEDIUMTEXT NULL;

ALTER TABLE `dbc_column_textual`
  CHANGE `description` `instructions` MEDIUMTEXT NULL;

ALTER TABLE `dbc_table`
  CHANGE `description` `instructions` MEDIUMTEXT NULL;

ALTER TABLE `dbc_workflow_step`
  CHANGE `description` `instructions` MEDIUMTEXT NULL
