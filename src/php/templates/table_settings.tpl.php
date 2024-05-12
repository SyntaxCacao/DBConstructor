<?php

declare(strict_types=1);

use DBConstructor\Forms\Form;
use DBConstructor\Models\Table;

/** @var array{form: Form, saved: bool, table: Table} $data */

?>
<main class="container container-small">
<?php if ($data["saved"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
  </div>
<?php } ?>
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Einstellungen</h1>
      <p class="main-subtitle">Tabelle angelegt am <?= htmlentities(date("d.m.Y \u\m H:i", strtotime($data["table"]->created))) ?> Uhr</p>
    </div>
  </header>
  <?= $data["form"]->generate() ?>
</main>
