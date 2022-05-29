<main>
  <div class="container">
    <?= $data["filterForm"]->generate() ?>
  </div>
<?php if ($data["rowCount"] > 0) { ?>
  <div class="container-expandable-outer">
    <div class="container-expandable-inner-centered">
      <table class="table">
        <tr class="table-heading">
          <th class="table-cell"><!--3.000--></th>
          <th class="table-cell"></th>
          <th class="table-cell">ID</th>
<?php   foreach($data["relationalColumns"] as $column) {
          /** @var \DBConstructor\Models\RelationalColumn $column */
          if (! $column->hide) { ?>
          <th class="table-cell" title="<?= htmlentities($column->name) ?>"><a href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $column->targetTableId ?>/"><?= htmlentities($column->label) ?></a></th>
<?php     }
        }
        foreach($data["textualColumns"] as $column) {
          /** @var \DBConstructor\Models\TextualColumn $column */
          if (! $column->hide) { ?>
          <th class="table-cell" title="<?= htmlentities($column->name) ?>"><?= htmlentities($column->label) ?></th>
<?php     }
        } ?>
          <th class="table-cell">Zuordnung</th>
          <th class="table-cell">Letzte Aktivität</th>
          <th class="table-cell">Angelegt</th>
        </tr>
<?php   foreach ($data["rows"] as $row) {
          /** @var \DBConstructor\Models\Row $row */ ?>
        <tr class="table-row">
          <td class="table-cell table-cell-actions"><a class="button button-smallest" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $data["table"]->id ?>/view/<?= $row->id ?>/"><span class="bi bi-file-earmark-text"></span>Aufrufen</a></td>
          <td class="table-cell page-table-view-icons"><span class="validation-step-icon" title="<?= $row->valid ? "gültig" : "ungültig" ?>"><span class="bi <?= $row->valid ? "bi-check-lg" : "bi-x-lg" ?>"></span></span><?php if ($row->flagged) { ?><span class="validation-step-icon" title="zur Nachverfolgung gekennzeichnet"><span class="bi bi-flag-fill"></span></span><?php } /*if ($row->assigneeId === $data["user"]->id) { ?><span class="validation-step-icon" title="mir zugewiesen"><span class="bi bi-person-fill"></span></span><?php }*/ if ($row->deleted) { ?><span class="validation-step-icon" title="gelöscht"><span class="bi bi-trash"></span></span><?php } ?></td>
          <td class="table-cell table-cell-numeric"><?= $row->id ?></td>
<?php     foreach($data["relationalColumns"] as $column) {
            /** @var \DBConstructor\Models\RelationalColumn $column */
            if ($column->hide) {
              continue;
            }
            if (isset($data["relationalFields"][$row->id][$column->id])) {
              /** @var \DBConstructor\Models\RelationalField $field */
              $field = $data["relationalFields"][$row->id][$column->id] ?>
<?php         if ($field->targetRowId === null) { ?>
          <td class="table-cell<?= ! $field->valid ? " table-cell-invalid" : "" ?> table-cell-null">NULL</td>
<?php         } else { ?>
          <td class="table-cell<?= ! $field->valid ? " table-cell-invalid" : "" ?> table-cell-numeric"><a class="main-link" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $column->targetTableId ?>/view/<?= $field->targetRowId ?>/"><?= $field->targetRowId ?><?php /* $firstCol = array_keys($field->getTargetRow())[0]; echo htmlentities($field->getTargetRow()[$firstCol]->columnName." = ".$field->getTargetRow()[$firstCol]->value); */ ?></a></td>
<?php         } ?>
<?php       } else { ?>
          <td class="table-cell table-cell-invalid table-cell-null">fehlend</td>
<?php       }
          }
          foreach($data["textualColumns"] as $column) {
            /** @var \DBConstructor\Models\TextualColumn $column */
            if ($column->hide) {
              continue;
            }
            if (isset($data["textualFields"][$row->id][$column->id])) {
              echo $column->generateCellValue($data["textualFields"][$row->id][$column->id]);
            } else {
              echo $column->generateCellValue();
            }
          } ?>
          <td class="table-cell"><?= $row->assigneeId === null ? "&ndash;" : htmlentities($row->assigneeFirstName." ".$row->assigneeLastName) ?></td>
          <td class="table-cell" title="Zuletzt bearbeitet von <?= htmlentities($row->lastEditorFirstName." ".$row->lastEditorLastName) ?>"><?= htmlentities(date("d.m.Y H:i", strtotime($row->lastUpdated))) ?></td>
          <td class="table-cell" title="Angelegt von <?= htmlentities($row->creatorFirstName." ".$row->creatorLastName) ?>"><?= htmlentities(date("d.m.Y H:i", strtotime($row->created))) ?></td>
        </tr>
<?php   } ?>
      </table>
    </div>
  </div>
  <nav class="pagination-container">
<?php   if ($data["pageCount"] > 1) {
          $query = "?";
          foreach ($_GET as $key => $value) {
            // strpos(...) = str_starts_with polyfill
            if (strpos($key , "field-") === 0) {
              $query .= urlencode($key)."=".urlencode($value)."&";
            }
          }
          if ($data["currentPage"] === 1) { ?>
    <span class="pagination-link pagination-link-disabled"><span class="bi bi-chevron-left"></span> Zurück</span>
<?php     } else { ?>
    <a class="pagination-link" href="<?= $query ?>page=<?= $data["currentPage"]-1 ?>"><span class="bi bi-chevron-left"></span> Zurück</a>
<?php     }
          if ($data["currentPage"] >= $data["pageCount"]) { ?>
    <span class="pagination-link pagination-link-disabled">Weiter <span class="bi bi-chevron-right"></span></span>
<?php     } else { ?>
    <a class="pagination-link" href="<?= $query ?>page=<?= $data["currentPage"]+1 ?>">Weiter <span class="bi bi-chevron-right"></span></a>
<?php     }
        } ?>
  </nav>
<?php } else { ?>
  <div class="blankslate">
    <h1 class="blankslate-heading">Keine Datensätze gefunden</h1>
    <p class="blankslate-text">Der Filter ist zu streng eingestellt oder es sind noch keine Datensätze vorhanden.</p>
  </div>
<?php } ?>
</main>
