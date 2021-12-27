<?php

declare(strict_types=1);

use DBConstructor\Models\RowAction;
use DBConstructor\Util\HeaderGenerator;
use DBConstructor\Util\MarkdownParser;

/** @var array $data */

?>
<main class="container">
<?php $header = new HeaderGenerator("Datensatz #".$data["row"]->id.($data["row"]->deleted ? " (gelöscht)" : ""));
      $header->subtitle = "Zuletzt bearbeitet von ".$data["row"]->lastEditorFirstName." ".$data["row"]->lastEditorLastName." am ".date("d.m.Y \u\m H:i", strtotime($data["row"]->lastUpdated))." Uhr";

      if ($data["row"]->flagged) {
        $header->buttonActions[] = [
          "href" => "?unflag",
          "icon" => "flag-fill",
          "text" => "Markiert",
        ];
      } else {
        $header->buttonActions[] = [
          "href" => "?flag",
          "icon" => "flag",
          "text" => "Markieren"
        ];
      }

      $header->additionalHTML = '<details class="dropdown main-header-action">'.
                                  '<summary><span class="button button-small"><span class="bi bi-person"></span>'.(isset($data["row"]->assigneeId) ? "Zuweisung: ".($data["row"]->assigneeId === $data["user"]->id ? "mir" : htmlentities($data["row"]->assigneeFirstName." ".$data["row"]->assigneeLastName)) : "Zuweisen").'</span></summary>'.
                                  '<ul class="dropdown-menu dropdown-menu-down dropdown-menu-left">'.
                                    '<li class="dropdown-item dropdown-item-form">'.$data["assigneeForm"]->generate().'</li>'.
                                  '</ul>'.
                                '</details>';

      if ($data["isAdmin"]) {
        $header->dropdownActions[] = [
          "href" => "?debug",
          "icon" => "wrench",
          "text" => "Debug-Ansicht",
          "divider" => true,
        ];
      }

      if ($data["row"]->deleted) {
        $header->dropdownActions[] = [
          "href" => "?restore",
          "icon" => "arrow-counterclockwise",
          "text" => "Wiederherstellen"
        ];
      } else {
        $header->dropdownActions[] = [
          "href" => "?delete",
          "icon" => "trash",
          "text" => "Löschen",
          "danger" => true,
          "confirm" => "Sind Sie sicher? Der Datensatz kann nach der Löschung wiederhergestellt werden."
        ];
      }

      $header->generate(); ?>
  <div class="row break-md">
    <div class="column width-7">
      <?php $data["editForm"]->generate() ?>
    </div>
    <div class="column width-5">
      <div class="timeline">
<?php foreach ($data["actions"] as $action) {
        /** @var RowAction $action */
        if ($action->action === RowAction::ACTION_ASSIGNMENT) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-person"></span></div>
<?php      if ($action->data === null) { ?>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat die Zuweisung vom Datensatz entfernt · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
<?php     } else { ?>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat den Datensatz <span class="timeline-item-body-emphasis"><?= $action->data === $action->userId ? "sich selbst" : htmlentities($action->data) ?></span> zugewiesen · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
<?php     } ?>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_CHANGE) {
          $relational = $action->data[RowAction::CHANGE_DATA_IS_RELATIONAL];
          if ($relational) {
            $column = $data["relationalColumns"][$action->data[RowAction::CHANGE_DATA_COLUMN_ID]];
            $previous = $action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE];
            $new = $action->data[RowAction::CHANGE_DATA_NEW_VALUE];

            if ($previous === null) {
              $previous = "NULL";
            }

            if ($new === null) {
              $new = "NULL";
            }
          } else {
            $column = $data["textualColumns"][$action->data[RowAction::CHANGE_DATA_COLUMN_ID]];
            $previous = $column->generatePrintableValue($action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE]);
            $new = $column->generatePrintableValue($action->data[RowAction::CHANGE_DATA_NEW_VALUE]);
          } ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-pencil"></span></div>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat das Feld <span class="timeline-item-body-emphasis"><?= htmlentities($column->label) ?></span> von <span class="timeline-item-body-emphasis"><?= htmlentities($previous) ?></span> auf <span class="timeline-item-body-emphasis"><?= htmlentities($new) ?></span> gesetzt · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_COMMENT) { ?>
        <div class="timeline-comment">
          <div class="box">
            <div class="box-row box-row-header"><p><span class="hide-md">Kommentar von </span><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> am <?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr</p></div>
            <div class="box-row markdown"><?= (new MarkdownParser())->parse($action->data) ?></div>
          </div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_CREATION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-plus-lg"></span></div>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat den Datensatz angelegt · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_DELETION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-trash"></span></div>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat den Datensatz gelöscht · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_FLAG) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-flag"></span></div>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat den Datensatz zur Nachverfolgung gekennzeichnet · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_RESTORATION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-arrow-counterclockwise"></span></div>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat den Datensatz wiederhergestellt · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_UNFLAG) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-flag"></span></div>
          <div class="timeline-item-body"><p><span class="timeline-item-body-emphasis"><?= htmlentities($action->userFirstName." ".$action->userLastName) ?></span> hat die Kennzeichnung vom Datensatz entfernt · <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } ?>
        <div class="timeline-filler"></div>
<?php } ?>
      </div>
      <?= $data["commentForm"]->generate() ?>
    </div>
  </div>
</main>
