<?php

declare(strict_types=1);

use DBConstructor\Models\Workflow;
use DBConstructor\Util\HeaderGenerator;

/** @var array $data */ ?>
<main class="container">
<?php if (isset($_REQUEST["inserted"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Daten wurden gespeichert.</p></div>
  </div>
<?php }

      $header = new HeaderGenerator("Eingaberoutinen");
      $header->subtitle = count($data["list"])." Eingaberoutine".(count($data["list"]) === 1 ? "" : "n")." ".($data["isManager"] ? "angelegt" : "verfügbar");

      if ($data["isManager"]) {
        $header->autoActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/workflows/create/",
          "icon" => "plus-circle",
          "text" => "Eingaberoutine anlegen"
        ];
      }

      $header->generate(); ?>
  <div class="box">
<?php foreach ($data["list"] as $workflow) {
        /** @var Workflow $workflow */ ?>
    <div class="box-row box-row-flex page-project-list-box">
      <div class="box-row-flex-extend">
        <h3 class="page-project-list-label"><?= htmlentities($workflow->label) ?><?= $workflow->active ? "" : " (deaktiviert)" ?></h3>
<?php   if ($workflow->description !== null) { ?>
        <p class="page-project-list-description"><?= htmlentities($workflow->description) ?></p>
<?php   } ?>
      </div>
      <div class="box-row-flex-conserve">
<?php   if ($workflow->active) { ?>
        <a class="button button-small" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $workflow->id ?>/"><span class="bi bi-play-circle"></span>Ausführen</a>
<?php   } ?>
        <a class="button button-small" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $workflow->id ?>/executions/" style="margin-left: 4px"><span class="bi bi-list"></span>Ausführungen</a>
<?php   if ($data["isManager"]) { ?>
        <a class="button button-small" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $workflow->id ?>/steps/" style="margin-left: 4px"><span class="bi bi-pencil"></span>Bearbeiten</a>
<?php   } ?>
        <!--
        <details class="dropdown" style="margin-left: 4px">
          <summary><span class="button button-small"><span class="bi bi-three-dots" style="margin-right: 0"></span></span></summary>
          <ul class="dropdown-menu dropdown-menu-down dropdown-menu-left">
            <li class="dropdown-item">
              <a class="dropdown-link" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $workflow->id ?>/executions/"><span class="bi bi-list"></span>Ausführungen anzeigen</a>
            </li>
<?php   if ($data["isManager"]) { ?>
            <li class="dropdown-item">
              <a class="dropdown-link" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/workflows/<?= $workflow->id ?>/steps/"><span class="bi bi-pencil"></span>Bearbeiten</a>
            </li>
<?php   } ?>
          </ul>
        </details>
        -->
      </div>
    </div>
<?php } ?>
  </div>
</main>
