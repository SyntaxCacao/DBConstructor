<?php

declare(strict_types=1);

/** @var array $data */

// Specificly setting "action" attribute because form might be accessed e.g. from /find/1212/, this would otherwise result in /find/1212/?id=56

?>
<main>
  <form action="<?= $data["baseurl"] ?>/find/" class="blankslate" method="get">
    <span class="blankslate-icon bi bi-search"></span>
    <h1 class="blankslate-heading">Datensatz anhand einer ID finden</h1>
    <p class="blankslate-text">Geben Sie die ID eines Datensatzes ein, um direkt auf seine Seite zu gelangen. So müssen Sie ihn nicht erst in den Tabellen suchen.</p>
    <div class="page-find-input-group"><!--
   --><input class="form-input page-find-input" name="id" placeholder="#8338" type="text"<?= isset($data["value"]) ? ' value="'.htmlentities($data["value"]).'"' : '' ?> autofocus><!--
   --><button class="button page-find-button" type="submit"><span class="bi bi-arrow-right no-margin"></span></button><!--
 --></div>
<?php if (isset($data["message"])) { ?>
    <p class="blankslate-text page-find-error"><?= $data["message"] ?></p>
<?php } ?>
  </form>
</main>
