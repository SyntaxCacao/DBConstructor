<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <!-- maximum-scale=1, user-scalable=0 to prevent iOS from zooming in on inputs -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?php if (isset($data["title"])) echo htmlentities($data["title"])." · "; ?>DBConstructor</title>
    <link href="<?php echo $data["baseurl"]; ?>/assets/build-<?php echo $data["version"]; ?>.min.css" rel="stylesheet">
    <link href="<?php echo $data["baseurl"]; ?>/assets/favicon.svg" rel="icon" type="image/svg+xml">
  </head>
  <body class="page-<?php echo str_replace("_", "-", $data["page"]); ?>" data-baseurl="<?php echo htmlentities($data["baseurl"]); ?>">
<?php if (! (isset($data["suppressNavbar"]) && $data["suppressNavbar"])) { ?>
    <nav class="navbar">
      <div class="container">
        <a class="navbar-brand" href="<?php echo $data["baseurl"]; ?>/">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 512 512"><!-- Font Awesome Free 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) --><path d="M488.6 250.2L392 214V105.5c0-15-9.3-28.4-23.4-33.7l-100-37.5c-8.1-3.1-17.1-3.1-25.3 0l-100 37.5c-14.1 5.3-23.4 18.7-23.4 33.7V214l-96.6 36.2C9.3 255.5 0 268.9 0 283.9V394c0 13.6 7.7 26.1 19.9 32.2l100 50c10.1 5.1 22.1 5.1 32.2 0l103.9-52 103.9 52c10.1 5.1 22.1 5.1 32.2 0l100-50c12.2-6.1 19.9-18.6 19.9-32.2V283.9c0-15-9.3-28.4-23.4-33.7zM358 214.8l-85 31.9v-68.2l85-37v73.3zM154 104.1l102-38.2 102 38.2v.6l-102 41.4-102-41.4v-.6zm84 291.1l-85 42.5v-79.1l85-38.8v75.4zm0-112l-102 41.4-102-41.4v-.6l102-38.2 102 38.2v.6zm240 112l-85 42.5v-79.1l85-38.8v75.4zm0-112l-102 41.4-102-41.4v-.6l102-38.2 102 38.2v.6z"></path></svg>
        </a>
<?php   if (isset($data["user"])) { ?>
        <div class="navbar-collapse">
          <ul class="navbar-items">
            <li class="navbar-item">
              <a class="navbar-link" href="<?php echo $data["baseurl"] ?>/">Projekte</a>
            </li>
            <li class="navbar-item">
              <a class="navbar-link" href="<?php echo $data["baseurl"] ?>/users/">Benutzer</a>
            </li>
            <li class="navbar-item">
              <a class="navbar-link" href="<?php echo $data["baseurl"] ?>/find/" title="Datensatz finden"><span class="bi bi-search" style="vertical-align: 0"></span></a>
            </li>
          </ul>
          <details class="navbar-right dropdown">
            <summary><span class="navbar-icon bi bi-person-circle"></span></summary>
            <ul class="dropdown-menu">
              <li class="dropdown-item dropdown-item-text">Angemeldet als<br><b><?php echo htmlentities($data["user"]->firstname)." ".htmlentities($data["user"]->lastname); ?></b></li>
              <li><hr class="dropdown-divider"></li>
              <li class="dropdown-item">
                <a class="dropdown-link" href="<?php echo $data["baseurl"] ?>/settings/"><span class="bi bi-gear"></span>Einstellungen</a>
              </li>
              <li class="dropdown-item">
                <a class="dropdown-link" href="<?php echo $data["baseurl"] ?>/login/?logout"><span class="bi bi-door-open"></span>Abmelden</a>
              </li>
            </ul>
          </details>
        </div>
<?php   } ?>
      </div>
    </nav>
<?php } ?>
    <?php require $data["page"].".tpl.php"?>

<?php foreach (\DBConstructor\Application::$instance->modals as $modal) echo $modal; ?>
    <script src="<?php echo $data["baseurl"]; ?>/assets/build-main-<?php echo $data["version"]; ?>.min.js"></script>
  </body>
</html>
