<?php

declare(strict_types=1);

use DBConstructor\Forms\Form;
use DBConstructor\Models\Project;

/** @var array{form: Form, project: Project} $data */

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
      <p class="main-subtitle">Projekt angelegt am <?php echo htmlentities(date("d.m.Y \u\m H:i", strtotime($data["project"]->created))); ?> Uhr</p>
    </div>
  </header>
  <?php echo $data["form"]->generate(); ?>
</main>
