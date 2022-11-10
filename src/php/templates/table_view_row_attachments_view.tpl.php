<?php

declare(strict_types=1);

use DBConstructor\Models\RowAttachment;
use DBConstructor\Util\HeaderGenerator;
use DBConstructor\Util\MarkdownParser;

/** @var array{attachment: RowAttachment} $data */

?>
<main class="container">
<?php $header = new HeaderGenerator($data["attachment"]->fileName);
      $header->subtitle = "Von {$data["attachment"]->uploaderFirstName} {$data["attachment"]->uploaderLastName} am ".date("d.m.Y \u\m H:i", strtotime($data["attachment"]->created))." Uhr hochgeladen";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/attachments/download/{$data["attachment"]->fileName}/",
        "download" => true,
        "icon" => "download",
        "text" => "Herunterladen"
      ];

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/?deleteAttachment={$data["attachment"]->id}",
        "icon" => "trash",
        "text" => "Löschen",
        "confirm" => "Sind Sie sicher?"
      ];

      $header->generate();

      if ($data["viewType"] === "csv") { ?>
  <p style="margin-bottom: 16px">Diese Vorschau kann fehlerhaft sein, wenn die hochgeladene Datei nicht dem erwarteten Format entspricht oder nicht für den richtigen Zeichensatz kodiert wurde.</p>
  <div class="table-wrapper">
    <table class="table page-table-view-attachments-view-table">
      <thead>
        <tr class="table-heading">
<?php   $stream = fopen($data["file"], "r");
        $tableHeading = fgetcsv($stream);
        foreach ($tableHeading as $cell) { ?>
          <th class="table-cell"><?= htmlentities($cell) ?></th>
<?php   } ?>
        </tr>
      </thead>
      <tbody>
<?php   while (($row = fgetcsv($stream)) !== false) { ?>
        <tr class="table-row">
<?php     foreach ($row as $cell) { ?>
          <td class="table-cell"><?= str_replace("\n", "<br>", htmlentities($cell)) ?></td>
<?php     } ?>
        </tr>
<?php   }
        fclose($stream); ?>
      </tbody>
    </table>
  </div>
<?php } else if ($data["viewType"] === "md") { ?>
  <div class="markdown"><?= MarkdownParser::parse(file_get_contents($data["file"])) ?></div>
<?php } else if ($data["viewType"] === "raw" || $data["viewType"] === "raw_toolarge") { ?>
  <div class="markdown">
<?php   if ($data["viewType"] === "raw_toolarge") { ?>
    <p>Die Datei ist zu groß für die Vorschau. Es wird nur der erste Teil angezeigt.</p>
<?php   }
        $stream = fopen($data["file"], "r"); ?>
    <pre><?= htmlentities(file_get_contents($data["file"], false, null, 0, pow(1024, 2))) ?></pre>
  </div>
<?php fclose($stream);
      } ?>
</main>
