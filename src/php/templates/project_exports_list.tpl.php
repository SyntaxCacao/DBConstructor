<?php

declare(strict_types=1);

use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Util\HeaderGenerator;

/** @var array{exports: array<Export>, project: Project} $data */

?>
<main class="container">
<?php $header = new HeaderGenerator("Bisherige Exporte");
      $header->subtitle = count($data["exports"])." mal exportiert";

      $header->buttonActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/run/",
        "icon" => "play-circle",
        "text" => "Export durchführen"
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
<?php foreach ($data["exports"] as $export) { ?>
    <tr class="table-row">
      <td class="table-cell table-cell-numeric"><?php echo $export->id; ?></td>
      <td class="table-cell"><?= htmlentities($export->getFormatLabel()) ?></td>
      <td class="table-cell"><?= htmlentities(date("d.m.Y H:i", strtotime($export->created))) ?></td>
      <td class="table-cell"><?= htmlentities($export->userFirstName." ".$export->userLastName) ?></td>
      <td class="table-cell"><?= is_null($export->note) ? "&ndash;" : htmlentities($export->note) ?></td>
      <td class="table-cell table-cell-actions"><a class="button <?= $export->deleted ? "button-disabled " : "" ?>button-smallest"<?php if (! $export->deleted) { ?> href="<?= "{$data["baseurl"]}/exports/$export->id/{$export->getFileName()}.zip" ?>" download<?php } ?>><span class="bi bi-download"></span>Herunterladen</a><?php if (! $export->deleted) { ?><a class="button button-smallest"><span class="bi bi-x-lg"></span>Löschen</span></a><?php } ?></td>
    </tr>
<?php } ?>
  </table>
</main>
