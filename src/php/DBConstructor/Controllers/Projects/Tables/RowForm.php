<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use Exception;
use DBConstructor\Util\JsonException;

abstract class RowForm extends Form
{
    /** @var bool */
    public $isEdit;

    /** @var array<string, string> */
    public $relationalColumnFields = [];

    /** @var array<RelationalColumn> */
    public $relationalColumns;

    /** @var array<string, string> */
    public $textualColumnFields = [];

    /** @var array<TextualColumn> */
    public $textualColumns;

    public function __construct(string $name, bool $isEdit)
    {
        parent::__construct($name);
        $this->isEdit = $isEdit;
    }

    public function addRelationalField(RelationalColumn $column, string $storedValue = null)
    {
        $field = new RelationalSelectField($column);

        if ($storedValue !== null) {
            $field->defaultValue = $storedValue;
        }

        $this->addField($field);
        $this->relationalColumnFields[$column->name] = $this->fields[$field->name];
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function addTextualField(TextualColumn $column, string $storedValue = null)
    {
        $fieldName = "textual-".$column->id;

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

            foreach ($type->options as $name => $label) {
                $field->addOption($name, $label);
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
        $this->textualColumnFields[$column->name] = $this->fields[$field->name];
    }

    /**
     * Result will not be returned, but echoed!
     *
     * @throws JsonException
     */
    public function generate(): string
    {
        echo $this->generateStartingTag();

        foreach ($this->relationalColumns as $column) {
            $column->generateInput($this->relationalColumnFields[$column->name], $this->isEdit);
        }

        foreach ($this->textualColumns as $column) {
            $column->generateInput($this->textualColumnFields[$column->name], $this->isEdit);
        }

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
}
