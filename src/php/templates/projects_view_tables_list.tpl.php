<main class="container main-container">
<?php if (isset($data["request"]["created"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Das Projekt wurde angelegt.</p></div>
  </div>
<?php } ?>
  <div class="row break-md" style="display: block"><?php /* BUG: issue with width on small devices with display=table */ ?>
    <div class="column width-9">
      <header class="main-header">
        <div class="main-header-header">
          <h1 class="main-heading">Tabellen</h1>
          <p class="main-subtitle"><?php echo count($data["tables"]); ?> angelegte Tabellen</p>
        </div>
<?php if (isset($data["user"]) && $data["user"]->admin) /* TODO Manager */ { ?>
          <div class="main-header-actions">
            <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/create/">Neue Tabelle</a>
          </div>
<?php } ?>
      </header>
<?php if (count($data["tables"]) > 0) { ?>
      <p style="margin-bottom: 16px">Relationen können sich nur auf Tabellen beziehen, die in der Übersicht über Ihnen positioniert sind.</p>
      <div class="table-wrapper">
        <table class="table">
          <tr class="table-heading">
            <th class="table-cell">Position</th>
            <th class="table-cell">Bezeichnung</th>
            <th class="table-cell">Technischer Name</th>
            <th class="table-cell">Datensätze</th>
<?php   if (isset($data["user"]) && $data["user"]->admin) /* TODO Manager */ { ?>
            <th class="table-cell"></th>
<?php   } ?>
          </tr>
<?php   $all = count($data["tables"]);
        $count = 0;
        foreach ($data["tables"] as $table) {
          $count += 1; ?>
            <tr class="table-row">
              <td class="table-cell"><?php echo $table["obj"]->position; ?></td>
              <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"]; ?>/projects/<?php echo $data["project"]->id; ?>/tables/<?php echo $table["obj"]->id ?>/"><?php echo htmlentities($table["obj"]->label); ?></a></td>
              <td class="table-cell table-cell-code"><?php echo htmlentities($table["obj"]->name); ?></td>
              <td class="table-cell"><?php echo $table["rows"]; ?></td>
<?php     if (isset($data["user"]) && $data["user"]->admin) /* TODO Manager */ { ?>
              <td class="table-cell table-cell-actions"><a class="button <?php if ($count == 1) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-up"></span>nach oben</a><a class="button <?php if ($count == $all) { ?>button-disabled <?php } ?>button-smallest"><span class="bi bi-arrow-down"></span>nach unten</a></td>
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
      <div class="markdown"><?php if (is_null($data["project"]->description)) { ?><p>Es ist keine Beschreibung angegeben.</p><?php } else { echo /*\DBConstructor\Util\SimpleMarkdown::format($data["project"]->description);*/(new \DBConstructor\Util\MarkdownParser())->parse($data["project"]->description); } ?></div>
      <p class="page-project-created">Projekt angelegt am <?php echo htmlentities(date("d.m.Y", strtotime($data["project"]->created))); ?></p>
    </div>
  </div>
</main>
