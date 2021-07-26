<main class="container container-small main-container">
  <div class="alerts">
<?php if ($data["name-success"]) { ?>
    <div class="alert"><p>Ihr Name wurde geändert.</p></div>
<?php } ?>
<?php if ($data["password-success"]) { ?>
    <div class="alert"><p>Ihr Passwort wurde geändert.</p></div>
<?php } ?>
  </div>
  <div class="main-header">
    <header class="main-header-header">
      <h1 class="main-heading">Einstellungen</h1>
      <p class="main-subtitle">Erstmalige Anmeldung am <?php echo date("d.m.Y \u\m H:i", strtotime($data["user"]->firstLogin)); ?> Uhr</p>
    </header>
  </div>
  <h2 class="form-heading">Name ändern</h2>
  <?php echo $data["name-form"]->generate(); ?>
  <h2 class="form-heading">Passwort ändern</h2>
  <?php echo $data["password-form"]->generate(); ?>
</main>
