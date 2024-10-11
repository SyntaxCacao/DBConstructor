<?php

declare(strict_types=1);

use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Util\HeaderGenerator;

/** @var array{export: Export, project: Project} $data */

?>
<div class="container">
  <?php $header = new HeaderGenerator($data["fileName"]);
  $header->subtitle = "Export #{$data["export"]->id}";

  $header->autoActions[] = [
    "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/exports/{$data["export"]->id}",
    "icon" => "arrow-left",
    "text" => "Zurück"
  ];

  $header->generate(); ?>
</div>
<?php $file = fopen(Export::getLocalDirectoryName($data["export"]->id)."/".$data["fileName"], "r"); ?>
<div class="container-expandable-outer">
  <div class="container-expandable-inner-centered">
    <table class="table">
<?php $row = fgetcsv($file); ?>
      <tr class="table-heading">
<?php $hasInternalId = false;
      $i = 0;
      foreach ($row as $cell) {
        $i += 1;
        if ($i === 2 && $cell === "_intid") {
          $hasInternalId = true;
        } ?>
        <th class="table-cell table-cell-paragraph"><?= htmlspecialchars($cell) ?></th>
<?php } ?>
      </tr>
<?php $i = 0;
      $more = false;
      while (($row = fgetcsv($file)) !== false) {
        $i += 1;

        if ($i <= $data["rowsPerPage"] * ($data["currentPage"] - 1)) {
          continue;
        }

        if ($i > $data["rowsPerPage"] * $data["currentPage"]) {
          $more = true;
          break;
        } ?>
      <tr class="table-row">
<?php   $cellIndex = 0;
        foreach ($row as $cell) {
          $cellIndex += 1;
          if ($hasInternalId && $cellIndex === 2 && ctype_digit($cell)) { ?>
        <td class="table-cell table-cell-numeric table-cell-paragraph"><a class="main-link" href="<?= $data["baseurl"] ?>/find?id=<?= htmlspecialchars($cell) ?>"><?= htmlspecialchars($cell) ?></a></td>
<?php     } else { ?>
        <td class="table-cell <?= $cellIndex === 1 ? "table-cell-numeric " : "" ?>table-cell-paragraph"><?= str_replace("\n", "<br>", htmlspecialchars($cell)) ?></td>
<?php     }
        } ?>
      </tr>
<?php }
      fclose($file); ?>
    </table>
  </div>
</div>
<?php if ($data["currentPage"] !== 1 || $more) { ?>
<nav class="pagination-container">
<?php   if ($data["currentPage"] === 1) { ?>
  <span class="pagination-link pagination-link-disabled"><span class="bi bi-chevron-left"></span> Zurück</span>
<?php   } else { ?>
  <a class="pagination-link" href="?page=<?= $data["currentPage"]-1 ?>"><span class="bi bi-chevron-left"></span> Zurück</a>
<?php   }
        if ($more) { ?>
  <a class="pagination-link" href="?page=<?= $data["currentPage"]+1 ?>">Weiter <span class="bi bi-chevron-right"></span></a>
<?php   } else { ?>
  <span class="pagination-link pagination-link-disabled">Weiter <span class="bi bi-chevron-right"></span></span>
<?php   } ?>
</nav>
<?php } ?>
