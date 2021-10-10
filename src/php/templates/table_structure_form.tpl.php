<main class="container container-small">
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading"><?php echo $data["heading"] ?></h1>
<?php if (isset($data["column"])) { ?>
      <p class="main-subtitle">Feld angelegt am <?php echo htmlentities(date("d.m.Y \u\m H:i", strtotime($data["column"]->created))) ?> Uhr</p>
<?php } ?>
    </div>
<?php if (isset($data["column"])) { ?>
    <div class="main-header-actions">
      <a class="button button-small button-danger js-confirm" data-confirm-message="Sind Sie sicher, dass dieses Feld sowie alle dazugehörigen Daten gelöscht werden sollen?" href="?delete">Feld löschen</a>
    </div>
<?php } ?>
  </header>
  <?php echo $data["form"]->generate() ?>
</main>
