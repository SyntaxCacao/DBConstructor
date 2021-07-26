<main class="container container-small main-container">
  <header class="main-header">
    <h1 class="main-heading"><?php echo $data["title"]; ?></h1>
  </header>

  <?php echo $data["form"]->generate(); ?>
</main>
