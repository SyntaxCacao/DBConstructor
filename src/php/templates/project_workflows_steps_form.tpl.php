<?php

declare(strict_types=1);

use DBConstructor\Util\HeaderGenerator;

/** @var array $data */ ?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Eingabeschritt ".(isset($data["step"]) ? $data["step"]->position." bearbeiten" : "anlegen"));

      if (isset($data["step"])) {
        $header->subtitle = "Der Eingabeschritt bezieht sich auf die Tabelle ".$data["step"]->tableLabel;
      }

      $header->buttonActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/workflows/".$data["workflow"]->id."/steps/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      if (isset($data["step"])) {
        $header->dropdownActions[] = [
          "href" => "?delete",
          "icon" => "node-minus",
          "text" => "Löschen",
          "confirm" => "Sind Sie sicher, dass dieser Eingabeschritt gelöschen werden soll?"
        ];
      }

      $header->generate();

      echo $data["form"]->generate(); ?>
</main>
