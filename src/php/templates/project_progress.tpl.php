<?php

declare(strict_types=1);

use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\Project;
use DBConstructor\Util\HeaderGenerator;

/** @var array{baseurl: string, filter: Form, isManager: boolean, participants: array<Participant>, project: Project, version: string} $data */

$header = new HeaderGenerator("Erfassungsfortschritt");
$header->subtitle = number_format($data["total"], 0, ",", ".")." Datensätze erfasst";

if ($data["totalUser"] > 0) {
  $header->subtitle .= " · ".number_format($data["totalUser"], 0, ",", ".")." von Ihnen";
}

?>
<?php if ($data["isManager"]) { ?>
<div class="container">
  <div class="row break-lg">
    <div class="column width-3 hide-down-lg"><br></div>
    <div class="column width-9">
      <?php $header->generate(); ?>
    </div>
  </div>
  <div class="row break-lg">
    <div class="column width-3">
      <div class="box">
<?php   $progressPages = [
          "/" => "Gesamtfortschritt",
          "/participants/" => "Erfassungsgeschwindigkeit einzelner Bearbeiter",
          "/tabular/" => "Erfassungsfortschritt einzelner Bearbeiter tabellarisch"
        ];
        foreach ($progressPages as $link => $label) { ?>
        <a class="box-row box-link<?= $data["currentProgressPage"] === $link ? " current" : "" ?>" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/progress<?= $link ?>"><?= $label ?></a>
<?php   } ?>
      </div>
    </div>
    <div class="column width-9">
<?php   if ($data["currentProgressPage"] === "/") {
          require "project_progress_total_inner.tpl.php";
        } else if ($data["currentProgressPage"] === "/participants/") {
          if (count($data["progress"]["weeks"]) < 3) { ?>
      <div class="blankslate">
        <h1 class="blankslate-heading">Nichts zu sehen</h1>
        <p class="blankslate-text">Es sind nicht genügend Daten für die grafische Darstellung vorhanden.</p>
      </div>
<?php     } else { ?>
      <?= $data["filter"]->generate() ?>
      <div class="main-subheader">
        <h2 class="main-subheading">Erfassungsgeschwindigkeit der einzelnen Bearbeiter</h2>
      </div>
      <div id="js-progress-chart-by-user"></div>
<?php     }
        } else if ($data["currentProgressPage"] === "/tabular/") { ?>
      <?= $data["filter"]->generate() ?>
      <div class="main-subheader">
        <h2 class="main-subheading">Von den einzelnen Bearbeitern angelegte Datensätze</h2>
      </div>
      <p style="margin-bottom: 16px">Gezeigt werden die von den einzelnen Bearbeitern zum Ablauf des gewählten Stichtags insgesamt angelegten Datensätze.</p>
      <div class="table-wrapper">
        <table class="table">
          <tr class="table-heading">
            <th class="table-cell"></th>
            <th class="table-cell">Anzahl</th>
            <th class="table-cell">Anteil in %</th>
          </tr>
<?php     foreach ($data["participants"] as $participant) {
            if (! isset($data["totals"][$participant->userId])) continue; ?>
          <tr class="table-row">
            <td class="table-cell"><?= htmlentities($participant->lastName.", ".$participant->firstName) ?></td>
            <td class="table-cell table-cell-numeric"><?= number_format($data["totals"][$participant->userId], 0, ",", ".") ?></td>
            <td class="table-cell table-cell-numeric"><?= number_format($data["percentages"][$participant->userId], 1, ",", ".") ?></td>
          </tr>
<?php     } ?>
          <tr class="table-heading">
            <th class="table-cell"><strong>Gesamt</strong></th>
            <th class="table-cell table-cell-numeric"><?= number_format($data["allUsers"], 0, ",", ".") ?></th>
            <th class="table-cell table-cell-numeric">100,0</th>
          </tr>
        </table>
      </div>
<?php   } ?>
    </div>
  </div>
</div>
<?php } else { ?>
<div class="container container-small">
<?php $header->generate();
      require "project_progress_total_inner.tpl.php"; ?>
</div>
<?php }
      if (isset($data["progress"]) && ($json = json_encode($data["progress"])) !== false) { ?>
<script type="text/javascript">
  const progressData = <?= $json ?>;
</script>
<?php } ?>
<script src="<?= $data["baseurl"] ?>/assets/build-charts-<?= $data["version"] ?>.min.js"></script>
