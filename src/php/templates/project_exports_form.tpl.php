<?php

declare(strict_types=1);

use DBConstructor\Forms\Form;
use DBConstructor\Util\HeaderGenerator;

/** @var array{form: Form} $data */

?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Export durchführen");
      $header->subtitle = number_format($data["validCount"], 0, ",", ".")." exportierbare Datensätze in diesem Projekt";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      $header->generate();

      if ($data["invalidCount"] > 0) { ?>
  <div class="alerts">
    <div class="alert"><p>Dieses Projekt enthält <strong><?= number_format($data["invalidCount"], 0, ",", ".") ?> ungültige Datensätze</strong>, die nicht mit exportiert werden.</p></div>
  </div>
<?php }

      echo $data["form"]->generate(); ?>
  <div class="page-project-export-processing"></div>
</main>
