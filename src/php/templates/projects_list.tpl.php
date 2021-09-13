<?php if (count($data["participatingProjects"]) > 0 || ($data["isAdmin"] && count($data["allProjects"]) > 0)) { ?>
<main class="container main-container">
<?php   if (isset($data["request"]["welcome"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Herzlich Willkommen. Sie haben sich erfolgreich angemeldet.</p></div>
  </div>
<?php   } else if (isset($data["request"]["left"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Sie haben das Projekt verlassen.</p></div>
  </div>
<?php   } ?>
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Projekte</h1>
      <p class="main-subtitle"><?php if ($data["isAdmin"]) { echo count($data["allProjects"])." Projekt"; if (count($data["allProjects"]) != 1) echo "e"; echo " angelegt"; } else { echo "An ".count($data["participatingProjects"])." Projekt"; if (count($data["participatingProjects"]) != 1) echo "en"; echo " beteiligt"; } ?></p>
    </div>
<?php   if ($data["isAdmin"]) { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/create/">Projekt anlegen</a>
    </div>
<?php   } ?>
  </header>
<?php   if (count($data["participatingProjects"]) > 0) { ?>
  <div class="box">
<?php     foreach ($data["participatingProjects"] as $project) { ?>
    <a class="box-row page-project-list-box" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $project->id ?>/">
      <h3 class="page-project-list-label"><?php echo htmlentities($project->label) ?></h3>
<?php       if (isset($project->description)) { ?>
      <p class="page-project-list-description"><?php echo htmlentities($project->description) ?></p>
<?php       } ?>
    </a>
<?php     } ?>
  </div>
<?php   }
        if ($data["isAdmin"] && count($data["participatingProjects"]) != count($data["allProjects"])) {
          if (count($data["participatingProjects"]) > 0) { ?>
  <h2 class="page-project-list-h2">Andere Projekte</h2>
<?php     } ?>
  <div class="box">
<?php     foreach ($data["allProjects"] as $project) {
            if (! array_key_exists($project->id, $data["participatingProjects"])) { ?>
    <div class="box-row box-row-flex page-project-list-box">
      <div class="box-row-flex-extend">
        <h3 class="page-project-list-label"><?php echo htmlentities($project->label) ?></h3>
<?php         if (isset($project->description)) { ?>
        <p class="page-project-list-description"><?php echo htmlentities($project->description) ?></p>
<?php         } ?>
      </div>
      <a class="button button-small box-row-flex-conserve page-project-list-button" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $project->id ?>/?join">Beitreten</a>
    </div>
<?php       }
          } ?>
  </div>
<?php   } ?>
</main>
<?php } else {
        if (isset($data["request"]["welcome"])) { ?>
<div class="container alerts">
  <div class="alert"><p>Herzlich Willkommen. Sie haben sich erfolgreich angemeldet.</p></div>
</div>
<?php   } ?>
<main class="blankslate">
  <svg class="blankslate-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 512 512"><!-- Font Awesome Free 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) --><path d="M488.6 250.2L392 214V105.5c0-15-9.3-28.4-23.4-33.7l-100-37.5c-8.1-3.1-17.1-3.1-25.3 0l-100 37.5c-14.1 5.3-23.4 18.7-23.4 33.7V214l-96.6 36.2C9.3 255.5 0 268.9 0 283.9V394c0 13.6 7.7 26.1 19.9 32.2l100 50c10.1 5.1 22.1 5.1 32.2 0l103.9-52 103.9 52c10.1 5.1 22.1 5.1 32.2 0l100-50c12.2-6.1 19.9-18.6 19.9-32.2V283.9c0-15-9.3-28.4-23.4-33.7zM358 214.8l-85 31.9v-68.2l85-37v73.3zM154 104.1l102-38.2 102 38.2v.6l-102 41.4-102-41.4v-.6zm84 291.1l-85 42.5v-79.1l85-38.8v75.4zm0-112l-102 41.4-102-41.4v-.6l102-38.2 102 38.2v.6zm240 112l-85 42.5v-79.1l85-38.8v75.4zm0-112l-102 41.4-102-41.4v-.6l102-38.2 102 38.2v.6z"></path></svg>
  <h3 class="blankslate-heading">Keine Projekte vorhanden</h3>
<?php   if ($data["isAdmin"]) { ?>
  <p class="blankslate-text">Es wurden noch keine Projekte angelegt.</p>
  <a class="button" href="<?php echo $data["baseurl"] ?>/projects/create/">Projekt anlegen</a>
<?php   } else { ?>
  <p class="blankslate-text">Sie wurden noch zu keinem Projekt hinzugefügt.</p>
<?php   } ?>
</main>
<?php } ?>
