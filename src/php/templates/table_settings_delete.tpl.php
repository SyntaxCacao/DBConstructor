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
<?php $header = new HeaderGenerator("Tabelle löschen");
      $header->subtitle = "Tabelle angelegt am ".date("d.m.Y \u\m H:i", strtotime($data["table"]->created))." Uhr";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/settings/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      $header->generate(); ?>
  <div class="markdown">
    <p>Die Tabelle kann gelöscht werden, wenn die folgenden Voraussetzungen erfüllt sind:</p>
    <ul>
      <li>Die Tabelle enthält keine Datensätze (<strong><?= $data["rows"] ?> Datensätze</strong> in dieser Tabelle).</li>
      <li>Keine Tabelle verweist auf diese Tabelle (<strong><?= $data["references"] ?> Relationsfelder</strong> in anderen Tabellen verweisen auf diese Tabelle).</li>
      <li>Keine Eingaberoutine ist auf diese Tabelle konfiguriert (<strong><?= $data["workflowSteps"] ?> Eingabeschritte</strong> beziehen sich auf diese Tabelle).</li>
    </ul>
<?php if ($data["allow"]) { ?>
    <p>Die Voraussetzungen sind erfüllt. Die Tabelle kann gelöscht werden.</p>
  </div>
  <br>
  <a class="button" href="?delete">Löschen</a>
<?php } else { ?>
    <p>Die Voraussetzungen sind nicht erfüllt. Die Tabelle kann derzeit nicht gelöscht werden.</p>
  </div>
<?php } ?>
</main>
