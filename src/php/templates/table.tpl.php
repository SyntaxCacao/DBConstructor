<div class="header">
  <div class="container">
    <header class="header-header">
      <a class="header-project-link" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id; ?>/"><?php echo $data["project"]->label; ?></a><span class="header-slash"> / </span><h1 class="header-table-name"><?php echo $data["table"]->label; ?></h1>
    </header>
  </div>

  <div class="tabnav">
    <div class="container">
      <nav class="tabnav-tabs">
<?php foreach ($data["table-tabs"]->tabs as $tab) { ?>
        <a class="tabnav-tab<?php if ($tab->link == $data["table-tabs"]->current->link) echo " selected"; ?>" href="<?php echo $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/"; if ($data["table-tabs"]->default != $tab->link) echo $tab->link."/"; ?>"><?php if (! is_null($tab->icon)) echo '<span class="bi bi-'.$tab->icon.'"></span>'; echo $tab->label; ?></a>
<?php } ?>
      </nav>
    </div>
  </div>
</div>

<?php
if (isset($data["tabpage"])) {
  require "table_".$data["table-tabs"]->current->link."_".$data["tabpage"].".tpl.php";
} else {
  require "table_".$data["table-tabs"]->current->link.".tpl.php";
}
?>
