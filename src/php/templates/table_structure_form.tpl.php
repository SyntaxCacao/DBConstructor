<main class="container container-small">
  <?php if (isset($data["isEmpty"]) && ! $data["isEmpty"]) { ?>
    <div class="alerts">
      <div class="alert alert-error"><p><strong>Wichtig:</strong> Diese Tabelle enthält bereits Datensätze. Das Anlegen einer neuen Spalte sowie das Bearbeiten oder Löschen bestehender Spalten führt dazu, dass alle bestehenden Datensätze in dieser Tabelle sowie Datensätze aus anderen Tabellen, die auf Datensätze in dieser Tabelle verweisen, neu validiert werden müssen. Je nach Zahl der betroffenen Datensätze kann dies längere Zeit in Anspruch nehmen. Sollte der Vorgang abgebrochen werden oder es zu einem Fehler kommen, muss eine manuelle Neuvalidierung erfolgen.</p></div>
    </div>
  <?php } ?>
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
