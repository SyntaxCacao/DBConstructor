<?php

declare(strict_types=1);

use DBConstructor\Util\HeaderGenerator;

/** @var array $data */

?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Dateien hochladen");
      $header->autoActions[] = [
          "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/",
          "icon" => "arrow-left",
          "text" => "Zurück"
      ];
      $header->generate(); ?>
  <div class="upload" data-upload-path="attachment/<?= $data["row"]->id ?>">
    <div class="box blankslate upload-zone">
      <span class="blankslate-icon bi bi-upload"></span>
      <h3 class="blankslate-heading">Dateien hierhin ziehen oder auswählen</h3>
      <label class="button" for="upload-1-input"><span class="bi bi-file-earmark-text"></span>Dateien auswählen</label>
      <input id="upload-1-input" class="hide upload-input" type="file" multiple>
    </div>
    <div class="box upload-list"></div>
  </div>
</main>
