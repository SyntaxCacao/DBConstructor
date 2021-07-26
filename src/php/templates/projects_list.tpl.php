<main class="container main-container">
  <div class="alerts">
    <?php
    if (isset($data["request"]["welcome"])) {
      echo '<div class="alert"><p>Herzlich Willkommen. Sie haben sich erfolgreich angemeldet.</p></div>';
    }
    ?>
  </div>
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Projekte</h1>
      <p class="main-subtitle">Übersicht der angelegten Projekte</p>
    </div>
<?php if (isset($data["user"]) && $data["user"]->admin) { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/create/">Neues Projekt</a>
    </div>
<?php } ?>
  </header>
  <?php if (count($data["projects"]) > 0) {?>
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">Name</th>
        <th class="table-cell">Beschreibung</th>
      </tr>
  <?php   foreach ($data["projects"] as $project) { ?>
      <tr>
        <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"]; ?>/projects/<?php echo $project->id; ?>/"><?php echo htmlentities($project->label); ?></a></td>
        <td class="table-cell"><?php if (is_null($project->description)) { ?>&ndash;<?php } else echo \DBConstructor\Util\SimpleMarkdown::removeMarkup($project->description); ?></td>
      </tr>
  <?php   } ?>
    </table>
  <?php } else { ?>
    <p>Es sind noch keine Projekte angelegt.</p>
  <?php } ?>
</main>
