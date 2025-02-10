<?php

declare(strict_types=1);

use DBConstructor\Controllers\Projects\Exports\NoteEditForm;
use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Util\HeaderGenerator;

/** @var array{editForm: NoteEditForm, export: Export, project: Project} $data */

?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Bemerkung bearbeiten");
      $header->subtitle = "Export #{$data["export"]->id}";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/{$data["export"]->id}/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      $header->generate();

      echo $data["editForm"]->generate(); ?>
</main>
