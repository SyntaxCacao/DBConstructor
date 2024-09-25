<?php

declare(strict_types=1);

use DBConstructor\Util\HeaderGenerator;

/** @var array $data */

?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Referenzen umleiten");
      $header->subtitle = "Alle Referenzen auf diesen Datensatz umleiten auf einen anderen Datensatz";
      $header->autoActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/references/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];
      $header->generate();

      echo $data["form"]->generate() ?>
</main>
