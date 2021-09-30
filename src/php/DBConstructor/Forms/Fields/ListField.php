<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class ListField extends GroupableField
{
    /** @var ListFieldColumn[] */
    public $columns = [];

    /** @var bool */
    protected $incomplete = false;

    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
    }

    public function addColumn(ListFieldColumn $column)
    {
        $this->columns[] = $column;
    }

    /**
     * @return bool true if row contains all required fields and was added, false otherwise
     */
    public function addRow(array $row): bool
    {
        foreach ($this->columns as $column) {
            if (! isset($row[$column->name]) && $column->required) {
                return false;
            }
        }

        $this->value[] = $row;
        return true;
    }

    public function generateField(): string
    {
        $html = '<input class="form-list-counter" name="field-'.htmlentities($this->name).'" type="hidden"';

        if (! is_null($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        $html .= ' value="';

        if (empty($this->value)) {
            $html .= "1";
        } else {
            $html .= count($this->value);
        }

        $html .= '" autocomplete="off"><div class="form-list-rows" data-list-name="'.htmlentities($this->name).'">';

        if (empty($this->value)) {
            $html .= '<div class="form-list-row">';

            foreach ($this->columns as $column) {
                $html .= $column->generateField($this->name, 1);
            }

            $html .= '<a class="button form-list-delete" href="#">Löschen</a></div>';
        } else {
            foreach ($this->value as $index => $row) {
                $html .= '<div class="form-list-row">';

                foreach ($this->columns as $column) {
                    if (isset($row[$column->name])) {
                        $html .= $column->generateField($this->name, $index+1, $row[$column->name]);
                    } else {
                        $html .= $column->generateField($this->name, $index+1);
                    }
                }

                $html .= '<a class="button form-list-delete" href="#">Löschen</a></div>';
            }
        }

        $html .= '</div><a class="button form-list-create" href="#">Zeile hinzufügen</a>';
        return $html;
    }

    public function hasValue(): bool
    {
        return count($this->value) > 0;
    }

    public function insertValue($value)
    {
        if (intval($value) == 0) {
            return;
        }

        $count = intval($value);
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $row = [];
            $incomplete = false;

            foreach ($this->columns as $column) {
                if (isset($_REQUEST["field-$this->name-$i-$column->name"])) {
                    if ($_REQUEST["field-$this->name-$i-$column->name"] == "") {
                        $incomplete = true;
                    } else {
                        $row[$column->name] = $_REQUEST["field-$this->name-$i-$column->name"];
                    }
                } else if ($column->required) {
                    continue 2;
                }
            }

            if (count($row) != 0) {
                $rows[] = $row;

                if ($incomplete) {
                    $this->incomplete = true;
                }
            }
        }

        $this->value = $rows;
    }

    public function validate(): array
    {
        if ($this->incomplete) {
            return ["Die Angaben sind unvollständig."];
        }

        return [];
    }
}
