<div class="container main-container">
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Beteiligte</h1>
      <p class="main-subtitle"><?php echo count($data["participants"]); ?> Beteiligte<?php if (count($data["participants"]) == 1) echo "r"; ?></p>
    </div>
<?php if (\DBConstructor\Application::$instance->hasAdminPermissions()) { // TODO: Manager? ?>
    <div class="main-header-actions">
      <a class="button button-small" href="#"><span class="bi bi-person-plus"></span>Benutzer hinzufügen</a>
    </div>
<?php } ?>
  </header>
</div>
<div class="container-expandable-outer">
  <div class="container-expandable-inner">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">Name</th>
        <th class="table-cell">Rolle</th>
        <th class="table-cell">Hinzugefügt</th>
<?php if (\DBConstructor\Application::$instance->hasAdminPermissions()) { // TODO: Manager? ?>
        <th class="table-cell"></th>
<?php } ?>
      </tr>
<?php foreach($data["participants"] as $participant) { ?>
      <tr>
        <td class="table-cell"><?php echo htmlentities($participant->userFirstName." ".$participant->userLastName); ?></td>
        <td class="table-cell"><?php if ($participant->isManager) { ?>Manager<?php } else { ?>Beteiligter<?php } ?></td>
        <td class="table-cell"><?php echo htmlentities(date("d.m.Y H:i", strtotime($participant->added))); ?></td>
<?php   if (\DBConstructor\Application::$instance->hasAdminPermissions()) { // TODO: Manager? ?>
        <td class="table-cell table-cell-actions">
<?php     if ($participant->isManager) { // TODO: Manager? ?>
          <a class="button <?php if ($participant->userId == $data["user"]->id) echo "button-disabled "; ?>button-smallest"><span class="bi bi-arrow-down"></span>Zurückstufen</a>
<?php     } else {?>
          <a class="button button-smallest"><span class="bi bi-arrow-up"></span>Befördern</a>
<?php     } ?>
          <a class="button <?php if ($participant->userId == $data["user"]->id) echo "button-disabled "; ?>button-smallest"><span class="bi bi-person-x"></span>Entfernen</a>
        </td>
<?php   } ?>
      </tr>
<?php } ?>
    </table>
  </div>
</div>
