UPDATE `dbc_column_relational` SET `instructions`=REPLACE(`instructions`, CHAR(13), '');

UPDATE `dbc_column_textual` SET `instructions`=REPLACE(`instructions`, CHAR(13), '');

UPDATE `dbc_field_textual` SET `value`=REPLACE(`value`, CHAR(13), '');

UPDATE `dbc_page_state` SET `text`=REPLACE(`text`, CHAR(13), '');

UPDATE `dbc_project` SET `notes`=REPLACE(`notes`, CHAR(13), '');

UPDATE `dbc_row_action` SET `data`=REPLACE(`data`, CHAR(13), '') WHERE `action`='comment';

UPDATE `dbc_table` SET `instructions`=REPLACE(`instructions`, CHAR(13), '');

UPDATE `dbc_workflow_step` SET `instructions`=REPLACE(`instructions`, CHAR(13), '')
