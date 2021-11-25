<?php

declare(strict_types=1);

use DBConstructor\Models\WorkflowExecution;
use DBConstructor\Models\WorkflowExecutionRow;
use DBConstructor\Models\WorkflowStep;

/** @var array $data */

?>
<main>
  <div class="container">
    <div class="main-header">
      <header class="main-header-header">
        <h1 class="main-heading">Ausführungen von <?= htmlentities($data["workflow"]->label) ?></h1>
        <p class="main-subtitle"><?= $data["count"] ?> mal ausgeführt</p>
      </header>
    </div>
  </div>
  <div class="container-expandable-outer">
    <div class="container-expandable-inner">
      <table class="table">
        <tr class="table-heading">
          <th class="table-cell">ID</th>
<?php foreach ($data["steps"] as $step) {
        /** @var WorkflowStep $step */ ?>
          <th class="table-cell"><?= htmlentities($step->getLabel()) ?></th>
<?php } ?>
          <th class="table-cell">Ausgeführt von</th>
          <th class="table-cell">Zeitpunkt</th>
        </tr>
<?php foreach ($data["executions"] as $execution) {
        /** @var WorkflowExecution $execution */ ?>
        <tr class="table-row">
          <td class="table-cell table-cell-numeric"><?= $execution->id ?></td>
<?php   foreach ($data["executionRows"][$execution->id] as $executionRow) {
          /** @var WorkflowExecutionRow $executionRow */ ?>
          <td class="table-cell table-cell-numeric"><?php if ($executionRow->rowExists) { ?><a class="main-link" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $data["steps"][$executionRow->stepId]->tableId ?>/view/<?= $executionRow->rowId ?>/"><?= $executionRow->rowId ?></a><?php } else { echo $executionRow->rowId; } ?></td>
<?php   } ?>
          <td class="table-cell"><?= htmlentities($execution->userFirstName." ".$execution->userLastName) ?></td>
          <td class="table-cell"><?= htmlentities(date("d.m.Y H:i", strtotime($execution->created))) ?></td>
        </tr>
<?php } ?>
      </table>
    </div>
  </div>
<?php if ($data["pages"] > 1) { ?>
  <nav class="pagination-container">
  <?php   if ($data["currentPage"] === 1) { ?>
    <span class="pagination-link pagination-link-disabled"><span class="bi bi-chevron-left"></span> Zurück</span>
<?php   } else { ?>
    <a class="pagination-link" href="?page=<?= $data["currentPage"]-1 ?>"><span class="bi bi-chevron-left"></span> Zurück</a>
<?php   }
        if ($data["currentPage"] >= $data["pages"]) { ?>
    <span class="pagination-link pagination-link-disabled">Weiter <span class="bi bi-chevron-right"></span></span>
<?php   } else { ?>
    <a class="pagination-link" href="?page=<?= $data["currentPage"]+1 ?>">Weiter <span class="bi bi-chevron-right"></span></a>
<?php   } ?>
  </nav>
<?php } ?>
</main>
