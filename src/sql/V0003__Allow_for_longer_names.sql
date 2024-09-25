ALTER TABLE `dbc_column_relational`
  CHANGE `name` `name` VARCHAR(64) NOT NULL,
  CHANGE `label` `label` VARCHAR(64) NOT NULL;

ALTER TABLE `dbc_column_textual`
  CHANGE `name` `name` VARCHAR(64) NOT NULL,
  CHANGE `label` `label` VARCHAR(64) NOT NULL;

ALTER TABLE `dbc_project`
  CHANGE `label` `label` VARCHAR(64) NOT NULL;

ALTER TABLE `dbc_table`
  CHANGE `name` `name` VARCHAR(64) NOT NULL,
  CHANGE `label` `label` VARCHAR(64) NOT NULL;

ALTER TABLE `dbc_workflow`
  CHANGE `label` `label` VARCHAR(64) NOT NULL;

ALTER TABLE `dbc_workflow_step`
  CHANGE `label` `label` VARCHAR(64) NULL
