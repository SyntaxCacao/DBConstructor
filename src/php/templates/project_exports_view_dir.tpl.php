<?php

declare(strict_types=1);

use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Util\HeaderGenerator;

/** @var array{export: Export, project: Project} $data */

?>
<main class="container">
<?php $header = new HeaderGenerator("Export #{$data["export"]->id}");

      if ($data["export"]->note !== null) {
        $header->title .= " ({$data["export"]->note})";
      }

      $header->subtitle = "Durchgeführt von {$data["export"]->userFirstName} {$data["export"]->userLastName} am ".date("d.m.Y \u\m H:i", strtotime($data["export"]->created))." Uhr";

      if ($data["archiveExists"]) {
        $header->autoActions[] = [
          "download" => true,
          "href" => "{$data["baseurl"]}/exports/{$data["export"]->id}/{$data["export"]->getArchiveDownloadName()}",
          "icon" => "download",
          "text" => "Herunterladen"
        ];
      }

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/{$data["export"]->id}/editnote/",
        "icon" => "pencil",
        "text" => "Bemerkung bearbeiten",
      ];

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/?delete={$data["export"]->id}",
        "icon" => "trash3",
        "text" => "Löschen",
        "confirm" => "Sind Sie sicher?",
        "danger" => true,
      ];

      $header->generate(); ?>
  <div class="table-wrapper">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">Dateiname</th>
        <th class="table-cell">Dateigröße</th>
        <th class="table-cell"></th>
      </tr>
<?php $files = scandir($data["directory"]);

      foreach ($files as $file) {
        if ($file == "." || $file == "..") {
          continue;
        } ?>
      <tr class="table-row">
        <td class="table-cell"><a class="main-link" href="<?= "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/{$data["export"]->id}/".htmlspecialchars($file) ?>"><?= htmlspecialchars($file) ?></a></td>
        <td class="table-cell table-cell-numeric"><?= Export::getPrintableFileSize($data["directory"]."/".$file) ?></td>
        <td class="table-cell table-cell-actions">
          <a class="button button-smallest" href="<?= "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/{$data["export"]->id}/".htmlspecialchars($file) ?>"><span class="bi bi-folder2-open"></span>Öffnen</a><!--
       --><a class="button button-smallest" href="<?= "{$data["baseurl"]}/exports/{$data["export"]->id}/".htmlspecialchars($file) ?>" download><span class="bi bi-download"></span>Herunterladen</a>
        </td>
      </tr>
<?php } ?>
    </table>
  </div>
</main>
