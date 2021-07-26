<div class="container main-container">
<?php if ($data["success"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Datenbank wurde exportiert.</p></div>
  </div>
<?php } ?>
  <header class="main-header">
    <h1 class="main-heading">Bisherige Exporte</h1>
  </header>
<?php if (count($data["exports"]) > 0) { ?>
</div>
<div class="container-expandable-outer">
  <div class="container-expandable-inner">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">ID</th>
        <th class="table-cell">Format</th>
        <th class="table-cell">Zeitpunkt</th>
        <th class="table-cell">Durchgeführt von</th>
        <th class="table-cell">Bemerkung</th>
        <th class="table-cell"></th>
      </tr>
<?php   foreach ($data["exports"] as $export) { ?>
        <tr class="table-row">
          <td class="table-cell table-cell-numeric"><?php echo $export->id; ?></td>
          <td class="table-cell"><?php echo htmlentities($export->getFormatLabel()); ?></td>
          <td class="table-cell"><?php echo htmlentities(date("d.m.Y H:i", strtotime($export->created))); ?></td>
          <td class="table-cell"><?php echo htmlentities($export->userFirstName." ".$export->userLastName); ?></td>
          <td class="table-cell"><?php if (is_null($export->note)) { ?>&ndash;<?php } else { echo htmlentities($export->note); } ?></td>
          <td class="table-cell table-cell-actions"><a class="button <?php if ($export->deleted) echo "button-disabled "; ?>button-smallest"<?php if (! $export->deleted) { ?> href="<?php echo $data["baseurl"]."/exports/$export->id/".$export->getFileName().".zip"; ?>" download<?php } ?>><span class="bi bi-download"></span>Herunterladen</a><?php if (! $export->deleted) { ?><a class="button button-smallest"><span class="bi bi-x-lg"></span>Löschen</span></a><?php } ?></td>
        </tr>
<?php   } ?>
    </table>
  </div>
</div>
<div class="container">
<?php } else { ?>
  <p>Bislang wurden keine Datenbankexporte durchgeführt.</p>
<?php } ?>
  <header class="main-header">
    <h1 class="main-heading">Export durchführen</h1>
  </header>
<?php echo $data["form"]->generate(); ?>
</div>
