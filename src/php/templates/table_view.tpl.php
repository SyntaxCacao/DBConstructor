<?php

declare(strict_types=1);

/** @var array $data */

?>
<main>
  <div class="container">
    <?= $data["filterForm"]->generate() ?>
  </div>
<?php if (isset($data["metaColumns"])) {
        $data["generator"]->generate(true, false, false, $data["metaColumns"]);
      } else {
        $data["generator"]->generate(true, false, false);
      } ?>
<?php if ($data["rowCount"] > 0 && $data["pageCount"] > 1) { ?>
  <nav class="pagination-container">
<?php   $query = "?";
        foreach ($_GET as $key => $value) {
          // strpos(...) = str_starts_with polyfill
          if (strpos($key , "field-") === 0 && $value !== "") {
            $query .= urlencode($key)."=".urlencode($value)."&";
          }
        }
        if ($data["currentPage"] === 1) { ?>
    <span class="pagination-link pagination-link-disabled"><span class="bi bi-chevron-left"></span> Zurück</span>
<?php   } else { ?>
    <a class="pagination-link" href="<?= $query ?>page=<?= $data["currentPage"]-1 ?>"><span class="bi bi-chevron-left"></span> Zurück</a>
<?php   }
        if ($data["currentPage"] >= $data["pageCount"]) { ?>
    <span class="pagination-link pagination-link-disabled">Weiter <span class="bi bi-chevron-right"></span></span>
<?php   } else { ?>
    <a class="pagination-link" href="<?= $query ?>page=<?= $data["currentPage"]+1 ?>">Weiter <span class="bi bi-chevron-right"></span></a>
<?php   } ?>
  </nav>
<?php } ?>
</main>
