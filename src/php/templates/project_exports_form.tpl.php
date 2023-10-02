<?php

declare(strict_types=1);

use DBConstructor\Forms\Form;

/** @var array{form: Form} $data */

?>
<main class="container container-small">
  <header class="main-header">
    <h1 class="main-heading">Export durchführen</h1>
  </header>
  <?= $data["form"]->generate() ?>
  <div class="page-project-export-processing"></div>
</main>
