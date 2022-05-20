<?php

declare(strict_types=1);

use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Table;
use DBConstructor\Util\HeaderGenerator;

/** @var array $data */

?>
<main class="container">
<?php $header = new HeaderGenerator("Referenzen zu Datensatz #".$data["row"]->id);
      $header->subtitle = $data["referencesCount"]." Referenz".($data["referencesCount"] === 1 ? "" : "en")." gefunden";
      $header->autoActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];
      $header->generate();

      foreach ($data["tables"] as $table) {
        /** @var Table $table */
        if (! isset($data["references"][$table->id])) {
          continue;
        } ?>
  <h2 class="main-subheading"><?= htmlentities($table->label) ?></h2>
  <table class="table">
    <tr class="table-heading">
      <th class="table-cell">ID</th>
      <th class="table-cell">Feld</th>
    </tr>
<?php   foreach ($data["references"][$table->id] as $field) {
          /** @var RelationalField $field */?>
    <tr class="table-row">
      <td class="table-cell<?= ! $field->rowValid ? " table-cell-invalid" : "" ?>  table-cell-numeric"><a class="main-link" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $table->id ?>/view/<?= $field->rowId ?>/"><?= $field->rowId ?></a></td>
      <td class="table-cell"><?= htmlentities($field->columnLabel) ?> <span class="table-cell-code-addition"><?= htmlentities($field->columnName) ?></span></td>
    </tr>
<?php   } ?>
  </table>
<?php } ?>
</main>
