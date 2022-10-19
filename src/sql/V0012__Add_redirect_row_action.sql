ALTER TABLE `dbc_row_action`
  MODIFY `action` ENUM('creation', 'change', 'comment', 'flag', 'unflag', 'assignment', 'deletion', 'restoration', 'redirection_origin', 'redirection_dest') NOT NULL;
