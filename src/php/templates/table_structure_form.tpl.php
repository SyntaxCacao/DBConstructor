<main class="container container-small main-container">
  <header class="main-header">
    <h1 class="main-heading"><?php echo $data["heading"] ?></h1>
    <div class="main-header-actions">
      <a class="button button-small button-danger js-confirm" data-confirm-message="Sind Sie sicher, dass dieses Feld sowie aller dazugehörigen Daten gelöscht werden soll?" href="?delete">Feld löschen</a>
    </div>
  </header>
  <?php echo $data["form"]->generate() ?>
<?php if (isset($data["overwriteForm"])) { ?>
  <h2 class="main-subheading">Übergangslösung: Validierungskriterien überschreiben</h2>
  <?php echo $data["overwriteForm"]->generate() ?>
<?php } ?>
</main>