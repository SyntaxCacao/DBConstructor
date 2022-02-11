<?php

declare(strict_types=1);

/** @var array $data */

?>
<main class="container container-small">
  <div class="main-header">
    <header class="main-header-header">
      <h1 class="main-heading"><?= $data["title"] ?></h1>
<?php if (isset($data["edituser"])) { ?>
      <p class="main-subtitle">Angelegt am <?= date("d.m.Y \u\m H:i", strtotime($data["edituser"]->created)) ?> Uhr<?php if (isset($data["edituser"]->creatorFirstName)) { ?> von <?= htmlentities($data["edituser"]->creatorFirstName." ".$data["edituser"]->creatorLastName) ?><?php } ?></p>
<?php } ?>
    </header>
  </div>

  <?= $data["form"]->generate() ?>
</main>
