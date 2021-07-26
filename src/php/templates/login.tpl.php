<main class="login-card">
  <div class="login-card-brand"><svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 512 512"><!-- Font Awesome Free 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) --><path d="M488.6 250.2L392 214V105.5c0-15-9.3-28.4-23.4-33.7l-100-37.5c-8.1-3.1-17.1-3.1-25.3 0l-100 37.5c-14.1 5.3-23.4 18.7-23.4 33.7V214l-96.6 36.2C9.3 255.5 0 268.9 0 283.9V394c0 13.6 7.7 26.1 19.9 32.2l100 50c10.1 5.1 22.1 5.1 32.2 0l103.9-52 103.9 52c10.1 5.1 22.1 5.1 32.2 0l100-50c12.2-6.1 19.9-18.6 19.9-32.2V283.9c0-15-9.3-28.4-23.4-33.7zM358 214.8l-85 31.9v-68.2l85-37v73.3zM154 104.1l102-38.2 102 38.2v.6l-102 41.4-102-41.4v-.6zm84 291.1l-85 42.5v-79.1l85-38.8v75.4zm0-112l-102 41.4-102-41.4v-.6l102-38.2 102 38.2v.6zm240 112l-85 42.5v-79.1l85-38.8v75.4zm0-112l-102 41.4-102-41.4v-.6l102-38.2 102 38.2v.6z"></path></svg></div>
  <hr class="login-card-hr">
<?php
if (isset($data["result"])) {
  if ($data["result"] == "credentials-incomplete") {
    echo '<div class="alert alert-error" role="alert"><p>Benutzername und Passwort eingeben.</p></div>';
  } else if ($data["result"] == "credentials-incorrect") {
    echo '<div class="alert alert-error" role="alert"><p>Benutzername oder Passwort ist falsch.</p></div>';
  } else if ($data["result"] == "locked") {
    echo '<div class="alert alert-error" role="alert"><p>Ihr Account wurde gesperrt.</p></div>';
  } else if ($data["result"] == "logged-out") {
    echo '<div class="alert" role="alert"><p>Sie wurden abgemeldet.</p></div>';
  }
}/* else if (! empty($data["request"]["return"])) {
  echo '<div class="alert" role="alert"><p>Bitte melden Sie sich erneut an.</p></div>';
}*/
?>
  <form class="form form-block" method="post">
    <label class="form-field">
      <input class="form-input" type="text" name="username" placeholder="Benutzername"<?php if (isset($data["request"]["username"])) echo ' value="'.htmlentities($data["request"]["username"]).'"'; ?> required>
    </label>
    <label class="form-field">
      <input class="form-input" type="password" name="password" placeholder="Passwort" required>
    </label>
    <button class="button" type="submit">Anmelden</button>
  </form>
</main>
