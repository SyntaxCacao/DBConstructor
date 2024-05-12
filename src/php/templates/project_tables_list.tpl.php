<?php

declare(strict_types=1);

use DBConstructor\Models\RowLoader;
use DBConstructor\Util\MarkdownParser;

/** @var array $data */

?>
<main class="container">
<?php if (isset($data["request"]["created"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Das Projekt wurde angelegt.</p></div>
  </div>
<?php } else if (isset($data["joined"]) && $data["joined"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Sie sind dem Projekt beigetreten.</p></div>
  </div>
<?php } ?>
  <div class="row break-md">
    <div class="column width-9">
      <header class="main-header">
        <div class="main-header-header">
          <h1 class="main-heading">Tabellen</h1>
          <p class="main-subtitle"><?php echo count($data["tables"]); ?> Tabelle<?php if (count($data["tables"]) != 1) echo "n" ?> angelegt</p>
        </div>
<?php if ($data["isManager"]) { ?>
        <div class="main-header-actions">
          <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/create/"><span class="bi bi-table"></span>Tabelle anlegen</a>
        </div>
<?php } ?>
      </header>
<?php if (count($data["tables"]) > 0) { ?>
      <div class="table-wrapper">
        <table class="table">
          <tr class="table-heading">
            <th class="table-cell">Bezeichnung</th>
            <th class="table-cell">Technischer Name</th>
            <th class="table-cell" title="Zahl der nicht als gelöscht markierten Datensätze in der Tabelle">Datensätze</th>
<?php   if ($data["isManager"]) { ?>
            <th class="table-cell"></th>
<?php   } ?>
          </tr>
<?php   $all = count($data["tables"]);
        $count = 0;
        foreach ($data["tables"] as $table) {
          $count += 1; ?>
          <tr class="table-row">
            <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"]; ?>/projects/<?php echo $data["project"]->id; ?>/tables/<?php echo $table->id ?>/"><?php echo htmlentities($table->label); ?></a></td>
            <td class="table-cell table-cell-code"><?php echo htmlentities($table->name); ?></td>
            <td class="table-cell"><?php echo number_format(intval($table->rowCount), 0, ",", "."); if (intval($table->assignedCount) > 0) echo '&nbsp; <a class="main-link page-table-list-assigned-counter" href="'.$data["baseurl"].'/projects/'.$data["project"]->id.'/tables/'.$table->id.'/view/?field-assignee='.$data["user"]->id.'&field-deleted='.RowLoader::FILTER_DELETED_INCLUDE.'" title="Mir zugewiesene Datensätze"><span class="bi bi-bell-fill"></span> <strong>'.number_format(intval($table->assignedCount), 0, ",", ".").'</strong>'; ?></td>
<?php     if ($data["isManager"]) { ?>
            <td class="table-cell table-cell-actions">
              <a class="button button-smallest" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $table->id ?>/settings/"><span class="bi bi-pencil"></span>Bearbeiten</a><!--
--><?php    if ($data["project"]->manualOrder) { ?><!--
           --><a class="button button-smallest<?php if ($count === 1) echo " button-disabled" ?>"<?php if ($count !== 1) { ?> href="?move=<?= $table->id ?>&position=<?= $count-1 ?>" title="Nach oben verschieben"<?php } ?>><span class="bi bi-arrow-up no-margin"></span></a><!--
           --><a class="button button-smallest<?php if ($count === $all) echo " button-disabled" ?>"<?php if ($count !== $all) { ?> href="?move=<?= $table->id ?>&position=<?= $count+1 ?>" title="Nach unten verschieben"<?php } ?>><span class="bi bi-arrow-down no-margin"></span></a>
<?php       } ?>
            </td>
<?php     } ?>
          </tr>
<?php   } ?>
        </table>
      </div>
<?php } else { ?>
      <p>Es sind noch keine Tabellen angelegt.</p>
<?php } ?>
    </div>
    <div class="column width-3">
      <header class="main-header">
        <h1 class="main-heading">Beschreibung</h1>
      </header>
      <div class="markdown">
<?php if (is_null($data["project"]->notes)) {
        echo "<p>";
        if (is_null($data["project"]->description)) {
          echo "Es ist keine Beschreibung vorhanden.";
        } else {
          echo htmlentities($data["project"]->description);
        }
        echo "</p>";
      } else {
        echo MarkdownParser::parse($data["project"]->notes);
      } ?>
        <p class="page-project-tables-created">Projekt angelegt am <?php echo htmlentities(date("d.m.Y", strtotime($data["project"]->created))); ?></p>
      </div>
<?php if ($data["rowsCreatedAllTime"] > 0) { ?>
      <header class="main-header">
        <h1 class="main-heading">Erfassungsfortschritt</h1>
      </header>
      <div class="markdown">
        <h2>Erfasste Datensätze</h2>
        <p>Insgesamt: <strong><?= number_format($data["rowsCreatedAllTime"], 0, ",", ".") ?></strong><br>
           Letzte Woche: <strong><?= number_format($data["rowsCreatedLastWeek"], 0, ",", ".") ?></strong><br>
           Laufende Woche: <strong><?= number_format($data["rowsCreatedThisWeek"], 0, ",", ".") ?></strong><?php
        $diff = $data["rowsCreatedThisWeek"] - $data["rowsCreatedLastWeek"];
        if ($diff === 0) {
          echo ' (±0)';
        } else if ($diff > 0) {
          echo ' <span class="page-project-tables-progress-more">(+'.number_format(($diff/$data["rowsCreatedLastWeek"])*100, 0, ",", ".").'%)</span>';
        } else {
          echo ' <span class="page-project-tables-progress-less">('.number_format(($diff/$data["rowsCreatedLastWeek"])*100, 0, ",", ".").'%)</span>';
        }
        ?></p>
<?php   if ($data["rowsCreatedUserAllTime"] > 0) { ?>
        <h2>Von Ihnen angelegte Datensätze</h2>
        <p>Insgesamt: <strong><?= number_format($data["rowsCreatedUserAllTime"], 0, ",", ".") ?></strong><br>
           Letzte Woche: <strong><?= number_format($data["rowsCreatedUserLastWeek"], 0, ",", ".") ?></strong><br>
           Laufende Woche: <strong><?= number_format($data["rowsCreatedUserThisWeek"], 0, ",", ".") ?></strong><?php
        $diff = $data["rowsCreatedUserThisWeek"] - $data["rowsCreatedUserLastWeek"];
        if ($diff === 0) {
          echo ' (±0)';
        } else if ($diff > 0) {
          echo ' <span class="page-project-tables-progress-more">(+'.number_format(($diff/$data["rowsCreatedUserLastWeek"])*100, 0, ",", ".").'%)</span>';
        } else {
          echo ' <span class="page-project-tables-progress-less">('.number_format(($diff/$data["rowsCreatedUserLastWeek"])*100, 0, ",", ".").'%)</span>';
        }
        ?></p>
<?php   } ?>
        <p><a href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/progress/">Mehr...</a></p>
      </div>
<?php } ?>
    </div>
  </div>
</main>
