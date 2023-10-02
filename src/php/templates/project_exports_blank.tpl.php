<?php

declare(strict_types=1);

use DBConstructor\Models\Project;

/** @var array{project: Project} $data */

?>
<main class="blankslate">
  <span class="blankslate-icon bi bi-box-seam"></span>
  <h1 class="blankslate-heading">Die Datenbank ist noch nicht exportiert worden.</h1>
  <p class="blankslate-text">Die im Projekt erfassten Daten können hier zur Weiterverwendung exportiert werden.</p>
  <a class="button" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/exports/run/">Weiter</a>
</main>
