<?php

declare(strict_types=1);

use DBConstructor\Controllers\Settings\Tokens\TokenForm;
use DBConstructor\Models\AccessToken;
use DBConstructor\Util\HeaderGenerator;

/** @var array{form: TokenForm, token: AccessToken} $data */

?>
<main class="container container-small">
<?php $header = new HeaderGenerator("Personal Access Token ".$data["form"]->getVerb());

      if (isset($data["token"])) {
        $header->subtitle = "Token angelegt am ".date("d.m.Y \u\m H:i", strtotime($data["token"]->created))." Uhr";
      }

      $header->autoActions[] = [
        "href" => $data["baseurl"]."/settings/tokens/",
        "icon" => "arrow-left",
        "text" => "Zurück"
      ];
      $header->generate();

      if ($data["form"]->newToken !== null) { ?>
  <div class="markdown">
    <p>Ihr Token wurde <?= $data["form"]->verbPerfect ?>. Es wird nur ein Mal angezeigt:</p>
    <pre><?= $data["form"]->newToken ?></pre>
  </div>
<?php } else {
        echo $data["form"]->generate();
      } ?>
</main>
