<main class="container">
<?php if ($data["success"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Der Datensatz wurde angelegt.</p></div>
  </div>
<?php } ?>
  <header class="main-header">
    <h1 class="main-heading">Datensatz anlegen</h1>
  </header>
<?php $data["form"]->generate() ?>
</main>
