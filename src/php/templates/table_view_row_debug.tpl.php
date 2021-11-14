<main class="container">
  <?php $header = new \DBConstructor\Util\HeaderGenerator("Datensatzt #".$data["row"]->id);
        $header->subtitle = "Debug-Ansicht";
        $header->autoActions[] = [
          "href" => $data["baseurl"]."/projects/".$data["project"]->id."/tables/".$data["table"]->id."/view/".$data["row"]->id."/",
          "icon" => "arrow-left",
          "text" => "Zurück"
        ];
        $header->generate(); ?>
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
