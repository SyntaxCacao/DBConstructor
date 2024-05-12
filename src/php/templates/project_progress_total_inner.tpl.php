<?php

declare(strict_types=1);

/** @var array $data */

?>
<?= $data["filter"]->generate() ?>
<?php if (count($data["progress"]) < 3) { ?>
  <div class="blankslate">
    <h1 class="blankslate-heading">Nichts zu sehen</h1>
    <p class="blankslate-text">Es sind nicht genügend Daten für die grafische Darstellung vorhanden.</p>
  </div>
<?php } else { ?>
<div class="main-subheader">
  <h2 class="main-subheading">Gesamtfortschritt: Angelegte Datensätze kumulativ</h2>
</div>
<div id="js-progress-chart-total"></div>
<div class="main-subheader">
  <h2 class="main-subheading">Erfassungsgeschwindigkeit: Angelegte Datensätze pro Woche</h2>
</div>
<div id="js-progress-chart-weekly"></div>
<?php } ?>
