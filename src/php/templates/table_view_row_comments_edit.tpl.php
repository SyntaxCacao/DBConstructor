<?php

declare(strict_types=1);

use DBConstructor\Controllers\Projects\Tables\View\CommentEditForm;
use DBConstructor\Models\Project;
use DBConstructor\Models\RowAction;
use DBConstructor\Models\Table;
use DBConstructor\Util\HeaderGenerator;

/** @var array{action: RowAction, baseurl: string, form: CommentEditForm, project: Project, table: Table} $data */

?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Kommentar bearbeiten");
      $header->subtitle = "Von {$data["action"]->userFirstName} {$data["action"]->userLastName} am ".date("d.m.Y \u\m H:i", strtotime($data["action"]->created))." Uhr";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["action"]->rowId}/#comment-{$data["action"]->id}",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["action"]->rowId}/?deleteComment={$data["action"]->id}",
        "icon" => "trash3",
        "text" => "Kommentar löschen",
        "confirm" => "Sind Sie sicher?",
        "danger" => true
      ];

      $header->generate();

      echo $data["form"]->generate(); ?>
</main>
