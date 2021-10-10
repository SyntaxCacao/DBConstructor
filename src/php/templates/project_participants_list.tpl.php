<div class="container">
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Beteiligte</h1>
      <p class="main-subtitle"><?php echo count($data["participants"]) ?> Benutzer hinzugefügt</p>
    </div>
<?php if ($data["isManager"]) { ?>
    <div class="main-header-actions">
      <a class="button button-small<?php if ($data["notParticipatingCount"] == 0) { ?> button-disabled"<?php } else { ?>" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/participants/add/"<?php } ?>><span class="bi bi-person-plus"></span>Benutzer hinzufügen</a>
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
<?php if ($data["isManager"]) { ?>
        <th class="table-cell"></th>
<?php } ?>
      </tr>
<?php foreach($data["participants"] as $participant) { ?>
      <tr class="table-row">
        <td class="table-cell"><?php echo htmlentities($participant->lastName.", ".$participant->firstName); if ($participant->locked) echo " <em>(gesperrt)</em>" ?></td>
        <td class="table-cell"><?php if ($participant->isManager) { ?>Manager<?php } else { ?>Beteiligter<?php } ?></td>
        <td class="table-cell"><?php echo htmlentities(date("d.m.Y H:i", strtotime($participant->added))) ?></td>
<?php   if ($data["isManager"]) { ?>
        <td class="table-cell table-cell-actions">
<?php     if ($participant->isManager) { ?>
          <a class="button <?php if ($data["managerCount"] <= 1) echo "button-disabled " ?>button-smallest js-confirm"<?php if ($data["managerCount"] > 1) echo ' href="?demote='.$participant->id.'"' ?> data-confirm-message="Sind Sie sicher, dass <?php echo htmlentities($participant->firstName." ".$participant->lastName) ?> nicht weiter Manager sein soll?"><span class="bi bi-arrow-down"></span>Zurückstufen</a>
<?php     } else {?>
          <a class="button button-smallest js-confirm" href="?promote=<?php echo $participant->id ?>" data-confirm-message="Sind Sie sicher, dass Sie <?php echo htmlentities($participant->firstName." ".$participant->lastName) ?> zum Manager machen wollen?"><span class="bi bi-arrow-up"></span>Befördern</a>
<?php     } ?>
          <a class="button <?php if ($participant->isManager && $data["managerCount"] <= 1) echo "button-disabled " ?>button-smallest js-confirm"<?php if (! $participant->isManager || $data["managerCount"] > 1) echo ' href="?remove='.$participant->id.'"' ?> data-confirm-message="Sind Sie sicher, dass Sie <?php echo htmlentities($participant->firstName." ".$participant->lastName) ?> aus diesem Projekt entfernen möchten?"><span class="bi bi-person-x"></span>Entfernen</a>
        </td>
<?php   } ?>
      </tr>
<?php } ?>
    </table>
  </div>
</div>
