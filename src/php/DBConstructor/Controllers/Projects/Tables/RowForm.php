<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Column;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use Exception;

abstract class RowForm extends Form
{
    /** @var array<string, Column> */
    public $columns = [];

    /** @var bool */
    public $isEdit;

    /** @var array<RelationalColumn> */
    public $relationalColumns;

    /** @var array<TextualColumn> */
    public $textualColumns;

    public function __construct(string $name, bool $isEdit)
    {
        parent::__construct($name);
        $this->isEdit = $isEdit;
    }

    public function addRelationalField(RelationalColumn $column, string $storedValue = null, string $stepId = null): string
    {
        $field = new RelationalSelectField("relational-".$column->id, null, $column->nullable, $column->targetTableId, $this->getRowId());

        if ($stepId !== null) {
            $field->name = "step-".$stepId."-".$field->name;
        }

        if ($storedValue !== null) {
            $field->defaultValue = $storedValue;
        }

        $this->addField($field);
        $this->columns[$field->name] = $column;

        return $field->name;
    }

    /**
     * @param string|array $storedValue
     * @throws Exception
     * @throws JsonException
     */
    public function addTextualField(TextualColumn $column, $storedValue = null, string $stepId = null): string
    {
        $fieldName = "textual-".$column->id;

        if ($stepId !== null) {
            $fieldName = "step-".$stepId."-".$fieldName;
        }

        if ($column->type == TextualColumn::TYPE_TEXT) {
            /** @var TextType $type */
            $type = $column->getValidationType();

            if ($type->fieldType == TextType::FIELD_INPUT) {
                $field = new TextField($fieldName);
            } else {
                if ($type->markdown == TextType::MARKDOWN_DISABLED) {
                    $field = new TextareaField($fieldName);
                } else {
                    $field = new MarkdownField($fieldName);
                }

                $field->larger = $type->fieldType == TextType::FIELD_TEXTAREA_LARGE;
            }

            $field->maxLength = 10000; // TODO: Check
            $field->spellcheck = false;
        } else if ($column->type == TextualColumn::TYPE_SELECTION) {
            /** @var SelectionType $type */
            $type = $column->getValidationType();
            $field = new SelectField($fieldName);
            $field->allowMultiple = $type->allowMultiple;

            foreach ($type->options as $name => $label) {
                // $name needs to be cast to string as PHP stores
                // numeric keys as ints even if they were put in the array as strings
                $field->addOption((string) $name, $label);
            }

            if (is_string($storedValue)) {
                if (! array_key_exists($storedValue, $type->options)) {
                    $field->addOption($storedValue, "Ungültig: $storedValue");
                }
            } else if (is_array($storedValue)) {
                foreach ($storedValue as $option) {
                    if (! array_key_exists($option, $type->options)) {
                        $field->addOption((string) $option, "Ungültig: ".((string) $option));
                    }
                }
            }
        } else if ($column->type == TextualColumn::TYPE_DATE) {
            $field = new TextField($fieldName);
            $field->maxLength = 10000; // TODO: Check
            $field->spellcheck = false;
        } else if ($column->type == TextualColumn::TYPE_INTEGER) {
            $field = new TextField($fieldName);
            $field->maxLength = 10000; // TODO Check
            $field->spellcheck = false;
        } else if ($column->type == TextualColumn::TYPE_DECIMAL) {
            $field = new TextField($fieldName);
            $field->maxLength = 10000; // TODO Check
            $field->spellcheck = false;
        } else if ($column->type == TextualColumn::TYPE_BOOLEAN) {
            /** @var BooleanType $type */
            $type = $column->getValidationType();
            $field = new SelectField($fieldName);

            if (is_null($type->falseLabel)) {
                $field->addOption(BooleanType::VALUE_FALSE, "false");
            } else {
                $field->addOption(BooleanType::VALUE_FALSE, $type->falseLabel);
            }

            if (is_null($type->trueLabel)) {
                $field->addOption(BooleanType::VALUE_TRUE, "true");
            } else {
                $field->addOption(BooleanType::VALUE_TRUE, $type->trueLabel);
            }
        } else {
            throw new Exception("Unsupported column type: ".$column->type);
        }

        $field->required = false;

        if ($storedValue !== null) {
            $field->defaultValue = $storedValue;
        }

        $this->addField($field);
        $this->columns[$field->name] = $column;

        return $field->name;
    }

    /**
     * Result will not be returned, but echoed!
     */
    public function generate(): string
    {
        echo $this->generateStartingTag();
        $this->generateFields();
        $this->generateAdditionalFields();
        echo $this->generateActions();
        echo $this->generateClosingTag();

        return "";
    }

    /**
     * To be overriden by InsertForm
     */
    public function generateAdditionalFields()
    {
    }

    /**
     * To be overriden by ExecutionForm
     */
    public function generateFields()
    {
        foreach ($this->columns as $fieldName => $column) {
            if (array_key_exists($fieldName, $this->issues)) {
                $errorMessages = $this->issues[$fieldName];
            } else {
                $errorMessages = [];
            }

            $column->generateInput($this->fields[$fieldName], $errorMessages, $this->isEdit);
        }
    }

    /**
     * @return string|null
     */
    public function getRowId()
    {
        return null;
    }
}
