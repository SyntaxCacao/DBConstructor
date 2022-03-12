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
            <th class="table-cell">Datensätze</th>
<?php   if ($data["isManager"]) { ?>
            <th class="table-cell"></th>
<?php   } ?>
          </tr>
<?php   $all = count($data["tables"]);
        $count = 0;
        foreach ($data["tables"] as $table) {
          $count += 1; ?>
          <tr class="table-row">
            <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"]; ?>/projects/<?php echo $data["project"]->id; ?>/tables/<?php echo $table["obj"]->id ?>/"><?php echo htmlentities($table["obj"]->label); ?></a></td>
            <td class="table-cell table-cell-code"><?php echo htmlentities($table["obj"]->name); ?></td>
            <td class="table-cell"><?php echo $table["rows"]; ?></td>
<?php     if ($data["isManager"]) { ?>
            <td class="table-cell table-cell-actions"><a class="button button-smallest" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $table["obj"]->id ?>/settings/"><span class="bi bi-pencil"></span>Bearbeiten</a></td>
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
        <p><?php if (is_null($data["project"]->description)) { ?>Es ist keine Beschreibung angegeben.<?php } else { echo htmlentities($data["project"]->description); } ?></p>
        <p class="page-project-created">Projekt angelegt am <?php echo htmlentities(date("d.m.Y", strtotime($data["project"]->created))); ?></p>
      </div>
    </div>
  </div>
</main>
