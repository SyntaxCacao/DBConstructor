<?php

declare(strict_types=1);

use DBConstructor\Models\Export;
use DBConstructor\Models\Project;

/** @var array{export: Export, project: Project} $data */

?>
<main class="blankslate">
  <span class="blankslate-icon bi bi-check"></span>
  <h1 class="blankslate-heading">Der Export ist abgeschlossen.</h1>
  <p class="blankslate-text">Die Exportdaten können nun heruntergeladen werden.</p>
  <div class="blankslate-buttons">
    <a class="button" href="<?= $data["baseurl"] ?>/exports/<?= $data["export"]->id ?>/<?= $data["export"]->getFileName() ?>.zip"><span class="bi bi-download"></span>Herunterladen</a><!--
 --><a class="button" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/exports/<?= $data["export"]->id ?>/"><span class="bi bi-folder2-open"></span>Öffnen</a><!--
 --><a class="button" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/exports/"><span class="bi bi-list"></span>Zur Übersicht</a>
  </div>
</main>
