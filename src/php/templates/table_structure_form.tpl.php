<main class="container container-small main-container">
  <header class="main-header">
    <h1 class="main-heading"><?php echo $data["heading"] ?></h1>
<?php if ($data["deletable"]) { ?>
    <div class="main-header-actions">
      <a class="button button-small button-danger js-confirm" data-confirm-message="Sind Sie sicher, dass dieses Feld sowie alle dazugehörigen Daten gelöscht werden sollen?" href="?delete">Feld löschen</a>
    </div>
<?php } ?>
  </header>
  <?php echo $data["form"]->generate() ?>
<?php if (isset($data["overwriteForm"])) { ?>
  <h2 class="main-subheading">Übergangslösung: Validierungskriterien überschreiben</h2>
  <?php echo $data["overwriteForm"]->generate() ?>
<?php } ?>
</main>
