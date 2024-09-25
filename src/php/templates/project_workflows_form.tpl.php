<main class="container container-small">
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Eingaberoutine <?= isset($data["workflow"]) ? "bearbeiten" : "anlegen" ?></h1>
<?php if (isset($data["workflow"])) { ?>
      <p class="main-subtitle">Angelegt am <?= htmlentities(date("d.m.Y \u\m H:i", strtotime($data["workflow"]->created))) ?> Uhr</p>
<?php } ?>
    </div>
<?php if (isset($data["workflow"])) { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $data["workflow"]->id ?>/steps/"><span class="bi bi-arrow-left"></span>Zurück</a>
    </div>
<?php } ?>
  </header>
  <?= $data["form"]->generate() ?>
</main>
