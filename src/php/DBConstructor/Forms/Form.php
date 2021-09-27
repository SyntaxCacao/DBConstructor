<?php

declare(strict_types=1);

namespace DBConstructor\Forms;

use DBConstructor\Forms\Fields\Field;

abstract class Form
{
    /** @var string */
    protected $name;

    /** @var string */
    public $buttonLabel = "Speichern";

    /** @var Field[] */
    protected $fields = [];

    /** @var string[] */
    protected $issues = [];

    /** @var string[] */
    protected $missing = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addField(Field $field)
    {
        $this->fields[$field->name] = $field;
    }

    public function generate(): string
    {
        $html = $this->generateStartingTag();

        foreach ($this->fields as $name => $field) {
            $issues = [];

            if (array_key_exists($name, $this->issues)) {
                $issues = $this->issues[$name];
            } else if (in_array($name, $this->missing)) {
                $issues = ["Bitte füllen Sie dieses Feld aus."];
            }

            $html .= $field->generateGroup($issues);
        }

        $html .= $this->generateActions().$this->generateClosingTag();
        return $html;
    }

    public function generateActions(): string
    {
        return '<div class="form-actions"><button class="button" type="submit">'.htmlentities($this->buttonLabel).'</button></div>';
    }

    public function generateClosingTag(): string
    {
        return '</form>';
    }

    public function generateStartingTag(): string
    {
        $html = '<form class="form" id="form-'.htmlentities($this->name).'" method="post" name="'.htmlentities($this->name).'">';
        $html .= '<input name="form-name" type="hidden" value="'.htmlentities($this->name).'">';
        return $html;
    }

    public abstract function perform(/*$mode, */ array $data);

    /**
     * @return bool returns true if form could be processed and false if form needs to be shown (again)
     */
    public function process(): bool
    {
        if (! isset($_REQUEST["form-name"]) || $_REQUEST["form-name"] != $this->name) {
            // TODO If action is performed, default (= database) values should be preferred over sent values on generation
            // TODO     but default values might no longer be up to date!
            foreach ($this->fields as $field) {
                if (isset($field->defaultValue)) {
                    $field->insertValue($field->defaultValue);
                }
            }

            return false;
        }

        $data = [];

        foreach ($this->fields as $name => $field) {
            if (isset($field->dependsOn)) {
                if ($field->dependsOnValue != $this->fields[$field->dependsOn]->value) {
                    continue;
                }
            }

            if ($field->disabled) {
                if (isset($field->defaultValue)) {
                    $field->insertValue($field->defaultValue);
                }
            } else if (isset($_REQUEST["field-$name"])) {
                $field->insertValue($_REQUEST["field-$name"]);
            }

            if ($field->hasValue()) {
                $fieldIssues = $field->validate();

                if (count($fieldIssues) > 0) {
                    $this->issues[$name] = $fieldIssues;
                } else {
                    $data[$name] = $field->value;
                }
            } else {
                if ($field->required) {
                    $this->missing[] = $name;
                } else {
                    $data[$name] = null;
                }
            }
        }

        if (count($this->missing) == 0 && count($this->issues) == 0) {
            $this->perform($data);
            return true;
        }

        return false;
    }
}
