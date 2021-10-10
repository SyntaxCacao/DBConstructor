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
<?php /* // TODO Alternative sidenav
      <ul class="filterlist">
<?php foreach ($data["pages"] as $page) { ?>
        <li class="filterlist-item">
          <a class="filterlist-link<?php if ($page->id == $data["wikiPage"]->id) echo " current" ?>" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/wiki/<?php if ($page->id != $data["wikiPage"]->id) echo $page->id."/"; ?>"><span class="bi bi-file-earmark"></span><?php echo htmlentities($page->title) ?></a>
        </li>
<?php } ?>
      </ul>
*/ ?>
    </div>
    <div class="column width-9">
<?php /*if (isset($data["request"]["saved"])) { // TODO Move here? ?>
      <div class="alerts">
        <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
      </div>
<?php } */?>
      <header class="main-header">
        <div class="main-header-header">
          <h1 class="main-heading"><?php echo htmlentities($data["state"]->title) ?></h1>
          <p class="main-subtitle">Zuletzt geändert von <?php echo htmlentities($data["state"]->creatorFirstName." ".$data["state"]->creatorLastName) ?> am <?php echo htmlentities(date("d.m.Y \u\m H:i", strtotime($data["state"]->created))) ?> Uhr</p>
        </div>
        <div class="main-header-actions">
          <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/wiki/<?php echo $data["wikiPage"]->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a>
          <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/wiki/<?php echo $data["wikiPage"]->id ?>/history/"><span class="bi bi-clock-history"></span>Historie</a>
        </div>
      </header>
      <div class="markdown"><?php echo (new \DBConstructor\Util\MarkdownParser)->parse($data["state"]->text) ?></div>
    </div>
  </div>
</main>
