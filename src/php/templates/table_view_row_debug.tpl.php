<main class="container">
  <?php \DBConstructor\Util\TemplateFunctions::printMainHeader("Datensatz #".$data["row"]->id, "Debug-Ansicht", [
    [
      "icon" => "bi-arrow-left",
      "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/",
      "text" => "Zurück"
    ]
  ]); ?>
  <div class="row break-md">
    <div class="column width-7 markdown">
      <pre><?php var_dump($data["row"]) ?></pre>
      <pre><?php var_dump($data["relationalColumns"]) ?></pre>
      <pre><?php var_dump($data["relationalFields"]) ?></pre>
      <pre><?php var_dump($data["textualColumns"]) ?></pre>
      <pre><?php var_dump($data["textualFields"]) ?></pre>
    </div>
    <div class="column width-5 markdown">
      <pre><?php var_dump($data["actions"]) ?></pre>
    </div>
  </div>
</main>
