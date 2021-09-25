<main class="container main-container">
<?php if (isset($data["request"]["saved"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Das Feld wurde gespeichert.</p></div>
  </div>

<?php } ?>
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Struktur</h1>
      <p class="main-subtitle"><?php
        if (count($data["relationalColumns"]) > 0) {
          echo count($data["relationalColumns"])." Relationsfeld";
          if (count($data["relationalColumns"]) > 1) echo "er";
          if (count($data["textualColumns"]) > 0) echo " · ";
        }
        if (count($data["textualColumns"]) > 0) {
          echo count($data["textualColumns"])." Wertfeld";
          if (count($data["textualColumns"]) > 1) echo "er";
        } ?></p>
    </div>
<?php if ($data["isManager"]) { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/relational/create/"><span class="bi bi-arrow-up-right"></span>Relationsfeld anlegen</a>
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/textual/create/"><span class="bi bi-input-cursor-text"></span>Wertfeld anlegen</a>
    </div>
<?php } ?>
  </header>

  <h2 class="main-subheading">Relationsfelder</h2>

<?php if (count($data["relationalColumns"]) > 0) {?>
  <div class="table-wrapper">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">Position</th>
        <th class="table-cell">Name</th>
        <th class="table-cell">Verweist auf</th>
<?php   if ($data["isManager"]) { ?>
        <th class="table-cell"></th>
<?php   } ?>
      </tr>
<?php   $count = 0;
        foreach ($data["relationalColumns"] as $column) {
          $count += 1;?>
      <tr class="table-row">
        <td class="table-cell"><?php echo htmlentities($column->position); ?></td>
        <td class="table-cell"><?php echo htmlentities($column->label); ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name); ?></span></td>
        <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id; ?>/tables/<?php echo $column->targetTableId; ?>/"><?php echo htmlentities($column->targetTableLabel); ?></a> <span class="table-cell-code-addition"><?php echo htmlentities($column->targetTableName) ?></span></td>
<?php     if ($data["isManager"]) { ?>
        <td class="table-cell table-cell-actions"><a class="button <?php if ($count == 1) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-up"></span>Nach oben</a><a class="button <?php if ($count == count($data["relationalColumns"])) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-down"></span>Nach unten</a><a class="button button-smallest" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/relational/<?php echo $column->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a></td>
<?php     } ?>
      </tr>
<?php   } ?>
    </table>
  </div>
<?php } else { ?>
  <p>Bislang sind keine Relationsfelder angelegt.</p>
<?php } ?>

  <h2 class="main-subheading">Wertfelder</h2>

<?php if (count($data["textualColumns"]) > 0) {?>
  <div class="table-wrapper">
  <table class="table">
    <tr class="table-heading">
      <th class="table-cell">Position</th>
      <th class="table-cell">Name</th>
      <th class="table-cell">Datentyp</th>
<?php   if ($data["isManager"]) { ?>
      <th class="table-cell"></th>
<?php   } ?>
    </tr>
<?php   $count = 0;
        foreach ($data["textualColumns"] as $column) {
          $count += 1;?>
    <tr class="table-row">
      <td class="table-cell"><?php echo htmlentities($column->position); ?></td>
      <td class="table-cell"><?php echo htmlentities($column->label); ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name); ?></span></td>
      <td class="table-cell"><?php echo htmlentities($column->getTypeLabel()); ?></td>
<?php     if ($data["isManager"]) { ?>
      <td class="table-cell table-cell-actions"><a class="button <?php if ($count == 1) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-up"></span>Nach oben</a><a class="button <?php if ($count == count($data["textualColumns"])) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-down"></span>Nach unten</a><a class="button button-smallest" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/textual/<?php echo $column->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a></td>
<?php     } ?>
    </tr>
<?php   } ?>
  </table>
  </div>
<?php } else { ?>
  <p>Bislang sind keine Wertfelder angelegt.</p>
<?php } ?>
</main>
