<main class="blankslate">
  <span class="blankslate-icon bi bi-diagram-3"></span>
  <h1 class="blankslate-heading">Diese Eingaberoutine wurde noch nie ausgeführt.</h1>
  <p class="blankslate-text">Hier wird nach der ersten Ausführung eine Übersicht aller Ausführungen angezeigt.</p>
<?php if ($data["workflow"]->active) { ?>
  <a class="button" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $data["workflow"]->id ?>/">Eingaberoutine ausführen</a>
<?php } ?>
</main>
