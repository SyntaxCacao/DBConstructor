<?php

declare(strict_types=1);

use DBConstructor\Util\HeaderGenerator;
use DBConstructor\Util\MarkdownParser;

/** @var array $data */

?>
<main class="container">
<?php if (isset($data["request"]["saved"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
  </div>
<?php } ?>
  <div class="row break-lg">
    <div class="column width-3">
      <header class="main-header">
        <div class="main-header-header">
          <h1 class="main-heading">Wiki</h1>
          <p class="main-subtitle"><?php echo count($data["pages"]) ?> Seite<?php if (count($data["pages"]) != 1) echo "n" ?> angelegt</p>
        </div>
        <div class="main-header-actions">
          <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/wiki/create/"><span class="bi bi-file-earmark-plus"></span>Neue Seite</a>
        </div>
      </header>

      <div class="box">
        <!--
        <div class="box-row box-header">
          <div class="box-header-header">
            <div class="box-title"><?php echo count($data["pages"]) ?> Seite<?php if ($data["pages"] != 1) echo "n" ?></div>
          </div>
          <div class="box-header-actions">
            <a class="button button-small" href="#">Neue Seite</a>
          </div>
        </div>
      -->
<?php $i = 0;
      $last = count($data["pages"]);
      foreach ($data["pages"] as $page) {
        $i++; ?>
        <div class="box-row box-row-flex">
          <a class="box-link<?php if ($page->id == $data["wikiPage"]->id) echo " current" ?> box-row-flex-extend" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/wiki/<?php if ($page->id != $data["project"]->mainPageId) echo $page->id."/"; ?>"><?php echo htmlentities($page->title) ?></a>
          <div class="box-row-flex-conserve">
            <?php if ($i != 1) { ?>
            <form action="" method="post" style="display: inline-block">
              <button class="button button-smallest"><input type="hidden" name="moveUp" value="<?php echo $page->id ?>"><span class="bi bi-arrow-up no-margin"></span></button>
            </form>
            <?php } ?>
            <?php if ($i != $last) { ?>
            <form action="" method="post" style="display: inline-block">
              <button class="button button-smallest"><input type="hidden" name="moveDown" value="<?php echo $page->id ?>"><span class="bi bi-arrow-down no-margin"></span></button>
            </form>
            <?php } ?>
          </div>
        </div>
<?php } ?>
      </div>
    </div>
    <div class="column width-9">
<?php $header = new HeaderGenerator($data["state"]->title);
      $header->subtitle = "Zuletzt geändert von ".$data["state"]->creatorFirstName." ".$data["state"]->creatorLastName." am ".date("d.m.Y \u\m H:i", strtotime($data["state"]->created))." Uhr";
      $header->autoActions = [
        [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/wiki/".$data["wikiPage"]->id."/edit/",
          "icon" => "pencil",
          "text" => "Bearbeiten"
        ],
        [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/wiki/".$data["wikiPage"]->id."/history/",
          "icon" => "clock-history",
          "text" => "Historie"
        ]
      ];

      $header->generate(); ?>
      <div class="markdown"><?php echo MarkdownParser::parse($data["state"]->text) ?></div>
    </div>
  </div>
</main>
