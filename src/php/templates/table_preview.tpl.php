<main class="container main-container">
  <header class="main-header">
    <h1 class="main-heading">Tabellenvorschau</h1>
  </header>
<?php if (count($data["relationalcolumns"]) > 0 || count($data["textualcolumns"]) > 0) { ?>
  <div class="table-wrapper">
  <table class="table">
    <tr class="table-heading">
      <th class="table-cell">ID</th>
<?php   foreach ($data["relationalcolumns"] as $column) { ?>
      <th class="table-cell"><!--<span class="bi bi-arrow-up-right"></span>--><?php echo htmlentities($column->label); ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name); ?></span></th>
<?php   } ?>
<?php   foreach ($data["textualcolumns"] as $column) { ?>
      <th class="table-cell"><?php echo htmlentities($column->label); ?> <span class="table-cell-code-addition"><?php echo htmlentities($column->name); ?></span></th>
<?php   } ?>
    </tr>
<?php   if (count($data["rows"]) > 0) {
          foreach ($data["rows"] as $row) { ?>
    <tr class="table-row">
      <td class="table-cell table-cell-numeric"><a class="main-link" href="#"><?php echo $row->id; ?></a></td>
<?php       foreach ($data["relationalcolumns"] as $column) {
              if (isset($data["relationalfields"][$row->id]) && isset($data["relationalfields"][$row->id][$column->id])) {
                if (is_null($data["relationalfields"][$row->id][$column->id])) { ?>
            <td class="table-cell"><i>null</i></td>
<?php           } else { ?>
            <td class="table-cell"><i>Beziehung: </i><?php $str = ""; foreach ($data["relationalfields"][$row->id][$column->id]["target"] as $value) $str .= $value["obj"]->value."; "; echo htmlentities($str); /* echo htmlentities($data["relationalfields"][$row->id][$column->id]["target"][0]["obj"]->value);*/ ?></td>
<?php           }
              } else { ?>
          <td class="table-cell"><i>&ndash;</i></td>
<?php         }
            }
            foreach ($data["textualcolumns"] as $column) {
              if (isset($data["textualfields"][$row->id]) && isset($data["textualfields"][$row->id][$column->id])) {
                if (is_null($data["textualfields"][$row->id][$column->id]->value)) { ?>
      <td class="table-cell<?php if ($data["textualfields"][$row->id][$column->id]->isInvalid()) echo " table-cell-invalid"; ?>"><i>null</i></td>
<?php           } else { ?>
      <td class="table-cell<?php if ($column->type == \DBConstructor\Models\TextualColumn::TYPE_INTEGER) echo " table-cell-numeric"; if ($data["textualfields"][$row->id][$column->id]->isInvalid()) echo " table-cell-invalid"; ?>"><?php echo htmlentities($data["textualfields"][$row->id][$column->id]->value); ?></td>
<?php           }
              } else { ?>
      <td class="table-cell"><i>&ndash;</i></td>
<?php         }
            } ?>
    </tr>
<?php     }
        } else { ?>
    <tr class="table-row">
      <td class="table-cell" colspan="<?php echo count($data["relationalcolumns"])+count($data["textualcolumns"])+1; ?>">Es sind keine Daten vorhanden.</td>
    </tr>
<?php   } ?>
  </table>
  </div>
<?php } else { ?>
  <p>Es sind noch keine Spalten angelegt.</p>
<?php } ?>
</main>
