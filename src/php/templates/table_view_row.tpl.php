<main class="container">
  <?php \DBConstructor\Util\TemplateFunctions::printMainHeader("Datensatz #".$data["row"]->id.($data["row"]->deleted ? " (gelöscht)" : "")) ?>
  <div class="row break-md">
    <div class="column width-7">
      <pre><?php var_dump($data["row"]); var_dump($data["relationalFields"]); var_dump($data["textualFields"]) ?></pre>
    </div>
    <div class="column width-5">Hallo</div>
  </div>
</main>
