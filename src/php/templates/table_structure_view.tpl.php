<main class="container main-container">
<?php if (isset($data["request"]["created"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Tabelle wurde angelegt.</p></div>
  </div>
<?php } else if (isset($data["request"]["columncreated"])) {?>
  <div class="alerts">
    <div class="alert"><p>Die Spalte wurde angelegt.</p></div>
  </div>
<?php } ?>

  <header class="main-header">
    <h1 class="main-heading">Struktur</h1>
<?php if (isset($data["user"]) && $data["user"]->admin) /* TODO Manager */ { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/create/?type=relational">Neue Relationsspalte</a>
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/create/?type=textual">Neue Wertspalte</a>
    </div>
<?php } ?>
  </header>

  <h2 class="main-subheading">Relationsspalten</h2>

<?php if (count($data["relationalcolumns"]) > 0) {?>
  <div class="table-wrapper">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">Position</th>
        <th class="table-cell">Name</th>
        <th class="table-cell">Verweist auf</th>
        <th class="table-cell">Erläuterung</th>
        <th class="table-cell"></th>
      </tr>
<?php   $count = 0;
        foreach ($data["relationalcolumns"] as $column) {
          $count += 1;?>
      <tr class="table-row">
        <td class="table-cell"><?php echo htmlentities($column->position); ?></td>
        <td class="table-cell"><?php echo htmlentities($column->label); ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name); ?></span></td>
        <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id; ?>/tables/<?php echo $column->targetTableId; ?>/"><?php echo htmlentities($column->targetTableLabel); ?></a> <span class="table-cell-code-addition"><?php echo htmlentities($column->targetTableName) ?></span></td>
        <td class="table-cell"><?php if (isset($column->description)) { echo htmlentities($column->description); } else { ?>&ndash;<?php } ?></td>
<?php     if (isset($data["user"]) && $data["user"]->admin) /* TODO Manager */ { ?>
        <td class="table-cell table-cell-actions"><a class="button <?php if ($count == 1) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-up"></span>nach oben</a><a class="button <?php if ($count == count($data["relationalcolumns"])) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-down"></span>nach unten</a><a class="button button-smallest"><span class="bi bi-pencil"></span>bearbeiten</a></td>
<?php     } ?>
      </tr>
    </table>
<?php   } ?>
  </div>
<?php } else { ?>
  <p>Bislang sind keine Relationsspalten angelegt.</p>
<?php } ?>

  <h2 class="main-subheading">Wertspalten</h2>

<?php if (count($data["textualcolumns"]) > 0) {?>
  <div class="table-wrapper">
  <table class="table">
    <tr class="table-heading">
      <th class="table-cell">Position</th>
      <th class="table-cell">Name</th>
      <th class="table-cell">Datentyp</th>
      <th class="table-cell">Erläuterung</th>
      <th class="table-cell"></th>
    </tr>
<?php   $count = 0;
        foreach ($data["textualcolumns"] as $column) {
          $count += 1;?>
    <tr class="table-row">
      <td class="table-cell"><?php echo htmlentities($column->position); ?></td>
      <td class="table-cell"><?php echo htmlentities($column->label); ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name); ?></span></td>
      <td class="table-cell"><?php echo htmlentities($column->getTypeLabel()); ?></td>
      <td class="table-cell"><?php if (isset($column->description)) { echo htmlentities($column->description); } else { ?>&ndash;<?php } ?></td>
<?php     if (isset($data["user"]) && $data["user"]->admin) /* TODO Manager */ { ?>
      <td class="table-cell table-cell-actions"><a class="button <?php if ($count == 1) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-up"></span>nach oben</a><a class="button <?php if ($count == count($data["textualcolumns"])) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-down"></span>nach unten</a><a class="button button-smallest"><span class="bi bi-pencil"></span>bearbeiten</a></td>
<?php     } ?>
    </tr>
<?php   } ?>
  </table>
  </div>
<?php } else { ?>
  <p>Bislang sind keine Wertspalten angelegt.</p>
<?php } ?>
</main>
