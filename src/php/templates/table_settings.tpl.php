<?php

declare(strict_types=1);

use DBConstructor\Controllers\Projects\Tables\TableForm;
use DBConstructor\Models\Project;
use DBConstructor\Models\Table;
use DBConstructor\Util\HeaderGenerator;

/** @var array{form: TableForm, saved: bool, project: Project, table: Table} $data */

?>
<main class="container container-small">
<?php if ($data["saved"]) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
  </div>
<?php }

      $header = new HeaderGenerator("Einstellungen");
      $header->subtitle = "Tabelle angelegt am ".date("d.m.Y \u\m H:i", strtotime($data["table"]->created))." Uhr";

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/settings/delete/",
        "icon" => "trash3",
        "text" => "Tabelle löschen"
      ];

      $header->generate();

      echo $data["form"]->generate(); ?>
</main>
