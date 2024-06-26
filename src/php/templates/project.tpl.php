<?php

declare(strict_types=1);

use DBConstructor\Controllers\TabRouter;
use DBConstructor\Models\Project;

/** @var array{baseurl: string, isManager: bool, project: Project, project-tabs: TabRouter, tabpage: string} $data */

?>
<div class="header">
  <div class="container">
    <header class="header-header">
      <h1 class="header-project-label"><?php echo htmlentities($data["project"]->label); ?></h1>
    </header>
  </div>

  <div class="tabnav">
    <div class="container">
      <nav class="tabnav-tabs">
<?php foreach ($data["project-tabs"]->tabs as $tab) {
        if ($tab->requireManager && ! $data["isManager"]) {
          continue;
        } ?>
        <a class="tabnav-tab<?php if ($tab->link == $data["project-tabs"]->current->link) echo " selected"; ?>" href="<?php echo $data["baseurl"]."/projects/".$data["project"]->id."/"; if ($data["project-tabs"]->default != $tab->link) echo $tab->link."/"; ?>"><?php if (! is_null($tab->icon)) echo '<span class="bi bi-'.$tab->icon.'"></span>'; echo $tab->label; ?></a>
<?php } ?>
      </nav>
    </div>
  </div>
</div>

<?php
if (isset($data["tabpage"])) {
  require "project_".$data["project-tabs"]->current->link."_".$data["tabpage"].".tpl.php";
} else {
  require "project_".$data["project-tabs"]->current->link.".tpl.php";
}
?>
