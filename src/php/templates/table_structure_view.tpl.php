<main class="container main-container">
<?php if (isset($data["request"]["saved"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Das Feld wurde gespeichert.</p></div>
  </div>
<?php } else if (isset($data["request"]["deleted"])) { ?>
  <div class="alerts">
    <div class="alert"><p>Das Feld wurde gelöscht.</p></div>
  </div>
<?php } ?>
  <header class="main-header">
    <div class="main-header-header">
      <h1 class="main-heading">Struktur</h1>
      <p class="main-subtitle"><?php
        if (count($data["relationalColumns"]) > 0) {
          echo count($data["relationalColumns"])." Relationsfeld";
          if (count($data["relationalColumns"]) > 1) echo "er";
          if (count($data["textualColumns"]) > 0) echo " · ";
        }
        if (count($data["textualColumns"]) > 0) {
          echo count($data["textualColumns"])." Wertfeld";
          if (count($data["textualColumns"]) > 1) echo "er";
        } ?></p>
    </div>
<?php if ($data["isManager"]) { ?>
    <div class="main-header-actions">
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/relational/create/"><span class="bi bi-arrow-up-right"></span>Relationsfeld anlegen</a>
      <a class="button button-small" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/textual/create/"><span class="bi bi-input-cursor-text"></span>Wertfeld anlegen</a>
    </div>
<?php } ?>
  </header>

  <h2 class="main-subheading">Relationsfelder</h2>

<?php if (count($data["relationalColumns"]) > 0) {?>
  <div class="table-wrapper">
    <table class="table">
      <tr class="table-heading">
        <th class="table-cell">Position</th>
        <th class="table-cell">Name</th>
        <th class="table-cell">Verweist auf</th>
<?php   if ($data["isManager"]) { ?>
        <th class="table-cell"></th>
<?php   } ?>
      </tr>
<?php   $count = 0;
        /** @var \DBConstructor\Models\RelationalColumn $column */
        foreach ($data["relationalColumns"] as $column) {
          $count += 1;?>
      <tr class="table-row">
        <td class="table-cell"><?php echo htmlentities($column->position) ?></td>
        <td class="table-cell"><?php echo htmlentities($column->label) ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name) ?></span></td>
        <td class="table-cell"><a class="main-link" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $column->targetTableId ?>/"><?php echo htmlentities($column->targetTableLabel) ?></a> <span class="table-cell-code-addition"><?php echo htmlentities($column->targetTableName) ?></span></td>
<?php     if ($data["isManager"]) { ?>
        <td class="table-cell table-cell-actions"><a class="button button-smallest" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/relational/<?php echo $column->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a></td>
<?php     } ?>
      </tr>
<?php   } ?>
    </table>
  </div>
<?php } else { ?>
  <p>Bislang sind keine Relationsfelder angelegt.</p>
<?php } ?>

  <h2 class="main-subheading">Wertfelder</h2>

<?php if (count($data["textualColumns"]) > 0) {?>
  <div class="table-wrapper">
  <table class="table">
    <tr class="table-heading">
      <th class="table-cell">Position</th>
      <th class="table-cell">Name</th>
      <th class="table-cell">Datentyp</th>
      <th class="table-cell">Eigenschaften</th>
<?php   if ($data["isManager"]) { ?>
      <th class="table-cell"></th>
<?php   } ?>
    </tr>
<?php   $count = 0;
        /** @var \DBConstructor\Models\TextualColumn $column */
        foreach ($data["textualColumns"] as $column) {
          $count += 1;?>
    <tr class="table-row">
      <td class="table-cell"><?php echo htmlentities($column->position) ?></td>
      <td class="table-cell"><?php echo htmlentities($column->label) ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name) ?></span></td>
      <td class="table-cell"><?php echo htmlentities($column->getTypeLabel()) ?></td>
      <td class="table-cell"><?php
          $elements = [];
          $type = $column->getValidationType();

          if ($type->nullable) {
            $elements[] = "optional";
          }

          if ($column->type == \DBConstructor\Models\TextualColumn::TYPE_TEXT) {
            /** @var \DBConstructor\Validation\Types\TextType $type */
            if ($type->minLength !== null && $type->maxLength !== null) {
              if ($type->minLength === $type->maxLength) {
                $elements[] = htmlentities($type->minLength." Zeichen");
              } else {
                $elements[] = htmlentities($type->minLength."–".$type->maxLength." Zeichen");
              }
            } else {
              if ($type->minLength !== null) {
                $elements[] = htmlentities("≥ ".$type->minLength." Zeichen");
              }
              if ($type->maxLength !== null) {
                $elements[] = htmlentities("≤ ".$type->maxLength." Zeichen");
              }
            }
            if ($type->regEx !== null) {
              $elements[] = "RegEx";
            }
          } else if ($column->type == \DBConstructor\Models\TextualColumn::TYPE_SELECTION) {
            /** @var \DBConstructor\Validation\Types\SelectionType $type */
            if ($type->allowMultiple) {
              $elements[] = "Mehrfachauswahl";
            }
            if (count($type->options) == 1) {
              $elements[] = "1 Option";
            } else {
              $elements[] = count($type->options)." Optionen";
            }
          } else if ($column->type == \DBConstructor\Models\TextualColumn::TYPE_INTEGER) {
            /** @var \DBConstructor\Validation\Types\IntegerType $type */
            if ($type->minDigits !== null && $type->maxDigits !== null) {
              if ($type->minDigits === $type->maxDigits) {
                $elements[] = htmlentities($type->minDigits." Stellen");
              } else {
                $elements[] = htmlentities($type->minDigits."–".$type->maxDigits." Stellen");
              }
            } else {
              if ($type->minDigits !== null) {
                $elements[] = htmlentities("≥ ".$type->minDigits." Stellen");
              }
              if ($type->maxDigits !== null) {
                $elements[] = htmlentities("≤ ".$type->maxDigits." Stellen");
              }
            }
            if ($type->minValue !== null && $type->maxValue !== null) {
              if ($type->minValue === $type->maxValue) {
                $elements[] = htmlentities($type->minValue);
              } else {
                $elements[] = htmlentities($type->minValue."–".$type->maxValue);
              }
            } else {
              if ($type->minValue !== null) {
                $elements[] = htmlentities("≥ ".$type->minValue);
              }
              if ($type->maxValue !== null) {
                $elements[] = htmlentities("≤ ".$type->maxValue);
              }
            }
            if ($type->regEx !== null) {
              $elements[] = "RegEx";
            }
          } else if ($column->type == \DBConstructor\Models\TextualColumn::TYPE_BOOLEAN) {
            /** @var \DBConstructor\Validation\Types\BooleanType $type */
            if ($type->forceTrue) {
              $elements[] = "nur true ist gültig";
            }
          }

          if (count($elements) > 0) {
            for ($i = 0; $i < count($elements); $i++) {
              if ($i != 0) {
                echo ", ";
              }
              echo $elements[$i];
            }
          } else {
            echo "–";
          } ?></td>
<?php     if ($data["isManager"]) { ?>
      <td class="table-cell table-cell-actions"><a class="button button-smallest" href="<?php echo $data["baseurl"] ?>/projects/<?php echo $data["project"]->id ?>/tables/<?php echo $data["table"]->id ?>/structure/textual/<?php echo $column->id ?>/edit/"><span class="bi bi-pencil"></span>Bearbeiten</a></td>
<?php     } ?>
    </tr>
<?php   } ?>
  </table>
  </div>
<?php } else { ?>
  <p>Bislang sind keine Wertfelder angelegt.</p>
<?php } ?>
</main>
