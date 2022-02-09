<?php

declare(strict_types=1);

use DBConstructor\Util\HeaderGenerator;

/** @var array $data */

?>
<main class="container">
  <?php $header = new HeaderGenerator("Datensatz #".$data["row"]->id);
        $header->subtitle = "Neuvalidierung";
        $header->autoActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/",
          "icon" => "arrow-left",
          "text" => "Zurück"
        ];
        $header->generate(); ?>
  <div class="markdown">
    <p>Der Datensatz wurde neu validiert. Nachstehend ist die gespeicherte Bewertung des Datensatzes und seiner Felder vor und nach der Neuvalidierung aufgeführt.</p>
    <h2>Datensatz insgesamt</h2>
    <p>Hier soll <code>true</code> stehen, wenn alle darunterstehenden Werte auch <code>true</code> sind.</p>
    <div class="row break-md">
      <div class="column width-6 markdown">
        <h3>Vorher</h3>
        <pre><?php var_dump($data["rowValidBefore"]) ?></pre>
      </div>
      <div class="column width-6 markdown">
        <h3>Nachher</h3>
        <pre><?php var_dump($data["rowValidAfter"]) ?></pre>
      </div>
    </div>
    <h2>Relationsfelder</h2>
    <div class="row break-md">
      <div class="column width-6 markdown">
        <h3>Vorher</h3>
        <pre><?php var_dump($data["relFieldsValidBefore"]) ?></pre>
      </div>
      <div class="column width-6 markdown">
        <h3>Nachher</h3>
        <pre><?php var_dump($data["relFieldsValidAfter"]) ?></pre>
      </div>
    </div>
    <h2>Wertfelder</h2>
    <div class="row break-md">
      <div class="column width-6 markdown">
        <h3>Vorher</h3>
        <pre><?php var_dump($data["textFieldsValidBefore"]) ?></pre>
      </div>
      <div class="column width-6 markdown">
        <h3>Nachher</h3>
        <pre><?php var_dump($data["textFieldsValidAfter"]) ?></pre>
      </div>
    </div>
  </div>
</main>
