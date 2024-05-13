<?php

declare(strict_types=1);

use DBConstructor\Models\AccessToken;
use DBConstructor\Util\HeaderGenerator;

/** @var array{tokens: array<AccessToken>} $data */

?>
<main class="container container">
<?php if (isset($_GET["saved"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
  </div>
<?php }

      $header = new HeaderGenerator("API-Zugriff verwalten");
      $header->subtitle = count($data["tokens"])." Token".(count($data["tokens"]) === 1 ? "" : "s")." angelegt";
      $header->autoActions[] = [
        "href" => $data["baseurl"]."/settings/tokens/create/",
        "icon" => "plus-circle",
        "text" => "Token anlegen"
      ];
      $header->autoActions[] = [
        "href" => $data["baseurl"]."/settings/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];
      $header->generate(); ?>
  <div class="markdown" style="margin-bottom: 16px">
    <p>Ein <em>Personal Access Token</em> wird zur Authentifizierung gegenüber der API benötigt. Unter Angabe eines Ihrer Tokens vollzogene Handlungen (etwa das Anlegen, Ändern oder Kommentieren von Datensätzen) werden Ihrem Benutzer zugerechnet. Die API steht nur Benutzern zur Verfügung, die Administrator sind oder denen die Berechtigung zum Zugriff auf die API durch einen Administrator besonders gestattet ist. Wenn Sie diese Seite sehen, erfüllt Ihr Benutzer diese Voraussetzung.</p>
    <p>Ein Token wird nur ein einziges Mal unmittelbar nach der Generierung angezeigt. Es hat eine von vornherein bestimmte Gültigkeitsdauer und soll geheim gehalten werden wie ein Passwort. Für jedes Token wird überdies im Einzelnen festgelegt, welche Funktionen der API damit verwendet werden können. Diese Berechtigungen können im Nachhinein verändert werden. Sie werden in jedem Falle durch diejenigen Berechtigungen begrenzt, die Ihrem Benutzer im betreffenden Projekt tatsächlich verliehen sind. So ist eine hier vergebene Berechtigung zum Verändern der Tabellenstruktur nur wirksam, wenn Sie im betreffenden Projekt über die Berechtigungen eines Managers verfügen.</p>
    <p>Nach Ablauf eines Tokens (und auch schon vorher) kann es erneuert werden. Dabei tritt an die Stelle des alten ein neues Token mit einer neu bestimmten Gültigkeitsdauer, im Übrigen aber denselben Eigenschaften.</p>
  </div>
<?php if (count($data["tokens"]) === 0) { ?>
  <div class="blankslate">
    <h3 class="blankslate-heading">Sie haben noch keine Tokens angelegt.</h3>
    <p class="blankslate-text">Personal Access Tokens ermöglichen den Zugriff auf die API.</p>
    <a class="button" href="<?= $data["baseurl"] ?>/settings/tokens/create/">Token anlegen</a>
  </div>
<?php } else { ?>
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr class="table-heading">
          <th class="table-cell" scope="col">ID</th>
          <th class="table-cell" scope="col">Bezeichnung</th>
          <th class="table-cell" scope="col">Gültig bis</th>
          <th class="table-cell" scope="col">Zuletzt erneuert</th>
          <th class="table-cell" scope="col">Angelegt</th>
          <th class="table-cell" scope="col"></th>
        </tr>
      </thead>
      <tbody>
<?php   foreach($data["tokens"] as $token) { ?>
        <tr class="table-row">
          <td class="table-cell"><?= $token->id ?></td>
          <td class="table-cell"><?= $token->label === null ? "–" : htmlentities($token->label) ?></td>
          <td class="table-cell"<?= $token->expired ? ' style="color: #cb2431"' : '' ?>><?= $token->expires === null ? "unbegrenzt" : date("d.m.Y H:i", strtotime($token->expires)) ?></td>
          <td class="table-cell"><?= $token->renewed === null ? "–" : date("d.m.Y H:i", strtotime($token->renewed)) ?></td>
          <td class="table-cell"><?= date("d.m.Y H:i", strtotime($token->created)) ?></td>
          <td class="table-cell table-cell-actions">
            <a class="button button-smallest" href="<?= $data["baseurl"] ?>/settings/tokens/<?= $token->id ?>/renew/"><span class="bi bi-arrow-clockwise"></span>Erneuern</a><!--
         --><a class="button button-smallest" href="<?= $data["baseurl"] ?>/settings/tokens/<?= $token->id ?>/<?= $token->disabled ? "enable" : "disable" ?>/"><span class="bi bi-<?= $token->disabled ? "play" : "pause" ?>-fill"></span><?= $token->disabled ? "Reaktivieren" : "Deaktivieren" ?></a><!--
         --><a class="button button-smallest" href="<?= $data["baseurl"] ?>/settings/tokens/<?= $token->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a><!--
         --><a class="button button-smallest button-danger js-confirm" href="<?= $data["baseurl"] ?>/settings/tokens/<?= $token->id ?>/delete/" data-confirm-message="Sind Sie sicher?"><span class="bi bi-trash3"></span>Löschen</a>
          </td>
        </tr>
<?php   } ?>
      </tbody>
    </table>
  </div>
<?php } ?>
</main>
