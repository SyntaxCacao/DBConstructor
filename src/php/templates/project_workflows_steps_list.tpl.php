<?php

declare(strict_types=1);

use DBConstructor\Models\WorkflowStep;
use DBConstructor\Util\HeaderGenerator;

/** @var array $data */

?>
<main class="container">
<?php if (isset($_REQUEST["saved"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
  </div>
<?php } else if (isset($_REQUEST["deleted"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Der Eingabeschritt wurde gelöscht.</p></div>
  </div>
<?php }
      $header = new HeaderGenerator($data["workflow"]->label.($data["workflow"]->active ? "" : " (deaktiviert)"));
      $header->subtitle = "Zuletzt bearbeitet von ".$data["workflow"]->lastEditorFirstName." ".$data["workflow"]->lastEditorLastName." am ".date("d.m.Y \u\m H:i", strtotime($data["workflow"]->lastUpdated))." Uhr";

      if ($data["workflow"]->active) {
        $header->autoActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/workflows/".$data["workflow"]->id."/steps/?deactivate",
          "icon" => "pause-fill",
          "text" => "Deaktivieren"
        ];
      } else {
        $header->autoActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/workflows/".$data["workflow"]->id."/steps/?activate",
          "icon" => "play",
          "text" => "Aktivieren"
        ];
      }

      $header->autoActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/workflows/".$data["workflow"]->id."/steps/create/",
        "icon" => "node-plus",
        "text" => "Eingabeschritt anlegen"
      ];

      $header->autoActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/workflows/".$data["workflow"]->id."/edit/",
        "icon" => "pencil",
        "text" => "Bearbeiten"
      ];

      $header->generate(); ?>
  <div class="box">
<?php foreach ($data["list"] as $step) {
  /** @var WorkflowStep $step */ ?>
    <div class="box-row box-row-flex page-project-list-box">
      <div class="box-row-flex-extend">
        <h3 class="page-project-list-label"><?= htmlentities($step->getLabel()) ?></h3>
        <p class="page-project-list-description">Bezieht sich auf <a href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $step->tableId ?>/"><?= htmlentities($step->tableLabel) ?></a></p>
      </div>
      <div class="box-row-flex-conserve">
        <a class="button button-small" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $data["workflow"]->id ?>/steps/<?= $step->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a>
      </div>
    </div>
<?php } ?>
  </div>
</main>
