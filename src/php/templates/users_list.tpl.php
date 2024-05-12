<div class="container">
  <div class="alerts">
    <?php if (isset($data["request"]["edited"])) { ?>
    <div class="alert"><p>Die Änderungen wurden gespeichert.</p></div>
    <?php }
          if (isset($data["request"]["created"])) { ?>
    <div class="alert"><p>Der Benutzer wurde angelegt.</p></div>
    <?php } ?>
  </div>
  <div class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Benutzer</h1>
      <p class="main-subtitle"><?php echo count($data["users"]); ?> Benutzer angelegt</p>
    </div>
<?php if ($data["isAdmin"]) { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?php echo $data["baseurl"]; ?>/users/create/"><span class="bi bi-person-plus"></span>Benutzer anlegen</a>
    </div>
<?php } ?>
  </div>
</div>
<div class="container-expandable-outer">
  <div class="container-expandable-inner">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">ID</th>
<?php if ($data["isAdmin"]) { ?><th class="table-cell">Benutzername</th><?php } ?>
        <th class="table-cell">Name</th>
        <th class="table-cell">Rolle</th>
        <th class="table-cell">Projekte</th>
<?php if ($data["isAdmin"]) { ?><th class="table-cell">Erste Anmeldung</th><?php } ?>
<?php if ($data["isAdmin"]) { ?><th class="table-cell">Letzte Anmeldung</th><?php } ?>
<?php if ($data["isAdmin"]) { ?><th class="table-cell"></th><?php } ?>
      </tr>
<?php foreach ($data["users"] as $user) { ?>
      <tr class="table-row">
        <td class="table-cell"><?php echo $user["obj"]->id; ?></td>
<?php if ($data["isAdmin"]) { ?><td class="table-cell"><?php echo htmlentities($user["obj"]->username); ?></td><?php } ?>
        <td class="table-cell"><?php echo htmlentities($user["obj"]->lastname).", ".htmlentities($user["obj"]->firstname); ?></td>
        <td class="table-cell"><?php if ($user["obj"]->isAdmin) { ?>Administrator<?php } else { ?>Benutzer<?php } if ($user["obj"]->locked) { ?> <span class="bi bi-lock-fill page-project-participants-locked" title="Dieser Benutzer ist gesperrt."></span><?php } ?></td>
        <td class="table-cell"><?php echo $user["projects"]; ?></td>
<?php if ($data["isAdmin"]) { ?><td class="table-cell"><?php if (isset($user["obj"]->firstLogin)) { echo htmlentities(date("d.m.Y H:i", strtotime($user["obj"]->firstLogin))); } else { ?>&ndash;<?php } ?></td><?php } ?>
<?php if ($data["isAdmin"]) { ?><td class="table-cell"><?php if (isset($user["obj"]->lastLogin)) { echo htmlentities(date("d.m.Y H:i", strtotime($user["obj"]->lastLogin))); } else { ?>&ndash;<?php } ?></td><?php } ?>
<?php if ($data["isAdmin"]) { ?><td class="table-cell table-cell-actions"><a class="button button-smallest" href="<?php echo $data["baseurl"]; ?>/users/<?php echo $user["obj"]->id; ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a></td><?php } ?>
      </tr>
<?php } ?>
    </table>
  </div>
</div>
