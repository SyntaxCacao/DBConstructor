<main class="container container-small main-container">
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Seite <?php echo $data["editMode"] ? "bearbeiten" : "anlegen" ?></h1>
    </div>
  </header>
  <?php echo $data["form"]->generate(); ?>
</main>
