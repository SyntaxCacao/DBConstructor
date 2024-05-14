<?php

declare(strict_types=1);

use DBConstructor\Models\User;
use DBConstructor\Util\HeaderGenerator;

/** @var array{user: User} $data */

?>
<main class="container container-small">
  <div class="alerts">
<?php if ($data["name-success"]) { ?>
    <div class="alert"><p>Ihr Name wurde geändert.</p></div>
<?php } ?>
<?php if ($data["password-success"]) { ?>
    <div class="alert"><p>Ihr Passwort wurde geändert.</p></div>
<?php } ?>
  </div>
<?php $header = new HeaderGenerator("Einstellungen");
      $header->subtitle = "Erstmalige Anmeldung am ".date("d.m.Y \u\m H:i", strtotime($data["user"]->firstLogin))." Uhr";

      if ($data["user"]->isAdmin || $data["user"]->hasApiAccess) {
        $header->dropdownActions[] = [
          "href" => $data["baseurl"]."/settings/tokens/",
          "icon" => "robot",
          "text" => "API-Zugriff verwalten"
        ];
      }

      $header->generate(); ?>
  <h2 class="form-heading">Name ändern</h2>
  <?php echo $data["name-form"]->generate(); ?>
  <h2 class="form-heading">Passwort ändern</h2>
  <?php echo $data["password-form"]->generate(); ?>
</main>
