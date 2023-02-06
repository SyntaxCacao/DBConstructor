<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Records;

use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\API\Projects\Tables\TablesNode;
use DBConstructor\Controllers\Projects\Tables\Insert\InsertForm;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowLoader;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;

class RecordsListLeaf extends LeafNode
{
    const OPTIONS_FILTER = [
        "exclude" => RowLoader::FILTER_DELETED_EXCLUDE,
        "include" => RowLoader::FILTER_DELETED_INCLUDE,
        "only" => RowLoader::FILTER_DELETED_ONLY
    ];

    const OPTIONS_ORDER = [
        "created" => RowLoader::ORDER_BY_CREATED,
        "lastActivity" => RowLoader::ORDER_BY_LAST_ACTIVITY
    ];

    const OPTIONS_ORDER_DIRECTION = [
        "asc" => RowLoader::ORDER_DIRECTION_ASCENDING,
        "desc" => RowLoader::ORDER_DIRECTION_DESCENDING
    ];

    const OPTIONS_VALIDITY = [
        "invalid" => RowLoader::FILTER_VALIDITY_INVALID,
        "valid" => RowLoader::FILTER_VALIDITY_VALID
    ];

    public function get(array $path): array
    {
        $params = $this->processQueryParameters([
            "assignee" => [
                "type" => "integerString",
                "default" => null
            ],
            "createdAfter" => [
                "type" => "string",
                "default" => null
            ],
            "createdBefore" => [
                "type" => "string",
                "default" => null
            ],
            "creator" => [
                "type" => "integerString",
                "default" => null
            ],
            "deleted" => [
                "type" => "options",
                "default" => null,
                "options" => self::OPTIONS_FILTER
            ],
            "flagged" => [
                "type" => "boolean",
                "default" => false
            ],
            "order" => [
                "type" => "options",
                "default" => null,
                "options" => self::OPTIONS_ORDER
            ],
            "orderDirection" => [
                "type" => "options",
                "default" => null,
                "options" => self::OPTIONS_ORDER_DIRECTION
            ],
            "page" => [
                "type" => "integer",
                "default" => 1
            ],
            "recordsPerPage" => [
                "type" => "integer",
                "default" => null
            ],
            "searchColumn" => [
                "type" => "string",
                "default" => null
            ],
            "searchValue" => [
                "type" => "string",
                "default" => null
            ],
            "updatedBy" => [
                "type" => "integerString",
                "default" => null
            ],
            "validity" => [
                "type" => "options",
                "default" => null,
                "options" => self::OPTIONS_VALIDITY
            ]
        ]);

        $loader = new RowLoader(TablesNode::$table->id);

        foreach (["assignee", "createdAfter", "createdBefore", "creator", "deleted", "order", "orderDirection", "recordsPerPage", "updatedBy", "validity"] as $name) {
            if ($params[$name] !== null) {
                $loader->$name = $params[$name];
            }
        }

        if ($params["flagged"]) {
            $loader->flagged = RowLoader::FILTER_FLAGGED;
        }

        if ($params["searchColumn"] !== null) {
            $loader->addSearch($params["searchColumn"], $params["searchValue"]);
        }

        if ($params["recordsPerPage"] !== null) {
            $loader->rowsPerPage = $params["recordsPerPage"];
        }

        // load
        $count = $loader->getRowCount();
        $rows = $loader->getRows($params["page"]);
        $relationalFields = RelationalField::loadRows($rows);
        $textualFields = TextualField::loadRows($rows);

        $result = [];
        $result["count"] = $count;
        $result["page"] = $params["page"];
        $result["pages"] = $loader->calcPages($count);
        $result["records"] = [];

        foreach ($rows as $row) {
            $result["records"][] = RecordsElementLeaf::buildRecordArray($row, TextualColumn::loadList(TablesNode::$table->id), $relationalFields[$row->id] ?? [], $textualFields[$row->id] ?? []);
        }

        return $result;
    }

    public function post(array $path): array
    {
        APIController::$instance->requireScope(ProjectsNode::$project->id, AccessToken::SCOPE_PROJECT_WRITE);

        $payload = $this->processPayload();

        $relationalColumns = RelationalColumn::loadList(TablesNode::$table->id);
        $textualColumns = TextualColumn::loadList(TablesNode::$table->id);
        $participants = Participant::loadList(ProjectsNode::$project->id);

        $data = [];
        $checkedRows = []; // contains IDs of rows that have been proven to exist

        // I. Validate all records, build data objects for all records
        // Doing this first so that if one of the posterior records fails, none will be inserted

        $errors = [];

        if (! isset($payload["records"]) || ! is_array($payload["records"])) {
            APIController::$instance->respond(422, [
                "message" => "No records submitted for insertion",
            ]);
        }

        foreach ($payload["records"] as $n => $record) {
            if (! is_array($record)) {
                $errors[] = "Data provided for record $n is not an array.";
                continue;
            }

            $anyValue = false; // true if at least one value provided is not null
            $recordData = ["next" => "new"];

            // 1. Relational values
            // a) Check keys in payload
            if (isset($record["relationalValues"]) && is_array($record["relationalValues"])) {
                foreach ($record["relationalValues"] as $columnId => $value) {
                    if (! array_key_exists($columnId, $relationalColumns)) {
                        $errors[] = "Record $n: a relational column with ID $columnId does not exist in this table.";
                    }
                }
            }

            // b) Put values in data object
            foreach ($relationalColumns as $column) {
                if (isset($record["relationalValues"]) && is_array($record["relationalValues"]) &&
                    isset($record["relationalValues"][$column->id]) && $record["relationalValues"][$column->id] !== "") {
                    // non-null value
                    $value = (string) $record["relationalValues"][$column->id];

                    if (intval($value) === 0 || ! ctype_digit($value)) {
                        $errors[] = "Record $n: relational column $column->id: $value is not an integer.";
                        continue;
                    }

                    // check if record exists
                    if (! isset($checkedRows[$value])) {
                        if (($row = Row::load($value)) === null) {
                            $errors[] = "Record $n: relational column $column->id: record $value does not exist.";
                            continue;
                        }

                        if ($row->tableId !== $column->targetTableId) {
                            $errors[] = "Record $n: relational column $column->id: record $value exists but does not belong to table targeted by this relational column (belongs to table $row->tableId, targeted is table $column->targetTableId).";
                            continue;
                        }

                        $checkedRows[] = $value;
                    }

                    $recordData["relational-".$column->id] = $value;
                    $anyValue = true;
                } else {
                    // null value
                    $recordData["relational-".$column->id] = null;
                }
            }

            // 2. Textual values
            // a) Check keys in payload
            if (isset($record["textualValues"]) && is_array($record["textualValues"])) {
                foreach ($record["textualValues"] as $columnId => $value) {
                    if (! array_key_exists($columnId, $textualColumns)) {
                        $errors[] = "Record $n: a textual column with ID $columnId does not exist in this table.";
                    }
                }
            }

            // b) Put values in data object
            foreach ($textualColumns as $column) {
                if (isset($record["textualValues"]) && is_array($record["textualValues"]) &&
                    isset($record["textualValues"][$column->id]) && $record["textualValues"][$column->id] !== "") {
                    // non-null value
                    $recordData["textual-".$column->id] = $record["textualValues"][$column->id];
                    $anyValue = true;
                } else {
                    // null value
                    $recordData["textual-".$column->id] = null;
                }
            }

            // 3. Comment, flag, assignee
            if (isset($record["comment"])) {
                if (is_string($record["comment"])) {
                    $recordData["comment"] = $record["comment"];
                } else {
                    $errors[] = "Record $n: comment is not provided as a string value.";
                }
            } else {
                $recordData["comment"] = null;
            }

            $recordData["flag"] = isset($record["flag"]) && $record["flag"] === true;

            if (isset($record["assignee"])) {
                if (! array_key_exists($record["assignee"], $participants)) {
                    $errors[] = "Record $n: assignee: user {$record["assignee"]} does not exist or does not participate in this project.";
                }

                $recordData["assignee"] = (string) $record["assignee"];
            } else {
                $recordData["assignee"] = null;
            }

            // 4. Check if at least on non-null value has been submitted
            if (! $anyValue) {
                $errors[] = "Record $n: failed to provide at least one non-null value.";
            }

            // 5.
            $data[] = $recordData;
        }

        if (count($errors) > 0) {
            APIController::$instance->respond(422, [
                "message" => implode(" ", $errors)
            ]);
        }

        // II. Insert records

        $inserted = [];

        foreach ($data as $recordData) {
            $form = new InsertForm();
            $form->api = true;
            $form->table = &TablesNode::$table;
            $form->relationalColumns = &$relationalColumns;
            $form->textualColumns = &$textualColumns;
            $form->perform($recordData);
            $inserted[] = $form->insertedId;
        }

        return ["inserted" => $inserted];
    }
}
