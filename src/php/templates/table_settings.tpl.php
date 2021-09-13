<?php if (isset($data["forbidden"])) { ?>
<main class="blankslate">
  <span class="blankslate-icon bi bi-x-lg"></span>
  <h1 class="blankslate-heading">Zugriff nicht gestattet</h1>
  <p class="blankslate-text">Der Zugriff auf die angeforderte Seite ist Ihnen nicht gestattet.</p>
</main>
<?php } else { ?>
<main class="container container-small main-container">
<?php   if ($data["saved"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
  </div>
<?php   } ?>
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Einstellungen</h1>
      <p class="main-subtitle">Tabelle angelegt am <?php echo htmlentities(date("d.m.Y \u\m H:i", strtotime($data["table"]->created))) ?> Uhr</p>
    </div>
  </header>
  <?php echo $data["form"]->generate(); ?>
</main>
<?php } ?>
