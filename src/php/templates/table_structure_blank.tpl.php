<main class="blankslate">
  <span class="blankslate-icon bi bi-diagram-3"></span>
  <h1 class="blankslate-heading">Die Tabelle wurde angelegt.</h1>
<?php if ($data["isManager"]) { ?>
  <p class="blankslate-text">Setzen Sie die Einrichtung fort, indem Sie zunächst einige Felder anlegen.</p>
  <div class="blankslate-buttons">
    <a class="button" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/relational/create/">Relationsfeld anlegen</a>
    <a class="button" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/textual/create/">Wertfeld anlegen</a>
  </div>
<?php } else { ?>
  <p class="blankslate-text">Ein Manager muss für diese Tabelle zunächst einige Felder anlegen.</p>
<?php } ?>
</main>
