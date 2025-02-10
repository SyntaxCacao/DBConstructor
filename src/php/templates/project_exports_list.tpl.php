<?php

declare(strict_types=1);

use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Util\HeaderGenerator;

/** @var array{exports: array<Export>, project: Project} $data */

?>
<main class="container">
<?php if (isset($data["deleteSuccess"])) { ?>
  <div class="alerts">
<?php   if ($data["deleteSuccess"]) { ?>
    <div class="alert"><p>Die Exportdaten wurden gelöscht.</p></div>
<?php   } else { ?>
    <div class="alert alert-error"><p>Das Löschen der Exportdaten ist fehlgeschlagen.</p></div>
<?php   } ?>
  </div>
<?php }

      $header = new HeaderGenerator("Export");
      $header->subtitle = count($data["exports"])." Exportdatei".(count($data["exports"]) === 1 ? "" : "en")." vorhanden";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/run/",
        "icon" => "play-circle",
        "text" => "Export durchführen"
      ];

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/docs/",
        "icon" => "file-earmark-text",
        "text" => "Strukturdokumentation"
      ];

      $header->generate(); ?>
  <table class="table">
    <tr class="table-heading">
      <th class="table-cell">ID</th>
      <th class="table-cell">Format</th>
      <th class="table-cell">Zeitpunkt</th>
      <th class="table-cell">Durchgeführt von</th>
      <th class="table-cell">Bemerkung</th>
      <th class="table-cell"></th>
    </tr>
<?php foreach ($data["exports"] as $export) {
        $existsArchive = $export->existsLocalArchive();
        $existsLocalDir = $export->existsLocalDirectory(); ?>
    <tr class="table-row">
      <td class="table-cell table-cell-numeric"><?php echo $export->id; ?></td>
      <td class="table-cell"><?= htmlentities($export->getFormatLabel()) ?></td>
      <td class="table-cell"><?= htmlentities(date("d.m.Y H:i", strtotime($export->created))) ?></td>
      <td class="table-cell"><?= htmlentities($export->userFirstName." ".$export->userLastName) ?><?php if ($export->api) { ?> <span class="table-cell-icon-inline bi bi-robot" title="Durch die API bewirkt"><?php } ?></td>
      <td class="table-cell"><?= is_null($export->note) ? "&ndash;" : htmlentities($export->note) ?></td>
      <td class="table-cell table-cell-actions">
        <a class="button <?= $existsArchive ? "" : "button-disabled " ?>button-smallest"<?php if ($existsArchive) { ?> href="<?= "{$data["baseurl"]}/exports/$export->id/{$export->getArchiveDownloadName()}" ?>" download<?php } else { ?> title="Die Exportdatei ist auf dem Server nicht mehr vorhanden oder nicht lesbar."<?php } ?>><span class="bi bi-download"></span>Herunterladen</a><!--
     --><a class="button <?= $existsLocalDir ? "" : "button-disabled " ?>button-smallest"<?php if ($existsLocalDir) { ?> href="<?= "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/$export->id/" ?>" <?php } ?>><span class="bi bi-folder2-open"></span>Öffnen</a><!--
     --><a class="button button-smallest js-confirm" data-confirm-message="Sind Sie sicher?" href="?delete=<?= $export->id ?>"><span class="bi bi-trash3"></span>Löschen</a>
      </td>
    </tr>
<?php } ?>
  </table>
</main>
