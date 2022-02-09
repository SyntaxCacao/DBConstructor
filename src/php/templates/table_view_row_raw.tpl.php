<?php

declare(strict_types=1);

use DBConstructor\Util\HeaderGenerator;

/** @var array $data */

?>
<main class="container">
<?php $header = new HeaderGenerator("Datensatz #".$data["row"]->id);
      $header->subtitle = "Debug-Ansicht";
      $header->autoActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];
      $header->generate(); ?>
  <div class="row break-md">
    <div class="column width-7 markdown">
      <h2>Datensatz</h2>
      <pre><?php var_dump($data["row"]) ?></pre>
      <h2>Relationsfelder der Tabelle</h2>
      <pre><?php var_dump($data["relationalColumns"]) ?></pre>
      <h2>Relationsfelder in diesem Datensatz</h2>
      <pre><?php var_dump($data["relationalFields"]) ?></pre>
      <h2>Wertfelder der Tabelle</h2>
      <pre><?php var_dump($data["textualColumns"]) ?></pre>
      <h2>Wertfelder in diesem Datensatz</h2>
      <pre><?php var_dump($data["textualFields"]) ?></pre>
    </div>
    <div class="column width-5 markdown">
      <h2>Historie</h2>
      <pre><?php var_dump($data["actions"]) ?></pre>
    </div>
  </div>
</main>
