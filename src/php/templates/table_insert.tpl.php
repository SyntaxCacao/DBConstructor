<main class="container">
<?php if ($data["success"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Der Datensatz wurde angelegt.</p></div>
  </div>
<?php } ?>
  <header class="main-header">
    <h1 class="main-heading">Datensatz anlegen</h1>
  </header>
<?php echo $data["form"]->generateStartingTag();

      foreach ($data["form"]->relationalColumns as $column) {
        /** @var \DBConstructor\Models\RelationalColumn $column */
        $column->generateInput($data["form"]->relationalColumnFields[$column->name]);
      }

      foreach ($data["form"]->textualColumns as $column) {
        /** @var \DBConstructor\Models\TextualColumn $column */
        $column->generateInput($data["form"]->textualColumnFields[$column->name]);
      }

      echo '<hr style="margin: 32px 0">';

      echo $data["form"]->generateAdditionalFields();

      echo $data["form"]->generateActions();

      echo $data["form"]->generateClosingTag(); ?>
</main>
