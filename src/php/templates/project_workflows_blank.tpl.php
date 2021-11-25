<main class="blankslate">
  <span class="blankslate-icon bi bi-pencil"></span>
  <h1 class="blankslate-heading">Es sind keine Eingaberoutinen angelegt.</h1>
  <p class="blankslate-text">Komplexe Erfassungsvorgänge können als Eingaberoutinen gespeichert werden.</p>
<?php if ($data["isManager"]) { ?>
  <a class="button" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/create/">Eingaberoutine anlegen</a>
<?php } ?>
</main>
