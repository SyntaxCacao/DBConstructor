<?php

declare(strict_types=1);

use DBConstructor\Application;
use DBConstructor\Models\Project;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;
use DBConstructor\Models\RowAttachment;
use DBConstructor\Models\Table;
use DBConstructor\Util\HeaderGenerator;
use DBConstructor\Util\MarkdownParser;

/** @var array{project: Project, table: Table, row: Row, actions: array<RowAction>, attachments: array<RowAttachment>} $data */

?>
<main class="container">
<?php if (isset($data["alert"]) && $data["alert"] === "comment-deleted") { ?>
  <div class="alerts">
    <div class="alert"><p>Der Kommentar wurde gelöscht.</p></div>
  </div>
<?php }

      $header = new HeaderGenerator('<span class="hide-down-sm">Datensatz </span>#'.$data["row"]->id);

      if ($data["row"]->deleted) {
        $header->title .= " (gelöscht)";
      }

      $header->title .= ' <div class="validation-step-icon"><span class="bi-'.($data["row"]->valid ? "check" : "x").'"></span></div>';
      $header->escapeTitle = false;
      $header->subtitle = "Zuletzt bearbeitet von ".$data["row"]->lastEditorFirstName." ".$data["row"]->lastEditorLastName." am ".date("d.m.Y \u\m H:i", strtotime($data["row"]->lastUpdated))." Uhr";

      $header->additionalHTML = '<a class="button button-small '.($data["row"]->flagged ? 'button-selected ' : '').'main-header-action" href="?'.($data["row"]->flagged ? "unflag" : "flag").'"><span class="bi '.($data["row"]->flagged ? "bi-flag-fill" : "bi-flag").' no-margin-down-md"></span><span class="hide-down-md"> Markier'.($data["row"]->flagged ? "t" : "en").'</span></a>'.
                                '<details class="dropdown main-header-action">'.
                                  '<summary><span class="button button-small'.(isset($data["row"]->assigneeId) ? '' : ' page-table-view-details-assignee-unset').'"><span class="bi '.(isset($data["row"]->assigneeId) ? 'bi-person-fill' : 'bi-person').'"></span>'.(isset($data["row"]->assigneeId) ? '<span class="hide-down-md">Zugewiesen: </span>'.($data["row"]->assigneeId === $data["user"]->id ? "Mir" : '<span class="hide-down-md">'.htmlentities($data["row"]->assigneeFirstName).' </span>'.htmlentities($data["row"]->assigneeLastName)) : '<span class="page-table-view-details-assignee-unset-label">Zuweisen</span>').'</span></summary>'.
                                  '<ul class="dropdown-menu dropdown-menu-down dropdown-menu-left">'.
                                    '<li class="dropdown-item dropdown-item-form">'.$data["assigneeForm"]->generate().'</li>'.
                                  '</ul>'.
                                '</details>';

      $header->dropdownActions[] = [
        "href" => "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/attachments/upload/",
        "icon" => "paperclip",
        "text" => "Dateien anhängen",
      ];

      $header->dropdownActions[] = [
        "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/references/",
        "icon" => "arrow-up-right",
        "text" => "Referenzen finden",
        "divider" => true
      ];

      if ($data["isManager"]) {
        $header->dropdownActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/raw/",
          "icon" => "wrench",
          "text" => "Debug-Ansicht"
        ];

        $header->dropdownActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/revalidate/",
          "icon" => "arrow-repeat",
          "text" => "Erneut validieren",
          "divider" => true
        ];
      }

      if ($data["row"]->deleted) {
        $header->dropdownActions[] = [
          "href" => "?restore",
          "icon" => "arrow-counterclockwise",
          "text" => "Wiederherstellen"
        ];

        if ($data["isManager"]) {
          $header->dropdownActions[] = [
            "href" => "?deletePerm",
            "icon" => "trash3",
            "text" => "Endgültig löschen",
            "confirm" => "Sind Sie sicher? Nach der endgültigen Löschung kann der Datensatz NICHT wiederhergestellt werden. Felder, die diesen Datensatz noch referenzieren, werden auf NULL gesetzt.",
            "danger" => true
          ];
        }
      } else {
        $header->dropdownActions[] = [
          "href" => "?delete",
          "icon" => "trash3",
          "text" => "Löschen",
          "confirm" => "Sind Sie sicher? Der Datensatz kann nach der Löschung wiederhergestellt werden."
        ];
      }

      $header->generate(); ?>
  <div class="row break-md">
    <div class="column width-7">
      <?php $data["editForm"]->generate() ?>
    </div>
    <div class="column width-5 page-table-view-details-column-right">
<?php if (count($data["attachments"]) > 0) { ?>
      <header class="main-subheader">
        <h2 class="main-subheading">Angehängte Dateien</h2>
      </header>
      <div class="box">
<?php   foreach ($data["attachments"] as $attachment) { ?>
        <div class="box-row box-row-flex">
          <div class="box-row-flex-conserve upload-list-icon"><span class="bi bi-<?= $attachment->getTypeIcon() ?>"></span></div>
          <div class="box-row-flex-extend">
            <p class="page-table-view-attachments-name"><a <?php if ($attachment->isViewable() && $attachment->getViewWarning() !== null) echo ' class="js-confirm"' ?>href="<?= "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/attachments/".($attachment->isViewable() ? "view" : "download")."/".htmlentities($attachment->fileName) ?>" title="<?= htmlentities($attachment->fileName) ?>"<?php if ($attachment->isViewable() && $attachment->getViewWarning() !== null) echo ' data-confirm-message="'.$attachment->getViewWarning().'"' ?>><?= htmlentities($attachment->fileName) ?></a></p>
            <p class="page-table-view-attachments-desc"><?= $attachment->getHumanFileSize() ?> · von <?= htmlentities($attachment->uploaderFirstName." ".$attachment->uploaderLastName) ?> · <span title="Hochgeladen am <?= htmlentities(date("d.m.Y \u\m H:i", strtotime($attachment->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($attachment->created))) ?></span></p>
          </div>
          <div class="box-row-flex-conserve box-row-margin-left">
<?php     if ($attachment->isViewable()) { ?>
            <a class="button button-smallest<?php if ($attachment->getViewWarning() !== null) echo ' js-confirm' ?>" href="<?= "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/attachments/view/".htmlentities($attachment->fileName) ?>" title="Datei ansehen"<?php if ($attachment->getViewWarning() !== null) echo ' data-confirm-message="'.$attachment->getViewWarning().'"' ?>><span class="bi bi-eye no-margin"></span></a>
<?php     } ?>
            <a class="button button-smallest" href="<?= "{$data["baseurl"]}/projects/{$data["project"]->id}/tables/{$data["table"]->id}/view/{$data["row"]->id}/attachments/download/".htmlentities($attachment->fileName) ?>" title="Datei herunterladen" download><span class="bi bi-download no-margin"></span></a>
<?php     if ($data["isManager"] || $attachment->uploaderId === $data["user"]->id) { ?>
            <a class="button button-smallest js-confirm" href="?deleteAttachment=<?= $attachment->id ?>" title="Datei löschen" data-confirm-message="Sind Sie sicher, dass Sie die Datei <?= htmlentities($attachment->fileName) ?> löschen wollen?"><span class="bi bi-trash3 no-margin"></span></a>
<?php     } ?>
          </div>
        </div>
<?php   } ?>
      </div>
<?php } ?>
      <header class="main-subheader">
        <h2 class="main-subheading">Historie</h2>
        <div class="main-header-actions">
          <a class="button button-small<?= $data["filtered"] ? " button-selected" : "" ?> main-header-action" href="<?= $data["filtered"] ? $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/" : "?filtered" ?>" title="Änderungen ausblenden"><span class="bi bi-funnel"></span>Filtern</a>
        </div>
      </header>
      <div class="timeline">
<?php foreach ($data["actions"] as $action) {
        $name = '<span class="timeline-item-body-emphasis"';
        if ($action->api) $name .= ' title="Durch die API bewirkt"';
        $name .= '>'.htmlentities($action->userFirstName." ".$action->userLastName);
        if ($action->api) $name .= '&nbsp;<span class="bi bi-robot page-table-view-history-robot"></span>';
        $name .= '</span>';

        if ($action->action === RowAction::ACTION_ASSIGNMENT) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-person"></span></div>
<?php     if ($action->data === null) { ?>
          <div class="timeline-item-body"><p><?= $name ?> hat die Zuweisung vom Datensatz entfernt&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
<?php     } else { ?>
          <div class="timeline-item-body"><p><?= $name ?> hat den Datensatz <span class="timeline-item-body-emphasis"><?= $action->data === $action->userId ? "sich selbst" : htmlentities($action->data) ?></span> zugewiesen&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
<?php     } ?>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_CHANGE) {
          $relational = $action->data[RowAction::CHANGE_DATA_IS_RELATIONAL];
          if ($relational) {
            $column = $data["relationalColumns"][$action->data[RowAction::CHANGE_DATA_COLUMN_ID]]->label ?? "(gelöschtes Feld)";
            $previous = $action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE];
            $new = $action->data[RowAction::CHANGE_DATA_NEW_VALUE];
          } else {
            if (isset($data["textualColumns"][$action->data[RowAction::CHANGE_DATA_COLUMN_ID]])) {
              $column = $data["textualColumns"][$action->data[RowAction::CHANGE_DATA_COLUMN_ID]];
              $previous = $column->generatePrintableValue($action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE]);
              $new = $column->generatePrintableValue($action->data[RowAction::CHANGE_DATA_NEW_VALUE]);
              $column = $column->label;
            } else {
              $column = "(gelöschtes Feld)";
              $previous = $action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE];
              $new = $action->data[RowAction::CHANGE_DATA_NEW_VALUE];
            }
          }

          if ($previous === null) {
            $previous = "NULL";
          }

          if ($new === null) {
            $new = "NULL";
          } ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-pencil"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat das Feld <span class="timeline-item-body-emphasis"><?= htmlentities($column) ?></span> von <span class="timeline-item-body-emphasis"><?= htmlentities($previous) ?></span> auf <span class="timeline-item-body-emphasis"><?= htmlentities($new) ?></span> gesetzt&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_COMMENT) { ?>
        <div id="comment-<?= $action->id ?>" class="timeline-comment">
          <div class="box">
            <div class="box-row box-row-header">
              <p><?= $name ?> <span class="hide-up-sm">·</span><span class="hide-down-sm">am</span> <?= date("d.m.Y", strtotime($action->created)) ?> <span class="hide-down-sm">um </span><?= date("H:i", strtotime($action->created)) ?><span class="hide-down-sm"> Uhr</span></p>
              <div class="timeline-comment-header-toolbar">
<?php     if ($action->isCommentExportExcluded()) { ?>
                <span class="timeline-comment-header-toolbar-tool bi bi-eye-slash" title="Dieser Kommentar wird beim Export nicht übernommen."></span>
<?php     }
          if ($action->isCommentEdited()) { ?>
                <span class="timeline-comment-header-toolbar-tool bi bi-pencil" title="Dieser Kommentar wurde nachträglich verändert."></span>
<?php     } ?>
                <details class="dropdown timeline-comment-dropdown">
                  <summary class="timeline-comment-header-toolbar-tool"><span class="bi bi-three-dots"></span></summary>
                  <ul class="dropdown-menu dropdown-menu-down dropdown-menu-left">
                    <li class="dropdown-item">
                      <a class="dropdown-link js-clipboard-write js-dropdown-close" href="#comment-<?= $action->id ?>" data-clipboard="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $data["table"]->id ?>/view/<?= $data["row"]->id ?>/#comment-<?= $action->id ?>"><span class="bi bi-link"></span>Link kopieren</a>
                    </li>
<?php     if ($action->permitCommentEdit(Application::$instance->user->id, $data["isManager"])) { ?>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-item">
<?php       if ($action->isCommentExportExcluded()) { ?>
                      <a class="dropdown-link" href="?includeCommentExport=<?= $action->id ?>"><span class="bi bi-eye-slash-fill"></span>Nicht ausblenden</a>
<?php       } else { ?>
                      <a class="dropdown-link" href="?excludeCommentExport=<?= $action->id ?>"><span class="bi bi-eye-slash"></span>Beim Export ausblenden</a>
<?php       } ?>
                    </li>
                    <li class="dropdown-item">
                      <a class="dropdown-link" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $data["table"]->id ?>/view/<?= $data["row"]->id ?>/comments/<?= $action->id ?>/"><span class="bi bi-pencil"></span>Bearbeiten</a>
                    </li>
                    <li class="dropdown-item">
                      <a class="dropdown-link dropdown-link-danger js-confirm" href="?deleteComment=<?= $action->id ?>" data-confirm-message="Sind Sie sicher?"><span class="bi bi-trash3"></span>Löschen</a>
                    </li>
<?php     } ?>
                  </ul>
                </details>
              </div>
            </div>
            <div class="box-row markdown"><?= MarkdownParser::parse($action->data[RowAction::COMMENT_DATA_TEXT]) ?></div>
          </div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_CREATION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-plus"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat den Datensatz angelegt&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_DELETION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-trash3"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat den Datensatz gelöscht&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_FLAG) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-flag"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat den Datensatz zur Nachverfolgung gekennzeichnet&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_REDIRECTION_DESTINATION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-box-arrow-in-up-right"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat <?= $action->data[RowAction::REDIRECTION_DATA_COUNT] === 1 ? 'eine Referenz' : '<span class="timeline-item-body-emphasis">'.$action->data[RowAction::REDIRECTION_DATA_COUNT].'</span> Referenzen' ?> auf Datensatz <a class="timeline-item-body-emphasis" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $data["table"]->id ?>/view/<?= $action->data[RowAction::REDIRECTION_DATA_ORIGIN] ?>/"><?= $action->data[RowAction::REDIRECTION_DATA_ORIGIN] ?></a> umgeleitet auf diesen Datensatz&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_REDIRECTION_ORIGIN) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-box-arrow-in-up-right"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat <?= $action->data[RowAction::REDIRECTION_DATA_COUNT] === 1 ? 'eine Referenz' : '<span class="timeline-item-body-emphasis">'.$action->data[RowAction::REDIRECTION_DATA_COUNT].'</span> Referenzen' ?> auf diesen Datensatz umgeleitet auf Datensatz <a class="timeline-item-body-emphasis" href="<?= $data["baseurl"] ?>/projects/<?= $data["project"]->id ?>/tables/<?= $data["table"]->id ?>/view/<?= $action->data[RowAction::REDIRECTION_DATA_DESTINATION] ?>/"><?= $action->data[RowAction::REDIRECTION_DATA_DESTINATION] ?></a>&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_RESTORATION) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-arrow-counterclockwise"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat den Datensatz wiederhergestellt&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } else if ($action->action === RowAction::ACTION_UNFLAG) { ?>
        <div class="timeline-item">
          <div class="timeline-item-icon"><span class="bi bi-flag"></span></div>
          <div class="timeline-item-body"><p><?= $name ?> hat die Kennzeichnung vom Datensatz entfernt&nbsp;· <span title="<?= htmlentities(date("d.m.Y \u\m H:i", strtotime($action->created))) ?> Uhr"><?= htmlentities(date("d.m.Y", strtotime($action->created))) ?></span></p></div>
        </div>
<?php   } ?>
        <div class="timeline-filler"></div>
<?php } ?>
      </div>
      <?= $data["commentForm"]->generate() ?>
    </div>
  </div>
</main>
